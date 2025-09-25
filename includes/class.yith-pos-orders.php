<?php
/**
 * Orders Class.
 * Handle orders.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Orders' ) ) {
	/**
	 * Class YITH_POS_Orders
	 *
	 */
	class YITH_POS_Orders {

		use YITH_POS_Singleton_Trait;

		/**
		 * YITH_POS_Orders constructor.
		 */
		private function __construct() {
			add_action( 'woocommerce_order_item_fee_after_calculate_taxes', array( $this, 'disable_taxes_for_discounts' ), 10, 1 );
			add_action( 'woocommerce_order_item_display_meta_key', array( $this, 'order_item_meta_label' ), 10, 1 );
			add_action( 'woocommerce_payment_complete_order_status', array( $this, 'filter_order_status' ), 20, 3 );
			add_action( 'woocommerce_coupon_get_items_to_validate', array( $this, 'filter_items_to_validate_for_discounts' ), 10, 2 );

			// The 'woocommerce_order_get_tax_location' filter requires WooCommerce 4.1 or greater.
			add_filter( 'woocommerce_order_get_tax_location', array( $this, 'order_tax_location_based_on_store_location' ), 10, 2 );

			// Use update_order hook too, since the REST API creates the order without coupons and then add them and update the order.
			add_action( 'woocommerce_new_order', array( $this, 'delete_pos_discount_coupons' ), 10, 2 );
			add_action( 'woocommerce_update_order', array( $this, 'delete_pos_discount_coupons' ), 10, 2 );

			// Force updating order lookups when creating/updating orders, to retrieve correct values in Reports.
			add_action( 'woocommerce_new_order', array( $this, 'update_order_lookups' ), 10, 2 );
			add_action( 'woocommerce_update_order', array( $this, 'update_order_lookups' ), 10, 2 );

			add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'handle_custom_query_vars' ), 10, 2 );
		}

		/**
		 * Filter coupon items to validate, to set a fake product for products created "on the fly" in POS.
		 * This prevents issues when using POS discounts on products created "on the fly" in POS.
		 *
		 * @param array        $items     Items.
		 * @param WC_Discounts $discounts The discounts class.
		 *
		 * @return array
		 * @since 2.1.3
		 */
		public function filter_items_to_validate_for_discounts( array $items, WC_Discounts $discounts ): array {
			$order = $discounts->get_object();
			if ( $order instanceof WC_Order && yith_pos_is_pos_order( $order ) ) {
				foreach ( $items as $item ) {
					$order_item = $item->object;
					if ( $order_item instanceof WC_Order_Item_Product && ! ! $order_item->get_meta( 'yith_pos_custom_product' ) ) {
						$item->product = new WC_Product_Simple();
						$item->product->set_name( $order_item->get_name() );
					}
				}
			}

			return $items;
		}

		/**
		 * Filter the order tax location to calculate taxes based on store location
		 *
		 * @param array    $args  Location args.
		 * @param WC_Order $order The order.
		 *
		 * @return array
		 * @since 1.0.2
		 */
		public function order_tax_location_based_on_store_location( $args, $order ) {
			if ( yith_pos_is_pos_order( $order ) ) {
				$store_id = absint( $order->get_meta( '_yith_pos_store' ) );
				$store    = yith_pos_get_store( $store_id );
				if ( $store && $store->get_country() ) {
					$args['country']  = $store->get_country();
					$args['state']    = $store->get_state();
					$args['postcode'] = $store->get_postcode();
					$args['city']     = $store->get_city();
				}
			}

			return $args;
		}


		/**
		 * Disable taxes for discounts
		 *
		 * @param WC_Order_Item_Fee $fee The Fee.
		 */
		public function disable_taxes_for_discounts( $fee ) {
			if ( $fee->get_total() < 0 && wc_tax_enabled() && $fee->get_order() && 'discount' === $fee->get_meta( '_yith_pos_fee_type' ) ) {
				$fee->set_taxes( false );
			}
		}

		/**
		 * Filter the order item meta labels
		 *
		 * @param string $key The key.
		 *
		 * @return string
		 */
		public function order_item_meta_label( $key ) {
			$labels = array(
				'yith_pos_order_item_note' => __( 'Note', 'yith-point-of-sale-for-woocommerce' ),
			);

			return array_key_exists( $key, $labels ) ? $labels[ $key ] : $key;
		}

		/**
		 * Filter the order status for POS orders on payment complete
		 *
		 * @param string   $order_status Order status.
		 * @param int      $order_id     Order ID.
		 * @param WC_Order $order        The order.
		 *
		 * @return string
		 * @since 1.0.1
		 */
		public function filter_order_status( $order_status, $order_id, $order ) {
			if ( absint( $order->get_meta( '_yith_pos_order' ) ) ) {
				$order_status = ! ! $order->get_items( 'shipping' ) ? 'processing' : 'completed';
				$order_status = apply_filters( 'yith_pos_order_status', $order_status, $order );
			}

			return $order_status;
		}

		/**
		 * Delete POS discount-coupons assigned to the order,
		 * since they were created only for a limited-time usage.
		 *
		 * @param int            $order_id The order ID.
		 * @param WC_Order|false $order    The order.
		 */
		public function delete_pos_discount_coupons( $order_id, $order = false ) {
			// Retrieve the order by order ID, if somewhere the action is used with the first param only.
			$order = ! ! $order ? $order : wc_get_order( $order_id );

			if ( $order && yith_pos_is_pos_order( $order ) ) {
				$coupon_items = $order->get_coupons();
				foreach ( $coupon_items as $item ) {
					$code = $item->get_code();
					if ( yith_pos_is_discount_coupon_code( $code ) ) {
						$coupon      = new WC_Coupon( $code );
						$description = $coupon->get_description();

						// Set the post_id to false in coupon_info to prevent showing a link in the order edit page.
						$coupon_info = $item->get_meta( 'coupon_info' );
						if ( $coupon_info ) {
							$coupon_info = json_decode( $coupon_info, true );
							if ( is_array( $coupon_info ) ) {
								$coupon_info[0] = false;
								$coupon_info    = wp_json_encode( $coupon_info );
								$item->update_meta_data( 'coupon_info', $coupon_info );
							}
						}

						if ( $description ) {
							$item->update_meta_data( '_yith_pos_discount_coupon_reason', $description );
						}
						$coupon->delete( true );
					}
				}

			}
		}

		/**
		 * Force updating order lookups when creating/updating orders.
		 * This is useful to get the correct Reports from lookup tables.
		 *
		 * @param int            $order_id The order ID.
		 * @param WC_Order|false $order    The order.
		 *
		 * @since 2.0.0
		 */
		public function update_order_lookups( $order_id, $order = false ) {
			if ( ! yith_pos_is_wc_feature_enabled( 'analytics' ) ) {
				return;
			}
			// Retrieve the order by order ID, if somewhere the action is used with the first param only.
			$order = ! ! $order ? $order : wc_get_order( $order_id );
			if ( $order && yith_pos_is_pos_order( $order ) ) {
				$order_scheduler = false;
				$classes         = array(
					'\Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler',
					'\Automattic\WooCommerce\Admin\Schedulers\OrdersScheduler',
				);
				foreach ( $classes as $class ) {
					if ( class_exists( $class ) ) {
						$order_scheduler = $class;
						break;
					}
				}
				$import_method = ! ! $order_scheduler ? "$order_scheduler::import" : false;
				if ( $import_method && is_callable( $import_method ) ) {
					$import_method( $order->get_id() );
				}
			}
		}

		/**
		 * Handle custom query vars for retrieving orders.
		 *
		 * @param array $query      Args for WP_Query.
		 * @param array $query_vars Query vars from WC_Order_Query.
		 *
		 * @return array Modified query.
		 * @since 2.0.0
		 */
		public function handle_custom_query_vars( $query, $query_vars ) {
			$meta_mapping = array(
				'yith_pos_cashier'  => '_yith_pos_cashier',
				'yith_pos_register' => '_yith_pos_register',
				'yith_pos_store'    => '_yith_pos_store',
			);

			foreach ( $meta_mapping as $key => $meta_key ) {
				if ( ! empty( $query_vars[ $key ] ) ) {
					$query['meta_query'][] = array(
						'key'   => $meta_key,
						'value' => $query_vars[ $key ],
					);
				}
			}

			return $query;
		}
	}
}
