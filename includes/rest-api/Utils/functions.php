<?php
/**
 * REST API functions
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\RestApi
 */

namespace YITH\POS\RestApi;

defined( 'ABSPATH' ) || exit;

/**
 * Get SQL clauses for filters.
 *
 * @param array  $query_args                Query args.
 * @param string $table_name                The table name.
 * @param false  $maybe_include_order_check Set true to include order check.
 *
 * @return object
 */
function get_sql_clauses_for_filters( $query_args, $table_name, $maybe_include_order_check = false ) {

	$clauses = (object) array(
		'from'  => '',
		'where' => '',
	);

	$meta_value = 0;
	$meta_key   = '';

	$register = isset( $query_args['register'] ) ? absint( $query_args['register'] ) : 0;
	$store    = $query_args['store'] ? absint( $query_args['store'] ) : 0;

	if ( $register ) {
		$meta_value = $register;
		$meta_key   = '_yith_pos_register';
	} elseif ( $store ) {
		$meta_value = $store;
		$meta_key   = '_yith_pos_store';
	} elseif ( $maybe_include_order_check ) {
		$meta_value = 1;
		$meta_key   = '_yith_pos_order';
	}

	if ( $meta_value && $meta_key ) {
		global $wpdb;
		$meta_table     = $wpdb->postmeta;
		$meta_alias     = 'pm_filters_clause';
		$meta_column_id = 'post_id';

		if ( \yith_plugin_fw_is_wc_custom_orders_table_usage_enabled() ) {
			$meta_table     = $wpdb->prefix . 'wc_orders_meta';
			$meta_alias     = 'wc_om_filters_clause';
			$meta_column_id = 'order_id';
		}

		// Include the 'parent_id' to consider also Refunds, since in case of refunds, the POS meta are set in the parent order.
		$clauses->from  = " JOIN {$meta_table} as {$meta_alias} ON ( {$meta_alias}.{$meta_column_id} = {$table_name}.order_id OR {$meta_alias}.{$meta_column_id} = {$table_name}.parent_id )";
		$clauses->where = " {$meta_alias}.meta_key = '{$meta_key}' AND {$meta_alias}.meta_value = '{$meta_value}'";
	}

	return $clauses;
}

/**
 * Get 'where' clause for order filters.
 *
 * @param array  $query_args                Query args.
 * @param string $table_name                The table name.
 * @param false  $maybe_include_order_check Set true to include order check.
 *
 * @return string
 */
function get_sql_where_clause_for_order_filters( $query_args, $table_name, $maybe_include_order_check = false ) {

	$where      = '';
	$meta_value = 0;
	$meta_key   = '';

	$register = isset( $query_args['register'] ) ? absint( $query_args['register'] ) : 0;
	$store    = $query_args['store'] ? absint( $query_args['store'] ) : 0;

	if ( $register ) {
		$meta_value = $register;
		$meta_key   = '_yith_pos_register';
	} elseif ( $store ) {
		$meta_value = $store;
		$meta_key   = '_yith_pos_store';
	} elseif ( $maybe_include_order_check ) {
		$meta_value = 1;
		$meta_key   = '_yith_pos_order';
	}

	if ( $meta_value && $meta_key ) {
		global $wpdb;

		if ( \yith_plugin_fw_is_wc_custom_orders_table_usage_enabled() ) {
			$orders_select = $wpdb->prepare( "SELECT order_id FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key=%s AND meta_value=%d", $meta_key, $meta_value );
		} else {
			$orders_select = $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%d", $meta_key, $meta_value );
		}

		return "{$table_name}.order_id IN ({$orders_select}) OR {$table_name}.parent_id IN ({$orders_select})";
	}

	return $where;
}
