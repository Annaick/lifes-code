<?php
/**
 * Assets Class.
 * Handle the asset registering and enqueueing.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Assets' ) ) {
	/**
	 * Assets Class.
	 *
	 * @class  YITH_POS_Assets
	 */
	class YITH_POS_Assets {
		use YITH_POS_Singleton_Trait;

		/**
		 * YITH_POS_Assets constructor.
		 */
		private function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'register_common_scripts' ), 11 );
			add_action( 'wp_enqueue_scripts', array( $this, 'register_common_scripts' ), 11 );

			if ( YITH_POS::is_request( 'admin' ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ), 11 );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 11 );
			} else {
				add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_scripts' ), 11 );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ), 11 );
			}

			add_filter( 'pre_load_script_translations', array( $this, 'script_translations' ), 10, 4 );
		}

		/**
		 * Return the script version.
		 *
		 * @return string
		 */
		private function get_script_version() {
			$version = YITH_POS_VERSION;
			if ( defined( 'YITH_POS_SCRIPT_DEBUG' ) && YITH_POS_SCRIPT_DEBUG ) {
				$version .= '-' . time();
			}

			return $version;
		}

		/**
		 * Register common scripts
		 */
		public function register_common_scripts() {
			$version = $this->get_script_version();

			wp_register_style( 'yith-pos-font', YITH_POS_ASSETS_URL . '/fonts/yith-pos-icon/yith-pos-icon.css', array(), $version );
		}

		/**
		 * Register admin scripts
		 */
		public function register_admin_scripts() {
			global $post;
			$post_id = $post && isset( $post->ID ) ? $post->ID : '';
			$version = $this->get_script_version();
			$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_register_script( 'yith-pos-admin-globals', YITH_POS_ASSETS_URL . '/js/admin/globals' . $suffix . '.js', array( 'jquery' ), $version, true );

			wp_register_script( 'yith-pos-admin', YITH_POS_ASSETS_URL . '/js/admin/admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'yith-pos-admin-globals' ), $version, true );

			wp_register_script( 'yith-pos-admin-validation', YITH_POS_ASSETS_URL . '/js/admin/validation' . $suffix . '.js', array( 'jquery' ), $version, true );
			wp_register_script( 'yith-pos-admin-store-wizard', YITH_POS_ASSETS_URL . '/js/admin/store-wizard' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'yit-metabox' ), $version, true );
			wp_register_script( 'yith-pos-admin-store-edit', YITH_POS_ASSETS_URL . '/js/admin/store-edit' . $suffix . '.js', array( 'jquery', 'yith-pos-admin-globals', 'yit-metabox' ), $version, true );
			wp_register_script( 'yith-pos-admin-receipt-edit', YITH_POS_ASSETS_URL . '/js/admin/receipt-edit' . $suffix . '.js', array( 'jquery' ), $version, true );
			wp_register_script( 'yith-pos-admin-gateways', YITH_POS_ASSETS_URL . '/js/admin/gateways' . $suffix . '.js', array( 'jquery' ), $version, true );
			wp_register_script( 'yith-pos-admin-products', YITH_POS_ASSETS_URL . '/js/admin/products' . $suffix . '.js', array( 'jquery' ), $version, true );
			wp_register_script( 'yith-pos-admin-bulk-edit', YITH_POS_ASSETS_URL . '/js/admin/bulk-edit' . $suffix . '.js', array( 'jquery' ), $version, true );
			wp_register_script( 'yith-pos-admin-order-edit', YITH_POS_ASSETS_URL . '/js/admin/order-edit' . $suffix . '.js', array( 'jquery' ), $version, true );

			wp_register_style( 'yith-pos-admin', YITH_POS_ASSETS_URL . '/css/admin/admin.css', array(), $version );
			wp_register_style( 'yith-pos-admin-store-edit', YITH_POS_ASSETS_URL . '/css/admin/store-edit.css', array(), $version );
			wp_register_style( 'yith-pos-admin-receipt-edit', YITH_POS_ASSETS_URL . '/css/admin/receipt-edit.css', array(), $version );
			wp_register_style( 'yith-pos-admin-products', YITH_POS_ASSETS_URL . '/css/admin/products.css', array( 'yith-plugin-fw-fields' ), $version );
			wp_register_style( 'yith-pos-admin-orders', YITH_POS_ASSETS_URL . '/css/admin/orders.css', array( 'yith-plugin-ui' ), $version );
			wp_register_style( 'yith-pos-admin-register-sessions', YITH_POS_ASSETS_URL . '/css/admin/register-sessions.css', array(), $version );

			wp_register_style( 'yith-pos-admin-dashboard', YITH_POS_ASSETS_URL . '/css/admin/dashboard.css', array(), $version );

			$dashboard_deps       = array( 'wp-api-fetch', 'wp-components', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-data', 'wc-components', 'react', 'react-dom', 'lodash' );
			$use_legacy_dashboard = version_compare( WC()->version, '6.7.0', '<' );
			$dashboard_js         = YITH_POS_REACT_URL . '/dashboard/index.js';
			if ( $use_legacy_dashboard ) {
				$dashboard_js = YITH_POS_URL . 'dist-legacy/dashboard/index.js';
			}
			wp_register_script( 'yith-pos-dashboard', $dashboard_js, $dashboard_deps, $version, true );

			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( 'yith-pos-dashboard', 'yith-point-of-sale-for-woocommerce', YITH_POS_LANGUAGES_PATH );
			}

			/* Localization ----------------- */

			$yith_pos_admin = array(
				'i18n'      => array(
					'one_register_required' => esc_html__( 'You need to create at least one Register before proceeding', 'yith-point-of-sale-for-woocommerce' ),
					'pos_results_only'      => esc_html__( 'POS results only', 'yith-point-of-sale-for-woocommerce' ),
				),
				'nonces'    => array(
					'store_wizard_save'        => wp_create_nonce( 'yith-pos-store-wizard-save' ),
					'store_wizard_get_summary' => wp_create_nonce( 'yith-pos-store-wizard-get-summary' ),
					'check_user_login'         => wp_create_nonce( 'yith-pos-check-user-login' ),
				),
				'templates' => array(
					'notice' => yith_plugin_fw_get_component(
						array(
							'type'        => 'notice',
							'notice_type' => '{{status}}',
							'id'          => '{{id}}',
							'message'     => '{{message}}',
						),
						false
					),
				),
			);

			wp_localize_script( 'yith-pos-admin', 'yith_pos_admin', $yith_pos_admin );
			wp_localize_script( 'yith-pos-admin-store-wizard', 'yith_pos_admin', $yith_pos_admin );
			wp_localize_script( 'yith-pos-admin-bulk-edit', 'yith_pos_admin', $yith_pos_admin );
			wp_localize_script( 'yith-pos-admin-validation', 'yith_pos_admin', $yith_pos_admin );

			$yith_pos_store_edit = array(
				'post_id'                           => $post_id,
				'create_register_nonce'             => wp_create_nonce( 'yith-pos-create-register' ),
				'update_register_nonce'             => wp_create_nonce( 'yith-pos-update-register' ),
				'delete_register_nonce'             => wp_create_nonce( 'yith-pos-delete-register' ),
				'i18n_register_delete_confirmation' => __( 'Are you sure you want to delete this Register?', 'yith-point-of-sale-for-woocommerce' ) . '<br /><br />' . __( 'This action cannot be undone and you will not be able to recover this data.', 'yith-point-of-sale-for-woocommerce' ),
				'i18n_delete_confirmation_title'    => __( 'Confirm delete', 'yith-point-of-sale-for-woocommerce' ),
				'i18n_delete_confirmation_no'       => __( 'No', 'yith-point-of-sale-for-woocommerce' ),
				'i18n_delete_confirmation_confirm'  => _x( 'Yes, delete', 'Delete confirmation action', 'yith-point-of-sale-for-woocommerce' ),
			);

			wp_localize_script( 'yith-pos-admin-store-edit', 'yith_pos_store_edit', $yith_pos_store_edit );
		}

		/**
		 * Register frontend scripts
		 */
		public function register_frontend_scripts() {
			$version = $this->get_script_version();
			$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_register_style( 'yith-pos-open-sans', '//fonts.googleapis.com/css?family=Open+Sans:400,600,700,800&display=swap', array(), $version );

			wp_register_style( 'yith-pos-frontend', YITH_POS_ASSETS_URL . '/css/frontend/pos.css', array(), $version );
			wp_register_style( 'yith-pos-login', YITH_POS_ASSETS_URL . '/css/frontend/login.css', array( 'yith-pos-font' ), $version );

			wp_register_style( 'yith-pos-rtl', YITH_POS_ASSETS_URL . '/css/frontend/pos-rtl.css', array(), $version );

			$pos_deps = array( 'wp-api-fetch', 'wp-components', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-data', 'wp-date', 'react', 'react-dom', 'lodash' );
			wp_register_script( 'yith-pos-frontend', YITH_POS_REACT_URL . '/pos/index.js', $pos_deps, $version, true );

			wp_register_script( 'yith-pos-register-login', YITH_POS_ASSETS_URL . '/js/register-login' . $suffix . '.js', array( 'jquery' ), $version, true );
		}

		/**
		 * Enqueue admin scripts
		 */
		public function enqueue_admin_scripts() {
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			$order_screen_ids = array_filter( array( 'shop_order', function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : '' ) );

			if ( yith_pos_is_admin_page( $order_screen_ids ) ) {
				global $post_id, $theorder;
				if ( $theorder instanceof WC_Order ) {
					$order = $theorder;
				} else {
					$order = wc_get_order( $post_id );
				}
				if ( $order ) {
					$coupon_items         = $order->get_coupons();
					$pos_discount_coupons = array();
					foreach ( $coupon_items as $item ) {
						if ( yith_pos_is_discount_coupon_code( $item->get_code() ) ) {
							$pos_discount_coupon_label = esc_html( apply_filters( 'yith_pos_discount_coupon_label', __( 'Discount', 'yith-point-of-sale-for-woocommerce' ), $item, $order ) );

							$reason = $item->get_meta( '_yith_pos_discount_coupon_reason' );
							if ( ! $reason ) {
								$coupon_data = $item->get_meta( 'coupon_data' ); // WooCommerce now stores the coupon data in the coupon_info meta. Kept for old coupon items, having data stored in coupon_data.
								if ( $coupon_data && isset( $coupon_data['description'] ) ) {
									$reason = $coupon_data['description'];
								}
							}

							$pos_discount_coupons[ $item->get_code() ] = implode( ' - ', array_filter( array( $pos_discount_coupon_label, $reason ) ) );
						}
					}
					wp_localize_script( 'yith-pos-admin-order-edit', 'yithPosDiscountCouponReasons', $pos_discount_coupons );

					wp_enqueue_style( 'yith-pos-admin' );
					wp_enqueue_style( 'yith-pos-admin-orders' );
					wp_enqueue_script( 'yith-pos-admin-order-edit' );
				}
			}

			if ( 'product' === $screen_id ) {
				wp_enqueue_script( 'yith-plugin-fw-fields' );
			}

			if ( yith_pos_is_admin_page( 'product' ) ) {
				wp_enqueue_script( 'yith-pos-admin-bulk-edit' );
			}

			if ( yith_pos_is_admin_page( 'any' ) ) {
				wp_enqueue_script( 'yith-pos-admin' );
				wp_enqueue_script( 'yith-pos-admin-validation' );
				wp_enqueue_style( 'yith-pos-admin' );
				wp_enqueue_style( 'yith-pos-font' );
			}

			if ( yith_pos_is_admin_page( 'store' ) ) {
				wp_enqueue_style( 'yith-pos-admin-store-edit' );
				wp_enqueue_script( 'yith-pos-admin-store-edit' );
				if ( yith_pos_is_store_wizard() ) {
					wp_enqueue_script( 'yith-pos-admin-store-wizard' );
				}
			}

			if ( yith_pos_is_admin_page( array( 'store', 'register' ) ) ) {
				wp_enqueue_script( 'selectWoo' );
				wp_enqueue_script( 'wc-enhanced-select' );
			}

			if ( yith_pos_is_admin_page( array( 'edit-store', 'edit-register' ) ) ) {
				wp_enqueue_script( 'yith-plugin-fw-fields' );
			}

			if ( yith_pos_is_admin_page( 'receipt' ) ) {
				wp_enqueue_style( 'yith-pos-admin-receipt-edit' );
				wp_enqueue_script( 'yith-pos-admin-receipt-edit' );
			}

			if ( yith_pos_is_admin_page( 'panel', 'registers', 'registers-register-sessions' ) ) {
				wp_enqueue_style( 'yith-pos-admin-register-sessions' );
				wp_enqueue_style( 'yith-pos-font' );
			}

			if ( yith_pos_is_admin_page( 'woocommerce_page_wc-settings' ) ) {
				wp_enqueue_script( 'yith-pos-admin-gateways' );
			}

			if ( yith_pos_is_admin_page( 'panel', 'dashboard' ) && yith_pos_is_wc_feature_enabled( 'analytics' ) ) {
				wp_enqueue_style( 'wc-components' );
				wp_enqueue_style( defined( 'WC_ADMIN_APP' ) ? WC_ADMIN_APP : 'wc-admin-app' );
				wp_enqueue_style( 'yith-pos-admin-dashboard' );
				wp_enqueue_script( 'yith-pos-dashboard' );

				wp_localize_script( 'yith-pos-dashboard', 'yithPosSettings', yith_pos_settings()->get_admin_settings() );
			}

			if ( function_exists( 'yith_pos_stock_management' ) && 'product' === $screen_id ) {
				wp_enqueue_style( 'yith-pos-admin-products' );
				wp_enqueue_script( 'yith-pos-admin-products' );
			}
		}

		/**
		 * Enqueue frontend scripts
		 */
		public function enqueue_frontend_scripts() {
			if ( is_yith_pos() ) {
				yith_pos_enqueue_style( 'yith-pos-open-sans' );
				if ( yith_pos_can_view_register() ) {
					do_action( 'yith_pos_enqueue_scripts' );

					yith_pos_enqueue_style( 'yith-pos-font' );
					yith_pos_enqueue_style( 'yith-pos-frontend' );
					yith_pos_enqueue_script( 'yith-pos-frontend' );
				} else {
					yith_pos_enqueue_style( 'yith-plugin-fw-icon-font' );
					yith_pos_enqueue_style( 'yith-pos-login' );
					$pos_login = $this->get_login_style();
					wp_add_inline_style( 'yith-pos-login', $pos_login );
					yith_pos_enqueue_script( 'yith-pos-register-login' );
				}

				if ( function_exists( 'wp_set_script_translations' ) ) {
					wp_set_script_translations( 'yith-pos-frontend', 'yith-point-of-sale-for-woocommerce', YITH_POS_DIR . 'languages' );
				}

				if ( is_rtl() ) {
					yith_pos_enqueue_style( 'yith-pos-rtl' );
				}
			}
		}

		/**
		 * Get style for 'login' page.
		 *
		 * @return string
		 */
		public function get_login_style() {
			$primary_color    = get_option( 'yith_pos_registers_primary', '#09adaa' );
			$secondary_color  = get_option( 'yith_pos_registers_products_background', '#eaeaea' );
			$background_color = get_option( 'yith_pos_login_background_color', '#707070' );
			$background_image = get_option( 'yith_pos_login_background_image' );

			$css = "
		        body{ background-color:{$background_color}}
		        #login .input-login, .yith-pos-form select{ border-color: {$secondary_color} }
		        #login .input-login:focus,.yith-pos-form select:focus { border-color: {$primary_color} }
		        #login .input-login:focus + label.float-label, #login .input-login:valid + label.float-label { color: {$primary_color} }
		        .yith-pos-form select:focus + label.float-label, .yith-pos-form select:valid + label.float-label { color: {$primary_color} }
		        .login-submit{ background-color: {$secondary_color} }
		        #login input[type=checkbox]+span:before{ color: {$primary_color}; border-color: {$secondary_color}}
                #login .login-submit{background-color: {$primary_color}}
		        #yith-pos-store-register-form a{ color: {$primary_color}; }
		        #login .login-submit, .yith-pos-form .submit{background-color: {$primary_color}}";

			if ( $background_image ) {
				$css .= " body{ background: url({$background_image}) center center; background-size: cover; background-repeat: no-repeat;}";
			}

			return $css;
		}

		/**
		 * Create the json translation through the PHP file
		 * so it's possible using normal translations (with PO files) also for JS translations
		 *
		 * @param string|null $json_translations JSON translations.
		 * @param string      $file              The file name.
		 * @param string      $handle            The handle.
		 * @param string      $domain            The text domain.
		 *
		 * @return string|null
		 */
		public function script_translations( $json_translations, $file, $handle, $domain ) {
			if ( 'yith-point-of-sale-for-woocommerce' === $domain && in_array( $handle, array( 'yith-pos-dashboard', 'yith-pos-frontend' ), true ) ) {
				$path = YITH_POS_LANGUAGES_PATH . 'yith-point-of-sale-for-woocommerce.php';
				if ( file_exists( $path ) ) {
					$translations = include $path;

					$json_translations = wp_json_encode(
						array(
							'domain'      => 'yith-point-of-sale-for-woocommerce',
							'locale_data' => array(
								'messages' =>
									array(
										'' => array(
											'domain'       => 'yith-point-of-sale-for-woocommerce',
											'lang'         => get_locale(),
											'plural-forms' => 'nplurals=2; plural=(n != 1);',
										),
									)
									+
									$translations,
							),
						)
					);

				}
			}

			return $json_translations;
		}

	}
}
