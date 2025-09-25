<?php
/**
 * "Customize My Account Page" integration class.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

/**
 * Class YITH_POS_Customize_My_Account_Page_Integration
 *
 * @since   1.0.6
 */
class YITH_POS_Customize_My_Account_Page_Integration extends YITH_POS_Integration {
	/**
	 * YITH_POS_Customize_My_Account_Page_Integration constructor.
	 */
	protected function __construct() {
		parent::__construct();

		add_filter( 'ywcmap_skip_verification', array( $this, 'filter_skip_verification' ), 10, 1 );
	}

	/**
	 * Maybe skip verification.
	 *
	 * @param string $verification Verification flag.
	 *
	 * @return string
	 */
	public function filter_skip_verification( $verification ) {
		$pos_request = wc_clean( wp_unslash( $request['yith_pos_request'] ?? '' ) );
		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && 'create-customer' === $pos_request ) {
			$verification = 'yes';
		}

		return $verification;
	}

}
