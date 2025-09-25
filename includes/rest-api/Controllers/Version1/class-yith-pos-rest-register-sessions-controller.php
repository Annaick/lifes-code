<?php
/**
 * REST API Register Sessions controller
 *
 * @package YITH\POS\RestApi
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Register Sessions controller class.
 *
 * @package YITH\POS\RestApi
 */
class YITH_POS_REST_Register_Sessions_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'register-sessions';

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/generate_reports',
			array(
				'args'   => array(
					'id' => array(
						'type' => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_generated_reports' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Retrieves generated reports of a specific register session.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_generated_reports( $request ) {
		$id      = $request['id'];
		$session = yith_pos_get_register_session( $id );

		if ( $session ) {
			$format  = $request['format'] ?? '';
			$reports = $session->generate_reports();

			foreach ( $reports as $id => &$report ) {
				$report['id'] = $id;
			}

			if ( 'flat' === $format ) {
				$reports = array_values( $reports );
			}

			return rest_ensure_response( $reports );
		}

		return new WP_Error( 'yith_pos_rest_register_session_not_found', __( 'Register session not found!', 'yith-point-of-sale-for-woocommerce' ), array( 'status' => 403 ) );
	}

	/**
	 * Checks if a given request has access to manage items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		$id              = $request['id'] ?? 0;
		$has_permissions = yith_pos_is_admin_and_can_use_pos();

		if ( ! $has_permissions ) {
			$session = yith_pos_get_register_session( $id );
			if ( $session ) {
				$register_id     = $session->get_register_id();
				$has_permissions = ! ! $register_id && yith_pos_user_can_use_register( $register_id );
			} else {
				return new WP_Error( 'yith_pos_rest_register_session_not_found', __( 'Register session not found!', 'yith-point-of-sale-for-woocommerce' ), array( 'status' => 403 ) );
			}
		}

		if ( ! $has_permissions ) {
			return new WP_Error( 'yith_pos_rest_cannot_read_register_session', __( 'Sorry, you cannot view this resource.', 'yith-point-of-sale-for-woocommerce' ), array( 'status' => 403 ) );
		}

		return true;
	}
}
