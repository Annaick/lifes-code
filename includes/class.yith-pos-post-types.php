<?php
/**
 * Post Types Class.
 * Handle custom post types.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Post_Types' ) ) {
	/**
	 * Class YITH_POS_Post_Types
	 *
	 */
	class YITH_POS_Post_Types {

		const STORE    = 'yith-pos-store';
		const REGISTER = 'yith-pos-register';
		const RECEIPT  = 'yith-pos-receipt';

		/**
		 * Store Post Type
		 *
		 * @var string
		 * @deprecated 2.0.0
		 */
		public static $store = 'yith-pos-store';

		/**
		 * Register Post Type
		 *
		 * @var string
		 * @deprecated 2.0.0
		 */
		public static $register = 'yith-pos-register';

		/**
		 * Receipt Post Type
		 *
		 * @var string
		 * @deprecated 2.0.0
		 */
		public static $receipt = 'yith-pos-receipt';

		/**
		 * Roles and Caps Version
		 *
		 * @var string
		 * @static
		 */
		public static $roles_and_caps_version = '1.0.1';

		/**
		 * Initialization.
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );

			add_filter( 'disable_months_dropdown', array( __CLASS__, 'remove_date_dropdown_from_list_table' ), 10, 2 );

			// Update post title when updating the '_name' post_meta.
			add_filter( 'updated_postmeta', array( __CLASS__, 'update_post_title' ), 10, 4 );
			add_filter( 'added_post_meta', array( __CLASS__, 'update_post_title' ), 10, 4 );

			// Disable autocomplete in forms.
			add_filter( 'post_edit_form_tag', array( __CLASS__, 'disable_autocomplete' ), 10, 1 );

			// Notices for status update.
			add_filter( 'removable_query_args', array( __CLASS__, 'removable_query_args' ), 10, 2 );
			add_filter( 'redirect_post_location', array( __CLASS__, 'post_location' ), 10, 2 );
			add_action( 'yit_before_metaboxes_tab', array( __CLASS__, 'print_notices' ), 15 );
			add_action( 'admin_notices', array( __CLASS__, 'print_notices' ), 10 );

			// Add fields in Rest API.
			add_action( 'rest_api_init', array( __CLASS__, 'add_registers_field_to_store' ) );

			// Regenerate roles and capabilities.
			add_action( 'init', array( __CLASS__, 'regenerate_roles_and_capabilities' ) );
			add_action( 'init', array( __CLASS__, 'maybe_regenerate_roles_and_caps' ) );

			add_action( 'plugins_loaded', array( __CLASS__, 'include_admin_handlers' ), 20 );
		}

		/**
		 * Include Admin Post Type handlers.
		 */
		public static function include_admin_handlers() {
			require_once trailingslashit( YITH_POS_INCLUDES_PATH ) . 'admin/post-types/class.yith-pos-receipt-post-type-admin.php';
			require_once trailingslashit( YITH_POS_INCLUDES_PATH ) . 'admin/post-types/class.yith-pos-register-post-type-admin.php';
			require_once trailingslashit( YITH_POS_INCLUDES_PATH ) . 'admin/post-types/class.yith-pos-store-post-type-admin.php';
		}

		/**
		 * Is a POS Post Type?
		 *
		 * @param string $post_type The post type.
		 *
		 * @return bool
		 */
		public static function is_pos_post_type( $post_type ) {
			return in_array( $post_type, array( self::REGISTER, self::STORE, self::RECEIPT ), true );
		}


		/**
		 * Register core post types.
		 */
		public static function register_post_types() {
			if ( post_type_exists( self::STORE ) ) {
				return;
			}

			do_action( 'yith_pos_before_register_post_type' );

			$labels_store = array(
				'name'               => __( 'Stores', 'yith-point-of-sale-for-woocommerce' ),
				'singular_name'      => __( 'Store', 'yith-point-of-sale-for-woocommerce' ),
				'add_new'            => __( 'Add New Store', 'yith-point-of-sale-for-woocommerce' ),
				'add_new_item'       => __( 'Add New Store', 'yith-point-of-sale-for-woocommerce' ),
				'edit'               => __( 'Edit', 'yith-point-of-sale-for-woocommerce' ),
				'edit_item'          => __( 'Edit Store', 'yith-point-of-sale-for-woocommerce' ),
				'new_item'           => __( 'New Store', 'yith-point-of-sale-for-woocommerce' ),
				'view'               => __( 'View Store', 'yith-point-of-sale-for-woocommerce' ),
				'view_item'          => __( 'View Store', 'yith-point-of-sale-for-woocommerce' ),
				'search_items'       => __( 'Search Store', 'yith-point-of-sale-for-woocommerce' ),
				'not_found'          => __( 'Not found', 'yith-point-of-sale-for-woocommerce' ),
				'not_found_in_trash' => __( 'Not found in trash', 'yith-point-of-sale-for-woocommerce' ),
			);

			$store_post_type_args = array(
				'label'               => __( 'Store', 'yith-point-of-sale-for-woocommerce' ),
				'labels'              => $labels_store,
				'description'         => __( 'This is where Stores are stored.', 'yith-point-of-sale-for-woocommerce' ),
				'public'              => true,
				'show_ui'             => true,
				'capability_type'     => self::STORE,
				'capabilities'        => array( 'create_posts' => 'create_' . self::STORE . 's' ),
				'map_meta_cap'        => true,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_in_menu'        => false,
				'hierarchical'        => false,
				'show_in_nav_menus'   => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => false,
				'has_archive'         => false,
				'menu_icon'           => 'dashicons-store',
				'show_in_rest'        => true,
			);

			register_post_type( self::STORE, apply_filters( 'yith_pos_register_post_type_store', $store_post_type_args ) );

			// Registers.

			$labels_register = array(
				'name'               => __( 'Registers', 'yith-point-of-sale-for-woocommerce' ),
				'singular_name'      => __( 'Register', 'yith-point-of-sale-for-woocommerce' ),
				'add_new'            => __( 'Add New Register', 'yith-point-of-sale-for-woocommerce' ),
				'add_new_item'       => __( 'Add New Register', 'yith-point-of-sale-for-woocommerce' ),
				'edit'               => __( 'Edit', 'yith-point-of-sale-for-woocommerce' ),
				'edit_item'          => __( 'Edit Register', 'yith-point-of-sale-for-woocommerce' ),
				'new_item'           => __( 'New Register', 'yith-point-of-sale-for-woocommerce' ),
				'view'               => __( 'View Register', 'yith-point-of-sale-for-woocommerce' ),
				'view_item'          => __( 'View Register', 'yith-point-of-sale-for-woocommerce' ),
				'search_items'       => __( 'Search Registers', 'yith-point-of-sale-for-woocommerce' ),
				'not_found'          => __( 'Not found', 'yith-point-of-sale-for-woocommerce' ),
				'not_found_in_trash' => __( 'Not found in trash', 'yith-point-of-sale-for-woocommerce' ),
			);

			$register_post_type_args = array(
				'label'               => __( 'Registers', 'yith-point-of-sale-for-woocommerce' ),
				'labels'              => $labels_register,
				'description'         => __( 'This is where Registers are stored.', 'yith-point-of-sale-for-woocommerce' ),
				'public'              => true,
				'show_ui'             => true,
				'capability_type'     => self::REGISTER,
				'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'        => true,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_in_menu'        => false,
				'hierarchical'        => false,
				'show_in_nav_menus'   => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => false,
				'has_archive'         => false,
				'menu_icon'           => 'dashicons-cart',
				'show_in_rest'        => true,
			);

			register_post_type( self::REGISTER, apply_filters( 'yith_pos_register_post_type_register', $register_post_type_args ) );

			// Receipts.

			$labels_receipt = array(
				'name'               => __( 'Receipts', 'yith-point-of-sale-for-woocommerce' ),
				'singular_name'      => __( 'Receipt', 'yith-point-of-sale-for-woocommerce' ),
				'add_new'            => __( 'Add New Receipt', 'yith-point-of-sale-for-woocommerce' ),
				'add_new_item'       => __( 'Add New Receipt', 'yith-point-of-sale-for-woocommerce' ),
				'edit'               => __( 'Edit', 'yith-point-of-sale-for-woocommerce' ),
				'edit_item'          => __( 'Edit Receipt', 'yith-point-of-sale-for-woocommerce' ),
				'new_item'           => __( 'New Receipt', 'yith-point-of-sale-for-woocommerce' ),
				'view'               => __( 'View Receipt', 'yith-point-of-sale-for-woocommerce' ),
				'view_item'          => __( 'View Receipt', 'yith-point-of-sale-for-woocommerce' ),
				'search_items'       => __( 'Search Receipts', 'yith-point-of-sale-for-woocommerce' ),
				'not_found'          => __( 'Not found', 'yith-point-of-sale-for-woocommerce' ),
				'not_found_in_trash' => __( 'Not found in trash', 'yith-point-of-sale-for-woocommerce' ),
			);

			$receipt_post_type_args = array(
				'label'               => __( 'Receipts', 'yith-point-of-sale-for-woocommerce' ),
				'labels'              => $labels_receipt,
				'description'         => __( 'This is where Receipts are stored.', 'yith-point-of-sale-for-woocommerce' ),
				'public'              => true,
				'show_ui'             => true,
				'capability_type'     => self::RECEIPT,
				'capabilities'        => array( 'create_posts' => 'create_' . self::RECEIPT . 's' ),
				'map_meta_cap'        => true,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_in_menu'        => false,
				'hierarchical'        => false,
				'show_in_nav_menus'   => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => false,
				'has_archive'         => false,
				'menu_icon'           => 'dashicons-text-page',
				'show_in_rest'        => true,
			);

			register_post_type( self::RECEIPT, apply_filters( 'yith_pos_register_post_type_receipt', $receipt_post_type_args ) );

			do_action( 'yith_pos_after_register_post_type' );

		}

		/**
		 * Remove the dropdown to filter post types by data inside the list table.
		 *
		 * @param bool   $disable   Disable flag.
		 * @param string $post_type Post type.
		 *
		 * @return bool
		 */
		public static function remove_date_dropdown_from_list_table( $disable, $post_type ) {
			if ( self::is_pos_post_type( $post_type ) ) {
				$disable = true;
			}

			return $disable;
		}

		/**
		 * Update the Post Title when updating the '_name' meta
		 *
		 * @param int    $meta_id    Meta ID.
		 * @param int    $object_id  Object ID.
		 * @param string $meta_key   Meta key.
		 * @param mixed  $meta_value Meta value.
		 */
		public static function update_post_title( $meta_id, $object_id, $meta_key, $meta_value ) {
			if ( '_name' === $meta_key && self::is_pos_post_type( get_post_type( $object_id ) ) ) {
				$title = sanitize_text_field( $meta_value );
				if ( get_the_title( $object_id ) !== $title ) {
					wp_update_post(
						array(
							'ID'         => $object_id,
							'post_title' => $title,
						)
					);
				}
			}
		}

		/**
		 * Disable autocomplete in edit form
		 *
		 * @param WP_Post $post The post.
		 */
		public static function disable_autocomplete( $post ) {
			if ( self::is_pos_post_type( get_post_type( $post ) ) ) {
				echo ' autocomplete="off" ';
			}
		}

		/**
		 * Replace the 'message' param when editing/publishing the Store post
		 * with the 'yith-pos-message' param to handle custom notices
		 *
		 * @param string $location The destination URL.
		 * @param int    $post_id  The post ID.
		 *
		 * @return string
		 */
		public static function post_location( $location, $post_id ) {
			$post_type = get_post_type( $post_id );
			if ( self::is_pos_post_type( $post_type ) ) {
				parse_str( wp_parse_url( $location, PHP_URL_QUERY ), $location_params );
				if ( $location_params && isset( $location_params['message'] ) ) {
					$location = remove_query_arg( 'message', $location );
					if ( self::STORE === $post_type && 6 === absint( $location_params['message'] ) ) {
						$location = admin_url( 'edit.php?post_type=' . self::STORE );
						$location = add_query_arg( array( 'yith-pos-updated-post' => $post_id ), $location );
					}
					$location = add_query_arg( array( 'yith-pos-message' => $location_params['message'] ), $location );
				}
			}

			return $location;
		}

		/**
		 * Handle Store edit notices
		 */
		public static function print_notices() {
			static $printed = false;
			$screen         = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
			$current_action = current_action();

			$is_edit_post       = 'yit_before_metaboxes_tab' === $current_action && $screen && self::is_pos_post_type( $screen->id );
			$is_store_edit_list = 'admin_notices' === $current_action && $screen && 'edit-' . self::STORE === $screen->id;

			// Edit notices for POS custom post types.
			if ( ! $printed && ( $is_edit_post || $is_store_edit_list ) && isset( $_GET['yith-pos-message'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$post_type = $is_store_edit_list ? self::STORE : $screen->id;
				$messages  = array(
					self::STORE    => array(
						// translators: %s is the name of the store.
						1 => __( 'Store "%s" updated!', 'yith-point-of-sale-for-woocommerce' ),
						// translators: %s is the name of the store.
						4 => __( 'Store "%s" updated!', 'yith-point-of-sale-for-woocommerce' ),
						// translators: %s is the name of the store.
						6 => __( "Congrats! You've just created your Store: %s.", 'yith-point-of-sale-for-woocommerce' ),
						// translators: %s is the name of the store.
						7 => __( 'Store "%s" saved!', 'yith-point-of-sale-for-woocommerce' ),
					),
					self::REGISTER => array(
						// translators: %s is the name of the register.
						1 => __( 'Register "%s" updated!', 'yith-point-of-sale-for-woocommerce' ),
						// translators: %s is the name of the register.
						4 => __( 'Register "%s" updated!', 'yith-point-of-sale-for-woocommerce' ),
						// translators: %s is the name of the register.
						6 => __( "Congrats! You've just created your Register: %s.", 'yith-point-of-sale-for-woocommerce' ),
						// translators: %s is the name of the register.
						7 => __( 'Register "%s" saved!', 'yith-point-of-sale-for-woocommerce' ),
					),
					self::RECEIPT  => array(
						// translators: %s is the name of the receipt.
						1 => __( 'Receipt "%s" updated!', 'yith-point-of-sale-for-woocommerce' ),
						// translators: %s is the name of the receipt.
						4 => __( 'Receipt "%s" updated!', 'yith-point-of-sale-for-woocommerce' ),
						// translators: %s is the name of the receipt.
						6 => __( "Congrats! You've just created your receipt: %s.", 'yith-point-of-sale-for-woocommerce' ),
						// translators: %s is the name of the receipt.
						7 => __( 'Receipt "%s" saved!', 'yith-point-of-sale-for-woocommerce' ),
					),
				);

				$message_to_show = absint( $_GET['yith-pos-message'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				if ( array_key_exists( $message_to_show, $messages[ $post_type ] ) ) {
					if ( in_array( $message_to_show, array( 1, 4, 6, 7 ), true ) ) {
						if ( $is_store_edit_list ) {
							$post_id = isset( $_GET['yith-pos-updated-post'] ) ? absint( $_GET['yith-pos-updated-post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						} elseif ( $is_edit_post ) {
							$post_id = $GLOBALS['post_id'];
						} else {
							$post_id = 0;
						}

						if ( $post_id ) {
							$message = sprintf( $messages[ $post_type ][ $message_to_show ], get_the_title( $post_id ) );
							yith_plugin_fw_get_component(
								array(
									'type'        => 'notice',
									'notice_type' => 'success',
									'message'     => $message,
									'inline'      => $is_edit_post,
								)
							);
						}
					}
				}
				$printed = true;
			}

			if ( ! empty( $_GET['yith-pos-roles-and-capabilities-regenerated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$message = __( 'YITH POS roles and capabilities regenerated correctly!', 'yith-point-of-sale-for-woocommerce' );
				yith_plugin_fw_get_component(
					array(
						'type'        => 'notice',
						'notice_type' => 'success',
						'message'     => $message,
						'inline'      => false,
					)
				);
			}

		}

		/**
		 * Add yith-pos-message param to query args to remove on loading through JS
		 *
		 * @param array $args Query args to be removed.
		 *
		 * @return array
		 */
		public static function removable_query_args( $args ) {
			$args[] = 'yith-pos-message';
			$args[] = 'yith-pos-updated-post';
			$args[] = 'yith-pos-roles-and-capabilities-regenerated';

			return $args;
		}

		/**
		 * Add 'registers' field to Store in REST API.
		 */
		public static function add_registers_field_to_store() {
			register_rest_field(
				self::STORE,
				'registers',
				array(
					'get_callback'    => 'yith_post_rest_get_register_list',
					'update_callback' => null,
					'schema'          => null,
				)
			);
		}

		/**
		 * Add the custom user roles
		 */
		public static function handle_roles_and_capabilities() {
			// Dummy gettext calls to get strings in the catalog.
			_x( 'YITH POS Manager', 'User role', 'yith-point-of-sale-for-woocommerce' );
			_x( 'YITH POS Cashier', 'User role', 'yith-point-of-sale-for-woocommerce' );

			$admin_caps   = yith_pos_get_admin_pos_capabilities();
			$manager_caps = array_merge( array_keys( get_role( 'shop_manager' )->capabilities ), yith_pos_get_manager_pos_capabilities() );
			$cashier_caps = array_merge( array_keys( get_role( 'subscriber' )->capabilities ), yith_pos_get_cashier_pos_capabilities() );

			$admin = get_role( 'administrator' );

			foreach ( $admin_caps as $admin_cap ) {
				$admin->add_cap( $admin_cap );
			}

			add_role(
				'yith_pos_manager',
				'YITH POS Manager',
				array_combine( $manager_caps, array_fill( 0, count( $manager_caps ), true ) )
			);
			add_role(
				'yith_pos_cashier',
				'YITH POS Cashier',
				array_combine( $cashier_caps, array_fill( 0, count( $cashier_caps ), true ) )
			);
		}

		/**
		 * Add the receipt default template
		 */
		public static function create_default_receipt() {
			$option_name  = 'yith_pos_receipt_default';
			$option_value = get_option( $option_name );

			if ( ! empty( $option_value ) ) {
				// The receipt post already exists.
				return;
			}

			$post_data = array(
				'post_status'    => 'publish',
				'post_type'      => self::RECEIPT,
				'post_author'    => 1,
				'post_name'      => 'receipt',
				'post_title'     => __( 'Default Receipt', 'yith-point-of-sale-for-woocommerce' ),
				'post_content'   => '',
				'post_parent'    => 0,
				'comment_status' => 'closed',
				'meta_input'     => array(
					'_name'                 => __( 'Default Receipt', 'yith-point-of-sale-for-woocommerce' ),
					'_logo'                 => YITH_POS_ASSETS_URL . '/images/logo-receipt.png',
					'_show_store_name'      => 'yes',
					'_show_vat'             => 'yes',
					'_vat_label'            => __( 'VAT:', 'yith-point-of-sale-for-woocommerce' ),
					'_show_address'         => 'yes',
					'_show_contact_info'    => 'yes',
					'_show_phone'           => 1,
					'_show_email'           => 1,
					'_show_fax'             => 1,
					'_show_order_date'      => 'yes',
					'_order_date_label'     => __( 'Date:', 'yith-point-of-sale-for-woocommerce' ),
					'_show_order_number'    => 'yes',
					'_order_number_label'   => __( 'Order:', 'yith-point-of-sale-for-woocommerce' ),
					'_show_order_customer'  => 'yes',
					'_order_customer_label' => __( 'Customer:', 'yith-point-of-sale-for-woocommerce' ),
					'_show_order_register'  => 'yes',
					'_order_register_label' => __( 'Register:', 'yith-point-of-sale-for-woocommerce' ),
					'_show_cashier'         => 'yes',
					'_cashier_label'        => __( 'Cashier:', 'yith-point-of-sale-for-woocommerce' ),
					'_show_shipping'        => 'yes',
					'_shipping_label'       => __( 'Shipping:', 'yith-point-of-sale-for-woocommerce' ),
					'_receipt_footer'       => __( 'Thanks for your purchase', 'yith-point-of-sale-for-woocommerce' ),
					'_show_social_info'     => '0',
					'_show_facebook'        => '0',
					'_show_twitter'         => '0',
					'_show_instagram'       => '0',
					'_show_youtube'         => '0',
				),
			);

			$post_id = wp_insert_post( $post_data );

			update_option( $option_name, $post_id );
		}

		/**
		 * Regenerate Roles and Capabilities
		 *
		 * @param bool $force Force flag.
		 */
		public static function regenerate_roles_and_capabilities( $force = false ) {
			if ( $force || ( isset( $_GET['yith-pos-regenerate-roles-and-capabilities'] ) && current_user_can( 'manage_options' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				remove_role( 'yith_pos_manager' );
				remove_role( 'yith_pos_cashier' );

				self::handle_roles_and_capabilities();
				if ( ! $force ) {
					wp_safe_redirect(
						add_query_arg(
							array( 'yith-pos-roles-and-capabilities-regenerated' => 1 ),
							remove_query_arg(
								array( 'yith-pos-regenerate-roles-and-capabilities' )
							)
						)
					);
				}
			}
		}

		/**
		 * Regenerate roles and caps if needed
		 *
		 * @since 1.0.1
		 */
		public static function maybe_regenerate_roles_and_caps() {
			$option          = 'yith_pos_roles_and_caps_version';
			$current_version = get_option( $option, '1.0.0' );

			if ( $current_version !== self::$roles_and_caps_version ) {
				self::regenerate_roles_and_capabilities( true );
				update_option( $option, self::$roles_and_caps_version );
			}
		}
	}
}
