<?php
/**
 * Receipt preview template field.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Views
 */

defined( 'YITH_POS' ) || exit();

global $post_id;
$receipt = yith_pos_get_receipt( ! ! $post_id && is_numeric( $post_id ) ? $post_id : false );
$logo    = $receipt->get_logo();

$products      = array(
	array(
		'name'     => _x( 'Sneakers', 'Sample product name', 'yith-point-of-sale-for-woocommerce' ),
		'quantity' => 1,
		'price'    => 98,
		'tax-5'    => 0,
		'tax-10'   => 9.8,
		'sku'      => 'FL311085',
	),
	array(
		'name'     => _x( 'Shopping bag', 'Sample product name', 'yith-point-of-sale-for-woocommerce' ),
		'quantity' => 1,
		'price'    => 40,
		'tax-5'    => 2,
		'tax-10'   => 0,
		'sku'      => 'YT548273',
	),
	array(
		'name'     => _x( 'T-shirt', 'Sample product name', 'yith-point-of-sale-for-woocommerce' ),
		'quantity' => 2,
		'price'    => 36,
		'tax-5'    => 0,
		'tax-10'   => 3.6,
		'sku'      => 'KS894314',
	),
);
$prices        = wp_list_pluck( $products, 'price' );
$tax_10_totals = wp_list_pluck( $products, 'tax-10' );
$tax_5_totals  = wp_list_pluck( $products, 'tax-5' );
$tax_10_total  = array_sum( $tax_10_totals );
$tax_5_total   = array_sum( $tax_5_totals );
$tax_total     = $tax_10_total + $tax_5_total;
$subtotal      = array_sum( $prices );
$total         = $subtotal + $tax_total;
$cash          = floor( ( $total + 10 ) / 10 ) * 10;
$change        = $cash - $total;

