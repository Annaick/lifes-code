<?php
/**
 * Shipping to Store Class.
 * Handle shipping method assignment to stores with stock validation.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Shipping_To_Store' ) ) {
	/**
	 * Class YITH_POS_Shipping_To_Store
	 * Manages shipping methods assigned to specific stores
	 */
	class YITH_POS_Shipping_To_Store {

		use YITH_POS_Singleton_Trait;

		/**
		 * Meta key for storing the assigned store to a shipping method instance.
		 */
		const STORE_META_KEY = '_yith_pos_assigned_store_id';

		/**
		 * Constructor.
		 */
		private function __construct() {
			// Add store assignment field to shipping method settings.
			add_filter( 'woocommerce_shipping_instance_form_fields_flat_rate', array( $this, 'add_store_field_to_shipping_method' ), 10, 1 );
			add_filter( 'woocommerce_shipping_instance_form_fields_free_shipping', array( $this, 'add_store_field_to_shipping_method' ), 10, 1 );
			add_filter( 'woocommerce_shipping_instance_form_fields_local_pickup', array( $this, 'add_store_field_to_shipping_method' ), 10, 1 );

			// Filter available shipping methods based on stock availability in assigned store.
			add_filter( 'woocommerce_package_rates', array( $this, 'filter_shipping_methods_by_store_stock' ), 100, 2 );

			// Hook into order processing to deduct stock from the correct store.
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'store_shipping_method_store_info' ), 10, 3 );
			add_filter( 'woocommerce_can_reduce_order_stock', array( $this, 'handle_stock_reduction_for_shipping_store' ), 50, 2 );

			// Hook into stock restoration.
			add_filter( 'woocommerce_can_restore_order_stock', array( $this, 'handle_stock_restoration_for_shipping_store' ), 50, 2 );
			add_filter( 'woocommerce_can_restock_refunded_items', array( $this, 'handle_stock_restoration_on_refund' ), 50, 3 );
		}

		/**
		 * Add store assignment field to shipping method settings.
		 *
		 * @param array $fields Shipping method form fields.
		 * @return array Modified fields.
		 */
		public function add_store_field_to_shipping_method( $fields ) {
			$stores = $this->get_stores_options();

			$fields[ self::STORE_META_KEY ] = array(
				'title'       => __( 'Assigned Store', 'yith-point-of-sale-for-woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Assign this shipping method to a specific store. Stock will be checked and deducted from this store.', 'yith-point-of-sale-for-woocommerce' ),
				'default'     => '',
				'options'     => $stores,
				'desc_tip'    => true,
			);

			return $fields;
		}

		/**
		 * Get list of stores for the select field.
		 *
		 * @return array Stores options.
		 */
		private function get_stores_options() {
			$options = array(
				'' => __( '-- No store assigned (use default stock) --', 'yith-point-of-sale-for-woocommerce' ),
			);

			$stores = yith_pos_get_stores(
				array(
					'post_status' => 'publish',
					'fields'      => 'stores',
				)
			);

			if ( $stores ) {
				foreach ( $stores as $store ) {
					if ( $store instanceof YITH_POS_Store ) {
						$options[ $store->get_id() ] = $store->get_name();
					}
				}
			}

			return $options;
		}

		/**
		 * Filter shipping methods based on stock availability in the assigned store.
		 *
		 * @param array $rates    Package rates.
		 * @param array $package  Shipping package.
		 * @return array Filtered rates.
		 */
		public function filter_shipping_methods_by_store_stock( $rates, $package ) {
			// Only filter for non-POS orders (website orders).
			if ( $this->is_pos_request() ) {
				return $rates;
			}

			$stock_manager = YITH_POS_Stock_Management::get_instance();
			if ( ! $stock_manager->is_enabled() ) {
				return $rates;
			}

			foreach ( $rates as $rate_id => $rate ) {
				$shipping_method = $this->get_shipping_method_from_rate( $rate );
				if ( ! $shipping_method ) {
					continue;
				}

				$store_id = $this->get_assigned_store_id( $shipping_method );
				if ( ! $store_id ) {
					// No store assigned, keep the method available.
					continue;
				}

				// Check if all products in package have sufficient stock in the assigned store.
				if ( ! $this->has_sufficient_stock_in_store( $package, $store_id ) ) {
					unset( $rates[ $rate_id ] );
				}
			}

			return $rates;
		}

		/**
		 * Get shipping method instance from rate.
		 *
		 * @param WC_Shipping_Rate $rate Shipping rate.
		 * @return WC_Shipping_Method|null Shipping method instance or null.
		 */
		private function get_shipping_method_from_rate( $rate ) {
			$method_id  = $rate->get_method_id();
			$instance_id = $rate->get_instance_id();

			if ( ! $instance_id ) {
				return null;
			}

			// Get the shipping method instance.
			$shipping_methods = WC()->shipping()->get_shipping_methods();
			if ( isset( $shipping_methods[ $method_id ] ) ) {
				$zones = WC_Shipping_Zones::get_zones();
				foreach ( $zones as $zone_data ) {
					foreach ( $zone_data['shipping_methods'] as $method ) {
						if ( $method->get_instance_id() === $instance_id ) {
							return $method;
						}
					}
				}

				// Check zone 0 (rest of the world).
				$zone_0 = new WC_Shipping_Zone( 0 );
				foreach ( $zone_0->get_shipping_methods() as $method ) {
					if ( $method->get_instance_id() === $instance_id ) {
						return $method;
					}
				}
			}

			return null;
		}

		/**
		 * Get assigned store ID from shipping method.
		 *
		 * @param WC_Shipping_Method $method Shipping method instance.
		 * @return int Store ID or 0 if not assigned.
		 */
		private function get_assigned_store_id( $method ) {
			$store_id = $method->get_option( self::STORE_META_KEY, '' );
			return absint( $store_id );
		}

		/**
		 * Check if all products in package have sufficient stock in the store.
		 *
		 * @param array $package  Shipping package.
		 * @param int   $store_id Store ID.
		 * @return bool True if all products have sufficient stock.
		 */
		private function has_sufficient_stock_in_store( $package, $store_id ) {
			if ( empty( $package['contents'] ) ) {
				return true;
			}

			$stock_manager = YITH_POS_Stock_Management::get_instance();

			foreach ( $package['contents'] as $item ) {
				$product = $item['data'];
				if ( ! $product || ! $product->managing_stock() ) {
					continue;
				}

				// Get the product that actually manages stock (for variations).
				$product_id_with_stock = $product->get_stock_managed_by_id();
				if ( $product_id_with_stock !== $product->get_id() ) {
					$product = wc_get_product( $product_id_with_stock );
				}

				if ( ! $product ) {
					continue;
				}

				$quantity = $item['quantity'];

				// Check if product has multi-stock enabled.
				if ( 'yes' === $product->get_meta( '_yith_pos_multistock_enabled' ) ) {
					$stock_amount = $stock_manager->get_stock_amount( $product, $store_id );

					// If store has stock defined, check it.
					if ( false !== $stock_amount ) {
						if ( $stock_amount < $quantity ) {
							return false;
						}
					} else {
						// No stock defined for this store, not available.
						return false;
					}
				}
				// If multi-stock not enabled, we allow the method (will use general stock).
			}

			return true;
		}

		/**
		 * Check if this is a POS request.
		 *
		 * @return bool True if POS request.
		 */
		private function is_pos_request() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			return isset( $_REQUEST['yith_pos_store'] ) || isset( $_REQUEST['yith_pos_request'] );
			// phpcs:enable
		}

		/**
		 * Store the shipping method's assigned store info in order meta when order is processed.
		 *
		 * @param int      $order_id Order ID.
		 * @param array    $posted_data Posted data.
		 * @param WC_Order $order Order object.
		 */
		public function store_shipping_method_store_info( $order_id, $posted_data, $order ) {
			// Skip for POS orders.
			if ( yith_pos_is_pos_order( $order ) ) {
				return;
			}

			$shipping_methods = $order->get_shipping_methods();
			foreach ( $shipping_methods as $shipping_item ) {
				$method_id   = $shipping_item->get_method_id();
				$instance_id = $shipping_item->get_instance_id();

				if ( ! $instance_id ) {
					continue;
				}

				// Get the shipping method to find assigned store.
				$shipping_method = $this->get_shipping_method_by_instance_id( $instance_id );
				if ( ! $shipping_method ) {
					continue;
				}

				$store_id = $this->get_assigned_store_id( $shipping_method );
				if ( $store_id ) {
					// Store the assigned store ID in order meta.
					$order->update_meta_data( '_yith_pos_shipping_store', $store_id );
					$order->save();
					break; // Only process first shipping method.
				}
			}
		}

		/**
		 * Get shipping method by instance ID.
		 *
		 * @param int $instance_id Instance ID.
		 * @return WC_Shipping_Method|null
		 */
		private function get_shipping_method_by_instance_id( $instance_id ) {
			$zones = WC_Shipping_Zones::get_zones();
			foreach ( $zones as $zone_data ) {
				foreach ( $zone_data['shipping_methods'] as $method ) {
					if ( $method->get_instance_id() === $instance_id ) {
						return $method;
					}
				}
			}

			// Check zone 0 (rest of the world).
			$zone_0 = new WC_Shipping_Zone( 0 );
			foreach ( $zone_0->get_shipping_methods() as $method ) {
				if ( $method->get_instance_id() === $instance_id ) {
					return $method;
				}
			}

			return null;
		}

		/**
		 * Handle stock reduction for orders with shipping-to-store.
		 * Prevent default WooCommerce stock reduction if we have a store assigned.
		 *
		 * @param bool     $can_reduce Can reduce flag.
		 * @param WC_Order $order      Order object.
		 * @return bool
		 */
		public function handle_stock_reduction_for_shipping_store( $can_reduce, $order ) {
			// Skip for POS orders (handled by POS stock management).
			if ( yith_pos_is_pos_order( $order ) ) {
				return $can_reduce;
			}

			$shipping_store_id = absint( $order->get_meta( '_yith_pos_shipping_store' ) );
			if ( $shipping_store_id && $can_reduce ) {
				// We have a store assigned, let's handle stock reduction manually.
				$this->reduce_stock_from_shipping_store( $order, $shipping_store_id );
				return false; // Prevent default WooCommerce stock reduction.
			}

			return $can_reduce;
		}

		/**
		 * Reduce stock from the assigned shipping store.
		 *
		 * @param WC_Order $order    Order object.
		 * @param int      $store_id Store ID.
		 */
		private function reduce_stock_from_shipping_store( $order, $store_id ) {
			if ( ! $order || 'yes' !== get_option( 'woocommerce_manage_stock' ) ) {
				return;
			}

			$stock_manager = YITH_POS_Stock_Management::get_instance();
			if ( ! $stock_manager->is_enabled() ) {
				return;
			}

			$changes = array();

			foreach ( $order->get_items() as $item ) {
				if ( ! $item->is_type( 'line_item' ) ) {
					continue;
				}

				$product = $item->get_product();
				$item_stock_reduced = $item->get_meta( '_reduced_stock', true );

				if ( $item_stock_reduced || ! $product || ! $product->managing_stock() ) {
					continue;
				}

				$qty       = $item->get_quantity();
				$item_name = $product->get_formatted_name();

				// Get the product that manages stock.
				$product_id_with_stock = $product->get_stock_managed_by_id();
				$product               = $product_id_with_stock !== $product->get_id() ? wc_get_product( $product_id_with_stock ) : $product;

				if ( ! $product ) {
					continue;
				}

				$stock_amount = $stock_manager->get_stock_amount( $product, $store_id );
				$new_stock    = false;
				$reduced_by   = false;

				if ( 'yes' === $product->get_meta( '_yith_pos_multistock_enabled' ) && false !== $stock_amount && $stock_amount > 0 ) {
					// Reduce from store stock.
					$new_stock  = $stock_manager->update_product_stock( $product, $qty, $store_id, $stock_amount, 'decrease' );
					$reduced_by = 'store';
				} else {
					// Fallback to general stock.
					$new_stock  = wc_update_product_stock( $product, $qty, 'decrease' );
					$reduced_by = 'general';
				}

				if ( is_wp_error( $new_stock ) ) {
					$order->add_order_note( sprintf( __( 'Unable to reduce stock for item %s.', 'woocommerce' ), $item_name ) );
					continue;
				}

				if ( false !== $new_stock ) {
					$item->add_meta_data( '_reduced_stock', $qty, true );
					switch ( $reduced_by ) {
						case 'store':
							$item->add_meta_data( '_yith_pos_reduced_stock_by_store', $store_id, true );
							$item->add_meta_data( '_yith_pos_reduced_stock_by_store_qty', $qty, true );
							break;
						case 'general':
							$item->add_meta_data( '_yith_pos_reduced_stock_by_general', $qty, true );
							break;
					}

					$item->save();

					$changes[] = array(
						'product' => $product,
						'from'    => $new_stock + $qty,
						'to'      => $new_stock,
					);
				}
			}

			if ( $changes ) {
				wc_trigger_stock_change_notifications( $order, $changes );
			}
		}

		/**
		 * Handle stock restoration for orders with shipping-to-store.
		 *
		 * @param bool     $can_restore Can restore flag.
		 * @param WC_Order $order       Order object.
		 * @return bool
		 */
		public function handle_stock_restoration_for_shipping_store( $can_restore, $order ) {
			// Skip for POS orders (handled by POS stock management).
			if ( yith_pos_is_pos_order( $order ) ) {
				return $can_restore;
			}

			$shipping_store_id = absint( $order->get_meta( '_yith_pos_shipping_store' ) );
			if ( $shipping_store_id && $can_restore ) {
				// We have a store assigned, let's handle stock restoration manually.
				$this->restore_stock_to_shipping_store( $order, $shipping_store_id );
				return false; // Prevent default WooCommerce stock restoration.
			}

			return $can_restore;
		}

		/**
		 * Restore stock to the assigned shipping store.
		 *
		 * @param WC_Order $order    Order object.
		 * @param int      $store_id Store ID.
		 */
		private function restore_stock_to_shipping_store( $order, $store_id ) {
			if ( ! $order || 'yes' !== get_option( 'woocommerce_manage_stock' ) ) {
				return;
			}

			$stock_manager = YITH_POS_Stock_Management::get_instance();
			if ( ! $stock_manager->is_enabled() ) {
				return;
			}

			$changes = array();

			foreach ( $order->get_items() as $item ) {
				if ( ! $item->is_type( 'line_item' ) ) {
					continue;
				}

				$product            = $item->get_product();
				$item_stock_reduced = $item->get_meta( '_reduced_stock', true );

				if ( ! $product ) {
					continue;
				}

				// Get the product that manages stock.
				$product_id_with_stock = $product->get_stock_managed_by_id();
				$product               = $product_id_with_stock !== $product->get_id() ? wc_get_product( $product_id_with_stock ) : $product;

				if ( ! $item_stock_reduced || ! $product || ! $product->managing_stock() ) {
					continue;
				}

				$item_name    = $product->get_formatted_name();
				$new_stock    = false;
				$restored_in  = false;

				// Check where stock was reduced from.
				if ( $item->get_meta( '_yith_pos_reduced_stock_by_store' ) === $store_id ) {
					$stock_amount = $stock_manager->get_stock_amount( $product, $store_id );
					if ( false !== $stock_amount ) {
						$new_stock   = $stock_manager->update_product_stock( $product, $item_stock_reduced, $store_id, $stock_amount, 'increase' );
						$restored_in = 'store';
					}
				} elseif ( $item->get_meta( '_yith_pos_reduced_stock_by_general' ) ) {
					$new_stock   = wc_update_product_stock( $product, $item_stock_reduced, 'increase' );
					$restored_in = 'general';
				}

				if ( is_wp_error( $new_stock ) ) {
					$order->add_order_note( sprintf( __( 'Unable to restore stock for item %s.', 'woocommerce' ), $item_name ) );
					continue;
				}

				if ( false !== $new_stock ) {
					$item->delete_meta_data( '_reduced_stock' );
					switch ( $restored_in ) {
						case 'store':
							$item->delete_meta_data( '_yith_pos_reduced_stock_by_store' );
							$item->delete_meta_data( '_yith_pos_reduced_stock_by_store_qty' );
							break;
						case 'general':
							$item->delete_meta_data( '_yith_pos_reduced_stock_by_general' );
							break;
					}
					$item->save();

					$changes[] = $item_name . ' ' . ( $new_stock - $item_stock_reduced ) . '&rarr;' . $new_stock;
				}
			}

			if ( $changes ) {
				$order->add_order_note( __( 'Stock levels increased:', 'woocommerce' ) . ' ' . implode( ', ', $changes ) );
			}
		}

		/**
		 * Handle stock restoration on refund for orders with shipping-to-store.
		 *
		 * @param bool     $allowed             Allowed flag.
		 * @param WC_Order $order               The order.
		 * @param array    $refunded_line_items Refunded line items.
		 * @return bool
		 */
		public function handle_stock_restoration_on_refund( $allowed, $order, $refunded_line_items ) {
			// Skip for POS orders (handled by POS stock management).
			if ( yith_pos_is_pos_order( $order ) ) {
				return $allowed;
			}

			$shipping_store_id = absint( $order->get_meta( '_yith_pos_shipping_store' ) );
			if ( $shipping_store_id ) {
				// We have a store assigned, let's handle stock restoration manually.
				$allowed = false; // Disable default WooCommerce restock.
				$this->restore_stock_on_refund( $order, $shipping_store_id, $refunded_line_items );
			}

			return $allowed;
		}

		/**
		 * Restore stock on refund from the assigned shipping store.
		 *
		 * @param WC_Order $order               Order object.
		 * @param int      $store_id            Store ID.
		 * @param array    $refunded_line_items Refunded line items.
		 */
		private function restore_stock_on_refund( $order, $store_id, $refunded_line_items ) {
			if ( ! $order || 'yes' !== get_option( 'woocommerce_manage_stock' ) ) {
				return;
			}

			$stock_manager = YITH_POS_Stock_Management::get_instance();
			if ( ! $stock_manager->is_enabled() ) {
				return;
			}

			$line_items = $order->get_items();

			foreach ( $line_items as $item_id => $item ) {
				if ( ! isset( $refunded_line_items[ $item_id ], $refunded_line_items[ $item_id ]['qty'] ) ) {
					continue;
				}

				$product            = $item->get_product();
				$item_stock_reduced = $item->get_meta( '_reduced_stock', true );
				$qty_to_refund      = $refunded_line_items[ $item_id ]['qty'];

				if ( ! $item_stock_reduced || ! $qty_to_refund || ! $product || ! $product->managing_stock() ) {
					continue;
				}

				// Get the product that manages stock.
				$product_id_with_stock = $product->get_stock_managed_by_id();
				$product               = $product_id_with_stock !== $product->get_id() ? wc_get_product( $product_id_with_stock ) : $product;

				if ( ! $product ) {
					continue;
				}

				$old_stock   = null;
				$new_stock   = false;
				$restored_in = false;

				// Check where stock was reduced from.
				if ( $item->get_meta( '_yith_pos_reduced_stock_by_store' ) === $store_id ) {
					$old_stock = $stock_manager->get_stock_amount( $product, $store_id );
					if ( false !== $old_stock ) {
						$new_stock   = $stock_manager->update_product_stock( $product, $qty_to_refund, $store_id, $old_stock, 'increase' );
						$restored_in = 'store';
					}
				} elseif ( $item->get_meta( '_yith_pos_reduced_stock_by_general' ) ) {
					$old_stock   = $product->get_stock_quantity();
					$new_stock   = wc_update_product_stock( $product, $qty_to_refund, 'increase' );
					$restored_in = 'general';
				}

				if ( false !== $new_stock && null !== $old_stock ) {
					// Update _reduced_stock meta to track changes.
					$item_stock_reduced = $item_stock_reduced - $qty_to_refund;

					if ( 0 < $item_stock_reduced ) {
						$item->update_meta_data( '_reduced_stock', $item_stock_reduced );
						switch ( $restored_in ) {
							case 'store':
								$item->update_meta_data( '_yith_pos_reduced_stock_by_store_qty', $item_stock_reduced );
								break;
							case 'general':
								$item->update_meta_data( '_yith_pos_reduced_stock_by_general', $item_stock_reduced );
								break;
						}
					} else {
						$item->delete_meta_data( '_reduced_stock' );
						switch ( $restored_in ) {
							case 'store':
								$item->delete_meta_data( '_yith_pos_reduced_stock_by_store' );
								$item->delete_meta_data( '_yith_pos_reduced_stock_by_store_qty' );
								break;
							case 'general':
								$item->delete_meta_data( '_yith_pos_reduced_stock_by_general' );
								break;
						}
					}

					// translators: 1: product ID 2: old stock level 3: new stock level.
					$order->add_order_note( sprintf( __( 'Item #%1$s stock increased from %2$s to %3$s.', 'woocommerce' ), $product->get_id(), $old_stock, $new_stock ) );

					$item->save();

					do_action( 'woocommerce_restock_refunded_item', $product->get_id(), $old_stock, $new_stock, $order, $product );
				}
			}
		}
	}
}

if ( ! function_exists( 'yith_pos_shipping_to_store' ) ) {
	/**
	 * Get instance of YITH_POS_Shipping_To_Store.
	 *
	 * @return YITH_POS_Shipping_To_Store
	 */
	function yith_pos_shipping_to_store() {
		return YITH_POS_Shipping_To_Store::get_instance();
	}
}
