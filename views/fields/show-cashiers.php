<?php
/**
 * Show cashiers field.
 *
 * @var array $field The field.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Views\Fields
 */

defined( 'YITH_POS' ) || exit();

list ( $field_id, $class, $name, $value, $std, $custom_attributes, $data ) = yith_plugin_fw_extract( $field, 'id', 'class', 'name', 'value', 'std', 'custom_attributes', 'data' );

global $post, $register;

$value = ! ! $value && is_array( $value ) ? $value : array();
$class = $class ?? '';

if ( $register ) {
	$store_id = $register->get_store_id();
} else {
	if ( get_post_type( $post->ID ) === YITH_POS_Post_Types::STORE ) {
		$store_id = $post->ID;
	} else {
		$register = yith_pos_get_register( $post->ID );
		$store_id = $register->get_store_id();
	}
}

$cashier_ids   = yith_pos_get_employees( 'cashier', $store_id );
$cashier_names = array_map( 'yith_pos_get_employee_name', $cashier_ids );
$cashiers      = array_combine( $cashier_ids, $cashier_names );

?>
<div id="<?php echo esc_attr( $field_id ); ?>-container" <?php echo yith_field_deps_data( $field ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		class="yith-plugin-fw-metabox-field-row <?php echo esc_attr( $class ); ?>">
	<span class="show-cashiers-select">
	<?php
	yith_plugin_fw_get_field(
		array(
			'id'      => $field_id . '[type]',
			'name'    => $name . '[type]',
			'class'   => 'wc-enhanced-select',
			'type'    => 'select',
			'label'   => '',
			'options' => array(
				'show' => __( 'Show Register to', 'yith-point-of-sale-for-woocommerce' ),
				'hide' => __( 'Hide Register to', 'yith-point-of-sale-for-woocommerce' ),
			),
			'value'   => $value['type'] ?? 'show',
		),
		true,
		false
	);
	?>
	</span>
	<span class="show-cashiers-list">
	<?php
	yith_plugin_fw_get_field(
		array(
			'id'          => $field_id . '[cashiers]',
			'name'        => $name . '[cashiers]',
			'class'       => 'wc-enhanced-select',
			'type'        => 'select',
			'placeholder' => __( 'Select a Cashier', 'yith-point-of-sale-for-woocommerce' ),
			'multiple'    => true,
			'label'       => '',
			'options'     => $cashiers,
			'value'       => $value['cashiers'] ?? array(),
		),
		true,
		false
	);
	?>
	</span>
</div>
