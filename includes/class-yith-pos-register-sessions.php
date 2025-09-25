<?php
/**
 * Register sessions class.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Register_Sessions' ) ) {
	/**
	 * Class YITH_POS_Register_Sessions
	 *
	 * @since  2.0.0
	 */
	class YITH_POS_Register_Sessions {
		use YITH_POS_Singleton_Trait;

		/**
		 * YITH_POS_Register_Sessions_Admin constructor.
		 */
		protected function __construct() {
			add_action( 'wp_loaded', array( $this, 'handle_actions' ), 20 );
		}

		/**
		 * Handle Register Session actions
		 */
		public function handle_actions() {
			$action = sanitize_text_field( wp_unslash( $_REQUEST['yith-pos-register-session-action'] ?? '' ) );
			$nonce  = sanitize_text_field( wp_unslash( $_REQUEST['yith-pos-register-session-nonce'] ?? '' ) );
			if ( $action && $nonce && wp_verify_nonce( $nonce, $action ) ) {
				$method = "handle_{$action}_action";
				if ( is_callable( array( $this, $method ) ) ) {
					$this->$method();
				}
			}
		}

		/**
		 * Update Session
		 */
		private function handle_update_action() {
			/**
			 * The register session.
			 *
			 * @var YITH_POS_Register_Session $register_session
			 */
			global $register_session;

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( $register_session ) {
				if ( ! current_user_can( 'yith_pos_manage_others_pos' ) ) {
					$store = yith_pos_get_store( $register_session->get_store_id() );
					if ( ! $store || ! in_array( get_current_user_id(), array_merge( $store->get_managers(), $store->get_cashiers() ), true ) ) {
						wp_die( esc_html__( 'Sorry, you are not allowed to edit this register session.', 'yith-point-of-sale-for-woocommerce' ) );
					}
				}

				if ( isset( $_REQUEST['note'] ) ) {
					$register_session->set_note( sanitize_textarea_field( wp_unslash( $_REQUEST['note'] ) ) );
				}

				$register_session->save();

				wp_safe_redirect( add_query_arg( array( 'updated' => 1 ), $register_session->get_edit_link() ) );
				exit;
			}

			// phpcs:enable
		}

		/**
		 * Download reports
		 */
		private function handle_download_reports_action() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$session_id       = absint( $_GET['session_id'] ?? 0 );
			$register_session = yith_pos_get_register_session( $session_id );

			if ( $register_session ) {
				if ( ! current_user_can( 'yith_pos_manage_others_pos' ) ) {
					$store = yith_pos_get_store( $register_session->get_store_id() );
					if ( ! $store || ! in_array( get_current_user_id(), array_merge( $store->get_managers(), $store->get_cashiers() ), true ) ) {
						wp_die( esc_html__( 'Sorry, you are not allowed to download this register session\'s reports.', 'yith-point-of-sale-for-woocommerce' ) );
					}
				}

				$reports = $register_session->is_closed() ? $register_session->get_report() : $register_session->generate_reports();
				$titles  = array(
					__( 'Entry', 'yith-point-of-sale-for-woocommerce' ),
					__( 'Value', 'yith-point-of-sale-for-woocommerce' ),
				);
				$rows    = array( $titles );

				foreach ( $reports as $report ) {
					$rows[] = array(
						$report['title'],
						$report['value'],
					);
				}
				$this->array_to_csv_download( $rows, "session_{$session_id}_reports.csv" );
			}
			// phpcs:enable
		}

		/**
		 * Download cashier reports
		 */
		private function handle_download_cashier_reports_action() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$session_id       = absint( $_GET['session_id'] ?? 0 );
			$cashier_id       = absint( $_GET['cashier_id'] ?? 0 );
			$register_session = yith_pos_get_register_session( $session_id );

			if ( $register_session && $cashier_id ) {
				$reports = $register_session->generate_reports( array( 'cashier' => $cashier_id ) );
				$titles  = array(
					__( 'Entry', 'yith-point-of-sale-for-woocommerce' ),
					__( 'Value', 'yith-point-of-sale-for-woocommerce' ),
				);
				$rows    = array( $titles );

				foreach ( $reports as $report ) {
					$rows[] = array(
						$report['title'],
						$report['value'],
					);
				}

				$this->array_to_csv_download( $rows, "session_{$session_id}_cashier_{$cashier_id}_reports.csv" );
			}
			// phpcs:enable
		}

		/** -------------------------------------------------
		 * CSV Export
		 * --------------------------------------------------
		 */

		/**
		 * Transform an array to CSV file
		 *
		 * @param array  $array     The array.
		 * @param string $filename  The file name.
		 * @param string $delimiter CSV delimiter.
		 */
		private function array_to_csv_download( $array, $filename = 'export.csv', $delimiter = ',' ) {
			self::download_headers( $filename );
			$f = fopen( 'php://output', 'w' );
			if ( in_array( get_option( 'blog_charset' ), array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' ), true ) ) {
				fputs( $f, "\xEF\xBB\xBF" ); // Add UTF-8 BOM (byte-order mark).
			}

			foreach ( $array as $line ) {
				fputcsv( $f, $line, $delimiter );
			}
			exit;
		}

		/**
		 * Download Headers
		 *
		 * @param string $filename The filename.
		 */
		private static function download_headers( $filename ) {
			$filename_array = explode( '.', $filename );
			$content_type   = strpos( $filename, '.' ) > 0 ? end( $filename_array ) : 'txt';

			header( 'X-Robots-Tag: noindex, nofollow', true );
			header( 'Content-Type: ' . $content_type . '; charset=' . get_option( 'blog_charset' ), true );
			header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		}
	}
}
