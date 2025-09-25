<?php
/**
 * REST API Server.
 * Handle loading the REST API and all REST API namespaces.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\RestApi
 */

namespace YITH\POS\RestApi;

defined( 'ABSPATH' ) || exit;

/**
 * Class Server
 *
 * @package YITH\POS\RestApi
 */
class Server {
	use \YITH_POS_Singleton_Trait;

	/**
	 * REST API namespaces and endpoints.
	 *
	 * @var array
	 */
	protected $controllers = array();

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
		add_filter( 'woocommerce_admin_rest_controllers', array( $this, 'wc_admin_rest_controllers' ), 10, 1 );
		add_filter( 'woocommerce_data_stores', array( __CLASS__, 'add_data_stores' ) );
	}

	/**
	 * Add data stores.
	 *
	 * @param array $data_stores Data stores.
	 *
	 * @return array
	 */
	public static function add_data_stores( $data_stores ) {
		return array_merge(
			$data_stores,
			array(
				'yith-pos-report-orders-stats'    => 'YITH\POS\RestApi\Reports\Orders\Stats\DataStore',
				'yith-pos-report-cashiers'        => 'YITH\POS\RestApi\Reports\Cashiers\DataStore',
				'yith-pos-report-payment-methods' => 'YITH\POS\RestApi\Reports\PaymentMethods\DataStore',
			)
		);
	}

	/**
	 * Add controllers
	 *
	 * @param array $controllers Controllers.
	 *
	 * @return array
	 */
	public function wc_admin_rest_controllers( $controllers ) {
		$controllers[] = 'YITH\POS\RestApi\Reports\Orders\Stats\Controller';
		$controllers[] = 'YITH\POS\RestApi\Reports\Cashiers\Controller';
		$controllers[] = 'YITH\POS\RestApi\Reports\PaymentMethods\Controller';

		return $controllers;
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		foreach ( $this->get_rest_namespaces() as $namespace => $controllers ) {
			foreach ( $controllers as $controller_name => $controller_class ) {
				$this->controllers[ $namespace ][ $controller_name ] = new $controller_class();
				$this->controllers[ $namespace ][ $controller_name ]->register_routes();
			}
		}
	}

	/**
	 * Get API namespaces - new namespaces should be registered here.
	 *
	 * @return array List of Namespaces and Main controller classes.
	 */
	protected function get_rest_namespaces() {
		return apply_filters(
			'yith_pos_rest_api_get_rest_namespaces',
			array(
				'yith-pos/v1' => $this->get_v1_controllers(),
			)
		);
	}

	/**
	 * List of controllers in the wc/v1 namespace.
	 *
	 * @return array
	 */
	public function get_v1_controllers() {
		return array(
			'orders-stats'      => 'YITH_POS_REST_Orders_Stats_Controller',
			'registers'         => 'YITH_POS_REST_Registers_Controller',
			'register-sessions' => 'YITH_POS_REST_Register_Sessions_Controller',
		);
	}
}
