<?php
/**
 * Customization Options
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Customization
 */

defined( 'YITH_POS' ) || exit;

$customization = array(
	'customization' => array(
		'settings_login_section_start'                     => array(
			'name' => __( 'Login page', 'yith-point-of-sale-for-woocommerce' ),
			'type' => 'title',
		),
		'settings_pos_page'                                => array(
			'name'  => __( 'Login page', 'yith-point-of-sale-for-woocommerce' ),
			'desc'  => __( 'Select the page of login', 'yith-point-of-sale-for-woocommerce' ),
			'id'    => 'settings_pos_page',
			'type'  => 'single_select_page',
			'class' => 'wc-enhanced-select',
			'css'   => 'min-width:300px;',
		),
		'settings_login_logo'                              => array(
			'name'             => __( 'Login logo', 'yith-point-of-sale-for-woocommerce' ),
			'desc'             => __( 'Select logo for login form', 'yith-point-of-sale-for-woocommerce' ),
			'type'             => 'yith-field',
			'yith-type'        => 'media',
			'allow_custom_url' => false,
			'id'               => 'yith_pos_login_logo',
			'default'          => YITH_POS_ASSETS_URL . '/images/logo-pos.png',
		),
		'settings_login_background_image'                  => array(
			'name'             => __( 'Login background image', 'yith-point-of-sale-for-woocommerce' ),
			'desc'             => __( 'Select image for background login page', 'yith-point-of-sale-for-woocommerce' ),
			'type'             => 'yith-field',
			'yith-type'        => 'media',
			'allow_custom_url' => false,
			'id'               => 'yith_pos_login_background_image',
		),
		'settings_login_background_color'                  => array(
			'name'      => __( 'Login background color', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Select the background color for login page', 'yith-point-of-sale-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'colorpicker',
			'id'        => 'yith_pos_login_background_color',
			'default'   => '#707070',
		),
		'settings_login_section_end'                       => array(
			'type' => 'sectionend',
		),
		'settings_color_start'                             => array(
			'name' => __( 'Colors', 'yith-point-of-sale-for-woocommerce' ),
			'type' => 'title',
		),
		'settings_registers_colors_primary'                => array(
			'name'      => __( 'Primary color', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Select the primary color for Registers', 'yith-point-of-sale-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'colorpicker',
			'id'        => 'yith_pos_registers_primary',
			'default'   => '#09adaa',
		),
		'settings_registers_colors_secondary'              => array(
			'name'      => __( 'Secondary color', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Select the secondary color for Registers', 'yith-point-of-sale-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'colorpicker',
			'id'        => 'yith_pos_registers_secondary',
			'default'   => '#c65338',
		),
		'settings_registers_colors_products_background'    => array(
			'name'      => __( 'Products grid background', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Select the background color for products grid', 'yith-point-of-sale-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'colorpicker',
			'id'        => 'yith_pos_registers_products_background',
			'default'   => '#eaeaea',
		),
		'settings_registers_colors_products_title_bg'      => array(
			'name'      => __( 'Product title background', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Select the background color for product title', 'yith-point-of-sale-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'colorpicker',
			'id'        => 'yith_pos_registers_products_title_background',
			'default'   => 'rgba(67, 67, 67, .75)',
		),
		'settings_registers_colors_save_cart_background'   => array(
			'name'      => __( 'Saved cart buttons background', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Select the background color for saved cart buttons', 'yith-point-of-sale-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'colorpicker',
			'id'        => 'yith_pos_registers_save_cart_background',
			'default'   => '#e09914',
		),
		'settings_registers_colors_pay_button_background'  => array(
			'name'      => __( 'Pay button background', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Select the background color for pay button', 'yith-point-of-sale-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'colorpicker',
			'id'        => 'yith_pos_registers_pay_button_background',
			'default'   => '#a0a700',
		),
		'settings_registers_colors_note_button_background' => array(
			'name'      => __( 'Note buttons background', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Select the background color for note button', 'yith-point-of-sale-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'colorpicker',
			'id'        => 'yith_pos_registers_note_button_background',
			'default'   => '#4d4d4d',
		),
		'settings_registers_colors_header_bar_background'  => array(
			'name'      => __( 'Header background', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Select the background color for header', 'yith-point-of-sale-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'colorpicker',
			'id'        => 'yith_pos_registers_header_bar_background',
			'default'   => '#435756',
		),
		'settings_color_end'                               => array(
			'type' => 'sectionend',
		),
	),
);

return apply_filters( 'yith_pos_panel_customization_tab', $customization );
