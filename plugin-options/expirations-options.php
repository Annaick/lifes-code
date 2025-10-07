<?php
/**
 * Options - Expirations tab
 *
 * @package YITH\POS\Options
 */

defined( 'YITH_POS' ) || exit;

$expirations = array(
	'expirations' => array(
		'home' => array(
			'type'   => 'custom_tab',
			'action' => 'yith_pos_expirations_tab',
		),
	),
);

return apply_filters( 'yith_pos_panel_expirations_tab', $expirations );
