<?php
/**
 * Options - Stock tab
 *
 * @package YITH\POS\Options
 */

defined( 'YITH_POS' ) || exit;

$stock = array(
	'stock' => array(
		'home' => array(
			'type'   => 'custom_tab',
			'action' => 'yith_pos_stock_tab',
		),
	),
);

return apply_filters( 'yith_pos_panel_stock_tab', $stock );


