<?php
/**
 * POS Template
 *
 * Template Name: YITH POS template Page
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Templates
 */

defined( 'YITH_POS' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended

// TODO: move this logic out of the template.
$logged_in    = is_user_logged_in();
$register_id  = 0;
$user_editing = false;

if ( $logged_in ) {
	$register_id = yith_pos_register_logged_in();
	if ( ! yith_pos_can_view_register() ) {
		$register_id  = isset( $_REQUEST['register'] ) ? absint( $_REQUEST['register'] ) : $register_id;
		$user_editing = isset( $_REQUEST['user-editing'] ) ? absint( $_REQUEST['user-editing'] ) : yith_pos_check_register_lock( $register_id );

		if ( $register_id && $user_editing ) {
			yith_pos_register_logout();
		}
	}
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>"/>
	<link rel="profile" href="https://gmpg.org/xfn/11"/>
	<?php yith_pos_head(); ?>
</head>

<body <?php yith_pos_body_class(); ?>>
<?php if ( ! $logged_in ) : ?>
	<?php wc_get_template( 'yith-pos-login.php', array(), '', YITH_POS_TEMPLATE_PATH ); ?>
<?php else : ?>
	<?php if ( yith_pos_can_view_register() ) : ?>
		<div id="yith-pos-root" data-no-support="<?php esc_attr_e( 'You are using an outdated browser; please update your browser or use a new generation web browser!', 'yith-point-of-sale-for-woocommerce' ); ?>"></div>
	<?php else : ?>
		<?php wc_get_template( 'yith-pos-store-register.php', compact( 'register_id', 'user_editing' ), '', YITH_POS_TEMPLATE_PATH ); ?>
	<?php endif; ?>
<?php endif; ?>
<?php yith_pos_footer(); ?>

</body>
</html>
