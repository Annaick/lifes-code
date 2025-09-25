<?php
/**
 * Register post-type admin class.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Register_Post_Type_Admin' ) ) {
	/**
	 * Class YITH_POS_Register_Post_Type_Admin
	 *
	 */
	class YITH_POS_Register_Post_Type_Admin extends YITH_Post_Type_Admin {

		/**
		 * The post type.
		 *
		 * @var string
		 */
		protected $post_type = YITH_POS_Post_Types::REGISTER;

		/**
		 * The object.
		 *
		 * @var YITH_POS_Register
		 */
		protected $object;

		/**
		 * YITH_POS_Register_Post_Type_Admin constructor
		 */
		public function __construct() {
			parent::__construct();

			add_filter( 'get_user_option_screen_layout_' . YITH_POS_Post_Types::REGISTER, '__return_true' );

			add_filter( 'wp_untrash_post_status', array( $this, 'untrash_post_status' ), 10, 3 );
			add_filter( 'views_edit-' . $this->post_type, '__return_false' );
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
		 *                        }
		 */
		protected function get_blank_state_params() {
			return array(
				'icon_class' => 'yith-pos-icon-accounting',
				'message'    => __( 'You have no register yet!', 'yith-point-of-sale-for-woocommerce' ),
			);
		}

		/**
		 * Pre-fetch any data for the row each column has access to it, by loading $this->object.
		 *
		 * @param int $post_id Post ID being shown.
		 */
		protected function prepare_row_data( $post_id ) {
			global $the_register;
			$the_register = yith_pos_get_register( $post_id );
			$this->object = $the_register;
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

			$new_columns = array(
				'cb' => $columns['cb'],
			);

			unset( $columns['cb'] );

			$new_columns['name']    = __( 'Register Name', 'yith-point-of-sale-for-woocommerce' );
			$new_columns['store']   = __( 'Store', 'yith-point-of-sale-for-woocommerce' );
			$new_columns['info']    = __( 'Info', 'yith-point-of-sale-for-woocommerce' );
			$new_columns['status']  = __( 'Status', 'yith-point-of-sale-for-woocommerce' );
			$new_columns['enabled'] = __( 'Enabled', 'yith-point-of-sale-for-woocommerce' );
			$new_columns['actions'] = __( 'Actions', 'yith-point-of-sale-for-woocommerce' );

			return array_merge( $new_columns, $columns );
		}

		/**
		 * Render Name column
		 */
		protected function render_name_column() {
			echo '<strong>' . esc_html( $this->object->get_name() ) . '</strong>';
		}

		/**
		 * Render Store column
		 */
		protected function render_store_column() {
			$store_id = $this->object->get_store_id();
			yith_pos_get_post_edit_link_html( $store_id, true );
		}

		/**
		 * Render Info column
		 */
		protected function render_info_column() {
			$register = $this->object;
			if ( $register->is_receipt_enabled() ) {
				$receipt_id = $register->get_receipt_id();
				// translators: %s is the receipt name.
				echo '<div>' . wp_kses_post( sprintf( esc_html__( 'Receipt: %s', 'yith-point-of-sale-for-woocommerce' ), yith_pos_get_post_edit_link_html( $receipt_id ) ) ) . '</div>';
			} else {
				echo '<div>' . esc_html__( 'No Receipt', 'yith-point-of-sale-for-woocommerce' ) . '</div>';
			}
		}

		/**
		 * Render status column
		 */
		protected function render_status_column() {
			$register    = $this->object;
			$status      = $register->get_status();
			$status_name = yith_pos_get_register_status_name( $status );
			$user        = yith_pos_get_register_lock( $register->get_id() );
			echo '<span class="yith-pos-register-status yith-pos-register-status--' . esc_attr( $status ) . '">' . esc_html( $status_name ) . '</span>';

			if ( $user ) {
				echo '<div class="yith-pos-register-status__used-by">' . esc_html( yith_pos_get_employee_name( $user ) ) . '</div>';
			}
		}

		/**
		 * Render enabled column
		 */
		protected function render_enabled_column() {
			$register = $this->object;
			if ( $register->is_published() ) {
				echo "<div class='yith-plugin-ui'>";
				yith_plugin_fw_get_field(
					array(
						'type'  => 'onoff',
						'class' => 'yith-pos-register-toggle-enabled',
						'value' => $register->is_enabled() ? 'yes' : 'no',
						'data'  => array(
							'register-id' => $register->get_id(),
							'security'    => wp_create_nonce( 'register-toggle-enabled' ),
						),
					),
					true
				);
				echo '</div>';
			} else {
				$post_status     = $register->get_post_status();
				$post_status_obj = get_post_status_object( $post_status );
				echo '<div class="yith-pos-post-status yith-pos-post-status--' . esc_attr( $post_status ) . '">' . esc_html( $post_status_obj->label ) . '</div>';
			}
		}

		/**
		 * Render Actions column
		 */
		protected function render_actions_column() {
			$actions  = yith_plugin_fw_get_default_post_actions( $this->object->get_id(), array( 'delete-directly' => true ) );
			$register = $this->object;

			if ( isset( $actions['edit'] ) ) {
				$store_id           = $register->get_store_id();
				$edit_register_link = add_query_arg( array( 'yith-pos-edit-register' => $register->get_id() ), get_edit_post_link( $store_id ) );

				$actions['edit']['url'] = $edit_register_link;
			}

			$open_register_link = add_query_arg(
				array(
					'yith-pos-register-direct-login-nonce' => wp_create_nonce( 'yith-pos-register-direct-login' ),
					'register'                             => $register->get_id(),
				),
				yith_pos_get_pos_page_url()
			);

			$actions['open-register'] = array(
				'type'   => 'action-button',
				'title'  => __( 'Open Register', 'yith-point-of-sale-for-woocommerce' ),
				'action' => 'open-register',
				'icon'   => 'enter',
				'url'    => $open_register_link,
			);

			yith_plugin_fw_get_action_buttons( $actions, true );
		}

		/**
		 * Render filters for Store and Status.
		 */
		public function render_filters() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$selected_store  = isset( $_REQUEST['store'] ) ? absint( $_REQUEST['store'] ) : '';
			$selected_status = isset( $_REQUEST['status'] ) ? wc_clean( wp_unslash( $_REQUEST['status'] ) ) : '';

			$store_ids   = yith_pos_get_stores();
			$store_names = array_map( 'yith_pos_get_register_name', $store_ids );
			$stores      = array_combine( $store_ids, $store_names );
			echo "<select name='store'>";
			echo "<option value=''>" . esc_html__( 'Filter by store', 'yith-point-of-sale-for-woocommerce' ) . '</option>';
			foreach ( $stores as $id => $name ) {
				echo '<option value="' . esc_attr( $id ) . '" ' . selected( $id, $selected_store, false ) . '>' . esc_html( $name ) . '</option>';
			}
			echo '</select>';

			$statuses = yith_pos_register_statuses();

			echo "<select name='status'>";
			echo "<option value=''>" . esc_html__( 'Filter by status', 'yith-point-of-sale-for-woocommerce' ) . '</option>';
			foreach ( $statuses as $id => $name ) {
				echo '<option value="' . esc_attr( $id ) . '" ' . selected( $id, $selected_status, false ) . '>' . esc_html( $name ) . '</option>';
			}
			echo '</select>';
			// phpcs:enable
		}

		/**
		 * Return true if the list is filtered, to show the "clear filters" button.
		 */
		protected function is_the_list_filtered() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return ! empty( $_GET['store'] ) || ! empty( $_GET['status'] );
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
			$changed    = false;

			if ( ! empty( $_REQUEST['store'] ) ) {
				$changed      = true;
				$meta_query[] = array(
					'key'   => '_store_id',
					'value' => absint( $_REQUEST['store'] ),
				);
			}

			if ( ! empty( $_REQUEST['status'] ) ) {
				$changed = true;
				$status  = wc_clean( wp_unslash( $_REQUEST['status'] ) );
				if ( 'closed' === $status ) {
					$meta_query[] = array(
						'relation' => 'OR',
						array(
							'key'   => '_status',
							'value' => 'closed',
						),
						array(
							'key'     => '_status',
							'compare' => 'NOT EXISTS',
						),
					);
				} else {
					$meta_query[] = array(
						'key'   => '_status',
						'value' => $status,
					);
				}
			}

			if ( $changed ) {
				$query_vars['meta_query'] = $meta_query;
			}

			// phpcs:enable

			return $query_vars;
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

		public function get_views( $views ) {
			return false;
		}

		/**
		 * Ensure statuses are correctly set to publish when restoring registers.
		 *
		 * @param string $new_status      The new status of the post being restored.
		 * @param int    $post_id         The ID of the post being restored.
		 * @param string $previous_status The status of the post at the point where it was trashed.
		 *
		 * @return string
		 * @since 1.0.15
		 */
		public static function untrash_post_status( $new_status, $post_id, $previous_status ) {
			return 'publish';
		}
	}
}

return YITH_POS_Register_Post_Type_Admin::instance();
