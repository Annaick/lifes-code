<?php
/**
 * Presets field.
 *
 * @var array $field The field.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Views\Fields
 */

defined( 'YITH_POS' ) || exit();

list ( $field_id, $class, $name, $value, $std, $min, $max, $step, $slot_num, $custom_attributes, $data ) = yith_plugin_fw_extract( $field, 'id', 'class', 'name', 'value', 'std', 'min', 'max', 'step', 'slot_num', 'custom_attributes', 'data' );

?>
<?php for ( $i = 0; $i < $slot_num; $i ++ ) : ?>
	<input type="number"
			id="<?php echo esc_attr( $field_id . '_' . $i ); ?>"
			class="presets <?php echo esc_attr( $class ); ?>"
			name="<?php echo esc_attr( $name ); ?>[]"

		<?php if ( isset( $step ) ) : ?>
			step="<?php echo esc_attr( $step ); ?>"
		<?php endif ?>

		<?php if ( isset( $min ) ) : ?>
			min="<?php echo esc_attr( $min ); ?>"
		<?php endif ?>

		<?php if ( isset( $max ) ) : ?>
			max="<?php echo esc_attr( $max ); ?>"
		<?php endif ?>

			value="<?php echo esc_attr( isset( $value[ $i ] ) ? esc_attr( $value[ $i ] ) : esc_attr( $std[ $i ] ) ); ?>"

		<?php if ( isset( $std ) ) : ?>
			data-std=="<?php echo esc_attr( $std[ $i ] ); ?>"
		<?php endif ?>

		<?php echo $custom_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<?php echo isset( $data ) ? yith_plugin_fw_html_data_to_string( $data ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	/>
<?php endfor; ?>
