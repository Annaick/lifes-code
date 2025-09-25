<?php
/**
 * Order POS info meta-box.
 *
 * @var string $store_name      The store name.
 * @var string $register_name   The register name.
 * @var string $cashier         The cashier name.
 * @var array  $payment_methods Payment methods.
 * @var string $currency        Order currency.
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Views
 */

defined( 'YITH_POS' ) || exit();

?>
<div class="yith-pos-info__list">
	<div class="yith-pos-info">
		<div class="yith-pos-info__label"><?php esc_html_e( 'Store', 'yith-point-of-sale-for-woocommerce' ); ?></div>
		<div class="yith-pos-info__content"><?php echo esc_html( $store_name ); ?></div>
	</div>

	<div class="yith-pos-info">
		<div class="yith-pos-info__label"><?php esc_html_e( 'Register', 'yith-point-of-sale-for-woocommerce' ); ?></div>
		<div class="yith-pos-info__content"><?php echo esc_html( $register_name ); ?></div>
	</div>

	<div class="yith-pos-info">
		<div class="yith-pos-info__label"><?php esc_html_e( 'Cashier', 'yith-point-of-sale-for-woocommerce' ); ?></div>
		<div class="yith-pos-info__content"><?php echo esc_html( $cashier ); ?></div>
	</div>

	<?php if ( $payment_methods ) : ?>
		<?php
		$gateways = WC()->payment_gateways()->payment_gateways();
		?>
		<div class="yith-pos-info">
			<div class="yith-pos-info__label"><?php esc_html_e( 'Payment methods', 'yith-point-of-sale-for-woocommerce' ); ?></div>
			<div class="yith-pos-info__content">
				<div class="payment-methods">
					<?php foreach ( $payment_methods as $payment_method ) : ?>
						<?php if ( isset( $gateways[ $payment_method['paymentMethod'] ] ) ) : ?>
							<?php
							$gateway_name = $gateways[ $payment_method['paymentMethod'] ]->title;
							?>
							<div class="payment-method">
								<span class="title"><?php echo esc_html( $gateway_name ); ?></span>
								<span class="amount"><?php echo wp_kses_post( wc_price( $payment_method['amount'], $currency ) ); ?></span>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endif ?>
</div>
