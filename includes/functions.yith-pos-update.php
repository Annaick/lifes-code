<?php
/**
 * Update Functions
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Functions
 */

defined( 'YITH_POS' ) || exit;

/**
 * Update products' catalog visibility to replace the yith_pos visibility with the WooCommerce 'hidden' one.
 *
 * @return bool False to stop the execution, true otherwise.
 */
function yith_pos_update_200_update_product_catalog_visibility() {
	$products = get_posts(
		array(
			'posts_per_page' => 10,
			'post_type'      => 'product',
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'slug',
					'terms'    => array( 'yith_pos' ),
				),
			),
		)
	);

	if ( ! $products ) {
		// Stop the execution, since there are no more products to update.
		return false;
	}

	foreach ( $products as $product_id ) {
		wp_set_post_terms( $product_id, array( 'exclude-from-search', 'exclude-from-catalog' ), 'product_visibility', false );
	}

	// Next execution!
	return true;
}

/**
 * Remove the "YITH POS only" catalog visibility.
 */
function yith_pos_update_200_remove_yith_pos_catalog_visibility() {
	$term = get_term_by( 'slug', 'yith_pos', 'product_visibility' );
	if ( $term ) {
		wp_delete_term( $term->term_id, 'yith_pos' );
	}
}

/**
 * Update DB Version.
 */
function yith_pos_update_200_db_version() {
	YITH_POS_Install::update_db_version( '2.0.0' );
}

/**
 * Update DB Version.
 */
function yith_pos_update_320_db_version() {
	YITH_POS_Install::update_db_version( '3.2.0' );
}
