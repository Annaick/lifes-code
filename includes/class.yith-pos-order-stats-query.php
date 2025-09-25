<?php
/**
 * Order Stats Class.
 * Handle orders' stats.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Order_Stats_Query' ) ) {
	/**
	 * Class YITH_POS_Order_Stats_Query
	 *
	 * @since  2.0.0
	 */
	class YITH_POS_Order_Stats_Query {
		/**
		 * Stores query data.
		 *
		 * @var array
		 */
		protected $query_vars = array();

		/**
		 * Order ids.
		 *
		 * @var int[]|null
		 */
		private $order_ids;

		/**
		 * Create a new query.
		 *
		 * @param array $args Query arguments.
		 */
		public function __construct( array $args = array() ) {
			$this->query_vars = wp_parse_args( $args, $this->get_default_query_vars() );
		}

		/**
		 * Get the default allowed query vars.
		 *
		 * @return array
		 */
		protected function get_default_query_vars() {
			return array(
				'limit'        => - 1,
				'status'       => array( 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed' ),
				'date_created' => false,
				'cashier'      => false,
				'register'     => false,
				'store'        => false,
				'order__in'    => false,
			);
		}

		/**
		 * Get valid args for wc_get_orders.
		 *
		 * @param array $query_vars Query vars.
		 *
		 * @return array
		 */
		private function get_wc_query_args( array $query_vars ): array {
			/**
			 * Key mapping for custom keys handled through the `woocommerce_order_data_store_cpt_get_orders_query` filter.
			 *
			 * @see YITH_POS_Orders::handle_custom_query_vars
			 */
			$key_mapping = array(
				'cashier'  => 'yith_pos_cashier',
				'register' => 'yith_pos_register',
				'store'    => 'yith_pos_store',
			);

			$args = array(
				'limit'  => $query_vars['limit'],
				'status' => $query_vars['status'],
				'return' => 'ids',
			);

			if ( ! empty( $query_vars['date_created'] ) ) {
				$args['date_created'] = $query_vars['date_created'];
			}

			if ( ! empty( $query_vars['order__in'] ) ) {
				$args['post__in'] = $query_vars['order__in'];
			}

			foreach ( $key_mapping as $var => $key ) {
				if ( ! empty( $query_vars[ $var ] ) ) {
					if( yith_plugin_fw_is_wc_custom_orders_table_usage_enabled() ){
						$args['meta_query'][] = array(
							'key'     => '_' . $key,
							'value'   => $query_vars[ $var ],
							'compare' => '=',
						);
					}else{
						$args[ $key ] = $query_vars[ $var ];
					}
				}
			}

			return $args;
		}

		/**
		 * Retrieve order ids related to the current query.
		 *
		 * @return int[]
		 */
		private function get_order_ids(): array {
			if ( is_null( $this->order_ids ) ) {
				$args = $this->get_wc_query_args( $this->query_vars );

				$this->order_ids = wc_get_orders( $args );
			}

			return $this->order_ids;
		}

		/**
		 * Return payment method stats.
		 *
		 * @return array
		 */
		private function get_payment_method_stats() {
			global $wpdb;
			$order_ids      = $this->get_order_ids();
			$stats          = array();
			$payment_prefix = '_yith_pos_gateway_';



			if ( ! ! $order_ids ) {
				$ids                       = implode( ',', $order_ids );
				$payment_key_search        = $payment_prefix . '%';
				$payment_gateways          = yith_pos_get_active_payment_methods();
				$payment_gateways_id_title = wp_list_pluck( $payment_gateways, 'title', 'id' );

                if ( yith_plugin_fw_is_wc_custom_orders_table_usage_enabled() ) {
                    $order_table = $wpdb->prefix.'wc_orders_meta';

                    $totals = $wpdb->get_results(
                        $wpdb->prepare(
                        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                            "SELECT meta_key as method, SUM(meta_value) as amount FROM $order_table  WHERE order_id IN ({$ids}) AND meta_key LIKE %s GROUP BY meta_key",
                            array(
                                $payment_key_search
                            )
                        ),
                        ARRAY_A
                    );
                } else {
                    $totals = $wpdb->get_results(
                        $wpdb->prepare(
                        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                            "SELECT meta_key as method, SUM(meta_value) as amount FROM $wpdb->postmeta WHERE post_id IN ({$ids}) AND meta_key LIKE %s GROUP BY meta_key",
                            $payment_key_search
                        ),
                        ARRAY_A
                    );
                }

				$other_sales = 0;

				foreach ( $totals as $total ) {
					$method = str_replace( $payment_prefix, '', $total['method'] );
					$amount = $total['amount'];

					if ( isset( $payment_gateways_id_title[ $method ] ) ) {
						$stat_key           = 'payment_' . $method;
						$stats[ $stat_key ] = array(
							'title' => $payment_gateways_id_title[ $method ],
							'type'  => 'price',
							'value' => $amount,
						);
					} else {
						$other_sales += $amount;
					}
				}

				if ( $other_sales ) {
					$totals['payment_other'] = array(
						'title' => __( 'Other', 'yith-point-of-sale-for-woocommerce' ),
						'type'  => 'price',
						'value' => $other_sales,
					);
				}
			}

			return $stats;
		}

		/**
		 * Return tax stats.
		 *
		 * @return array
		 */
		private function get_tax_stats() {
			global $wpdb;
			$order_ids = $this->get_order_ids();
			$stats     = array(
				'itemized' => array(),
				'totals'   => array(),
			);

			if ( ! ! $order_ids ) {
				$ids    = implode( ',', $order_ids );
				$table  = $wpdb->prefix . 'wc_order_tax_lookup';
				$select = 'tax_rate_id, SUM(shipping_tax) as shipping_tax, SUM(order_tax) as order_tax, SUM(total_tax) as total_tax';
				$query  = "SELECT {$select} FROM {$table} WHERE order_id IN ({$ids}) GROUP BY tax_rate_id";

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$results = $wpdb->get_results( $query, ARRAY_A );

				$item_taxes     = array();
				$shipping_taxes = array();
				$totals         = array(
					'shipping_tax' => 0,
					'order_tax'    => 0,
					'total_tax'    => 0,
				);

				foreach ( $results as $result ) {
					$id       = $result['tax_rate_id'];
					$tax_name = yith_pos_get_tax_name_by_id( $id );
					if ( $result['order_tax'] > 0 ) {
						$item_taxes[ 'tax_' . $id ] = array(
							'title' => $tax_name,
							'type'  => 'price',
							'value' => $result['order_tax'],
						);
					}

					if ( $result['shipping_tax'] > 0 ) {
						$shipping_taxes[ 'tax_shipping_' . $id ] = array(
							'title' => $tax_name . ' - ' . __( 'Shipping', 'yith-point-of-sale-for-woocommerce' ),
							'type'  => 'price',
							'value' => $result['shipping_tax'],
						);
					}

					$totals['shipping_tax'] += $result['shipping_tax'];
					$totals['order_tax']    += $result['order_tax'];
					$totals['total_tax']    += $result['total_tax'];
				}

				$key_title = array(
					'shipping_tax' => __( 'Shipping Tax', 'yith-point-of-sale-for-woocommerce' ),
					'order_tax'    => __( 'Order Tax', 'yith-point-of-sale-for-woocommerce' ),
					'total_tax'    => __( 'Total Tax', 'yith-point-of-sale-for-woocommerce' ),
				);

				$stats['itemized'] = array_merge( $item_taxes, $shipping_taxes );

				foreach ( $totals as $key => $value ) {
					if ( $value > 0 ) {
						$stats['totals'][ $key ] = array(
							'title' => $key_title[ $key ] ?? '',
							'type'  => 'price',
							'value' => $value,
						);
					}
				}
			}

			return $stats;
		}

		/**
		 * Return tax stats.
		 *
		 * @return array
		 */
		private function get_order_stats() {
			global $wpdb;
			$order_ids = $this->get_order_ids();
			$stats     = array();

			if ( ! ! $order_ids ) {
				$ids     = implode( ',', $order_ids );
				$table   = $wpdb->prefix . 'wc_order_stats';
				$columns = array(
					'num_items_sold' => array(
						'title' => __( 'Products', 'yith-point-of-sale-for-woocommerce' ),
						'type'  => 'number',
					),
					'total_sales'    => array(
						'title' => __( 'Total Sales', 'yith-point-of-sale-for-woocommerce' ),
						'type'  => 'price',
					),
					'tax_total'      => array(
						'title' => __( 'Tax Total', 'yith-point-of-sale-for-woocommerce' ),
						'type'  => 'price',
					),
					'shipping_total' => array(
						'title' => __( 'Shipping', 'yith-point-of-sale-for-woocommerce' ),
						'type'  => 'price',
					),
					'net_total'      => array(
						'title' => __( 'Net Sales', 'yith-point-of-sale-for-woocommerce' ),
						'type'  => 'price',
					),
				);

				$select = array_map(
					function ( $column ) {
						return "SUM($column) as $column";
					},
					array_keys( $columns )
				);
				$select = implode( ', ', $select );
				$query  = "SELECT {$select} FROM {$table} WHERE order_id IN ({$ids})";

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$results = $wpdb->get_row( $query, ARRAY_A );

				foreach ( $results as $key => $value ) {
					if ( $value > 0 ) {
						$stats[ $key ] = array(
							'title' => $columns[ $key ]['title'] ?? '',
							'type'  => $columns[ $key ]['type'] ?? 'price',
							'value' => $value,
						);
					}
				}
			}

			return $stats;
		}


		/**
		 * Retrieve stats.
		 *
		 * @return array
		 */
		public function get_stats(): array {
			$order_stats    = $this->get_order_stats();
			$tax_stats      = $this->get_tax_stats();
			$itemized_taxes = $tax_stats['itemized'];
			$tax_totals     = $tax_stats['totals'];

			$merged_stats = array_merge( $order_stats, $tax_totals );

			$stats = array(
				'orders_count' => array(
					'title' => __( 'Orders', 'yith-point-of-sale-for-woocommerce' ),
					'type'  => 'number',
					'value' => count( $this->get_order_ids() ),
				),
			);

			if ( isset( $order_stats['num_items_sold'] ) ) {
				$stats['num_items_sold'] = $order_stats['num_items_sold'];
			}

			$stats = array_merge( $stats, $this->get_payment_method_stats() );

			foreach ( array( 'net_total', 'shipping_total' ) as $key ) {
				if ( isset( $merged_stats[ $key ] ) ) {
					$stats[ $key ] = $merged_stats[ $key ];
				}
			}

			$stats = array_merge( $stats, $itemized_taxes );

			foreach ( array( 'order_tax', 'shipping_tax', 'total_tax', 'total_sales' ) as $total_key ) {
				if ( isset( $merged_stats[ $total_key ] ) ) {
					$stats[ $total_key ] = $merged_stats[ $total_key ];
				}
			}

			// Format stats.
			foreach ( $stats as &$stat ) {
				$type          = $stat['type'] ?? 'price';
				$default_value = in_array( $type, array( 'price', 'number' ), true ) ? 0 : '';
				$value         = $stat['value'] ?? $default_value;
				if ( 'price' === $type ) {
					$stat['value'] = yith_pos_format_price( $value );
				}
			}

			return $stats;
		}
	}
}
