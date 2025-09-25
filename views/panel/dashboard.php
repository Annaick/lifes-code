<?php
/**
 * Dashboard view.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Views
 */

defined( 'YITH_POS' ) || exit();

$cta_url   = admin_url( 'admin.php?page=wc-settings&tab=advanced&section=features' );
$cta_label = false;
?>

<div class='woocommerce-layout'>
	<div id="yith-pos-admin-root">
		<?php if ( ! yith_pos_is_wc_feature_enabled( 'analytics' ) ) : ?>
			<div class="yith-pos-dashboard-required-feature yith-plugin-fw">
				<div class="yith-pos-dashboard-required-feature__icon">
					<?php yith_pos_svg( 'pos-and-analytics', true ); ?>
				</div>
				<div class="yith-pos-dashboard-required-feature__message">
					<?php
					echo sprintf(
					// translators: 1. POS plugin name; 2. 'WooCommerce Analytics' feature name.
						esc_html__( '%1$s requires the %2$s feature enabled to show reports here and generate reports correctly for register sessions!', 'yith-point-of-sale-for-woocommerce' ),
						'<strong>' . esc_html( YITH_POS_PLUGIN_NAME ) . '</strong>',
						'<strong>WooCommerce Analytics</strong>'
					);
					?>
				</div>
				<div class="yith-pos-dashboard-required-feature__message__cta">
					<?php
					$cta_breadcrumb = implode(
						' > ',
						array(
							'WooCommerce',
							__( 'Settings', 'woocommerce' ),
							__( 'Advanced', 'woocommerce' ),
							__( 'Features', 'woocommerce' ),
						)
					);
					echo sprintf(
					// translators: %s is the link of the page where enabling the feature.
						esc_html__( 'Enable it in %s', 'yith-point-of-sale-for-woocommerce' ),
						'<a href="' . esc_url( $cta_url ) . '">' . esc_html( $cta_breadcrumb ) . '</a>'
					);
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div><!-- /.woocommerce-layout -->
