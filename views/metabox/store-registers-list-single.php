<?php
/**
 * Store registers list.
 *
 * @var int               $register_id The register ID.
 * @var YITH_POS_Register $register    The register.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Views
 */

defined( 'YITH_POS' ) || exit();

global $register, $register_id;

$default_toggle_class = is_numeric( $register_id ) ? 'yith-pos-settings-box--closed' : '';
$field_name_prefix    = "register-{$register_id}_";
$options              = yith_pos_get_register_options( $register );

?>
<div class="yith-pos-store-register yith-pos-settings-box <?php echo esc_attr( $default_toggle_class ); ?>"
		data-field-name-prefix="<?php echo esc_attr( $field_name_prefix ); ?>" data-register-id="<?php echo esc_attr( $register_id ); ?>">
	<div class="yith-pos-settings-box__header">
		<span class="yith-pos-settings-box__title"><?php echo esc_html( $register->get_name( 'edit' ) ); ?></span>
		<span class="yith-pos-settings-box__toggle"><span class="dashicons dashicons-arrow-up-alt2"></span></span>
		<span class="yith-pos-settings-box__enabled">
			<?php
			yith_plugin_fw_get_field(
				array(
					'id'    => 'yith-pos-store-register-enabled-' . $register_id,
					'class' => 'yith-pos-register-toggle-enabled',
					'type'  => 'onoff',
					'value' => $register->get_enabled( 'edit' ),
					'data'  => array(
						'register-id' => $register->get_id(),
						'security'    => wp_create_nonce( 'register-toggle-enabled' ),
					),
				),
				true,
				false
			)
			?>
		</span>
		<span class="yith-pos-settings-box__title_actions">
			<?php
			yith_plugin_fw_get_component(
				array(
					'type'   => 'action-button',
					'title'  => __( 'Delete', 'yith-point-of-sale-for-woocommerce' ),
					'action' => 'delete',
					'icon'   => 'trash',
					'url'    => '#',
					'class'  => 'yith-pos-register-delete',
				)
			);
			?>
		</span>
	</div>
	<div class="yith-pos-settings-box__content">
		<?php foreach ( $options as $option_key => $option ) : ?>
			<?php
			$label           = $option['label'] ?? '';
			$extra_class     = ! empty( $option['required'] ) ? 'yith-plugin-fw--required' : '';
			$getter          = 'get_' . $option_key;
			$default         = $option['std'] ?? '';
			$value           = is_callable( array( $register, $getter ) ) ? $register->$getter( 'edit' ) : $default;
			$container_class = 'yith-pos-store-register__' . $option_key . '-field-container';
			$option['value'] = $value;
			$option['id']    = $field_name_prefix . $option_key;
			$option['name']  = $option['id'];

			if ( isset( $option['deps'], $option['deps']['id'] ) ) {
				$option['deps']['id'] = "register-{$register_id}" . $option['deps']['id'];
			}
			if ( isset( $option['type'] ) && 'hidden' === $option['type'] ) {
				$extra_class .= ' yith-pos-settings-box__content__row--hidden ';
			}
			$container_class .= ' ' . $extra_class;

			if ( isset( $option['extra_row_class'] ) ) {
				$container_class .= ' ' . $option['extra_row_class'];
			}

			?>
			<div class="yith-pos-settings-box__content__row <?php echo esc_attr( $container_class ); ?>" <?php echo yith_field_deps_data( $option ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<div class="yith-pos-settings-box__content__row__label"><?php echo esc_html( $label ); ?></div>
				<div class="yith-pos-settings-box__content__row__field">
					<?php yith_plugin_fw_get_field( $option, true, false ); ?>

					<?php if ( isset( $option['desc'] ) ) : ?>
						<span class="description"><?php echo wp_kses_post( $option['desc'] ); ?></span>
					<?php endif; ?>
				</div>

			</div>
		<?php endforeach; ?>
		<div class="yith-pos-settings-box__actions">
			<span class="yith-pos-register-update yith-pos-big-button yith-update-button"><?php esc_html_e( 'Update', 'yith-point-of-sale-for-woocommerce' ); ?></span>
			<span class="yith-pos-register-create yith-pos-big-button yith-save-button"><?php esc_html_e( 'Create', 'yith-point-of-sale-for-woocommerce' ); ?></span>
		</div>
	</div>
</div>
