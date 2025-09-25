<?php
/**
 * Frontend Class.
 * Handle the asset registering and enqueueing.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Frontend' ) ) {
	/**
	 * Class YITH_POS_Frontend
	 * Main Frontend Class
	 */
	class YITH_POS_Frontend {

		use YITH_POS_Singleton_Trait;

		/**
		 * YITH_POS_Frontend constructor.
		 */
		private function __construct() {
			add_action( 'template_redirect', array( $this, 'register_login_logout_handler' ) );

			add_action( 'yith_pos_footer', array( $this, 'print_script_settings' ) );

			add_filter( 'woocommerce_rest_product_object_query', array( $this, 'extends_rest_product_query' ), 10, 2 );

			// Product search.
			add_filter( 'woocommerce_rest_product_object_query', array( $this, 'search_product_args' ), 10, 2 );
			add_filter( 'woocommerce_rest_product_object_query', array( $this, 'filter_product_on_sale' ), 10, 2 );

			// Order search.
			add_filter( 'woocommerce_rest_shop_order_object_query', array( $this, 'search_order_args' ), 10, 2 );

			// Add information to REST objects.
			add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'extend_rest_order_response' ), 10, 3 );
			add_filter( 'woocommerce_rest_prepare_customer', array( $this, 'extend_rest_customer_response' ), 10, 3 );

			add_filter( 'woocommerce_rest_prepare_product_variation_object', array( $this, 'extend_rest_product_response' ), 10, 3 );
			add_filter( 'woocommerce_rest_prepare_product_object', array( $this, 'extend_rest_product_response' ), 10, 3 );

			if ( 'yes' === get_option( 'yith_pos_show_vat_field_on_frontend', 'yes' ) ) {
				add_filter( 'woocommerce_billing_fields', array( $this, 'add_billing_vat' ) );
			}

			add_action( 'rest_api_init', array( $this, 'generate_password_for_new_users' ) );
		}

		/**
		 * Generate password for new users created through POS
		 * even if WooCommerce settings require the password field to be set on checkout.
		 *
		 * @since 2.0.0
		 */
		public function generate_password_for_new_users() {
			$pos_request = sanitize_title( wp_unslash( $_REQUEST['yith_pos_request'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'create-customer' === $pos_request ) {
				add_filter( 'pre_option_woocommerce_registration_generate_password', array( $this, 'force_generate_password_for_new_users' ) );
			}
		}

		/**
		 * Force generating password for new users.
		 *
		 * @return string
		 * @since 2.0.0
		 */
		public function force_generate_password_for_new_users() {
			return 'yes';
		}

		/**
		 * Filter the product on sale.
		 * This method has been written because WC add all products as post_in without handling the excluded products.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 *
		 * @return array
		 */
		public function filter_product_on_sale( $args, $request ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended

			if ( isset( $_GET['yith_on_sale'] ) ) {
				$on_sale_ids      = wc_get_product_ids_on_sale();
				$exclude          = isset( $_GET['exclude'] ) ? array_map( 'absint', explode( ',', wc_clean( wp_unslash( $_GET['exclude'] ) ) ) ) : array();
				$args['post__in'] = array_diff( $on_sale_ids, $exclude );
				unset( $args['post__not_in'] );
			}

			// phpcs:enable

			return $args;
		}

		/**
		 * Add billing VAT field.
		 *
		 * @param array $address_fields Address fields.
		 *
		 * @return array
		 */
		public function add_billing_vat( $address_fields ) {
			if ( apply_filters( 'yith_pos_add_billing_vat_field', true ) ) {
				$address_fields['billing_vat'] = array(
					'label'    => yith_pos_get_vat_field_label(),
					'required' => false,
					'type'     => 'text',
					'class'    => array( 'form-row-wide' ),
					'priority' => 35,
				);
			}

			return $address_fields;
		}

		/**
		 * Add parent_categories to product variations (for coupon check).
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Product       $product  The product.
		 * @param WP_REST_Request  $request  Request object.
		 *
		 * @return WP_REST_Response
		 * @deprecated 2.0.0 | use YITH_POS_Frontend::extend_rest_product_response instead.
		 */
		public function rest_parent_categories( $response, $product, $request ) {
			yith_pos_deprecated_function( 'YITH_POS_Frontend::rest_parent_categories', '2.0', 'YITH_POS_Frontend::extend_rest_product_response' );

			return $this->extend_rest_product_response( $response, $product, $request );
		}

		/**
		 * Set product thumbnails in REST response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Product       $product  The product.
		 * @param WP_REST_Request  $request  Request object.
		 *
		 * @return WP_REST_Response
		 * @deprecated 2.0.0 | use YITH_POS_Frontend::extend_rest_product_response instead.
		 */
		public function rest_product_thumbnails( $response, $product, $request ) {
			yith_pos_deprecated_function( 'YITH_POS_Frontend::rest_product_thumbnails', '2.0', 'YITH_POS_Frontend::extend_rest_product_response' );

			return $this->extend_rest_product_response( $response, $product, $request );
		}

		/**
		 * Set product thumbnails in REST response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Product       $product  The product.
		 * @param WP_REST_Request  $request  Request object.
		 *
		 * @return WP_REST_Response
		 */
		public function extend_rest_product_response( $response, $product, $request ) {
			if ( $product ) {
				$pos_request = $request['yith_pos_request'] ?? false;
				$context     = $request['context'] ?? 'view';

				if ( ! ! $pos_request ) {
					$data     = $response->get_data();
					$store_id = $request['yith_pos_store'] ?? false;
					$changed  = false;

					if ( $store_id ) {
						$parsed_product       = yith_pos_parse_product_stock( $product, $store_id );
						$multistock_condition = get_option( 'yith_pos_multistock_condition', 'allowed' );
						if ( apply_filters( 'yith_pos_parse_product_stock_check', true, $parsed_product, $multistock_condition, $store_id ) && ( $parsed_product->get_stock_quantity( $context ) > 0 || 'general' != $multistock_condition ) ) {
							$data['manage_stock']   = $parsed_product->managing_stock();
							$data['stock_quantity'] = $parsed_product->get_stock_quantity( $context );
							$data['stock_status']   = $parsed_product->get_stock_status( $context );
							$data['in_stock']       = $parsed_product->is_in_stock();
							$data['backordered']    = $parsed_product->is_on_backorder();
							$changed                = true;
						}

					}

					$image = $product->is_type( 'variation' ) ? yith_pos_rest_get_product_thumbnail( $product->get_parent_id(), $product->get_id() ) : yith_pos_rest_get_product_thumbnail( $product->get_id() );

					if ( $image ) {
						$data['yith_pos_image'] = $image;
						$changed                = true;
					}

					if ( $product->is_type( 'variation' ) ) {
						$variable   = wc_get_product( $product->get_parent_id() );
						$categories = ! ! $variable ? $this->get_taxonomy_terms( $variable ) : array();

						$data['parent_categories'] = $categories;
						$changed                   = true;
					}

					if ( $changed ) {
						$response->set_data( $data );
					}
				}
			}

			return $response;
		}

		/**
		 * Add fields in order REST response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Order         $order    The order.
		 * @param WP_REST_Request  $request  Request object.
		 *
		 * @return WP_REST_Response
		 */
		public function extend_rest_order_response( $response, $order, $request ) {
			$pos_request = $request['yith_pos_request'] ?? false;
			$pos_context = $request['yith_pos_context'] ?? false;

			if ( $order && ! ! $pos_request ) {
				$data = $response->get_data();

				if ( 'stats' !== $pos_context ) {
					$store_id = $request['yith_pos_store'] ?? false;
					$language = $request['lang'] ?? false;

					// Add item thumbnails.
					if ( isset( $data['line_items'] ) ) {
						foreach ( $data['line_items'] as &$line_item ) {
							$variation_id = $line_item['variation_id'] ?? 0;
							$product_id   = $line_item['product_id'];

							$line_item['yith_pos_image'] = yith_pos_rest_get_product_thumbnail( $product_id, $variation_id );

							if ( in_array( $pos_request, array( 'create-order' ), true ) ) {
								$real_product_id   = ! ! $variation_id ? $variation_id : $product_id;
								$rest_query_params = array(
									'yith_pos_request' => $pos_request,
									'yith_pos_store'   => $store_id,
								);
								if ( $language ) {
									$rest_query_params['lang'] = $language;
								}
								$product_data = yith_pos_do_rest_request( 'GET', "/wc/v3/products/{$real_product_id}", $rest_query_params );
								if ( ! is_wp_error( $product_data ) ) {
									$line_item['product'] = $product_data;
								}
							}
						}
					}

					$info        = array();
					$store_id    = $order->get_meta( '_yith_pos_store' );
					$register_id = $order->get_meta( '_yith_pos_register' );
					$cashier_id  = $order->get_meta( '_yith_pos_cashier' );

					if ( $store_id ) {
						$info['store_name'] = yith_pos_get_store_name( $store_id );
					}

					if ( $register_id ) {
						$info['register_name'] = yith_pos_get_register_name( $register_id );
					}

					if ( $cashier_id ) {
						$info['cashier_name'] = yith_pos_get_employee_name( $cashier_id, array( 'hide_nickname' => true ) );
					}

					if ( $info ) {
						$data['yith_pos_data'] = $info;
					}
				}

				$data['multiple_payment_methods'] = yith_pos_get_order_payment_methods( $order );
				$data['pos_payment_details']      = yith_pos_get_order_payment_details( $order );

				$response->set_data( $data );
			}

			return $response;
		}

		/**
		 * Add data in customer REST response.
		 *
		 * @param WP_REST_Response $response  The response object.
		 * @param WP_User          $user_data The user data.
		 * @param WP_REST_Request  $request   Request object.
		 *
		 * @return WP_REST_Response
		 * @since 2.10.0
		 */
		public function extend_rest_customer_response( $response, $user_data, $request ) {
			$pos_request = $request['yith_pos_request'] ?? false;

			// Add meta_data for cashiers and managers, since WooCommerce adds it only for administrators (ie: useful for showing VAT).
			if ( ! ! $pos_request && current_user_can( 'yith_pos_view_users' ) ) {
				$data = $response->get_data();

				if ( ! isset( $data['meta_data'] ) ) {
					try {
						$customer      = new WC_Customer( $user_data->ID );
						$customer_data = $customer->get_data();
						if ( isset( $customer_data['meta_data'] ) ) {
							$data['meta_data'] = $customer_data['meta_data'];

							$response->set_data( $data );
						}
					} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
						// Do not add the meta_data.
					}
				}
			}

			return $response;
		}


		/**
		 * Retrieve taxonomy terms
		 *
		 * @param WC_Product $product  The product.
		 * @param string     $taxonomy The taxonomy.
		 *
		 * @return array
		 */
		protected function get_taxonomy_terms( WC_Product $product, string $taxonomy = 'product_cat' ): array {
			$terms          = array();
			$taxonomy_terms = wc_get_object_terms( $product->get_id(), $taxonomy );

			foreach ( $taxonomy_terms as $term ) {
				$terms[] = array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}

			return $terms;
		}


		/**
		 * Extends REST product query.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 *
		 * @return array
		 */
		public function extends_rest_product_query( $args, $request ) {
			$meta_query = array();

			if ( isset( $request['yith_pos_stock_status'] ) ) {
				$stock_statuses = explode( ',', $request['yith_pos_stock_status'] );
				$meta_query[]   = array(
					'key'     => '_stock_status',
					'value'   => $stock_statuses,
					'compare' => 'IN',
				);
			}

			if ( ( $request['yith_pos_has_price'] ?? 'no' ) === 'yes' ) {
				$meta_query[] = array(
					'key'     => '_price',
					'value'   => '',
					'compare' => '!=',
				);
			}

			if ( $meta_query ) {
				$args['meta_query'] = array_merge( $args['meta_query'] ?? array(), $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}

			if ( isset( $request['exclude_category'] ) ) {
				$stock_statuses = explode( ',', $request['exclude_category'] );
				$tax_query      = array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $stock_statuses,
					'operator' => 'NOT IN',
				);
				if ( isset( $args['tax_query'] ) ) {
					$args['tax_query'][] = $tax_query;
				} else {
					$args['tax_query'] = array( $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				}
			}

			return $args;
		}

		/**
		 * Extend search product to sku for product and product variation.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 *
		 * @return array
		 */
		public function search_product_args( $args, $request ) {
			global $wpdb;
			$pos_request = $request['yith_pos_request'] ?? false;
			$pos_scan    = $request['yith_pos_scan'] ?? null; // Values: yes, no, sku.

			if ( 'search-products' === $pos_request && isset( $args['s'] ) && ! is_null( $pos_scan ) && 'sku' !== $pos_scan ) {
				$is_custom_barcode_search = yith_plugin_fw_is_true( $pos_scan );
				$include_variations       = apply_filters( 'yith_pos_search_include_variations', $is_custom_barcode_search, $args, $request );
				$include_searching_by_sku = apply_filters( 'yith_pos_search_include_searching_by_sku', false, $args, $request );
				$per_page                 = apply_filters( 'yith_pos_search_products_per_page', $args['posts_per_page'] ?? 9 );

				if ( $include_variations ) {
					add_filter( 'pre_get_posts', array( $this, 'filter_query_post_type' ), 10 );
				}

				if ( $is_custom_barcode_search ) {
					$barcode_meta = yith_pos_get_barcode_meta();
					$search       = esc_sql( trim( $args['s'] ) );
					$query        = $wpdb->prepare(
						"SELECT p.ID FROM $wpdb->posts p
                            LEFT JOIN $wpdb->postmeta pm2 ON ( pm2.post_id = p.ID AND pm2.meta_key = %s )
                            WHERE p.post_type in('product', 'product_variation') AND p.post_status = 'publish'
                            AND pm2.meta_key = %s AND pm2.meta_value = %s
                            GROUP BY p.ID LIMIT %d",
						$barcode_meta,
						$barcode_meta,
						$search,
						$per_page
					);
					$query        = apply_filters( 'yith_pos_query_custom_barcode_search', $query, $barcode_meta, $search, $per_page );
				} elseif ( $include_variations || $include_searching_by_sku ) {
					$title_search  = apply_filters( 'yith_pos_search_by_title_arg', '%' . esc_sql( $args['s'] ) . '%', $args['s'] );
					$use_exact_sku = ! ! apply_filters( 'yith_pos_search_use_exact_sku', true, $args, $request );
					$barcode_meta  = yith_pos_get_barcode_meta();


					$sku_search = esc_sql( $args['s'] );
					if ( ! $use_exact_sku ) {
						$sku_search = '%' . $sku_search . '%';
					}
					$sku_search = apply_filters( 'yith_pos_search_by_sku_arg', $sku_search, $args['s'] );

					$join  = '';
					$where = $include_variations ? "p.post_type in ('product', 'product_variation')" : "p.post_type = 'product'";

					$where .= " AND p.post_status = 'publish' ";

					$limit = $wpdb->prepare( 'LIMIT %d', $per_page );

					if ( $include_searching_by_sku ) {
						$join .= " LEFT JOIN $wpdb->postmeta pm1 ON ( pm1.post_id = p.ID) ";
						if ( $use_exact_sku ) {
							$where .= $wpdb->prepare( " AND ( p.post_title LIKE %s OR ( pm1.meta_key = %s AND pm1.meta_value = %s ) ) ", $title_search, $barcode_meta, $sku_search );
						} else {
							$where .= $wpdb->prepare( " AND ( p.post_title LIKE %s OR ( pm1.meta_key = %s AND pm1.meta_value LIKE %s ) ) ", $title_search, $barcode_meta, $sku_search );
						}
					} else {
						$where .= $wpdb->prepare( ' AND p.post_title LIKE %s ', $title_search );
					}

					$query = "SELECT DISTINCT p.ID FROM $wpdb->posts p {$join} WHERE {$where} {$limit}";
				} else {
					// Use the standard WooCommerce Search through REST API.
					$query                  = false;
					$args['posts_per_page'] = $per_page;
				}

				if ( $query ) {
					$results = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

					if ( $results ) {
						$args['post__in'] = $results;
						unset( $args['s'] );
					}
				}
			}

			// phpcs:enable

			return $args;
		}


		/**
		 * Filter the orders for the store.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 *
		 * @return array
		 */
		public function search_order_args( $args, $request ) {
			$pos_request = $request['yith_pos_request'] ?? false;

			if ( 'get-orders' === $pos_request ) {
				$meta_query = array(
					'relation' => 'AND',
				);

				$specific_query_set = false;
				$store_id           = absint( $request['store'] ?? 0 );
				$register_id        = absint( $request['register'] ?? 0 );
				$cashier_id         = absint( $request['cashier'] ?? 0 );

				if ( $store_id && ! $register_id ) {
					$specific_query_set = true;
					$meta_query[]       = array(
						'key'     => '_yith_pos_store',
						'value'   => $store_id,
						'compare' => '=',
					);
				}

				if ( $register_id ) {
					$specific_query_set = true;
					$meta_query[]       = array(
						'key'     => '_yith_pos_register',
						'value'   => $register_id,
						'compare' => '=',
					);
				}

				if ( $cashier_id ) {
					$specific_query_set = true;
					$meta_query[]       = array(
						'key'     => '_yith_pos_cashier',
						'value'   => $cashier_id,
						'compare' => '=',
					);
				}

				if ( ! $specific_query_set ) {
					$meta_query[] = array(
						'key'     => '_yith_pos_order',
						'value'   => '1',
						'compare' => '=',
					);
				}

				$args['meta_query'] = apply_filters( 'yith_pos_search_order_meta_query', $meta_query, $request ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}

			return $args;
		}

		/**
		 * Extend the query also for product variation.
		 *
		 * @param WP_Query $query The WP query object.
		 */
		public function filter_query_post_type( $query ) {
			$query->query_vars['post_type'] = array( 'product', 'product_variation' );
		}


		/**
		 * Handle login/logout for POS registers.
		 */
		public function register_login_logout_handler() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
			if ( is_yith_pos() ) {

				$register_id = isset( $_POST['register'] ) ? absint( $_POST['register'] ) : yith_pos_register_logged_in();

				if ( isset( $_GET['yith-pos-register-direct-login-nonce'], $_GET['register'] ) ) {
					$register_id = absint( $_GET['register'] );
				}
				if ( $register_id && ! isset( $_GET['user-editing'] ) && ! isset( $_GET['yith-pos-take-over-nonce'] ) ) {
					$user_editing = yith_pos_check_register_lock( $register_id );
					if ( $user_editing ) {
						// Another user is managing the register.
						$args = array(
							'user-editing' => $user_editing,
							'register'     => $register_id,
							'store'        => isset( $_POST['store'] ) ? absint( $_POST['store'] ) : '',
						);
						wp_safe_redirect( add_query_arg( $args, yith_pos_get_pos_page_url() ) );
						exit;
					}
				}
				$action      = '';
				$register_id = false;
				$redirect    = false;

				if ( isset( $_POST['yith-pos-register-login-nonce'], $_POST['register'] ) && wp_verify_nonce( wc_clean( wp_unslash( $_POST['yith-pos-register-login-nonce'] ) ), 'yith-pos-register-login' ) ) {
					$action      = 'login';
					$register_id = absint( $_POST['register'] );
					$redirect    = yith_pos_get_pos_page_url();
				} elseif ( isset( $_GET['yith-pos-register-direct-login-nonce'], $_GET['register'] ) && wp_verify_nonce( wc_clean( wp_unslash( $_GET['yith-pos-register-direct-login-nonce'] ) ), 'yith-pos-register-direct-login' ) ) {
					$action      = 'direct-login';
					$register_id = absint( $_GET['register'] );
					$redirect    = yith_pos_get_pos_page_url();
				} elseif ( isset( $_GET['yith-pos-take-over-nonce'], $_GET['register'] ) && wp_verify_nonce( wc_clean( wp_unslash( $_GET['yith-pos-take-over-nonce'] ) ), 'yith-pos-take-over' ) ) {
					$action      = 'take-over';
					$register_id = absint( $_GET['register'] );
					$redirect    = yith_pos_get_pos_page_url();
				} elseif ( isset( $_GET['yith-pos-register-close-nonce'], $_GET['register'] ) && wp_verify_nonce( wc_clean( wp_unslash( $_GET['yith-pos-register-close-nonce'] ) ), 'yith-pos-register-close-' . absint( $_GET['register'] ) ) ) {
					$action      = 'close-register';
					$register_id = absint( $_GET['register'] );
					// TODO: redirect to a specific page to show the report for closing register.
					$redirect = yith_pos_get_pos_page_url();
				} elseif ( ! empty( $_GET['yith-pos-user-logout'] ) ) {
					$action   = 'logout';
					$redirect = yith_pos_get_pos_page_url();
				} elseif ( ! empty( $_GET['yith-pos-register-logout'] ) ) {
					$action   = 'register-logout';
					$redirect = yith_pos_get_pos_page_url();
				}

				if ( $register_id && ! yith_pos_user_can_use_register( $register_id ) ) {
					wp_die( esc_html__( 'Error: you cannot get access to this Register!', 'yith-point-of-sale-for-woocommerce' ) );
				}

				switch ( $action ) {
					case 'login':
					case 'direct-login':
					case 'take-over':
						if ( $register_id ) {
							yith_pos_maybe_open_register( $register_id );
							yith_pos_set_register_lock( $register_id );
							yith_pos_register_login( $register_id );
						}
						break;

					case 'close-register':
						if ( $register_id ) {
							yith_pos_close_register( $register_id );
						} // no break // Please DON'T break me, since I need to logout.
					case 'register-logout':
					case 'logout':
						$register_id = yith_pos_register_logged_in();
						if ( $register_id ) {
							yith_pos_unset_register_lock( $register_id );
						}
						yith_pos_register_logout();

						if ( 'logout' === $action ) {
							wp_logout();
						}
						break;
				}

				if ( $redirect ) {
					wp_safe_redirect( $redirect );
					exit;
				}
			}
			// phpcs:enable
		}

		/**
		 * Print script settings.
		 */
		public function print_script_settings() {
			$settings = yith_pos_settings()->get_frontend_settings();
			if ( $settings ) {
				?>
				<script type="text/javascript">
					var yithPosSettings = yithPosSettings || JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode( $settings ) ); ?>' ) );
				</script>
				<?php
			}
		}

	}
}

if ( ! function_exists( 'yith_pos_frontend' ) ) {
	/**
	 * Unique access to instance of YITH_POS_Frontend class
	 *
	 * @return YITH_POS_Frontend
	 */
	function yith_pos_frontend() {
		return YITH_POS_Frontend::get_instance();
	}
}
