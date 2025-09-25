<?php
/**
 * Options
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Options
 */

defined( 'YITH_POS' ) || exit();

return array(
	'registers-register-sessions' => array(
		'registers-register-sessions-tab' => array(
			'type'           => 'custom_tab',
			'action'         => 'yith_pos_register_sessions_tab',
			'show_container' => true,
		),
	),
);
