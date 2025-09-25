<?php
/**
 * Register Template
 *
 * @author  YITH <plugins@yithemes.com>
 * @var bool|int $register_id  The register ID.
 *
 * @var bool|int $user_editing The user currently editing.
 * @package YITH\POS\Templates
 */

defined( 'YITH_POS' ) || exit;

$stores       = yith_pos_get_allowed_store_registers_by_user();
$stores_count = count( $stores );

if ( ! $stores ) {
	wp_die( esc_html__( 'Sorry, you are not allowed to see this content', 'yith-point-of-sale-for-woocommerce' ) );
}

$stores_json = wp_json_encode( $stores );
$stores_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $stores_json ) : _wp_specialchars( $stores_json, ENT_QUOTES, 'UTF-8', true );
$logo        = get_option( 'yith_pos_login_logo' );

$pos_url    = yith_pos_get_pos_page_url();
$logout_url = add_query_arg( array( 'yith-pos-user-logout' => true ), $pos_url )
?>
<div id="yith-pos-store-register-form" class="yith-pos-form" data-stores="<?php echo $stores_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
	<div class="yith-pos-form-wrap">
		<h1>
			<?php if ( $logo ) : ?>
				<img src="<?php echo esc_url( $logo ); ?>"/>
			<?php endif; ?>
			<?php esc_html_e( 'Choose Store and Register', 'yith-point-of-sale-for-woocommerce' ); ?>
		</h1>
		<form method="post">
			<?php if ( $register_id && $user_editing ) : ?>
				<div class="yith-pos-form-row yith-pos-change-register-or-take-over">
					<?php
					$register_name  = '<strong>' . esc_html( get_the_title( $register_id ) ) . '</strong>';
					$take_over_link = add_query_arg(
						array(
							'register'                 => $register_id,
							'yith-pos-take-over-nonce' => wp_create_nonce( 'yith-pos-take-over' ),
						)
					);
					// translators: %s is the Register name.
					$take_over_text = sprintf( esc_html__( 'Take over %s', 'yith-point-of-sale-for-woocommerce' ), $register_name );

					echo implode(
						'<br />',
						array(
							sprintf(
							// translators: 1. Register name; 2. user name.
								esc_html__( '%1$s is currently in use by %2$s.', 'yith-point-of-sale-for-woocommerce' ),
								$register_name, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								'<strong>' . esc_html( get_userdata( $user_editing )->display_name ) . '</strong>'
							),
							sprintf(
							// translators: %s is "Take over Register" text.
								esc_html__( 'Choose another Register or %s', 'yith-point-of-sale-for-woocommerce' ),
								"<a href='{$take_over_link}'>{$take_over_text}</a>" // // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							),
						)
					);

					?>
				</div>
			<?php endif; ?>
			<div class="yith-pos-form-row with-select">
				<select id="yith-pos-store-register-form__store" name="store">
					<?php if ( $stores_count > 1 ) : ?>
						<option value="" class="placeholder"><?php esc_html_e( 'Choose a Store', 'yith-point-of-sale-for-woocommerce' ); ?></option>
					<?php endif; ?>
					<?php foreach ( $stores as $store ) : ?>
						<option value="<?php echo esc_attr( $store['id'] ); ?>"><?php echo esc_html( $store['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<label class="float-label" for="yith-pos-store-register-form__store"><?php esc_html_e( 'Store', 'yith-point-of-sale-for-woocommerce' ); ?></label>
			</div>
			<div class="yith-pos-form-row with-select">
				<select id="yith-pos-store-register-form__register" name="register">
					<option value="" class="placeholder"><?php esc_html_e( 'Choose a Register', 'yith-point-of-sale-for-woocommerce' ); ?></option>
				</select>
				<label class="float-label" for="yith-pos-store-register-form__register"><?php esc_html_e( 'Register', 'yith-point-of-sale-for-woocommerce' ); ?></label>
			</div>

			<div class="yith-pos-form-row">
				<button type="submit" class="submit"><?php esc_html_e( 'Open Register', 'yith-point-of-sale-for-woocommerce' ); ?></button>
			</div>

			<div class="yith-pos-form-row yith-pos-logout-row">
				<a class="logout" href="<?php echo esc_url( $logout_url ); ?>"><?php esc_html_e( 'Logout', 'yith-point-of-sale-for-woocommerce' ); ?></a>
			</div>
			<?php wp_nonce_field( 'yith-pos-register-login', 'yith-pos-register-login-nonce' ); ?>

		</form>

		<?php do_action( 'yith_pos_after_store_register_form' ); ?>
	</div>
</div>
