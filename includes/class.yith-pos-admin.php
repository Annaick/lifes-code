<?php
/**
 * Admin Class.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Admin' ) ) {
	/**
	 * Class YITH_POS_Admin
	 *
	 */
	class YITH_POS_Admin {

		use YITH_POS_Singleton_Trait;

		/**
		 * Panel object.
		 *
		 * @var YIT_Plugin_Panel_WooCommerce
		 */
		private $panel;

		/**
		 * The panel page.
		 *
		 * @var string
		 */
		private $panel_page = 'yith_pos_panel';

		/**
		 * Store post type admin class.
		 *
		 * @var YITH_POS_Store_Post_Type_Admin
		 */
		public $store_post_type_admin;

		/**
		 * Magic getter to handle deprecations.
		 *
		 * @param string $key The key.
		 */
		public function __get( $key ) {
			switch ( $key ) {
				case 'receipt_post_type_admin':
					yith_pos_doing_it_wrong( $key, 'Post type admin classes should not accessed by YITH_POS_Admin class.', '2.0.0' );
					if ( class_exists( 'YITH_POS_Receipt_Post_Type_Admin' ) && is_callable( 'YITH_POS_Receipt_Post_Type_Admin::instance' ) ) {
						return YITH_POS_Receipt_Post_Type_Admin::instance();
					}
					break;
				case 'register_post_type_admin':
					yith_pos_doing_it_wrong( $key, 'Post type admin classes should not accessed by YITH_POS_Admin class.', '2.0.0' );
					if ( class_exists( 'YITH_POS_Register_Post_Type_Admin' ) && is_callable( 'YITH_POS_Register_Post_Type_Admin::instance' ) ) {
						return YITH_POS_Register_Post_Type_Admin::instance();
					}
					break;
				case 'store_post_type_admin':
					yith_pos_doing_it_wrong( $key, 'Post type admin classes should not accessed by YITH_POS_Admin class.', '2.0.0' );
					if ( class_exists( 'YITH_POS_Store_Post_Type_Admin' ) && is_callable( 'YITH_POS_Store_Post_Type_Admin::instance' ) ) {
						return YITH_POS_Store_Post_Type_Admin::instance();
					}
					break;
			}

			return null;
		}

		/**
		 * YITH_POS_Admin constructor.
		 */
		private function __construct() {

			YITH_POS_Register_Sessions_Admin::get_instance();

			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );
			add_action( 'add_meta_boxes', array( $this, 'add_pos_order_info_meta_box' ) );

			add_filter( 'plugin_action_links_' . plugin_basename( YITH_POS_DIR . '/' . basename( YITH_POS_FILE ) ), array( $this, 'action_links' ) );
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 3 );

			add_action( 'yith_pos_dashboard_tab', array( $this, 'dashboard_tab' ) );

			add_filter( 'yith_plugin_fw_get_field_template_path', array( $this, 'add_custom_field_path' ), 20, 2 );

			add_action( 'admin_bar_menu', array( $this, 'admin_bar_menus' ), 32 );

			add_filter( 'woocommerce_admin_billing_fields', array( $this, 'add_billing_vat' ) );
			add_filter( 'woocommerce_customer_meta_fields', array( $this, 'add_billing_vat_meta_field' ) );

			// Order filter: yith-pos or online orders.
			add_action( 'restrict_manage_posts', array( $this, 'add_order_filters' ), 10, 1 );
			add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'add_order_filters' ), 10, 1 );
			add_action( 'pre_get_posts', array( $this, 'filter_orders' ), 10, 1 );
			add_action( 'woocommerce_shop_order_list_table_prepare_items_query_args', array( $this, 'filter_orders_hpos' ), 10, 1 );

			add_filter( 'woocommerce_payment_gateways_setting_columns', array( $this, 'gateway_enabled_pos_column' ), 10, 1 );
			add_action( 'woocommerce_payment_gateways_setting_column_status_pos', array( $this, 'gateway_pos_column_content' ), 10, 1 );

			add_action( 'pre_get_posts', array( $this, 'filter_post_types_for_managers' ) );
			add_filter( 'wp_count_posts', array( $this, 'count_post_types_for_managers' ), 10, 2 );

			// Show payment method in orders list (admin view) for POS orders with no billing address set.
			add_filter( 'woocommerce_order_get_formatted_billing_address', array( $this, 'show_payment_method_on_orders_list' ) );

		}

		/**
		 * Add the column Enabled on YITH POS on Gateway WooCommerce Settings.
		 *
		 * @param array $default_columns Default Columns.
		 *
		 * @return array
		 */
		public function gateway_enabled_pos_column( $default_columns ) {
			$i = array_search( 'status', array_keys( $default_columns ), true );
			if ( $i++ ) {
				$default_columns = array_slice( $default_columns, 0, $i, true ) + array( 'status_pos' => __( 'Enabled on YITH POS', 'yith-point-of-sale-for-woocommerce' ) ) + array_slice( $default_columns, $i, count( $default_columns ) - $i, true );
			} else {
				$default_columns['status_pos'] = __( 'Enabled on YITH POS', 'yith-point-of-sale-for-woocommerce' );
			}

			return $default_columns;
		}

		/**
		 * Add on-off field on gateways table.
		 *
		 * @param WC_Payment_Gateway $gateway The gateway.
		 */
		public function gateway_pos_column_content( $gateway ) {

			$pos_gateways      = yith_pos_get_enabled_gateways_option();
			$required_gateways = (array) yith_pos_get_required_gateways();

			$method_title = implode( ' - ', array_filter( array_unique( array( $gateway->get_method_title(), $gateway->get_title() ) ) ) );
			$is_required  = in_array( $gateway->id, $required_gateways, true );

			echo '<td class="status_pos" width="5%">';
			if ( ! $is_required ) {
				echo '<a class="yith_pos_gateway_toggle_enable" href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( $gateway->id ) ) ) . '">';
			}

			if ( in_array( $gateway->id, $pos_gateways, true ) ) {
				// translators: %s is the payment gateway name.
				echo '<span class="woocommerce-input-toggle woocommerce-input-toggle--enabled_on_yith_pos" aria-label="' . esc_attr( sprintf( __( 'The "%s" payment method is currently enabled on YITH POS', 'yith-point-of-sale-for-woocommerce' ), $method_title ) ) . '">' . esc_attr__( 'Yes', 'yith-point-of-sale-for-woocommerce' ) . '</span>';
			} else {
				// translators: %s is the payment gateway name.
				echo '<span class="woocommerce-input-toggle woocommerce-input-toggle--disabled" aria-label="' . esc_attr( sprintf( __( 'The "%s" payment method is currently disabled on YITH POS', 'yith-point-of-sale-for-woocommerce' ), $method_title ) ) . '">' . esc_attr__( 'No', 'yith-point-of-sale-for-woocommerce' ) . '</span>';
			}
			if ( ! $is_required ) {
				echo '</a>';
			}

			echo '</td>';
		}

		/**
		 * Get the Panel tabs
		 *
		 * @return array
		 */
		public function get_panel_tabs() {
			$tabs_with_caps = array(
				'dashboard'     => array(
					'title'       => __( 'Dashboard', 'yith-point-of-sale-for-woocommerce' ),
					'icon'        => 'dashboard',
					'description' => 'An overview of orders and sales from POS',
					'capability'  => 'yith_pos_manage_pos_options',
				),
				'stores'        => array(
					'title'       => __( 'Stores', 'yith-point-of-sale-for-woocommerce' ),
					'description' => __( 'Create, edit, and manage all your stores', 'yith-point-of-sale-for-woocommerce' ),
					'icon'        => '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"></path></svg>',
					'capability'  => yith_pos_get_post_capability( 'edit_posts', YITH_POS_Post_Types::STORE ),
				),
				'registers'     => array(
					'title'       => __( 'Registers', 'yith-point-of-sale-for-woocommerce' ),
					'description' => __( 'Manage your stores\' registers', 'yith-point-of-sale-for-woocommerce' ),
					'icon'        => '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z"></path></svg>',
					'capability'  => yith_pos_get_post_capability( 'edit_posts', YITH_POS_Post_Types::REGISTER ),
				),
				'receipts'      => array(
					'title'       => __( 'Receipts', 'yith-point-of-sale-for-woocommerce' ),
					'description' => __( 'Create and customize your registers\' receipts', 'yith-point-of-sale-for-woocommerce' ),
					'icon'        => '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"></path></svg>',
					'capability'  => yith_pos_get_post_capability( 'edit_posts', YITH_POS_Post_Types::RECEIPT ),
				),
				'settings'      => array(
					'title'       => __( 'General options', 'yith-point-of-sale-for-woocommerce' ),
					'icon'        => 'settings',
					'description' => __( 'Set the general options for the plugin behavior', 'yith-point-of-sale-for-woocommerce' ),
					'capability'  => 'yith_pos_manage_pos_options',
				),
				'customization' => array(
					'title'       => __( 'Customization', 'yith-point-of-sale-for-woocommerce' ),
					'icon'        => '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 11.25l1.5 1.5.75-.75V8.758l2.276-.61a3 3 0 10-3.675-3.675l-.61 2.277H12l-.75.75 1.5 1.5M15 11.25l-8.47 8.47c-.34.34-.8.53-1.28.53s-.94.19-1.28.53l-.97.97-.75-.75.97-.97c.34-.34.53-.8.53-1.28s.19-.94.53-1.28L12.75 9M15 11.25L12.75 9"></path></svg>',
					'description' => __( 'Customize the design of the rendered elements in your site', 'yith-point-of-sale-for-woocommerce' ),
					'capability'  => 'yith_pos_manage_pos_options',
				),
			);

			$tabs_with_caps = apply_filters( 'yith_pos_settings_admin_tabs_with_caps', $tabs_with_caps );
			$tabs           = array();

			foreach ( $tabs_with_caps as $key => $tab ) {
				$capability = $tab['capability'] ?? 'yith_pos_manage_pos';
				if ( current_user_can( $capability ) ) {
					if ( isset( $tab['capability'] ) ) {
						unset( $tab['capability'] );
					}
					$tabs[ $key ] = $tab;
				}
			}

			return apply_filters( 'yith_pos_settings_admin_tabs', $tabs );
		}

		/**
		 * Add a panel under YITH Plugins tab
		 *
		 * @use      YIT_Plugin_Panel_WooCommerce class
		 * @see      plugin-fw/lib/yit-plugin-panel-woocommerce.php
		 */
		public function register_panel() {

			if ( ! empty( $this->panel ) ) {
				return;
			}

			$admin_tabs = $this->get_panel_tabs();

			$args = array(
				'ui_version'       => 2,
				'create_menu_page' => true,
				'parent_slug'      => '',
				'page_title'       => 'YITH Point of Sale for WooCommerce',
				'menu_title'       => 'Point of Sale',
				'capability'       => 'yith_pos_manage_pos',
				'parent'           => '',
				'parent_page'      => 'yit_plugin_panel',
				'class'            => yith_set_wrapper_class(),
				'page'             => $this->panel_page,
				'admin-tabs'       => $admin_tabs,
				'options-path'     => YITH_POS_DIR . '/plugin-options',
				'plugin_version'   => YITH_POS_VERSION,
				'plugin_icon'      => YITH_POS_ASSETS_URL . '/images/plugins/point-of-sale.svg',
				'plugin_slug'      => YITH_POS_SLUG,
				'help_tab'         => array(
					'main_video' => array(
						'desc' => _x( 'Check this video to learn how to configure <b>POS stores:</b>', 'Help tab - Video title', 'yith-point-of-sale-for-woocommerce' ),
						'url'  => array(
							'en' => 'https://www.youtube.com/embed/bgfQY994DyA',
							'it' => 'https://www.youtube.com/embed/bgfQY994DyA', // TODO: Update with IT video
							'es' => 'https://www.youtube.com/embed/Smug92R9zNk',
						),
					),
					'playlists'  => array(
						'en' => 'https://www.youtube.com/watch?v=bgfQY994DyA&list=PLDriKG-6905kuIL83d4fpqnF7XLnXV7xa',
						'it' => 'https://www.youtube.com/watch?v=BA20jf6hBvg&list=PL9c19edGMs0_813AKcPSsfnrKsz0GWqFq',
						'es' => 'https://www.youtube.com/watch?v=Smug92R9zNk&list=PL9Ka3j92PYJM4bnGwZOzjbr-SFJLgfuNN',
					),
					'hc_url'     => 'https://support.yithemes.com/hc/en-us/categories/4402922216849-YITH-POINT-OF-SALE-FOR-WOOCOMMERCE-POS-',
				),
				'is_premium'       => true,
				'your_store_tools' => $this->get_store_tools_tab_options(),
				'welcome_modals'   => $this->get_welcome_modals_options(),
			);

			$this->panel = new YIT_Plugin_Panel_WooCommerce( $args );
		}

		/**
		 * Get recommended store tools
		 *
		 * @return array Recommended store tools
		 */
		protected function get_store_tools_tab_options() {
			return array(
				'items' => array(
					'wishlist'               => array(
						'name'           => 'YITH WooCommerce Wishlist',
						'icon_url'       => YITH_POS_ASSETS_URL . '/images/plugins/wishlist.svg',
						'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-wishlist/',
						'description'    => _x(
							'Allow your customers to create lists of products they want and share them with family and friends.',
							'[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Wishlist',
							'yith-woocommerce-product-bundles'
						),
						'is_active'      => defined( 'YITH_WCWL_PREMIUM' ),
						'is_recommended' => true,
					),
					'gift-cards'             => array(
						'name'           => 'YITH WooCommerce Gift Cards',
						'icon_url'       => YITH_POS_ASSETS_URL . '/images/plugins/gift-cards.svg',
						'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-gift-cards/',
						'description'    => _x(
							'Sell gift cards in your shop to increase your earnings and attract new customers.',
							'[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Gift Cards',
							'yith-woocommerce-product-bundles'
						),
						'is_active'      => defined( 'YITH_YWGC_PREMIUM' ),
						'is_recommended' => true,
					),
					'request-a-quote'        => array(
						'name'        => 'YITH WooCommerce Request a Quote',
						'icon_url'    => YITH_POS_ASSETS_URL . '/images/plugins/request-a-quote.svg',
						'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-request-a-quote/',
						'description' => _x(
							'Hide prices and/or the "Add to cart" button and let your customers request a custom quote for every product.',
							'[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Request a Quote',
							'yith-woocommerce-product-bundles'
						),
						'is_active'   => defined( 'YITH_YWRAQ_PREMIUM' ),
					),
					'points-rewards'         => array(
						'name'        => 'YITH WooCommerce Points and Rewards',
						'icon_url'    => YITH_POS_ASSETS_URL . '/images/plugins/points-rewards.svg',
						'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-points-and-rewards/',
						'description' => _x(
							'Loyalize your customers with an effective points-based loyalty program and instant rewards.',
							'[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Points and Rewards',
							'yith-woocommerce-product-bundles'
						),
						'is_active'   => defined( 'YITH_YWPAR_PREMIUM' ),
					),
					'product-addons'         => array(
						'name'        => 'YITH WooCommerce Product Add-Ons & Extra Options',
						'icon_url'    => YITH_POS_ASSETS_URL . '/images/plugins/product-add-ons.svg',
						'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-product-add-ons/',
						'description' => _x(
							'Add paid or free advanced options to your product pages using fields like radio buttons, checkboxes, drop-downs, custom text inputs, and more.',
							'[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Product Add-Ons',
							'yith-woocommerce-product-bundles'
						),
						'is_active'   => defined( 'YITH_WAPO_PREMIUM' ),
					),
					'dynamic-pricing'        => array(
						'name'        => 'YITH WooCommerce Dynamic Pricing and Discounts',
						'icon_url'    => YITH_POS_ASSETS_URL . '/images/plugins/dynamic-pricing-and-discounts.svg',
						'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-dynamic-pricing-and-discounts/',
						'description' => _x(
							'Increase conversions through dynamic discounts and price rules, and build powerful and targeted offers.',
							'[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Dynamic Pricing and Discounts',
							'yith-woocommerce-product-bundles'
						),
						'is_active'   => defined( 'YITH_YWDPD_PREMIUM' ),
					),
					'customize-my-account'   => array(
						'name'        => 'YITH WooCommerce Customize My Account Page',
						'icon_url'    => YITH_POS_ASSETS_URL . '/images/plugins/customize-myaccount-page.svg',
						'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-customize-my-account-page/',
						'description' => _x(
							'Customize the My Account page of your customers by creating custom sections with promotions and ad-hoc content based on your needs.',
							'[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Customize My Account',
							'yith-woocommerce-product-bundles'
						),
						'is_active'   => defined( 'YITH_WCMAP_PREMIUM' ),
					),
					'recover-abandoned-cart' => array(
						'name'        => 'YITH WooCommerce Recover Abandoned Cart',
						'icon_url'    => YITH_POS_ASSETS_URL . '/images/plugins/recover-abandoned-cart.svg',
						'url'         => '//yithemes.com/themes/plugins/yith-woocommerce-recover-abandoned-cart/',
						'description' => _x(
							'Contact users who have added products to the cart without completing the order and try to recover lost sales.',
							'[YOUR STORE TOOLS TAB] Description for plugin Recover Abandoned Cart',
							'yith-woocommerce-product-bundles'
						),
						'is_active'   => defined( 'YITH_YWRAC_PREMIUM' ),
					),
				),
			);
		}

		/**
		 * Get Welcome modals settings
		 *
		 * @return array Welcome modals settings
		 */
		protected function get_welcome_modals_options() {
			return array(
				'on_close' => function () {
					update_option( 'yith_pos_welcome_modal', 'no' );
				},
				'modals'   => array(
					'welcome' => array(
						'type'        => 'welcome',
						'description' => __( 'Use WooCommerce to set up a modern and versatile cash register', 'yith-point-of-sale-for-woocommerce' ),
						'show'        => get_option( 'yith_pos_welcome_modal', 'welcome' ) === 'welcome',
						'items'       => array(
							'documentation' => array(),
							'how-to-video'  => array(
								'url' => array(
									'en' => 'https://www.youtube.com/watch?v=PLLkF00Yi5c',
									'it' => 'https://www.youtube.com/watch?v=rqKNScId3EM',
									'es' => 'https://www.youtube.com/watch?v=jLt6nMBP2hI',
								),
							),
							'create-store'  => array(
								'title'       => __( 'Create the store, set up a cash register', 'yith-point-of-sale-for-woocommerce' ),
								'description' => __( '...and start the adventure!', 'yith-point-of-sale-for-woocommerce' ),
								'url'         => add_query_arg( array( 'post_type' => 'yith-pos-store' ), admin_url( 'edit.php' ) ),
							),
						),
					),
				),
			);
		}

		/**
		 * Filter post types for managers.
		 *
		 * @param WP_Query $query The WP Query.
		 */
		public function filter_post_types_for_managers( $query ) {
			if (
				isset( $query->query['post_type'] ) &&
				in_array( $query->query['post_type'], array( YITH_POS_Post_Types::STORE, YITH_POS_Post_Types::REGISTER ), true ) &&
				! current_user_can( 'yith_pos_manage_others_pos' )
			) {

				if ( YITH_POS_Post_Types::STORE === $query->query['post_type'] ) {
					$query->set( 'meta_query', yith_pos_get_manager_stores_meta_query() );
				} elseif ( YITH_POS_Post_Types::REGISTER === $query->query['post_type'] ) {
					$manager_stores = yith_pos_get_manager_stores();
					$manager_stores = ! ! $manager_stores ? $manager_stores : array( 0 );
					$query->set(
						'meta_query',
						array(
							array(
								'key'     => '_store_id',
								'value'   => $manager_stores,
								'compare' => 'IN',
							),
						)
					);
				}
			}
		}

		/**
		 * Count post types for managers.
		 *
		 * @param array  $counts    List of counts.
		 * @param string $post_type The post type.
		 *
		 * @return array
		 */
		public function count_post_types_for_managers( $counts, $post_type ) {
			if (
				in_array( $post_type, array( YITH_POS_Post_Types::STORE, YITH_POS_Post_Types::REGISTER ), true ) &&
				! current_user_can( 'yith_pos_manage_others_pos' )
			) {
				$stati = get_post_stati();

				foreach ( $stati as $status ) {
					if ( YITH_POS_Post_Types::STORE === $post_type ) {
						$meta_query = yith_pos_get_manager_stores_meta_query();
					} else {
						$manager_stores = yith_pos_get_manager_stores();
						$manager_stores = ! ! $manager_stores ? $manager_stores : array( 0 );
						$meta_query     = array(
							array(
								'key'     => '_store_id',
								'value'   => $manager_stores,
								'compare' => 'IN',
							),
						);
					}
					$args            = array(
						'post_type'      => $post_type,
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'post_status'    => $status,
						'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					);
					$posts           = get_posts( $args );
					$counts->$status = count( $posts );
				}
			}

			return $counts;
		}

		/**
		 * Render tab
		 */
		public function dashboard_tab() {
			echo "<div class='woocommerce-page'>";
			yith_pos_get_view( 'panel/dashboard.php' );
		}


		/**
		 * Add additional custom fields.
		 *
		 * @param string $field_template The field template.
		 * @param array  $field          The field.
		 *
		 * @return string
		 */
		public function add_custom_field_path( $field_template, $field ) {
			$custom_types = array(
				'show-categories',
				'show-products',
				'show-cashiers',
				'presets',
			);

			if ( in_array( $field['type'], $custom_types, true ) ) {
				$field_template = YITH_POS_VIEWS_PATH . '/fields/' . $field['type'] . '.php';
			}

			return $field_template;
		}

		/**
		 * Adds row meta.
		 *
		 * @param array    $row_meta_args Row meta arguments.
		 * @param string[] $plugin_meta   An array of the plugin's metadata,
		 *                                including the version, author,
		 *                                author URI, and plugin URI.
		 * @param string   $plugin_file   Path to the plugin file relative to the plugins directory.
		 *
		 * @return array
		 */
		public function plugin_row_meta( $row_meta_args, $plugin_meta, $plugin_file ) {
			if ( YITH_POS_INIT === $plugin_file ) {
				$row_meta_args['slug']       = YITH_POS_SLUG;
				$row_meta_args['is_premium'] = true;
			}

			return $row_meta_args;
		}

		/**
		 * Action Links
		 * add the action links to plugin admin page
		 *
		 * @param array $links The links.
		 *
		 * @return   array
		 * @use      plugin_action_links_{$plugin_file_name}
		 */
		public function action_links( $links ) {

			if ( function_exists( 'yith_add_action_links' ) ) {
				$links = yith_add_action_links( $links, $this->panel_page, true, YITH_POS_SLUG );
			}

			return $links;
		}

		/**
		 * Add the "Visit POST" link in admin bar main menu.
		 *
		 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
		 *
		 * @since 2.4.0
		 */
		public function admin_bar_menus( $wp_admin_bar ) {
			if ( ! is_admin() || ! is_admin_bar_showing() ) {
				return;
			}

			// Show only when the user is a member of this site, or they're a super admin.
			if ( ! is_user_member_of_blog() && ! is_super_admin() ) {
				return;
			}

			// Don't display when shop page is the same of the page on front.
			if ( intval( get_option( 'page_on_front' ) ) === yith_pos_get_pos_page_id() ) {
				return;
			}

			// Add an option to visit the store.
			$wp_admin_bar->add_node(
				array(
					'parent' => 'site-name',
					'id'     => 'view-pos',
					'title'  => __( 'Visit POS', 'yith-point-of-sale-for-woocommerce' ),
					'href'   => yith_pos_get_pos_page_url(),
				)
			);
		}

		/**
		 * Add VAT inside the customer billing information.
		 *
		 * @param array $billing_fields Billing fields.
		 *
		 * @return array
		 */
		public function add_billing_vat( $billing_fields ) {
			$billing_fields['vat'] = array(
				'label' => yith_pos_get_vat_field_label(),
				'show'  => true,
			);

			return $billing_fields;
		}

		/**
		 * Add VAT inside the customer billing information.
		 *
		 * @param array $fields Customer fields.
		 *
		 * @return array
		 */
		public function add_billing_vat_meta_field( $fields ) {
			$fields['billing']['fields']['billing_vat'] = array(
				'label'       => yith_pos_get_vat_field_label(),
				'description' => '',
			);

			return $fields;
		}


		/**
		 * Add filters to orders for YITH_POS orders or online orders.
		 *
		 * @param string $order_type The order type or the post type.
		 */
		public function add_order_filters( $order_type ) {
			if ( 'shop_order' === $order_type ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$selected_type = isset( $_REQUEST['yith_pos_order_type'] ) ? wc_clean( wp_unslash( $_REQUEST['yith_pos_order_type'] ) ) : '';

				yith_plugin_fw_get_field(
					array(
						'type'              => 'select',
						'name'              => 'yith_pos_order_type',
						'class'             => 'wc-enhanced-select',
						'options'           => array(
							''       => '',
							'pos'    => __( 'YITH POS', 'yith-point-of-sale-for-woocommerce' ),
							'online' => __( 'Online', 'yith-point-of-sale-for-woocommerce' ),
						),
						'value'             => $selected_type,
						'data'              => array(
							'placeholder' => __( 'Filter by YITH POS or online', 'yith-point-of-sale-for-woocommerce' ),
							'allow_clear' => 'true',
						),
						'custom_attributes' => array(
							'style'       => 'min-width:200px;',
							'aria-hidden' => 'true',
						),
					),
					true,
					false
				);
			}
		}

		/**
		 * Get meta query to filter orders based on order type (POS or online).
		 *
		 * @param string $type The order type.
		 *
		 * @return array
		 */
		protected function get_filter_orders_meta_query( $type ) {
			if ( 'pos' === $type ) {
				return array(
					array(
						'key'   => '_yith_pos_order',
						'value' => '1',
					),
				);
			} elseif ( 'online' === $type ) {
				return array(
					array(
						'key'     => '_yith_pos_order',
						'compare' => 'NOT EXISTS',
					),
				);
			}

			return array();
		}

		/**
		 * Filter the the YITH_POS orders from the other online orders.
		 *
		 * @param WP_Query $query The WP query.
		 */
		public function filter_orders( $query ) {
			if ( $query->is_main_query() && isset( $query->query['post_type'] ) && 'shop_order' === $query->query['post_type'] ) {
				$meta_query        = ! ! $query->get( 'meta_query' ) ? $query->get( 'meta_query' ) : array();
				$type              = wc_clean( wp_unslash( $_REQUEST['yith_pos_order_type'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$filter_meta_query = $this->get_filter_orders_meta_query( $type );

				if ( $filter_meta_query ) {
					$query->set( 'meta_query', array_merge( $meta_query, $filter_meta_query ) );
				}
			}
		}

		/**
		 * Filter the YITH_POS orders from the other online orders.
		 *
		 * @param array $args The query args.
		 */
		public function filter_orders_hpos( $args ) {
			$meta_query        = $args['meta_query'] ?? array();
			$type              = wc_clean( wp_unslash( $_REQUEST['yith_pos_order_type'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$filter_meta_query = $this->get_filter_orders_meta_query( $type );

			if ( $filter_meta_query ) {
				$args['meta_query'] = array_merge( $meta_query, $filter_meta_query );
			}

			return $args;
		}

		/**
		 * Add the related bookings metabox in orders
		 *
		 * @param string $post_type The post type.
		 *
		 * @since 2.11.0
		 */
		public function add_pos_order_info_meta_box( $post_type ) {
			$order_screen_ids = array_filter( array( 'shop_order', function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : '' ) );

			if ( in_array( $post_type, $order_screen_ids, true ) ) {
				add_meta_box(
					'yith-pos-order-info',
					__( 'POS Info', 'yith-point-of-sale-for-woocommerce' ),
					array( $this, 'print_pos_order_info_meta_box' ),
					$post_type,
					'side',
					'high'
				);
			}
		}

		/**
		 * Print the POS order info meta-box.
		 *
		 * @param WP_Post|WC_Order $post The post.
		 *
		 * @since 2.11.0
		 */
		public function print_pos_order_info_meta_box( $post ) {
			$order = $post instanceof WC_Order ? $post : wc_get_order( $post->ID );
			if ( $order ) {
				$store_id    = $order->get_meta( '_yith_pos_store' );
				$register_id = $order->get_meta( '_yith_pos_register' );
				$cashier_id  = $order->get_meta( '_yith_pos_cashier' );

				$store      = yith_pos_get_store( $store_id );
				$store_name = $store instanceof YITH_POS_Store ? $store->get_name() : '';

				$register      = yith_pos_get_register( $register_id );
				$register_name = $register instanceof YITH_POS_Register ? $register->get_name() : '';

				$cashier = get_user_by( 'id', $cashier_id );

				$args = array(
					'register_name'   => $register_name,
					'store_name'      => $store_name,
					'cashier'         => $cashier ? $cashier->first_name . ' ' . $cashier->last_name : '',
					'payment_methods' => yith_pos_get_order_payment_methods( $order ),
					'currency'        => $order->get_currency(),
				);

				yith_pos_get_view( 'metabox/shop-order-pos-info-metabox.php', $args );
			}
		}

		/**
		 * Show payment method in case billing address is not set for the order
		 *
		 * @param string $address Formatted billing address.
		 *
		 * @return string
		 */
		public function show_payment_method_on_orders_list( $address ) {
			if ( empty( $address ) ) {
				$address = "\n";
			}

			return $address;
		}
	}
}

if ( ! function_exists( 'yith_pos_admin' ) ) {
	/**
	 * Unique access to instance of YITH_POS_Admin class
	 *
	 * @return YITH_POS_Admin
	 */
	function yith_pos_admin() {
		return YITH_POS_Admin::get_instance();
	}
}
