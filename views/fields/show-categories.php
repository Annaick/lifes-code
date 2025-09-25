<?php
/**
 * Show categories field.
 *
 * @var array $field The field.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Views\Fields
 */

defined( 'YITH_POS' ) || exit();

list ( $field_id, $class, $name, $value, $std, $custom_attributes, $data ) = yith_plugin_fw_extract( $field, 'id', 'class', 'name', 'value', 'std', 'custom_attributes', 'data' );

$value           = ! ! $value && is_array( $value ) ? $value : array();
$category_string = array();
$new_value       = array();

if ( isset( $value['categories'] ) ) {
	foreach ( $value['categories'] as $key => $term_id ) {
		$the_term = get_term_by( 'id', $term_id, 'product_cat' );
		if ( $the_term ) {
			$category_string[ $the_term->term_id ] = $the_term->formatted_name .= $the_term->name . ' (' . $the_term->count . ')';
			$new_value[]                           = $the_term->term_id;
		}
	}
}

$category_args = array(
	'type'             => 'hidden',
	'class'            => 'wc-product-search',
	'id'               => $field_id . '[categories]',
	'name'             => $name . '[categories]',
	'data-placeholder' => __( 'Search Category', 'yith-point-of-sale-for-woocommerce' ),
	'data-allow_clear' => false,
	'data-selected'    => $category_string,
	'data-multiple'    => true,
	'data-action'      => 'yith_pos_search_categories',
	'value'            => implode( ',', $new_value ),
);
?>
<div id="<?php echo esc_attr( $field_id ); ?>-container" <?php echo yith_field_deps_data( $field ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="yith-plugin-fw-metabox-field-row">
	<span class="show_category_label"><?php esc_html_e( 'Category to', 'yith-point-of-sale-for-woocommerce' ); ?></span>
	<span class="show_category_select">
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
	<span class="show_category_list"><?php yit_add_select2_fields( $category_args ); ?></span>
</div>
