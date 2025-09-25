<?php
/**
 * REST API Orders Stats controller
 *
 * @package YITH\POS\RestApi
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Orders Stats controller class.
 *
 * @package YITH\POS\RestApi
 */
class YITH_POS_REST_Orders_Stats_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'yith-pos/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'orders-stats';

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'args'   => array(
					'id' => array(
						'type' => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Retrieves order stats.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$format       = $request['format'] ?? '';
		$allowed_args = array( 'status', 'date_created', 'cashier', 'register', 'store', 'order__in' );
		$args         = array();

		foreach ( $allowed_args as $key ) {
			if ( isset( $request[ $key ] ) ) {
				$args[ $key ] = $request[ $key ];
			}
		}

		$stats_query = new YITH_POS_Order_Stats_Query( $args );
		$stats       = $stats_query->get_stats();

		foreach ( $stats as $id => &$stat ) {
			$stat['id'] = $id;
		}

		if ( 'flat' === $format ) {
			$stats = array_values( $stats );
		}

		return rest_ensure_response( $stats );
	}

	/**
	 * Checks if a given request has access to manage items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'yith_pos_view_orders' ) ) {
			return new WP_Error( 'yith_pos_rest_cannot_read_order_stats', __( 'Sorry, you cannot view this resource.', 'yith-point-of-sale-for-woocommerce' ), array( 'status' => 403 ) );
		}

		return true;
	}
}
