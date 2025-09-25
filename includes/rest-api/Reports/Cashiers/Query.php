<?php
/**
 * REST API cashiers report query.
 * Class for parameter-based Cashiers Report querying
 * Usage example:
 * ```$args = array(
 *          'before'       => '2018-07-19 00:00:00',
 *          'after'        => '2018-07-05 00:00:00',
 *          'page'         => 2,
 *          'order'        => 'desc',
 *          'orderby'      => 'items_sold',
 *         );
 * $report = new \YITH\POS\RestApi\Reports\Cashiers\Query( $args );
 * $mydata = $report->get_data();```
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\RestApi\Reports\Cashiers
 */

namespace YITH\POS\RestApi\Reports\Cashiers;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\API\Reports\Query as ReportsQuery;

/**
 * Class Query
 *
 * @package YITH\POS\RestApi\Reports\Cashiers
 */
class Query extends ReportsQuery {

	const REPORT_NAME = 'yith-pos-report-cashiers';

	/**
	 * Valid fields for Cashiers report.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array();
	}

	/**
	 * Get categories data based on the current query vars.
	 *
	 * @return array
	 * @throws \Exception The exception.
	 */
	public function get_data() {

		$args = apply_filters( 'yith_pos_reports_cashiers_query_args', $this->get_query_vars() );
		/**
		 * The data store.
		 *
		 * @var DataStore $data_store
		 */
		$data_store = \WC_Data_Store::load( self::REPORT_NAME );
		$results    = $data_store->get_data( $args );

		return apply_filters( 'yith_pos_reports_cashiers_select_query', $results, $args );
	}
}
