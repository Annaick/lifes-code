<?php
/**
 * Settings Class.
 * Handle settings.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Settings' ) ) {
	/**
	 * Class YITH_POS_Settings
	 *
	 */
	class YITH_POS_Settings {
		use YITH_POS_Singleton_Trait;

		/**
		 * Settings.
		 *
		 * @var array
		 */
		private $settings = array();

		/**
		 * YITH_POS_Settings constructor.
		 */
		private function __construct() {
		}

		/** -------------------------------------------------------
		 * Public Getters
		 */

		/**
		 * Get the common settings
		 *
		 * @return array
		 */
		public function get_common_settings() {
			return $this->get_settings( 'common' );
		}

		/**
		 * Get the admin settings
		 *
		 * @return array
		 */
		public function get_admin_settings() {
			return $this->get_settings( 'admin' );
		}

		/**
		 * Get the frontend settings
		 *
		 * @return array
		 */
		public function get_frontend_settings() {
			return $this->get_settings( 'frontend' );
		}

		/** -------------------------------------------------------
		 * Private Getters
		 */

		/**
		 * Get settings
		 *
		 * @param string $type Type of settings; possible values are 'common', 'admin', 'frontend'.
		 *
		 * @return mixed
		 */
		private function get_settings( $type = 'frontend' ) {
			$type = in_array( $type, array( 'common', 'admin', 'frontend' ), true ) ? $type : 'common';
			if ( ! isset( $this->settings[ $type ] ) ) {
				$getter                  = "get_raw_{$type}_settings";
				$this->settings[ $type ] = apply_filters( 'yith_pos_components_settings', $this->$getter(), $type );
				$this->settings[ $type ] = apply_filters( "yith_pos_components_{$type}_settings", $this->settings[ $type ] );
			}

			return $this->settings[ $type ];
		}

		/**
		 * Get raw common settings.
		 *
		 * @return array
		 */
		private function get_raw_common_settings() {
			$pos_url  = yith_pos_get_pos_page_url();
			$base_url = preg_replace( '/http[s]?:\/\/[^\/]+/', '', $pos_url );
			if ( is_null( $base_url ) ) {
				$base_url = str_replace( network_home_url(), '', $pos_url );
				$base_url = '/' . ltrim( $base_url, '/' );
			}
			$base_url = untrailingslashit( $base_url );

			$settings = array(
				'wc'             => self::get_wc_data(),
				'dateFormat'     => wc_date_format(),
				'timeFormat'     => wc_time_format(),
				'gmtOffset'      => intval( get_option( 'gmt_offset' ) ),
				'timezone'       => get_option( 'timezone_string' ),
				'posUrl'         => $pos_url,
				'baseUrl'        => $base_url,
				'siteLocale'     => esc_attr( get_bloginfo( 'language' ) ),
				'language'       => defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : get_bloginfo( 'language' ),
				'siteTitle'      => get_bloginfo( 'name' ),
				'adminUrl'       => admin_url(),
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'assetsUrl'      => YITH_POS_ASSETS_URL,
				'paymentMethods' => array(
					'enabledIds' => yith_pos_get_enabled_gateways_option(),
				),
			);

			return $settings;
		}

		/**
		 * Get raw admin settings.
		 *
		 * @return array
		 */
		private function get_raw_admin_settings() {
			$common_settings = $this->get_raw_common_settings();

			$settings = array(
				'stores' => array_map(
					function ( $id ) {
						return array(
							'id'   => $id,
							'name' => yith_pos_get_store_name( $id ),
						);
					},
					yith_pos_get_stores()
				),
			);

			$settings = array_merge( $common_settings, $settings );

			return $settings;
		}

		/**
		 * Get raw frontend settings.
		 *
		 * @return array
		 */
		private function get_raw_frontend_settings() {
			$common_settings = $this->get_raw_common_settings();
			$settings        = $common_settings;

			if ( is_yith_pos() ) {
				$register_id = yith_pos_register_logged_in();
				if ( $register_id ) {
					$register                                = yith_pos_get_register( $register_id );
					$register_data                           = $register->get_current_data();
					$register_data['query_options']          = $register->get_inclusion_query_options();
					$register_data['category_query_options'] = $register->get_category_query_options();

					$session                  = $register->get_current_session();
					$register_data['session'] = $session ? $session->get_data() : false;

					if ( $register_data['session'] ) {
						foreach ( $register_data['session']['cashiers'] as $cashier_key => $cashier ) {
							$register_data['session']['cashiers'][ $cashier_key ]['name'] = yith_pos_get_employee_name( $cashier['id'], array( 'hide_nickname' => true ) );
						}

						$register_data['session']['nonce'] = wp_create_nonce( 'yith-pos-register-session-update-' . $session->get_id() );

						$register_data['session']['downloadReportsUrl']        = $session->get_download_reports_url();
						$register_data['session']['downloadCashierReportsUrl'] = $session->get_download_cashier_reports_url();
					}

					$store                           = $register->get_store();
					$store_data                      = $store->get_current_data();
					$store_data['formatted_address'] = $store->get_formatted_address();

					$receipt                       = $register->get_receipt();
					$receipt_data                  = ! ! $receipt ? $receipt->get_current_data() : false;
					$receipt_translatable_elements = array(
						'sku_label',
						'vat_label',
						'order_date_label',
						'order_number_label',
						'order_customer_label',
						'shipping_label',
						'order_register_label',
						'cashier_label',
					);

					if ( $receipt_data ) {
						foreach ( $receipt_translatable_elements as $translatable_element ) {
							if ( isset( $receipt_data[ $translatable_element ] ) ) {
								$receipt_data[ $translatable_element ] = yith_pos_translate( $receipt_data[ $translatable_element ] );
							}
						}
					}

					$pos_url = yith_pos_get_pos_page_url();

					// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
					// Disable code sniffer rule, since it doesn't work correctly (double arrows are correctly aligned).

					$settings = array(
						'register'                            => $register_data,
						'store'                               => $store_data,
						'receipt'                             => $receipt_data,
						'user'                                => self::get_user_data(),
						'tax'                                 => self::get_tax_data( $store ),
						'color_scheme'                        => self::get_color_scheme(),
						'loggerEnabled'                       => isset( $_GET['logger-enabled'] ),
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						'addressFormat'                       => yith_pos_get_format_address( $store->get_country() ),
						'adminUrl'                            => admin_url(),
						'logoutUrl'                           => add_query_arg( array( 'yith-pos-user-logout' => true ), $pos_url ),
						'registerLogoutUrl'                   => add_query_arg( array( 'yith-pos-register-logout' => true ), $pos_url ),
						'closeRegisterUrl'                    => add_query_arg(
							array(
								'yith-pos-register-close-nonce' => wp_create_nonce( 'yith-pos-register-close-' . $register_id ),
								'register'                      => $register_id,
							),
							$pos_url
						),
						'logoUrl'                             => get_option( 'yith_pos_login_logo', '' ),
						'numericControllerDiscountPresets'    => get_option( 'yith_pos_numeric_controller_discount_presets', array(
							5,
							10,
							15,
							20,
						) ),
						'feeAndDiscountPresets'               => get_option( 'yith_pos_fee_and_discount_presets', array(
							5,
							10,
							15,
							20,
							50,
						) ),
						'audioEnabled'                        => get_option( 'yith_pos_audio_enabled', 'yes' ),
						'heartbeat'                           => array(
							'nonce'    => wp_create_nonce( 'yith-pos-heartbeat' ),
							'interval' => 30,
						),
						'notifyNoStockAmount'                 => get_option( 'woocommerce_notify_no_stock_amount', 0 ),
						'multistockEnabled'                   => get_option( 'yith_pos_multistock_enabled', 'no' ),
						'multistockCondition'                 => get_option( 'yith_pos_multistock_condition', 'allowed' ),
						'showStockOnPOS'                      => get_option( 'yith_pos_show_stock_on_pos', 'no' ),
						'closeModalsWhenClickingOnBackground' => get_option( 'yith_pos_close_modals_when_clicking_on_background', 'yes' ),
						'maxProductSearchResults'             => max( min( absint( get_option( 'yith_pos_max_product_search_results', 10 ) ), 100 ), 1 ),
						'vatFieldLabel'                       => yith_pos_get_vat_field_label(),
						'errorMessages'                       => yith_pos_get_error_message_capabilities(),
						'barcodeMeta'                         => yith_pos_get_barcode_meta(),
					);

					// phpcs:enable

					$settings = array_merge( $common_settings, $settings );
				}
			}

			return $settings;
		}

		/** -------------------------------------------------------
		 * Public Static Getters - to get specific settings
		 */

		/**
		 * Get WC data
		 *
		 * @return array
		 */
		public static function get_wc_data() {
			$currency_code    = get_woocommerce_currency();
			$payment_gateways = yith_pos_get_active_payment_methods();

			$payment_gateways = array_map(
				function ( $gateway ) {
					return array(
						'id'          => $gateway->id,
						'title'       => $gateway->get_title(),
						'description' => $gateway->get_description(),
					);
				},
				$payment_gateways
			);

			$wc_settings = array(
				'currency'                  => array(
					'code'               => $currency_code,
					'precision'          => wc_get_price_decimals(),
					'symbol'             => html_entity_decode( get_woocommerce_currency_symbol( $currency_code ) ),
					'position'           => get_option( 'woocommerce_currency_pos' ),
					'decimal_separator'  => wc_get_price_decimal_separator(),
					'thousand_separator' => wc_get_price_thousand_separator(),
					'price_format'       => html_entity_decode( get_woocommerce_price_format() ),
				),
				'placeholderImageSrc'       => wc_placeholder_img_src(),
				'stockStatuses'             => wc_get_product_stock_status_options(),
				'dataEndpoints'             => array(),
				'couponTypes'               => wc_get_coupon_types(),
				'calcDiscountsSequentially' => 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ),
				'paymentGateways'           => $payment_gateways,
				'paymentGatewaysIdTitle'    => wp_list_pluck( $payment_gateways, 'title', 'id' ),
				'orderStatuses'             => wc_get_order_statuses(),
				'autoGeneratePassword'      => get_option( 'woocommerce_registration_generate_password', 'yes' ),
				'countries'                 => yith_pos_get_countries(),
				'discountRoundingMode'      => defined( 'WC_DISCOUNT_ROUNDING_MODE' ) && PHP_ROUND_HALF_UP === WC_DISCOUNT_ROUNDING_MODE ? 'half-up' : 'half-down',
			);

			return apply_filters( 'yith_pos_wc_settings', $wc_settings );
		}

		/**
		 * Get user data
		 *
		 * @return array
		 */
		private static function get_user_data() {
			$user_id   = get_current_user_id();
			$user      = array( 'id' => $user_id );
			$user_data = get_userdata( $user_id );

			if ( ! ! $user_data ) {
				if ( $user_data->first_name || $user_data->last_name ) {
					// translators: 1. First name; 2. Last name.
					$user['fullName'] = esc_html( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), ucfirst( $user_data->first_name ), ucfirst( $user_data->last_name ) ) );
				} else {
					$user['fullName'] = esc_html( ucfirst( $user_data->display_name ) );
				}

				$user['firstName']   = $user_data->first_name;
				$user['lastName']    = $user_data->last_name;
				$user['displayName'] = $user_data->display_name;
			}
			$user['avatarURL'] = get_avatar_url( (int) $user_id, array( 'size' => 140 ) );
			$user['posCaps']   = yith_pos_get_current_user_pos_capabilities();

			return $user;
		}

		/**
		 * Get tax data
		 *
		 * @param YITH_POS_Store|false $store The store.
		 *
		 * @return array
		 */
		private static function get_tax_data( $store = false ) {
			$tax_classes_and_rates = array();
			$tax_classes           = array();
			$tax_classes_labels    = array();
			if ( wc_tax_enabled() && $store ) {
				$tax_classes        = WC_Tax::get_tax_class_slugs();
				$tax_classes_labels = WC_Tax::get_tax_classes();
				$tax_classes[]      = '';
				foreach ( $tax_classes as $tax_class ) {
					$tax_classes_and_rates[ $tax_class ] = WC_Tax::find_rates(
						array(
							'country'   => $store->get_country(),
							'state'     => $store->get_state(),
							'postcode'  => $store->get_postcode(),
							'city'      => $store->get_city(),
							'tax_class' => $tax_class,
						)
					);
				}
			}

			$show_including_tax = 'incl' === get_option( 'woocommerce_tax_display_cart' );

			$data = array(
				'enabled'                     => wc_tax_enabled(),
				'priceIncludesTax'            => wc_prices_include_tax(),
				'showPriceIncludingTaxInShop' => 'incl' === get_option( 'woocommerce_tax_display_shop' ),
				'showPriceIncludingTax'       => $show_including_tax,
				'classesAndRates'             => $tax_classes_and_rates,
				'classes'                     => $tax_classes,
				'classesLabels'               => $tax_classes_labels,
				'roundAtSubtotal'             => 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ),
				'shippingTaxClass'            => get_option( 'woocommerce_shipping_tax_class' ),
			);

			return $data;
		}

		/**
		 * Return the list of colors by options.
		 *
		 * @return array
		 */
		private static function get_color_scheme() {
			$color_list_key = array(
				'primary'                   => '#09adaa',
				'secondary'                 => '#c65338',
				'products_background'       => '#eaeaea',
				'save_cart_background'      => '#e09914',
				'pay_button_background'     => '#a0a700',
				'note_button_background'    => '#4d4d4d',
				'header_bar_background'     => '#435756',
				'products_title_background' => 'rgba(67, 67, 67, .75)',
			);

			$color_options = array();
			foreach ( $color_list_key as $color => $default_value ) {
				$color_code = get_option( 'yith_pos_registers_' . $color, $default_value );
				if ( yith_pos_validate_hex( $color_code ) ) {
					$color_options[ '--' . $color ] = $color_code;

					if ( in_array( $color, array( 'primary' ), true ) ) {
						$color_options[ '--dark_' . $color ]   = wc_hex_darker( $color_code, 15 );
						$color_options[ '--darker_' . $color ] = wc_hex_darker( $color_code, 30 );
					}
				} elseif ( strpos( $color_code, 'rgb' ) !== false ) {
					$color_options[ '--' . $color ] = $color_code;

					if ( strpos( $color_code, 'rgba' ) !== false ) {
						$rgba = sscanf( $color_code, 'rgba(%d, %d, %d, %f)' );
					} else {
						$rgba    = sscanf( $color_code, 'rgb(%d, %d, %d)' );
						$rgba[3] = 1;
					}

					$hsl = yith_pos_rgb2hsl( array( $rgba[0], $rgba[1], $rgba[2] ) );

					if ( in_array( $color, array( 'primary' ), true ) ) {
						$dark_rgb                              = yith_pos_hsl2rgb(
							array(
								$hsl[0],
								$hsl[1],
								$hsl[2] * .9,
							)
						);
						$color_options[ '--dark_' . $color ]   = sprintf( 'rgba(%d, %d, %d, %f)', $dark_rgb[0], $dark_rgb[1], $dark_rgb[2], $rgba[3] );
						$darker_rgb                            = yith_pos_hsl2rgb(
							array(
								$hsl[0],
								$hsl[1],
								$hsl[2] * .7,
							)
						);
						$color_options[ '--darker_' . $color ] = sprintf( 'rgba(%d, %d, %d, %f)', $darker_rgb[0], $darker_rgb[1], $darker_rgb[2], $rgba[3] );
					}
				}
			}

			return $color_options;
		}

	}
}

if ( ! function_exists( 'yith_pos_settings' ) ) {
	/**
	 * Unique access to instance of YITH_POS_Settings class
	 *
	 * @return YITH_POS_Settings
	 * @since 1.0.0
	 */
	function yith_pos_settings() {
		return YITH_POS_Settings::get_instance();
	}
}
