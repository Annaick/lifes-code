<?php
/**
 * Register Session List Table Class.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Register_Session_List_Table' ) ) {
	/**
	 * Register Session List Table Class.
	 */
	class YITH_POS_Register_Session_List_Table extends WP_List_Table {

		/**
		 * YITH_POS_Register_Session_List_Table constructor.
		 */
		public function __construct() {
			parent::__construct(
				array(
					'singular' => 'yith_pos_register_session',
					'plural'   => 'yith_pos_register_sessions',
					'screen'   => 'yith-pos-register-sessions',
				)
			);
		}

		/**
		 * Get the columns
		 *
		 * @return array
		 */
		public function get_columns() {
			return array(
				'ID'          => __( 'Session ID', 'yith-point-of-sale-for-woocommerce' ),
				'store'       => __( 'Store', 'yith-point-of-sale-for-woocommerce' ),
				'register'    => __( 'Register', 'yith-point-of-sale-for-woocommerce' ),
				'open_date'   => __( 'Opening Time', 'yith-point-of-sale-for-woocommerce' ),
				'closed_date' => __( 'Closing Time', 'yith-point-of-sale-for-woocommerce' ),
				'total_sales' => __( 'Total Sales', 'yith-point-of-sale-for-woocommerce' ),
				'actions'     => '',
			);
		}

		/**
		 * Column default.
		 *
		 * @param YITH_POS_Register_Session $register_session The register session.
		 * @param string                    $column_name      The column name.
		 */
		public function column_default( $register_session, $column_name ) {
			$date_format = yith_pos_admin_date_format();

			switch ( $column_name ) {
				case 'ID':
					echo sprintf(
						'<a class="view-register-session-details" href="%s"><strong>%s</strong></a>',
						esc_url( add_query_arg( array( 'session_id' => $register_session->get_id() ) ) ),
						'#' . absint( $register_session->get_id() )
					);
					break;

				case 'store':
					yith_pos_get_post_edit_link_html( $register_session->get_store_id(), true );
					break;

				case 'register':
					yith_pos_get_post_edit_link_html( $register_session->get_register_id(), true );
					break;

				case 'open_date':
					$open_date = $register_session->get_open_date();
					$date      = $open_date->date_i18n( $date_format );

					echo sprintf( '<div class="register-session-date">%s</div>', esc_html( $date ) );
					break;

				case 'closed_date':
					$closed_date = $register_session->get_closed_date();
					if ( $closed_date ) {
						$date = $closed_date->date_i18n( $date_format );

						echo sprintf( '<div class="register-session-date">%s</div>', esc_html( $date ) );
					}
					break;
				case 'total_sales':
					if ( $register_session->is_closed() ) {
						echo wp_kses_post( wc_price( $register_session->get_total() ) );
					}
					break;
				case 'actions':
					$actions = array(
						'view'     => array(
							'type'   => 'action-button',
							'title'  => _x( 'View', 'Action', 'yith-point-of-sale-for-woocommerce' ),
							'action' => 'eye',
							'url'    => $register_session->get_edit_link(),
						),
						'download' => array(
							'type'   => 'action-button',
							'title'  => __( 'Download reports', 'yith-point-of-sale-for-woocommerce' ),
							'action' => 'download',
							'url'    => $register_session->get_download_reports_url(),
						),
					);

					yith_plugin_fw_get_action_buttons( $actions, true );
					break;
			}
		}

		/**
		 * Prepare items to be shown
		 */
		public function prepare_items() {
			$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
			$current_page          = absint( $this->get_pagenum() );
			$per_page              = 20;

			$args = array(
				'items_per_page' => $per_page,
				'page'           => $current_page,
				'paginate'       => true,
			);

			if ( ! current_user_can( 'yith_pos_manage_others_pos' ) ) {
				$manager_stores = yith_pos_get_manager_stores();
				$manager_stores = ! ! $manager_stores ? $manager_stores : array( 0 );

				$args['store_id'] = $manager_stores;
			}

			$query       = yith_pos_get_register_sessions( $args );
			$this->items = $query->items;

			$this->set_pagination_args(
				array(
					'total_items' => $query->total,
					'per_page'    => $per_page,
					'total_pages' => $query->max_num_pages,
				)
			);
		}
	}
}
