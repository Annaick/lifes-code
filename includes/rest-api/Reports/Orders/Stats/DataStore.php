<?php
/**
 * REST API order stats report data-store
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\RestApi\Reports\Orders\Stats
 */

namespace YITH\POS\RestApi\Reports\Orders\Stats;

defined( 'ABSPATH' ) || exit;

use function YITH\POS\RestApi\get_sql_where_clause_for_order_filters;

/**
 * Class DataStore
 *
 * @package YITH\POS\RestApi\Reports\Orders\Stats
 */
class DataStore extends \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore {

	/**
	 * Updates the totals and intervals database queries with parameters used for Orders report: categories, coupons and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 */
	protected function orders_stats_sql_filter( $query_args ) {
		$stats_table_name = self::get_db_table_name();

		$where = get_sql_where_clause_for_order_filters( $query_args, $stats_table_name, true );

		if ( $where ) {
			$this->total_query->add_sql_clause( 'where', "AND ( $where )" );
			$this->interval_query->add_sql_clause( 'where', "AND ( $where )" );
		}

		parent::orders_stats_sql_filter( $query_args );
	}
}
