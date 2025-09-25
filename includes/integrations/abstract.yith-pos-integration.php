<?php
/**
 * Base Integration class.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

/**
 * Class YITH_POS_Integration
 *
 * @abstract
 * @since   1.0.6
 */
abstract class YITH_POS_Integration {

	use YITH_POS_Singleton_Trait;

	/**
	 * Constructor
	 */
	protected function __construct() {
	}

	/**
	 * Return true if the integration is active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return true;
	}
}
