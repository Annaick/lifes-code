<?php
/**
 * Products Class.
 * Handle products.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;


if ( ! class_exists( 'YITH_POS_Products' ) ) {
	/**
	 * Class YITH_POS_Products
	 *
	 * @deprecated 2.0.0
	 */
	class YITH_POS_Products {

		use YITH_POS_Singleton_Trait;

		/**
		 * YITH_POS_Products constructor.
		 *
		 * @deprecated 2.0.0
		 */
		private function __construct() {
			yith_pos_deprecated_function( 'YITH_POS_Products', '2.0.0' );
		}

		/**
		 * Handle deprecation of methods.
		 *
		 * @param string $name      Method name.
		 * @param array  $arguments Arguents.
		 */
		public function __call( $name, $arguments ) {
			yith_pos_deprecated_function( 'YITH_POS_Products::' . $name, '2.0.0' );

			return $arguments[0] ?? false;
		}
	}
}
