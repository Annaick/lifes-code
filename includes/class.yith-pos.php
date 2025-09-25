<?php
/**
 * Main Class.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS' ) ) {
	/**
	 * Class YITH_POS
	 * Main Class
	 *
	 */
	class YITH_POS {

		use YITH_POS_Singleton_Trait;

		/**
		 * Admin instance.
		 *
		 * @var YITH_POS_Admin
		 */
		public $admin;

		/**
		 * Frontend instance.
		 *
		 * @var YITH_POS_Frontend
		 */
		public $frontend;

		/**
		 * Orders class instance.
		 *
		 * @var YITH_POS_Orders
		 */
		public $orders;

		/**
		 * Assets class instance.
		 *
		 * @var YITH_POS_Assets
		 */
		public $assets;

		/**
		 * The POS page template.
		 *
		 * @var string
		 */
		public static $page_template = 'yith-pos-page.php';

		/**
		 * Page templates.
		 *
		 * @var array
		 */
		public $post_page_templates = array();

		/**
		 * YITH_POS constructor.
		 */
		private function __construct() {
			$this->load();

			YITH_POS_Install::get_instance();

			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_pos_gateways' ) );

			// Allow cashiers and managers to list products through REST API.
			add_filter( 'woocommerce_rest_check_permissions', array( $this, 'filter_rest_permissions' ), 10, 4 );
			add_filter( 'woocommerce_rest_check_permissions', array( $this, 'allow_creating_coupons_for_discounts' ), 10, 4 );

			$this->post_page_templates = array(
				self::$page_template => __( 'YITH POS template Page', 'yith-point-of-sale-for-woocommerce' ),
			);
			add_action( 'init', array( $this, 'add_pos_page' ) );
			add_filter( 'template_include', array( $this, 'view_pos_template' ) );

			add_action( 'before_woocommerce_init', array( $this, 'declare_wc_features_support' ) );

			add_action( 'wp_loaded', array( $this, 'register_plugin_for_activation' ), 99 );
			add_action( 'wp_loaded', array( $this, 'register_plugin_for_updates' ), 99 );
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		public function load() {
			require_once YITH_POS_INCLUDES_PATH . 'abstract.yith-pos-db.php';
			require_once YITH_POS_INCLUDES_PATH . 'class.yith-pos-install.php';

			require_once YITH_POS_INCLUDES_PATH . 'class.yith-pos-orders.php';
			require_once YITH_POS_INCLUDES_PATH . 'class.yith-pos-ajax.php';
			require_once YITH_POS_INCLUDES_PATH . 'class.yith-pos-stock-management.php';
			require_once YITH_POS_INCLUDES_PATH . 'class.yith-pos-settings.php';
			require_once YITH_POS_INCLUDES_PATH . 'class.yith-pos-order-stats-query.php';
			require_once YITH_POS_INCLUDES_PATH . 'class-yith-pos-register-sessions.php';

			require_once YITH_POS_INCLUDES_PATH . 'integrations/class.yith-pos-integrations.php';

			// Gateways classes.
			require_once YITH_POS_INCLUDES_PATH . 'gateways/class.yith-pos-payment-gateway-cache.php';
			require_once YITH_POS_INCLUDES_PATH . 'gateways/class.yith-pos-payment-gateway-chip-pin.php';

			// Objects.
			require_once YITH_POS_INCLUDES_PATH . 'objects/abstract.yith-pos-data.php';
			require_once YITH_POS_INCLUDES_PATH . 'objects/abstract.yith-post-cpt-object.php';
			require_once YITH_POS_INCLUDES_PATH . 'objects/class.yith-pos-store.php';
			require_once YITH_POS_INCLUDES_PATH . 'objects/class.yith-pos-register.php';
			require_once YITH_POS_INCLUDES_PATH . 'objects/class.yith-pos-receipt.php';
			require_once YITH_POS_INCLUDES_PATH . 'objects/class-yith-pos-register-session.php';

			require_once YITH_POS_INCLUDES_PATH . 'objects/data-stores/class-yith-pos-object-data-store-interface.php';
			require_once YITH_POS_INCLUDES_PATH . 'objects/data-stores/class-yith-pos-register-session-data-store.php';

			// REST.
			require_once YITH_POS_INCLUDES_PATH . 'rest-api/Loader.php';

			// Deprecated 2.0.0.
			require_once YITH_POS_INCLUDES_PATH . 'class.yith-pos-products.php';

			// Let's start loading...
			\YITH\POS\RestApi\Loader::get_instance();

			YITH_POS_Post_Types::init();
			YITH_POS_Ajax::get_instance();
			YITH_POS_Register_Sessions::get_instance();
			YITH_POS_Stock_Management::get_instance();

			YITH_POS_Integrations::get_instance();

			$this->orders = YITH_POS_Orders::get_instance();

			if ( self::is_request( 'admin' ) || self::is_request( 'frontend' ) ) {
				require_once YITH_POS_INCLUDES_PATH . 'class.yith-pos-assets.php';
				$this->assets = YITH_POS_Assets::get_instance();
			}

			if ( self::is_request( 'admin' ) ) {
				require_once YITH_POS_INCLUDES_PATH . 'admin/class-yith-pos-register-sessions-admin.php';

				require_once YITH_POS_INCLUDES_PATH . 'class.yith-pos-admin.php';
				$this->admin = YITH_POS_Admin();
			}

			require_once YITH_POS_INCLUDES_PATH . 'class.yith-pos-frontend.php';
			if ( self::is_request( 'frontend' ) ) {
				$this->frontend = yith_pos_frontend();
			}

		}

		/**
		 * What type of request is this?
		 *
		 * @param string $type admin, ajax, cron or frontend.
		 *
		 * @return bool
		 */
		public static function is_request( $type ) {
			switch ( $type ) {
				case 'admin':
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					return is_admin() && ! defined( 'DOING_AJAX' ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX && ( ! isset( $_REQUEST['context'] ) || ( isset( $_REQUEST['context'] ) && 'frontend' !== $_REQUEST['context'] ) ) );
				case 'ajax':
					return defined( 'DOING_AJAX' );
				case 'frontend':
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}

			return false;
		}

		/**
		 * Add the POS Gateway inside the WC list.
		 *
		 * @param array $gateways WooCommerce gateways.
		 *
		 * @return array
		 */
		public function add_pos_gateways( $gateways ) {
			$gateways[] = 'YITH_POS_Payment_Gateway_Cash';
			$gateways[] = 'YITH_POS_Payment_Gateway_Chip_Pin';

			return $gateways;
		}


		/**
		 * Allow Cashiers and managers to list products through REST API
		 *
		 * @param bool   $permission Has permission flag.
		 * @param string $context    The context.
		 * @param int    $object_id  The object ID.
		 * @param string $object     The object type.
		 *
		 * @return bool
		 */
		public function filter_rest_permissions( $permission, $context, $object_id, $object ) {
			$permissions_map = array(
				'product'           => array(
					'yith_pos_view_products'   => array( 'read' ),
					'yith_pos_create_products' => array( 'create' ),
				),
				'product_variation' => array(
					'yith_pos_view_products'   => array( 'read' ),
					'yith_pos_create_products' => array( 'create' ),
				),
				'product_cat'       => array(
					'yith_pos_view_product_cats' => array( 'read' ),
				),
				'shop_order'        => array(
					'yith_pos_create_orders' => array( 'create' ),
					'yith_pos_view_orders'   => array( 'read' ),
				),
				'shop_coupon'       => array(
					'yith_pos_view_coupons' => array( 'read' ),
				),
				'reports'           => array(
					'yith_pos_view_reports' => array( 'read' ),
				),
				'user'              => array(
					'yith_pos_view_users'   => array( 'read' ),
					'yith_pos_edit_users'   => array( 'edit' ),
					'yith_pos_create_users' => array( 'create', 'edit' ),
				),
				'settings'          => array(
					'yith_pos_use_pos' => array( 'read' ),
				),
				'shipping_methods'  => array(
					'yith_pos_use_pos' => array( 'read' ),
				),
			);

			if ( ! $permission ) {
				$caps = array_key_exists( $object, $permissions_map ) ? $permissions_map[ $object ] : array();
				foreach ( $caps as $_cap => $_contexts ) {
					if ( current_user_can( $_cap ) && in_array( $context, (array) $_contexts, true ) ) {
						$permission = true;
						break;
					}
				}
			}

			return $permission;
		}

		/**
		 * Allow creating coupons for discounts also for cashiers.
		 *
		 * @param bool   $has_permission Has permission flag.
		 * @param string $context        The context.
		 * @param int    $object_id      The object ID.
		 * @param string $object_type    The object type.
		 *
		 * @return bool
		 * @since 2.0.1
		 */
		public function allow_creating_coupons_for_discounts( $has_permission, $context, $object_id, $object_type ) {
			if ( ! $has_permission && 'shop_coupon' === $object_type && 'create' === $context ) {
				$pos_request = sanitize_title( wp_unslash( $_REQUEST['yith_pos_request'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				if ( 'generate-discount-coupons' === $pos_request && current_user_can( 'yith_pos_use_pos' ) ) {
					$has_permission = true;
				}
			}

			return $has_permission;
		}

		/**
		 * Add the page Pos
		 */
		public function add_pos_page() {
			$option_name  = 'settings_pos_page';
			$option_value = get_option( $option_name );

			if ( $option_value && get_post( $option_value ) ) {
				// The page already exists.
				update_post_meta( $option_value, '_wp_page_template', self::$page_template );

				return;
			}

			global $wpdb;
			$slug = esc_sql( _x( 'pos', 'slug of the page', 'yith-point-of-sale-for-woocommerce' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s LIMIT 1;", $slug ) );

			if ( $page_found ) {
				! $option_value && update_option( $option_name, $page_found );
			} else {
				$page_data = array(
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'post_author'    => 1,
					'post_name'      => $slug,
					'post_title'     => __( 'YITH POS', 'yith-point-of-sale-for-woocommerce' ),
					'post_content'   => '',
					'post_parent'    => 0,
					'comment_status' => 'closed',
				);

				$page_id = wp_insert_post( $page_data );
				add_post_meta( $page_id, '_wp_page_template', self::$page_template );
				update_option( $option_name, $page_id );
			}
		}

		/**
		 * Checks if the template is assigned to the page
		 *
		 * @param string $template The template.
		 *
		 * @return string
		 */
		public function view_pos_template( $template ) {
			global $post;

			if ( ! $post ) {
				return $template;
			}

			// Return default template if we don't have a custom one defined.
			$post_page_template = get_post_meta( $post->ID, '_wp_page_template', true );
			if ( ! isset( $this->post_page_templates[ $post_page_template ] ) ) {
				return $template;
			}

			$file = get_stylesheet_directory() . '/' . get_post_meta( $post->ID, '_wp_page_template', true );
			if ( file_exists( $file ) ) {
				return $file;
			}

			$file = get_template_directory() . '/' . get_post_meta( $post->ID, '_wp_page_template', true );
			if ( file_exists( $file ) ) {
				return $file;
			}

			$file = YITH_POS_TEMPLATE_PATH . get_post_meta( $post->ID, '_wp_page_template', true );

			return file_exists( $file ) ? $file : $template;
		}

		/**
		 * Declare support for WooCommerce features.
		 *
		 * @since 2.11.0
		 */
		public function declare_wc_features_support() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', YITH_POS_INIT, true );
			}
		}

		/**
		 * Register plugins for activation tab
		 */
		public function register_plugin_for_activation() {
			if ( ! function_exists( 'YIT_Plugin_Licence' ) ) {
				require_once '../plugin-fw/lib/yit-plugin-licence.php';
			}
			YIT_Plugin_Licence()->register( YITH_POS_INIT, YITH_POS_SECRET_KEY, YITH_POS_SLUG );
		}

		/**
		 * Register plugins for update tab
		 */
		public function register_plugin_for_updates() {
			if ( ! function_exists( 'YIT_Upgrade' ) ) {
				require_once '../plugin-fw/lib/yit-upgrade.php';
			}
			YIT_Upgrade()->register( YITH_POS_SLUG, YITH_POS_INIT );
		}
	}
}

if ( ! function_exists( 'yith_pos' ) ) {
	/**
	 * Unique access to instance of YITH_POS class
	 *
	 * @return YITH_POS
	 */
	function yith_pos() {
		return YITH_POS::get_instance();
	}
}