?>
<div class="receipt-container">
	<div class="receipt-header">
		<div id="logo" default="<?php esc_url( $logo ); ?>">
			<img src="<?php echo esc_url( $logo ); ?>">
		</div>
		<div id="name" data-dep="_show_store_name">
			<?php esc_attr_e( 'Store Name', 'yith-point-of-sale-for-woocommerce' ); ?>
		</div>
		<div id="vat" data-dep="_show_vat">
			<strong data-dep_label="_vat_label"><?php esc_attr_e( 'VAT', 'yith-point-of-sale-for-woocommerce' ); ?>:</strong> B39000000
		</div>
		<div id="address" data-dep="_show_address">
			Calle Rúe del Percebe, 13<br/>28000 - Madrid
		</div>
		<div id="contact-info" data-dep="_show_contact_info">
			<div id="phone" data-dep="_show_phone">
				<strong><?php esc_html_e( 'Phone:', 'yith-point-of-sale-for-woocommerce' ); ?></strong> 555.555.666
			</div>
			<div id="email" data-dep="_show_email">
				<strong><?php esc_html_e( 'Email:', 'yith-point-of-sale-for-woocommerce' ); ?></strong> info@website.com
			</div>
			<div id="fax" data-dep="_show_fax">
				<strong><?php esc_html_e( 'Fax:', 'yith-point-of-sale-for-woocommerce' ); ?></strong> 555.444.222
			</div>
			<div id="website" data-dep="_show_website">
				<strong><?php esc_html_e( 'Web:', 'yith-point-of-sale-for-woocommerce' ); ?></strong> www.website.com
			</div>
		</div>
		<div id="social-info" data-dep="_show_social_info">
			<div id="facebook" data-dep="_show_facebook">
				<strong><?php esc_html_e( 'Facebook:', 'yith-point-of-sale-for-woocommerce' ); ?></strong>
				facebook.com/my-store<br/></div>
			<div id="twitter" data-dep="_show_twitter">
				<strong><?php esc_html_e( 'Twitter:', 'yith-point-of-sale-for-woocommerce' ); ?></strong> @my_store
			</div>
			<div id="instagram" data-dep="_show_instagram">
				<strong><?php esc_html_e( 'Instagram:', 'yith-point-of-sale-for-woocommerce' ); ?></strong>
				instagram.com/my_store
			</div>
			<div id="youtube" data-dep="_show_youtube">
				<strong><?php esc_html_e( 'YouTube:', 'yith-point-of-sale-for-woocommerce' ); ?></strong>
				youtube.com/store_channel
			</div>
		</div>
	</div>
	<div class="order">
		<div class="order-content products">
			<?php foreach ( $products as $product ) : ?>
				<?php
				$product_tax         = $product['tax-10'] ?? $product['tax-5'] ?? 0;
				$price_excluding_tax = $product['price'];
				$price_including_tax = $price_excluding_tax + $product_tax;
				?>
				<div class="product">
					<div class="product__name"><?php echo esc_html( sprintf( '%s x %s', $product['quantity'], $product['name'] ) ); ?></div>
					<div class="product__sku" data-dep="_show_sku">
						<span class="product__sku__label" data-dep_label="_sku_label"><?php esc_html_e( 'SKU:', 'yith-point-of-sale-for-woocommerce' ); ?></span>
						<?php echo esc_html( $product['sku'] ); ?>
					</div>
				</div>
				<div class="price">
					<div class="price-including-tax">
						<?php echo wc_price( $price_including_tax ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="price-excluding-tax">
						<?php echo wc_price( $price_excluding_tax ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="tax-row-container">
			<div class="order-content tax-row-total">
				<div class="tax-title">
					<?php esc_html_e( 'Tax', 'yith-point-of-sale-for-woocommerce' ); ?>
				</div>
				<div class="tax-amount">
					<?php echo wc_price( $tax_total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>

			<div class="order-content tax-row-itemized">
				<div class="tax-title"><?php printf( '%s 10%%', esc_html__( 'Tax', 'yith-point-of-sale-for-woocommerce' ) ); ?></div>
				<div class="tax-amount">
					<?php echo wc_price( $tax_10_total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="tax-title"><?php printf( '%s 5%%', esc_html__( 'Tax', 'yith-point-of-sale-for-woocommerce' ) ); ?></div>
				<div class="tax-amount">
					<?php echo wc_price( $tax_5_total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
		<div class="order-content">
			<div class="total-title">
				<?php esc_html_e( 'Total', 'yith-point-of-sale-for-woocommerce' ); ?>
			</div>
			<div class="total-amount">
				<?php echo wc_price( $total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<div class="order-content">
			<div class="payment-method">
				<?php esc_html_e( 'Cash', 'yith-point-of-sale-for-woocommerce' ); ?>
			</div>
			<div class="payment-total">
				<?php echo wc_price( $cash ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="payment-change">
				<?php esc_html_e( 'Change', 'yith-point-of-sale-for-woocommerce' ); ?>
			</div>
			<div class="total-change">
				<?php echo wc_price( $change ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	</div>
	<div class="order-data">
		<div id="order-date" data-dep="_show_order_date">
			<strong data-dep_label="_order_date_label"><?php esc_attr_e( 'Date', 'yith-point-of-sale-for-woocommerce' ); ?>
				:</strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ) ) ); ?></div>
		<div id="order-number" data-dep="_show_order_number">
			<strong data-dep_label="_order_number_label"><?php esc_attr_e( 'Order', 'yith-point-of-sale-for-woocommerce' ); ?>:</strong>
			452
		</div>
		<div id="order-customer" data-dep="_show_order_customer">
			<strong data-dep_label="_order_customer_label"><?php esc_attr_e( 'Customer', 'yith-point-of-sale-for-woocommerce' ); ?>
				:</strong> John Doe
		</div>
		<div id="order-shipping" data-dep="_show_shipping">
			<strong data-dep_label="_shipping_label"><?php esc_attr_e( 'Shipping:', 'yith-point-of-sale-for-woocommerce' ); ?></strong> John Doe 14 Street 543 New York, 12345 United States (US)
		</div>
		<div id="order-register" data-dep="_show_order_register">
			<strong data-dep_label="_order_register_label"><?php esc_attr_e( 'Register', 'yith-point-of-sale-for-woocommerce' ); ?>
				:</strong> 23878457-2
		</div>
		<div id="cashier" data-dep="_show_cashier">
			<strong data-dep_label="_cashier_label"><?php esc_attr_e( 'Cashier', 'yith-point-of-sale-for-woocommerce' ); ?>
				:</strong> Jane Doe
		</div>
	</div>
	<div class="receipt-footer">
		<strong data-dep_label="_receipt_footer"><?php esc_attr_e( 'Thanks for your purchase', 'yith-point-of-sale-for-woocommerce' ); ?></strong>
	</div>
</div>
