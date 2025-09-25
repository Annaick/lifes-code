<?php
/**
 * Receipt post-type admin class.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Receipt_Post_Type_Admin' ) ) {
	/**
	 * Class YITH_POS_Receipt_Post_Type_Admin
	 *
	 */
	class YITH_POS_Receipt_Post_Type_Admin extends YITH_Post_Type_Admin {

		/**
		 * The post type.
		 *
		 * @var string
		 */
		protected $post_type = YITH_POS_Post_Types::RECEIPT;

		/**
		 * The object.
		 *
		 * @var YITH_POS_Receipt
		 */
		protected $object;

		/**
		 * YITH_POS_Receipt_Post_Type_Admin constructor
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'admin_menu', array( $this, 'remove_publish_box' ) );
			add_action( 'admin_init', array( $this, 'add_meta_boxes' ), 1 );

			add_action( 'yith_pos_preview_receipt', array( $this, 'preview_receipt_template' ) );

			add_filter( 'get_user_option_screen_layout_' . $this->post_type, '__return_true' );
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
		 *              }
		 */
		protected function get_blank_state_params() {
			return array(
				'icon_class' => 'yith-pos-icon-accounting',
				'message'    => __( 'You have no receipt yet!', 'yith-point-of-sale-for-woocommerce' ),
				'cta'        => array(
					'title' => _x( 'Create receipt', 'Button text', 'yith-point-of-sale-for-woocommerce' ),
					'url'   => add_query_arg( array( 'post_type' => YITH_POS_Post_Types::RECEIPT ), admin_url( 'post-new.php' ) ),
				),
			);
		}

		/**
		 * Pre-fetch any data for the row each column has access to it, by loading $this->object.
		 *
		 * @param int $post_id Post ID being shown.
		 */
		protected function prepare_row_data( $post_id ) {
			global $the_receipt;
			$the_receipt  = yith_pos_get_receipt( $post_id );
			$this->object = $the_receipt;
		}

		/**
		 * Manage the column name on list table.
		 *
		 * @param array $columns Columns.
		 *
		 * @return array
		 */
		public function define_columns( $columns ) {
			unset( $columns['date'] );
			$columns['registers'] = __( 'Registers with this template', 'yith-point-of-sale-for-woocommerce' );
			$columns['actions']   = __( 'Actions', 'yith-point-of-sale-for-woocommerce' );

			return $columns;
		}

		/**
		 * Render Registers column
		 */
		protected function render_registers_column() {
			$registers = $this->object->get_registers();
			if ( $registers ) {
				yith_pos_compact_list( array_filter( array_map( 'yith_pos_get_register_full_name', $registers ) ) );
			}
		}

		/**
		 * Render Actions column
		 */
		protected function render_actions_column() {
			$actions = yith_plugin_fw_get_default_post_actions( $this->object->get_id(), array( 'delete-directly' => true ) );

			yith_plugin_fw_get_action_buttons( $actions, true );
		}

		/**
		 * Remove publish box from edit booking
		 */
		public function remove_publish_box() {
			remove_meta_box( 'submitdiv', $this->post_type, 'side' );
		}

		/**
		 * Add meta boxes to edit the the receipt
		 */
		public function add_meta_boxes() {
			$args = require_once YITH_POS_DIR . '/plugin-options/metabox/receipt-options.php';

			foreach ( $args as $key => $metabox_args ) {
				$metabox_template = YIT_Metabox( $key );
				$metabox_template->init( $metabox_args );
			}
		}

		/**
		 * Get the receipt preview template field to show inside the meta-box.
		 */
		public function preview_receipt_template() {
			yith_pos_get_view( 'metabox/receipt-preview-template.php' );
		}
	}
}

return YITH_POS_Receipt_Post_Type_Admin::instance();
