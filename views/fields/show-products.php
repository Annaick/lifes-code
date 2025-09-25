<?php
/**
 * Show products field.
 *
 * @var array $field The field.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Views\Fields
 */

defined( 'YITH_POS' ) || exit();

list ( $field_id, $class, $name, $value, $std, $custom_attributes, $data ) = yith_plugin_fw_extract( $field, 'id', 'class', 'name', 'value', 'std', 'custom_attributes', 'data' );

$value = ! ! $value && is_array( $value ) ? $value : array();
?>
<div id="<?php echo esc_attr( $field_id ); ?>-container" <?php echo yith_field_deps_data( $field ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		class="yith-plugin-fw-metabox-field-row">
	<span class="show_product_label"><?php esc_html_e( 'Product to', 'yith-point-of-sale-for-woocommerce' ); ?></span>
	<span class="show_product_select">
	<?php
	yith_plugin_fw_get_field(
		array(
			'id'      => $field_id . '[type]',
			'name'    => $name . '[type]',
			'class'   => 'wc-enhanced-select',
			'type'    => 'select',
			'label'   => '',
			'options' => array(
				'include' => __( 'Include', 'yith-point-of-sale-for-woocommerce' ),
				'exclude' => __( 'Exclude', 'yith-point-of-sale-for-woocommerce' ),
			),
			'value'   => $value['type'] ?? 'include',
		),
		true,
		false
	);
	?>
	</span>
	<span class="show_product_list">
	<?php
	yith_plugin_fw_get_field(
		array(
			'id'       => $field_id . '[products]',
			'name'     => $name . '[products]',
			'type'     => 'ajax-products',
			'multiple' => true,
			'label'    => '',
			'value'    => $value['products'] ?? array(),
		),
		true,
		false
	);
	?>
	</span>
</div>
