<?php
/**
 * Options
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Options
 */

defined( 'YITH_POS' ) || exit;

$receipts = array(
	'receipts' => array(
		'receipts_list' => array(
			'type'                  => 'post_type',
			'post_type'             => YITH_POS_Post_Types::RECEIPT,
			'wp-list-style'         => 'classic',
			'wp-list-auto-h-scroll' => true,
		),
	),
);

return apply_filters( 'yith_pos_panel_receipts_tab', $receipts );
