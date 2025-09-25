<?php
/**
 * REST API loader.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\RestApi
 */

namespace YITH\POS\RestApi;

defined( 'ABSPATH' ) || exit;

/**
 * Class Loader
 *
 * @package YITH\POS\RestApi
 */
class Loader {

	use \YITH_POS_Singleton_Trait;

	/**
	 * The server.
	 *
	 * @var Server
	 */
	private $server;

	/**
	 * Loader constructor.
	 */
	protected function __construct() {
		if ( yith_pos_is_wc_admin_enabled() ) {
			$this->load();
			$this->include_files();
			$this->init();
		}
	}

	/**
	 * Load.
	 */
	protected function load() {
		require_once __DIR__ . '/Server.php';
		$this->server = Server::get_instance();
	}

	/**
	 * Include files.
	 */
	protected function include_files() {
		// Functions.
		require_once __DIR__ . '/Utils/functions.php';

		// Controllers.
		$controller_files = array(
			'Version1' => array_keys( $this->server->get_v1_controllers() ),
		);

		foreach ( $controller_files as $version => $controllers ) {
			foreach ( $controllers as $controller ) {
				$filename = "class-yith-pos-rest-{$controller}-controller.php";
				$path     = "/Controllers/{$version}/$filename";
				require_once __DIR__ . $path;
			}
		}

		require_once __DIR__ . '/Reports/Orders/Stats/Controller.php';
		require_once __DIR__ . '/Reports/Orders/Stats/DataStore.php';
		require_once __DIR__ . '/Reports/Orders/Stats/Query.php';

		require_once __DIR__ . '/Reports/Cashiers/Controller.php';
		require_once __DIR__ . '/Reports/Cashiers/DataStore.php';
		require_once __DIR__ . '/Reports/Cashiers/Query.php';

		require_once __DIR__ . '/Reports/PaymentMethods/Controller.php';
		require_once __DIR__ . '/Reports/PaymentMethods/DataStore.php';
		require_once __DIR__ . '/Reports/PaymentMethods/Query.php';
	}

	/**
	 * Init.
	 */
	protected function init() {
		$this->server->init();
	}
}
