<?php
/**
 * Plugins list for integrations.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

return array(
	'barcodes'                  => array(
		'name'        => 'YITH WooCommerce Barcodes and QR Codes',
		'title'       => 'Barcodes and QR Codes',
		'icon'        => '//yithemes.com/wp-content/uploads/2019/05/yith-woocommerce-barcodes-and-qr-codes.svg',
		'landing_uri' => '//yithemes.com/themes/plugins/yith-woocommerce-barcodes-and-qr-codes/',
		'description' => __( 'Generate barcodes for your products, in the product details page and use them to scan products in POS.', 'yith-point-of-sale-for-woocommerce' ),
	),
	'customize-my-account-page' => array(
		'show' => false,
	),
	'pdf-invoice'               => array(
		'show'            => false,
		'premium'         => 'YITH_YWPI_PREMIUM',
		'version'         => 'YITH_YWPI_VERSION',
		'min_version'     => '3.7.0',
		'version_compare' => '>=',
	),
	'wpml'                      => array(
		'show' => false,
	),
);
