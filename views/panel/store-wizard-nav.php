<?php
/**
 * Store wizard nav.
 *
 * @var int $current_page Current page number.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Views
 */

defined( 'YITH_POS' ) || exit();

$steps = array(
	1 => array(
		'title'    => __( 'Step 1', 'yith-point-of-sale-for-woocommerce' ),
		'subtitle' => __( 'Store info', 'yith-point-of-sale-for-woocommerce' ),
	),
	2 => array(
		'title'    => __( 'Step 2', 'yith-point-of-sale-for-woocommerce' ),
		'subtitle' => __( 'Employees', 'yith-point-of-sale-for-woocommerce' ),
	),
	3 => array(
		'title'    => __( 'Step 3', 'yith-point-of-sale-for-woocommerce' ),
		'subtitle' => __( 'Registers', 'yith-point-of-sale-for-woocommerce' ),

	),
	4 => array(
		'title'    => __( 'Step 4', 'yith-point-of-sale-for-woocommerce' ),
		'subtitle' => __( 'Save Store', 'yith-point-of-sale-for-woocommerce' ),
	),
);
?>

<div id="yith-pos-wizard-nav" class="yith-pos-wizard__has-current-page-data" data-current-page="<?php echo esc_attr( $current_page ); ?>">
	<?php foreach ( $steps as $index => $step ) : ?>
		<?php
		$is_active = $index === $current_page;
		?>
		<div id="yith-pos-wizard-nav__step-<?php echo esc_attr( $index ); ?>" class="yith-pos-wizard-nav__step <?php echo $is_active ? 'active' : ''; ?>" data-step="<?php echo esc_attr( $index ); ?>">
			<div class="yith-pos-wizard-nav__step__name">
				<div class="yith-pos-wizard-nav__step__title"><?php echo esc_html( $step['title'] ); ?></div>
				<div class="yith-pos-wizard-nav__step__subtitle"><?php echo esc_html( $step['subtitle'] ); ?></div>
			</div>
		</div>
	<?php endforeach; ?>
</div>
