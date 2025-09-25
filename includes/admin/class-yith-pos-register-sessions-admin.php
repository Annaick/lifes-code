<?php
/**
 * Register session admin class.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Register_Sessions_Admin' ) ) {
	/**
	 * Class YITH_POS_Register_Sessions_Admin
	 *
	 */
	class YITH_POS_Register_Sessions_Admin {
		use YITH_POS_Singleton_Trait;

		/**
		 * YITH_POS_Register_Sessions_Admin constructor.
		 */
		protected function __construct() {
			add_action( 'wp_loaded', array( $this, 'init_globals' ) );

			add_action( 'yith_pos_register_sessions_tab', array( $this, 'register_sessions_tab' ) );
		}

		/**
		 * Init globals.
		 */
		public function init_globals() {
			global $pagenow, $register_session;
			if (
				'admin.php' === $pagenow && isset( $_GET['page'] ) && 'yith_pos_panel' === $_GET['page'] && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				yith_pos_is_admin_page( true, 'registers', 'registers-register-sessions' )
			) {
				$session_id       = absint( $_GET['session_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$register_session = yith_pos_get_register_session( $session_id );
			}
		}

		/**
		 * Register Sessions tab
		 */
		public function register_sessions_tab() {
			global $register_session;
			$page = ! ! $register_session ? 'edit' : 'list';

			if ( 'edit' === $page ) {
				yith_pos_get_view( 'panel/register-sessions/edit.php', compact( 'register_session' ) );
			} else {
				yith_pos_get_view( 'panel/register-sessions/list.php' );
			}
		}
	}
}
