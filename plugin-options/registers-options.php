<?php
/**
 * Options
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Options
 */

defined( 'YITH_POS' ) || exit;

$registers = array(
	'registers' => array(
		'registers-tabs' => array(
			'type'     => 'multi_tab',
			'sub-tabs' => array(
				'registers-all-registers'     => array(
					'title'       => __( 'All Registers', 'yith-point-of-sale-for-woocommerce' ),
					'description' => __( 'Manage your stores\' registers. Note: You can create new registers only on the store creation/edit page ', 'yith-point-of-sale-for-woocommerce' ),
				),
				'registers-register-sessions' => array(
					'title'       => __( 'Register Sessions', 'yith-point-of-sale-for-woocommerce' ),
					'description' => __( 'An overview of the registers\' opening and closing activities', 'yith-point-of-sale-for-woocommerce' ),
				),
			),
		),
	),
);

return apply_filters( 'yith_pos_panel_registers_tab', $registers );
