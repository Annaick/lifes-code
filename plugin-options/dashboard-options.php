<?php
/**
 * Options
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Options
 */

defined( 'YITH_POS' ) || exit;

$dashboard = array(
	'dashboard' => array(
		'home' => array(
			'type'   => 'custom_tab',
			'action' => 'yith_pos_dashboard_tab',
		),
	),
);

return apply_filters( 'yith_pos_panel_dashboard_tab', $dashboard );
