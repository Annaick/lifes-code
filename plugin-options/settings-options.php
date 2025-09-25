<?php
/**
 * Options
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Options
 */

defined( 'YITH_POS' ) || exit;

$indexed_payment_methods = yith_pos_get_indexed_payment_methods( true );

$settings = array(
	'settings' => array(
		'settings_section_start'                           => array(
			'type' => 'sectionstart',
		),
		'settings_registers_title'                         => array(
			'title' => _x( 'General Settings', 'Panel: page title', 'yith-point-of-sale-for-woocommerce' ),
			'type'  => 'title',
			'desc'  => '',
		),
		'settings_registers_audio_enabled'                 => array(
			'name'      => __( 'Enable sound effect', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Enable or disable the sound effect when a product is added to cart', 'yith-point-of-sale-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'id'        => 'yith_pos_audio_enabled',
			'default'   => 'yes',
		),
		'settings_registers_close_modals_on_bg'            => array(
			'name'      => __( 'Close popup windows when clicking on the background', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'If enabled, all popup windows will be closed by clicking on the background', 'yith-point-of-sale-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'id'        => 'yith_pos_close_modals_when_clicking_on_background',
			'default'   => 'yes',
		),
		'vat_field_label'                                  => array(
			'name'              => __( 'VAT number field label', 'yith-point-of-sale-for-woocommerce' ),
			'desc'              => __( 'Choose the label for the VAT number field shown in POS and on frontend.', 'yith-point-of-sale-for-woocommerce' ),
			'type'              => 'yith-field',
			'yith-type'         => 'text',
			'id'                => 'yith_pos_vat_field_label',
			'default'           => '',
			'custom_attributes' => array(
				'placeholder' => __( 'VAT number', 'yith-point-of-sale-for-woocommerce' ),
			),
		),
		'show_vat_field_on_frontend'                       => array(
			'name'      => __( 'Show the VAT number field on frontend', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'If enabled, the VAT number field will also show on WooCommerce Checkout and My Account pages.', 'yith-point-of-sale-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'id'        => 'yith_pos_show_vat_field_on_frontend',
			'default'   => 'yes',
		),
		'max_product_search_results'                       => array(
			'name'      => __( 'Max number of results when searching products', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Set the maximum number of results to be shown when searching products in POS.', 'yith-point-of-sale-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'number',
			'id'        => 'yith_pos_max_product_search_results',
			'default'   => 10,
			'min'       => 1,
			'max'       => 100,
			'step'      => 1,
		),
		'settings_section_end'                             => array(
			'type' => 'sectionend',
		),
		'settings_gateways_section_start'                  => array(
			'name' => __( 'Payment method settings', 'yith-point-of-sale-for-woocommerce' ),
			'type' => 'title',
		),
		'payment_methods'                                  => array(
			'id'        => 'yith_pos_general_gateway_enabled',
			'yith-type' => 'checkbox-array',
			'type'      => 'yith-field',
			'name'      => __( 'Payment methods', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Select the global payment methods. These methods can be overridden for every single Register.', 'yith-point-of-sale-for-woocommerce' ),
			'class'     => 'yith-pos-register-payment-methods no-bottom',
			'options'   => $indexed_payment_methods,
			'std'       => array_keys( $indexed_payment_methods ),
		),
		'settings_gateways_section_start_end'              => array(
			'type' => 'sectionend',
		),
		'settings_presets_section_start'                   => array(
			'name' => __( 'Preset settings', 'yith-point-of-sale-for-woocommerce' ),
			'type' => 'title',
		),
		'number_keyboard_presets'                          => array(
			'id'        => 'yith_pos_numeric_controller_discount_presets',
			'yith-type' => 'presets',
			'type'      => 'yith-field',
			'name'      => __( 'Number Keyboard presets', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Set the percentage discount presets within the number keyboard', 'yith-point-of-sale-for-woocommerce' ),
			'slot_num'  => 4,
			'std'       => array( 5, 10, 15, 20 ),
			'step'      => 1,
			'min'       => 1,
			'max'       => 100,
		),
		'fee_and_discount_presets'                         => array(
			'id'        => 'yith_pos_fee_and_discount_presets',
			'yith-type' => 'presets',
			'type'      => 'yith-field',
			'name'      => __( 'Fee and Discount presets', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Set the percentage discount presets within the fee and discount editor', 'yith-point-of-sale-for-woocommerce' ),
			'slot_num'  => 5,
			'std'       => array( 5, 10, 15, 20, 50 ),
			'step'      => 1,
			'min'       => 1,
			'max'       => 100,
		),
		'settings_presets_section_end'                     => array(
			'type' => 'sectionend',
		),
		'settings_stock_start'                             => array(
			'name' => __( 'Stock Management', 'yith-point-of-sale-for-woocommerce' ),
			'type' => 'title',
		),
		'settings_show_stock_on_pos'                       => array(
			'name'      => __( 'Show stock on register', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Choose if to show the stock count on the register', 'yith-point-of-sale-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'id'        => 'yith_pos_show_stock_on_pos',
			'default'   => 'no',
		),
		'settings_multistock_enabled'                      => array(
			'name'      => __( 'Enable multistock', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => __( 'Choose if enable or disable the multistock option', 'yith-point-of-sale-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'id'        => 'yith_pos_multistock_enabled',
			'default'   => 'yes',
		),
		'settings_multistock_condition'                    => array(
			'name'      => __( 'Products of a store without stock are', 'yith-point-of-sale-for-woocommerce' ),
			'desc'      => implode(
				'<br />',
				array(
					__( 'Choose how to manage the purchase of products when no stock value has been set for a specific store.', 'yith-point-of-sale-for-woocommerce' ),
					__( "When you enable the multi-stock option, you'll have to set the product stock for every store. If one of the store stock is not specified, then, this settings will apply.", 'yith-point-of-sale-for-woocommerce' ),
				)
			),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'id'        => 'yith_pos_multistock_condition',
			'options'   => array(
				'allowed'     => __( 'purchasable without stock management', 'yith-point-of-sale-for-woocommerce' ),
				'general'     => __( 'purchasable from the general stock', 'yith-point-of-sale-for-woocommerce' ),
				'not_allowed' => __( 'non-purchasable', 'yith-point-of-sale-for-woocommerce' ),
			),
			'default'   => 'not_allowed',
			'deps'      => array(
				'id'    => 'yith_pos_multistock_enabled',
				'value' => 'yes',
			),
		),
		'settings_stock_end'                               => array(
			'type' => 'sectionend',
		),
	),
);

return apply_filters( 'yith_pos_panel_settings_tab', $settings );
