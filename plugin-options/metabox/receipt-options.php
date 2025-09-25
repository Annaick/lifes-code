<?php
/**
 * Options
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Options
 */

defined( 'YITH_POS' ) || exit;

$is_update    = isset( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$html_buttons = '<div class="yith-pos-receipt-action-buttons">';
if ( $is_update ) {
	$the_post_id = absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$html_buttons .= '<button class="yith-plugin-fw__button--primary yith-plugin-fw__button--xl yith-pos-save-receipt" >' . esc_html__( 'Update Receipt', 'yith-point-of-sale-for-woocommerce' ) . '</button>';
} else {
	$html_buttons .= '<input type="submit" name="publish" class="yith-plugin-fw__button--primary yith-plugin-fw__button--xl yith-pos-save-receipt" value="' . esc_html__( 'Save Receipt', 'yith-point-of-sale-for-woocommerce' ) . '">';
}
$html_buttons .= '</div>';

$receipt_tabs = array(
	'receipt_general_tab' => array(
		'label'  => __( 'General Settings', 'yith-point-of-sale-for-woocommerce' ),
		'fields' => array(
			'name'                => array(
				'type'              => 'text',
				'label'             => __( 'Receipt name', 'yith-point-of-sale-for-woocommerce' ),
				'extra_row_class'   => 'yith-pos-required-field-row',
				'desc'              => __( 'Enter a name to identify this receipt template', 'yith-point-of-sale-for-woocommerce' ),
				'class'             => 'yith-required-field',
				'custom_attributes' => 'required data-message="' . __( 'The receipt name is required.', 'yith-point-of-sale-for-woocommerce' ) . '"',
			),
			'enable_gift_receipt' => array(
				'type'  => 'onoff',
				'label' => __( 'Enable gift receipt', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Enable to add the option to print a gift receipt besides the regular receipt.', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => 'yes',
			),
			'show_sku'            => array(
				'type'  => 'onoff',
				'label' => __( 'Show SKU', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( "Enable this option to show the products' SKUs in this receipt.", 'yith-point-of-sale-for-woocommerce' ),
				'std'   => 'no',
			),
			'sku_label'           => array(
				'type'  => 'text',
				'label' => __( 'SKU label', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Enter the label for the SKU field.', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => __( 'SKU:', 'yith-point-of-sale-for-woocommerce' ),
				'deps'  => array(
					'id'    => '_show_sku',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
		),
	),
	'receipt_header_tab'  => array(
		'label'  => __( 'Receipt Header', 'yith-point-of-sale-for-woocommerce' ),
		'fields' => array(
			'logo'              => array(
				'type'             => 'media',
				'label'            => __( 'Logo', 'yith-point-of-sale-for-woocommerce' ),
				'allow_custom_url' => false,
				'std'              => YITH_POS_ASSETS_URL . '/images/logo-receipt.png',
				'desc'             => __( 'Upload your logo to customize the receipt. Supported image formats : gif, jpg, jpeg, png.', 'yith-point-of-sale-for-woocommerce' ),
			),
			'show_store_name'   => array(
				'type'  => 'onoff',
				'label' => __( 'Name', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Show or hide the Store name in this receipt', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => 'yes',
			),
			'show_vat'          => array(
				'type'  => 'onoff',
				'label' => __( 'Show VAT', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Show or hide the VAT number in this receipt', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => 'yes',
			),
			'vat_label'         => array(
				'type'  => 'text',
				'label' => __( 'VAT label', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Enter the label for VAT field. Default: VAT', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => __( 'VAT:', 'yith-point-of-sale-for-woocommerce' ),
				'deps'  => array(
					'id'    => '_show_vat',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
			'show_address'      => array(
				'type'  => 'onoff',
				'label' => __( 'Address', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Show or hide the Address of the store in this receipt', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => 'yes',
			),
			'show_contact_info' => array(
				'type'            => 'onoff',
				'label'           => __( 'Contact Info', 'yith-point-of-sale-for-woocommerce' ),
				'extra_row_class' => 'yith-pos-option-no-bottom-margin',
				'desc'            => __( 'Show or hide the Contact Info of the store in this receipt', 'yith-point-of-sale-for-woocommerce' ),
				'std'             => 'yes',
			),
			'show_phone'        => array(
				'type'            => 'checkbox',
				'label'           => '',
				'extra_row_class' => 'yith-pos-child-option yith-pos-option-no-bottom-margin',
				'desc-inline'     => __( 'Phone', 'yith-point-of-sale-for-woocommerce' ),
				'std'             => '1',
				'deps'            => array(
					'id'    => '_show_contact_info',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
			'show_email'        => array(
				'type'            => 'checkbox',
				'label'           => '',
				'extra_row_class' => 'yith-pos-child-option yith-pos-option-no-bottom-margin',
				'desc-inline'     => __( 'E-mail', 'yith-point-of-sale-for-woocommerce' ),
				'std'             => '1',
				'deps'            => array(
					'id'    => '_show_contact_info',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
			'show_fax'          => array(
				'type'            => 'checkbox',
				'label'           => '',
				'extra_row_class' => 'yith-pos-child-option yith-pos-option-no-bottom-margin',
				'desc-inline'     => __( 'Fax', 'yith-point-of-sale-for-woocommerce' ),
				'std'             => '1',
				'deps'            => array(
					'id'    => '_show_contact_info',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
			'show_website'      => array(
				'type'            => 'checkbox',
				'label'           => '',
				'extra_row_class' => 'yith-pos-child-option yith-pos-option-no-bottom-margin',
				'desc-inline'     => __( 'Website', 'yith-point-of-sale-for-woocommerce' ),
				'std'             => '1',
				'deps'            => array(
					'id'    => '_show_contact_info',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
			'show_social_info'  => array(
				'type'            => 'onoff',
				'label'           => __( 'Socials', 'yith-point-of-sale-for-woocommerce' ),
				'extra_row_class' => 'yith-pos-option-no-bottom-margin',
				'desc'            => __( 'Show or hide the socials of the store in this receipt', 'yith-point-of-sale-for-woocommerce' ),
				'std'             => 'yes',
			),
			'show_facebook'     => array(
				'type'            => 'checkbox',
				'label'           => '',
				'extra_row_class' => 'yith-pos-child-option yith-pos-option-no-bottom-margin',
				'desc-inline'     => __( 'Facebook', 'yith-point-of-sale-for-woocommerce' ),
				'std'             => '1',
				'deps'            => array(
					'id'    => '_show_social_info',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
			'show_twitter'      => array(
				'type'            => 'checkbox',
				'label'           => '',
				'extra_row_class' => 'yith-pos-child-option yith-pos-option-no-bottom-margin',
				'desc-inline'     => __( 'Twitter', 'yith-point-of-sale-for-woocommerce' ),
				'std'             => '1',
				'deps'            => array(
					'id'    => '_show_social_info',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
			'show_instagram'    => array(
				'type'            => 'checkbox',
				'label'           => '',
				'extra_row_class' => 'yith-pos-child-option yith-pos-option-no-bottom-margin',
				'desc-inline'     => __( 'Instagram', 'yith-point-of-sale-for-woocommerce' ),
				'std'             => '1',
				'deps'            => array(
					'id'    => '_show_social_info',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
			'show_youtube'      => array(
				'type'            => 'checkbox',
				'label'           => '',
				'extra_row_class' => 'yith-pos-child-option yith-pos-option-no-bottom-margin',
				'desc-inline'     => __( 'Youtube', 'yith-point-of-sale-for-woocommerce' ),
				'std'             => '1',
				'deps'            => array(
					'id'    => '_show_social_info',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
		),
	),
	'receipt_order_tab'   => array(
		'label'  => __( 'Order Info', 'yith-point-of-sale-for-woocommerce' ),
		'fields' => array(
			'show_order_date'      => array(
				'type'  => 'onoff',
				'label' => __( 'Show order date', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Show or hide the order date in this receipt.', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => 'yes',
			),
			'order_date_label'     => array(
				'type'  => 'text',
				'label' => __( 'Order date label', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Enter the label for the "order date" field.', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => __( 'Date:', 'yith-point-of-sale-for-woocommerce' ),
				'deps'  => array(
					'id'    => '_show_order_date',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
			'show_order_number'    => array(
				'type'  => 'onoff',
				'label' => __( 'Show order number', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Show or hide the order number in this receipt.', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => 'yes',
			),
			'order_number_label'   => array(
				'type'  => 'text',
				'label' => __( 'Order number label', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Enter the label for the "order number" field.', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => __( 'Order:', 'yith-point-of-sale-for-woocommerce' ),
				'deps'  => array(
					'id'    => '_show_order_number',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
			'show_order_customer'  => array(
				'type'  => 'onoff',
				'label' => __( 'Show customer name', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Show or hide the customer name in this receipt.', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => 'yes',
			),
			'order_customer_label' => array(
				'type'  => 'text',
				'label' => __( 'Customer label', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Enter the label for the "customer" field.', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => __( 'Customer:', 'yith-point-of-sale-for-woocommerce' ),
				'deps'  => array(
					'id'    => '_show_order_customer',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
			'show_shipping'        => array(
				'type'  => 'onoff',
				'label' => __( 'Show shipping details', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Show or hide the shipping details in this receipt.', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => 'no',
			),
			'shipping_label'       => array(
				'type'  => 'text',
				'label' => __( 'Shipping label', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Enter the label for the "shipping details" field.', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => __( 'Shipping:', 'yith-point-of-sale-for-woocommerce' ),
				'deps'  => array(
					'id'    => '_show_shipping',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
			'show_order_register'  => array(
				'type'  => 'onoff',
				'label' => __( 'Show register name', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Show or hide the register name in this receipt.', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => 'yes',
			),
			'order_register_label' => array(
				'type'  => 'text',
				'label' => __( 'Register label', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Enter the label for the "register" field.', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => __( 'Register:', 'yith-point-of-sale-for-woocommerce' ),
				'deps'  => array(
					'id'    => '_show_order_register',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
			'show_cashier'         => array(
				'type'  => 'onoff',
				'label' => __( 'Show cashier name', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Show or hide the cashier name in this receipt.', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => 'yes',
			),
			'cashier_label'        => array(
				'type'  => 'text',
				'label' => __( 'Cashier label', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Enter the label for the "cashier" field.', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => __( 'Cashier:', 'yith-point-of-sale-for-woocommerce' ),
				'deps'  => array(
					'id'    => '_show_cashier',
					'value' => 'yes',
					'type'  => 'hide',
				),
			),
		),
	),
	'receipt_footer_tab'  => array(
		'label'  => __( 'Receipt footer', 'yith-point-of-sale-for-woocommerce' ),
		'fields' => array(
			'receipt_footer' => array(
				'type'  => 'textarea',
				'label' => __( 'Footer text', 'yith-point-of-sale-for-woocommerce' ),
				'desc'  => __( 'Enter optional text for footer area in receipt template', 'yith-point-of-sale-for-woocommerce' ),
				'std'   => __( 'Thanks for your purchase', 'yith-point-of-sale-for-woocommerce' ),
			),
		),
	),
);

if ( wc_tax_enabled() ) {
	$show_including_tax                  = 'incl' === get_option( 'woocommerce_tax_display_cart' );
	$show_itemized_tax_in_receipt        = apply_filters( 'yith_pos_show_itemized_tax_in_receipt', false );
	$show_price_including_tax_in_receipt = apply_filters( 'yith_pos_show_price_including_tax_in_receipt', $show_including_tax );
	$show_tax_row_in_receipt             = apply_filters( 'yith_pos_show_tax_row_in_receipt', wc_tax_enabled() && ! $show_price_including_tax_in_receipt );

	yith_pos_deprecated_filter( 'yith_pos_show_itemized_tax_in_receipt', '2.0.0' );
	yith_pos_deprecated_filter( 'yith_pos_show_price_including_tax_in_receipt', '2.0.0' );
	yith_pos_deprecated_filter( 'yith_pos_show_tax_row_in_receipt', '2.0.0' );

	$general_tax_options = array(
		'show_prices_including_tax' => array(
			'type'                  => 'onoff',
			'label'                 => __( 'Show prices including tax', 'yith-point-of-sale-for-woocommerce' ),
			'desc'                  => __( 'Show prices including tax in this receipt.', 'yith-point-of-sale-for-woocommerce' ),
			'std'                   => $show_price_including_tax_in_receipt ? 'yes' : 'no',
			'disable_if_has_filter' => 'yith_pos_show_price_including_tax_in_receipt',
			'data'                  => array(
				'update-container-data' => 'show-prices-including-tax',
			),
		),
		'show_tax_details'          => array(
			'type'                  => 'onoff',
			'label'                 => __( 'Show tax details', 'yith-point-of-sale-for-woocommerce' ),
			'desc'                  => __( 'Show tax details in this receipt.', 'yith-point-of-sale-for-woocommerce' ),
			'std'                   => $show_tax_row_in_receipt ? 'yes' : 'no',
			'disable_if_has_filter' => 'yith_pos_show_tax_row_in_receipt',
			'deps'                  => array(
				'id'    => '_show_prices_including_tax',
				'value' => 'yes',
				'type'  => 'hide',
			),
			'data'                  => array(
				'update-container-data' => 'show-tax-details',
			),
		),
		'show_taxes'                => array(
			'type'                  => 'radio',
			'label'                 => __( 'Show taxes', 'yith-point-of-sale-for-woocommerce' ),
			'desc'                  => __( 'Choose how to show taxes in this receipt.', 'yith-point-of-sale-for-woocommerce' ),
			'options'               => array(
				'total'    => __( 'as a single total', 'yith-point-of-sale-for-woocommerce' ),
				'itemized' => __( 'itemized', 'yith-point-of-sale-for-woocommerce' ),
			),
			'std'                   => $show_itemized_tax_in_receipt ? 'itemized' : 'total',
			'disable_if_has_filter' => 'yith_pos_show_itemized_tax_in_receipt',
			'data'                  => array(
				'update-container-data'  => 'show-taxes',
				'yith-pos-multiple-deps' => wp_json_encode(
					array(
						array(
							'_show_prices_including_tax' => 'yes',
							'_show_tax_details'          => 'yes',
						),
						array(
							'_show_prices_including_tax' => 'no',
						),
					)
				),
			),
		),
	);

	foreach ( $general_tax_options as $key => $option ) {
		$filter = $option['disable_if_has_filter'] ?? '';
		if ( $filter ) {
			$value = $option['std'] ?? '';
			unset( $option['disable_if_has_filter'] );

			if ( has_filter( $filter ) ) {
				$real_option = $option;
				unset( $real_option['label'] );
				$real_option['value'] = $value;

				ob_start();
				?>
				<label><?php echo esc_html( $option['label'] ); ?></label>
				<?php
				$message = sprintf(
					// translators: %s is the name of the WordPress filter.
					esc_html__( 'You cannot edit this field because you have a custom code using the following filter %s. If you want to use this option instead, please remove that custom code.', 'yith-point-of-sale-for-woocommerce' ),
					'<code>' . esc_html( $filter ) . '</code>'
				);
				yith_plugin_fw_get_component(
					array(
						'type'        => 'notice',
						'notice_type' => 'warning',
						'message'     => $message,
						'dismissible' => false,
					)
				);
				?>
				<div class="yith-disabled">
					<?php yith_plugin_fw_get_field( $real_option, true ); ?>
				</div>
				<div class="clear"></div>
				<span class="description"><?php echo wp_kses_post( $option['desc'] ?? '' ); ?></span>
				<?php

				$general_tax_options[ $key ] = array(
					'type'  => 'html',
					'label' => ' ',
					'html'  => ob_get_clean(),
				);
			}
		}
	}

	$receipt_tabs['receipt_general_tab']['fields'] = array_merge( $receipt_tabs['receipt_general_tab']['fields'], $general_tax_options );
}

$save = array(
	'yith_pos_receipts_save' => array(
		'label' => '',
		'type'  => 'html',
		'html'  => $html_buttons,
	),
);

foreach ( $receipt_tabs as $tab_key => &$the_tab ) {
	$the_tab['fields'] = array_merge( $the_tab['fields'], $save );
}

return array(
	'yith-pos-receipt'         => array(
		'label'    => __( 'Edit', 'yith-point-of-sale-for-woocommerce' ),
		'pages'    => YITH_POS_Post_Types::RECEIPT,
		'context'  => 'normal',
		'priority' => 'high',
		'class'    => yith_set_wrapper_class(),
		'tabs'     =>
			$receipt_tabs,
	),
	'yith-pos-receipt-preview' => array(
		'label'    => __( 'Receipt Preview', 'yith-point-of-sale-for-woocommerce' ),
		'pages'    => YITH_POS_Post_Types::RECEIPT,
		'context'  => 'normal',
		'priority' => 'default',
		'class'    => yith_set_wrapper_class(),
		'tabs'     => array(
			'preview' => array(
				'label'  => __( 'Print Preview', 'yith-point-of-sale-for-woocommerce' ),
				'fields' => array(
					'receipt_preview_title'   => array(
						'type' => 'title',
						'desc' => '',
					),
					'receipt_preview_content' => array(
						'label'  => '',
						'type'   => 'custom',
						'action' => 'yith_pos_preview_receipt',
					),
					'receipt_preview_print'   => array(
						'label' => '',
						'type'  => 'html',
						'html'  => "<div style='text-align: center'><span id='print_receipt' class='button-primary'>" . __( 'Print Example', 'yith-point-of-sale-for-woocommerce' ) . '</span></div>',
					),
				),
			),
		),
	),
);
