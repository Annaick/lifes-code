<?php
/**
 * Functions
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Functions
 */

defined( 'YITH_POS' ) || exit;

if ( ! function_exists( 'yith_pos_get_register_session' ) ) {
	/**
	 * Retrieve a register session.
	 *
	 * @param int|YITH_POS_Register_Session $session The session.
	 *
	 * @return false|YITH_POS_Register_Session
	 */
	function yith_pos_get_register_session( $session ) {
		try {
			return ! ! $session ? new YITH_POS_Register_Session( $session ) : false;
		} catch ( Exception $e ) {
			return false;
		}
	}
}

if ( ! function_exists( 'yith_pos_get_register_sessions' ) ) {
	/**
	 * Get register sessions.
	 *
	 * @param array $args Arguments.
	 *
	 * @return array|object
	 */
	function yith_pos_get_register_sessions( array $args = array() ) {
		return YITH_POS_Register_Session_Data_Store::query( $args );
	}
}
