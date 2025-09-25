<?php
/**
 * Login Template
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Templates
 */

defined( 'YITH_POS' ) || exit;

$logo = get_option( 'yith_pos_login_logo' );

?>
<div id="login">
	<div class="pos-login-wrapper">
		<h1>
			<?php
			if ( $logo ) :
				?>
				<img  src="<?php echo esc_url( $logo ); ?>"/><?php endif ?>
			<?php esc_html_e( 'Login', 'yith-point-of-sale-for-woocommerce' ); ?>
		</h1>

		<?php wc_print_notices(); ?>

		<form class="yith-pos-login-form" method="post">

			<?php do_action( 'yith_pos_login_form_start' ); ?>

			<div class="yith-pos-login-form-row">
				<input type="text" name="username" id="username" autocomplete="username" class="input-login" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['username'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>" required/>
				<label class="float-label" for="username"><?php esc_html_e( 'Username or email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
			</div>
			<div class="yith-pos-login-form-row">
				<input type="password" name="password" id="password" autocomplete="current-password" class="input-login" required/>
				<label class="float-label" for="password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
			</div>

			<?php do_action( 'yith_pos__login_form' ); ?>

			<div class="yith-pos-login-form-row">
				<label class="yith-pos-login-__rememberme">
					<input name="rememberme" type="checkbox" id="rememberme" value="forever"/> <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
				</label>
			</div>
			<div class="yith-pos-login-form-row">
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="login-submit" name="login" value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>"><?php esc_html_e( 'Log in', 'woocommerce' ); ?></button>
			</div>

			<?php do_action( 'yith_pos_login_form_end' ); ?>

		</form>
	</div>
</div>
<?php do_action( 'yith_pos_login_after_form' ); ?>
<?php do_action( 'wp_footer'); ?>
<?php

