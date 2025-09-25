<?php
/**
 * REST API order stats report query
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\RestApi\Reports\Orders\Stats
 */

namespace YITH\POS\RestApi\Reports\Orders\Stats;

defined( 'ABSPATH' ) || exit;

/**
 * Class Query
 *
 * @package YITH\POS\RestApi\Reports\Orders\Stats
 */
class Query extends \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\Query {

	/**
	 * Get revenue data based on the current query vars.
	 *
	 * @return array
	 * @throws \Exception The exception.
	 */
	public function get_data() {
		$args = apply_filters( 'woocommerce_reports_orders_stats_query_args', $this->get_query_vars() );

		/**
		 * The data store.
		 *
		 * @var DataStore $data_store
		 */
		$data_store = \WC_Data_Store::load( 'yith-pos-report-orders-stats' );
		$results    = $data_store->get_data( $args );

		return apply_filters( 'woocommerce_reports_orders_stats_select_query', $results, $args );
	}
}
