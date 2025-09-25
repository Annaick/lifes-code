<?php
/**
 * Store post-type admin class.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Store_Post_Type_Admin' ) ) {
	/**
	 * Class YITH_POS_Store_Post_Type_Admin
	 *
	 */
	class YITH_POS_Store_Post_Type_Admin extends YITH_Post_Type_Admin {

		/**
		 * The post type.
		 *
		 * @var string
		 */
		protected $post_type = YITH_POS_Post_Types::STORE;

		/**
		 * The object.
		 *
		 * @var YITH_POS_Store
		 */
		protected $object;

		/**
		 * YITH_POS_Store_Post_Type_Admin constructor
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'admin_menu', array( $this, 'remove_publish_box' ) );
			add_action( 'admin_init', array( $this, 'add_meta_boxes' ), 1 );

			add_filter( 'get_user_option_screen_layout_' . $this->post_type, '__return_true' );

			add_filter( 'admin_body_class', array( $this, 'add_wizard_body_class' ) );
			add_filter( 'yit_before_metaboxes_tab', array( $this, 'print_wizard_nav' ) );
			add_filter( 'yit_after_metaboxes_tab', array( $this, 'print_wizard_pagination' ) );

			add_action( 'yith_pos_store_metabox_registers_list', array( $this, 'print_registers_list_in_metabox' ) );

			add_action( 'delete_post', array( $this, 'delete_registers_when_deleting_the_store' ), 10, 1 );

			add_action( 'save_post', array( $this, 'remove_user_roles_before_saving_the_store' ), 5, 1 );
			add_action( 'save_post', array( $this, 'add_user_roles_after_saving_the_store' ), 100, 1 );
		}

		/**
		 * Retrieve an array of parameters for blank state.
		 *
		 * @return array{
		 * @type string $icon_url The icon URL.
		 * @type string $message  The message to be shown.
		 * @type string $cta      The call-to-action button title.
		 * @type string $cta_icon The call-to-action button icon.
		 * @type string $cta_url  The call-to-action button URL.
		 *              }
		 */
		protected function get_blank_state_params() {
			return array(
				'icon_url' => YITH_POS_ASSETS_URL . '/images/store-register.png',
				'message'  => implode(
					'<br />',
					array(
						// translators: %s is the plugin name.
						sprintf( esc_html__( 'Thanks for choosing %s!', 'yith-point-of-sale-for-woocommerce' ), '<strong>' . esc_html( YITH_POS_PLUGIN_NAME ) . '</strong>' ),
						esc_html__( 'Now, the first step is to create a Store: after that, you will be able to use our powerful Register to sell your products.', 'yith-point-of-sale-for-woocommerce' ),
					)
				),
				'cta'      => array(
					'title' => __( 'Create your first store', 'yith-point-of-sale-for-woocommerce' ),
					'url'   => add_query_arg( array( 'post_type' => YITH_POS_Post_Types::STORE ), admin_url( 'post-new.php' ) ),
				),
			);
		}

		/**
		 * Pre-fetch any data for the row each column has access to it, by loading $this->object.
		 *
		 * @param int $post_id Post ID being shown.
		 */
		protected function prepare_row_data( $post_id ) {
			global $the_store;
			$the_store    = yith_pos_get_store( $post_id );
			$this->object = $the_store;
		}

		/**
		 * Define columns.
		 *
		 * @param array $columns Columns.
		 *
		 * @return array
		 */
		public function define_columns( $columns ) {
			if ( isset( $columns['date'] ) ) {
				unset( $columns['date'] );
			}

			if ( isset( $columns['title'] ) ) {
				unset( $columns['title'] );
			}

			$new_columns = array( 'cb' => $columns['cb'] );
			unset( $columns['cb'] );

			$new_columns['title']     = __( 'Store Name', 'yith-point-of-sale-for-woocommerce' );
			$new_columns['employees'] = __( 'Employees', 'yith-point-of-sale-for-woocommerce' );
			$new_columns['registers'] = __( 'Registers', 'yith-point-of-sale-for-woocommerce' );
			$new_columns['enabled']   = __( 'Enabled', 'yith-point-of-sale-for-woocommerce' );
			$new_columns['actions']   = __( 'Actions', 'yith-point-of-sale-for-woocommerce' );

			return array_merge( $new_columns, $columns );
		}

		/**
		 * Render employees column
		 */
		protected function render_employees_column() {
			$store    = $this->object;
			$managers = $store->get_managers();
			$cashiers = $store->get_cashiers();

			if ( $managers ) {
				echo '<strong>' . esc_html__( 'Managers', 'yith-point-of-sale-for-woocommerce' ) . ':</strong>';
				yith_pos_compact_list( array_map( 'yith_pos_get_employee_name', $managers ) );
			}

			if ( $cashiers ) {
				echo '<strong>' . esc_html__( 'Cashiers', 'yith-point-of-sale-for-woocommerce' ) . ':</strong>';
				yith_pos_compact_list( array_map( 'yith_pos_get_employee_name', $cashiers ) );
			}
		}

		/**
		 * Render registers column
		 */
		protected function render_registers_column() {
			$store     = $this->object;
			$registers = $store->get_register_ids();

			if ( $registers ) {
				yith_pos_compact_list( array_map( 'yith_pos_get_register_name', $registers ) );
			}
		}

		/**
		 * Render enabled column
		 */
		protected function render_enabled_column() {
			$store = $this->object;
			if ( $store->is_published() ) {
				echo "<div class='yith-plugin-ui'>";
				yith_plugin_fw_get_field(
					array(
						'type'  => 'onoff',
						'id'    => 'yith-pos-store-toggle-enabled-' . $store->get_id(),
						'class' => 'yith-pos-store-toggle-enabled',
						'value' => $store->is_enabled() ? 'yes' : 'no',
						'data'  => array(
							'store-id' => $store->get_id(),
							'security' => wp_create_nonce( 'store-toggle-enabled' ),
						),
					),
					true
				);
				echo '</div>';
			} else {
				$post_status     = $store->get_post_status();
				$post_status_obj = get_post_status_object( $post_status );
				echo '<div class="yith-pos-post-status yith-pos-post-status--' . esc_attr( $post_status ) . '">' . esc_html( $post_status_obj->label ) . '</div>';
			}
		}

		/**
		 * Render Actions column
		 */
		protected function render_actions_column() {
			$actions = yith_plugin_fw_get_default_post_actions( $this->object->get_id(), array( 'delete-directly' => true ) );

			$show_registers_link = add_query_arg(
				array(
					'post_type' => YITH_POS_Post_Types::REGISTER,
					'store'     => $this->object->get_id(),
				),
				admin_url( 'edit.php' )
			);

			$actions['show-registers'] = array(
				'type'   => 'action-button',
				'title'  => __( 'Show Registers', 'yith-point-of-sale-for-woocommerce' ),
				'action' => 'show-registers',
				'icon'   => 'cash-register',
				'url'    => $show_registers_link,
			);

			yith_plugin_fw_get_action_buttons( $actions, true );
		}

		/**
		 * Handle any custom filters.
		 *
		 * @param array $query_vars Query vars.
		 *
		 * @return array
		 */
		protected function query_filters( $query_vars ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$meta_query = $query_vars['meta_query'] ?? array();

			if ( ! empty( $_REQUEST['enabled'] ) ) {
				$enabled = 'yes' === $_REQUEST['enabled'];
				if ( $enabled ) {
					$meta_query[] = array(
						'relation' => 'OR',
						array(
							'key'   => '_enabled',
							'value' => 'yes',
						),
						array(
							'key'     => '_enabled',
							'compare' => 'NOT EXISTS',
						),
					);
				} else {
					$meta_query[] = array(
						'key'   => '_enabled',
						'value' => 'no',
					);
				}
				$query_vars['meta_query'] = $meta_query;
			}

			return $query_vars;
			// phpcs:enable
		}

		/**
		 * Render filters
		 */
		public function render_filters() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$selected_enabled = isset( $_REQUEST['enabled'] ) ? wc_clean( wp_unslash( $_REQUEST['enabled'] ) ) : '';

			$enabled_statuses = array(
				'yes' => __( 'Enabled', 'yith-point-of-sale-for-woocommerce' ),
				'no'  => __( 'Disabled', 'yith-point-of-sale-for-woocommerce' ),
			);

			echo "<select name='enabled'>";
			echo "<option value=''>" . esc_html__( 'Filter by status', 'yith-point-of-sale-for-woocommerce' ) . '</option>';
			foreach ( $enabled_statuses as $id => $name ) {
				echo '<option value="' . esc_attr( $id ) . '" ' . selected( $id, $selected_enabled, false ) . '>' . esc_html( $name ) . '</option>';
			}
			echo '</select>';
			// phpcs:enable
		}

		/**
		 * Return true if the list is filtered, to show the "clear filters" button.
		 */
		protected function is_the_list_filtered() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return ! empty( $_GET['enabled'] );
		}

		/**
		 * Add Cashier and Manager roles to users after saving the store.
		 *
		 * @param int $post_id Post ID.
		 */
		public function add_user_roles_after_saving_the_store( $post_id ) {
			if ( get_post_type( $post_id ) === YITH_POS_Post_Types::STORE ) {
				$store = yith_pos_get_store( $post_id );
				if ( $store ) {
					foreach ( $store->get_managers() as $manager_id ) {
						yith_pos_maybe_add_user_role( $manager_id, 'yith_pos_manager' );
					}
					foreach ( $store->get_cashiers() as $cashier_id ) {
						yith_pos_maybe_add_user_role( $cashier_id, 'yith_pos_cashier' );
					}
				}
			}
		}

		/**
		 * Remove user roles before saving the store.
		 *
		 * @param int $post_id The post ID.
		 */
		public function remove_user_roles_before_saving_the_store( $post_id ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended

			if ( get_post_type( $post_id ) === YITH_POS_Post_Types::STORE ) {

				$store = yith_pos_get_store( $post_id );

				if ( isset( $_REQUEST['yit_metaboxes']['_managers'] ) ) {
					$old_managers = $store->get_managers();
					// Manager IDs are stored as array of strings.
					$new_managers = wc_clean( wp_unslash( $_REQUEST['yit_metaboxes']['_managers'] ) );
					$diff         = array_diff( $old_managers, $new_managers );
					if ( $diff ) {
						foreach ( $diff as $manager_id ) {
							yith_pos_maybe_remove_user_role( $manager_id, 'manager', $post_id );
						}
					}
				}

				if ( isset( $_REQUEST['yit_metaboxes']['_cashiers'] ) ) {
					$old_cashiers = $store->get_cashiers();
					// Cashiers IDs are stored as array of strings.
					$new_cashiers = wc_clean( wp_unslash( $_REQUEST['yit_metaboxes']['_cashiers'] ) );
					$diff         = array_diff( $old_cashiers, $new_cashiers );

					if ( $diff ) {
						foreach ( $diff as $cashier_id ) {
							yith_pos_maybe_remove_user_role( $cashier_id, 'cashier', $post_id );
						}
					}
				}
			}

			// phpcs:enable
		}

		/**
		 * Delete all registers of the store if the store is deleted
		 *
		 * @param int $post_id Post ID.
		 */
		public function delete_registers_when_deleting_the_store( $post_id ) {
			if ( get_post_type( $post_id ) === YITH_POS_Post_Types::STORE ) {
				$store = yith_pos_get_store( $post_id );
				if ( $store ) {
					$store->delete_all_registers();
				}
			}
		}

		/**
		 * Return the current page of the wizard stored in the Store.
		 *
		 * @return int|mixed
		 */
		private function get_wizard_current_page() {
			static $current_page;
			if ( ! isset( $current_page ) ) {
				global $post_id;
				$current_page = get_post_meta( $post_id, '_wizard_current_page', true );
				$current_page = ! ! $current_page ? absint( $current_page ) : 1;
			}

			return $current_page;
		}

		/**
		 * Add the wizard class to body if you're creating a new store or editing a draft one
		 *
		 * @param string $class The class.
		 *
		 * @return string
		 */
		public function add_wizard_body_class( $class ) {
			if ( yith_pos_is_store_wizard() ) {
				$class .= ' yith-pos-store-wizard ';
			}

			return $class;
		}

		/**
		 * Print the wizard nav
		 */
		public function print_wizard_nav() {
			if ( yith_pos_is_store_wizard() ) {
				$args = array(
					'current_page' => $this->get_wizard_current_page(),
				);
				yith_pos_get_view( 'panel/store-wizard-nav.php', $args );
			}
		}

		/**
		 * Print the wizard pagination
		 */
		public function print_wizard_pagination() {
			if ( yith_pos_is_store_wizard() ) {
				$args = array(
					'current_page' => $this->get_wizard_current_page(),
				);
				yith_pos_get_view( 'panel/store-wizard-pagination.php', $args );
			}
		}

		/**
		 * Remove publish box from edit booking
		 */
		public function remove_publish_box() {
			remove_meta_box( 'submitdiv', $this->post_type, 'side' );
		}

		/**
		 * Add meta boxes to edit the store
		 */
		public function add_meta_boxes() {
			$args             = require_once YITH_POS_DIR . '/plugin-options/metabox/store-options.php';
			$metabox_template = YIT_Metabox( 'yith-pos-store' );
			$metabox_template->init( $args );
		}

		/**
		 * Return the title of section
		 *
		 * @param int  $step    Step number.
		 * @param bool $publish True if published.
		 *
		 * @return string
		 * @deprecated 2.0.0 | use yith_pos_get_store_section_title_html instead.
		 */
		public function get_section_title( $step = 1, $publish = true ) {
			yith_pos_deprecated_function( 'YITH_POS_Store_Post_Type_Admin::get_section_title', '2.0.0', 'yith_pos_get_store_section_title_html' );

			return yith_pos_get_store_section_title_html( $step, $publish );
		}

		/**
		 * Print register list in meta-box.
		 */
		public function print_registers_list_in_metabox() {
			global $post_id;
			$args = array(
				'store_id'  => $post_id,
				'registers' => yith_pos_get_registers_by_store( $post_id ),
			);
			yith_pos_get_view( 'metabox/store-registers-list.php', $args );
		}

		/**
		 * Define bulk actions.
		 *
		 * @param array $actions Existing actions.
		 *
		 * @return array
		 */
		public function define_bulk_actions( $actions ) {
			return false;
		}
	}
}

return YITH_POS_Store_Post_Type_Admin::instance();
