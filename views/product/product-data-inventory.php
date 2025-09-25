<?php
/**
 * Product data inventory.
 *
 * @var bool   $multistock_enabled Multi-stock enabled flag.
 * @var string $is_enabled         Enabled value (yes|no).
 * @var array  $multistock         Multi-stock info.
 * @var int    $loop               The loop index (could be not defined).
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Views
 */

defined( 'YITH_POS' ) || exit();

$stores = yith_pos_get_stores( array( 'fields' => 'stores' ) );
$loop   = $loop ?? false;
?>
<div class="yith-pos-stock-management yith-plugin-ui">
	<h3><?php esc_html_e( 'POS Inventory', 'yith-point-of-sale-for-woocommerce' ); ?></h3>
	<?php if ( ! $multistock_enabled ) : ?>
		<div class="multistock_info">
			<?php
			printf( '%s <a href="%s">%s</a>', esc_html__( 'To enable the multistock, it is necessary activate the option in ', 'yith-point-of-sale-for-woocommerce' ), esc_url( get_admin_url( null, 'admin.php?page=yith_pos_panel&tab=settings' ) ), esc_html__( 'YITH > Point of Sale > Customization > Enable multistock', 'yith-point-of-sale-for-woocommerce' ) )
			?>
		</div>
	<?php else : ?>
		<div class="form-field menu_order_field ">
			<label for="menu_order"><?php esc_html_e( 'Enable Multi Stock in POS', 'yith-point-of-sale-for-woocommerce' ); ?></label>
			<?php
			yith_plugin_fw_get_field(
				array(
					'type'  => 'onoff',
					'id'    => false !== $loop ? "_yith_pos_multistock_enabled-{$loop}" : '_yith_pos_multistock_enabled',
					'name'  => false !== $loop ? "_yith_pos_multistock_enabled[{$loop}]" : '_yith_pos_multistock_enabled',
					'class' => 'yith-pos-product-multistock-enabled',
					'value' => $is_enabled,
				),
				true
			)
			?>
		</div>
		<?php if ( $stores ) : ?>
			<div class="yith-pos-product-multi-stock" data-list='<?php echo wp_json_encode( $multistock ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>'>
				<div class="form-field menu_order_field ">
					<label for="menu_order"><?php esc_html_e( 'In store', 'yith-point-of-sale-for-woocommerce' ); ?></label>
					<div class="yith-pos-multistock-options">
						<?php
						$i = 0;
						?>
						<?php if ( ! ! $multistock ) : ?>
							<?php foreach ( $multistock as $key => $single_store_stock ) : ?>
								<div class="yith-pos-group">
									<?php
									$name_store = false !== $loop ? "_yith_pos_multistock[{$loop}][{$i}][store]" : "_yith_pos_multistock[{$i}][store]";
									$name_stock = false !== $loop ? "_yith_pos_multistock[{$loop}][{$i}][stock]" : "_yith_pos_multistock[{$i}][stock]";
									$i ++;
									?>
									<span class="store">
								<select class="wc-enhanced-select" name="<?php echo esc_attr( $name_store ); ?>">
									<option value="0"><?php esc_html_e( 'Select Store', 'yith-point-of-sale-for-woocommerce' ); ?></option>
								<?php
								foreach ( $stores as $store ) :
									$selected = ( $key === $store->get_id() );
									?>
									<option value="<?php echo esc_attr( $store->get_id() ); ?>" <?php selected( $selected, true ); ?>><?php echo esc_html( $store->get_name() ); ?></option>
								<?php endforeach; ?>
							</select>
								</span>
									<span><?php esc_html_e( 'set a stock of: ', 'yith-point-of-sale-for-woocommerce' ); ?></span>
									<span class="stock"><input name="<?php echo esc_attr( $name_stock ); ?>" type="number" min="0" step="1" value="<?php echo esc_attr( $single_store_stock ); ?>"/></span>
									<span><?php esc_html_e( 'units', 'yith-point-of-sale-for-woocommerce' ); ?></span>
									<span><i class="yith-icon yith-icon-trash"></i></span>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<div class="add-new-row">
						<a href="" class="clone-stock-group" data-max="<?php echo esc_attr( count( $stores ) ); ?>" data-loop="<?php echo false !== $loop ? esc_attr( $loop ) : ''; ?>"><?php esc_html_e( '+ manage stock for another store', 'yith-point-of-sale-for-woocommerce' ); ?></a>
						<script type="text/template" id="tmpl-yith-pos-stock-manager<?php echo false !== $loop ? esc_attr( $loop ) : ''; ?>">
							<?php
							$name_store = false !== $loop ? "_yith_pos_multistock[{$loop}][{{data.id}}][store]" : '_yith_pos_multistock[{{data.id}}][store]';
							$name_stock = false !== $loop ? "_yith_pos_multistock[{$loop}][{{data.id}}][stock]" : '_yith_pos_multistock[{{data.id}}][stock]';
							?>
							<div class="yith-pos-group" data-id="{{data.id}}">
								<span class="store">
									<select class="wc-enhanced-select" name="<?php echo esc_attr( $name_store ); ?>">
										<option value="0"><?php esc_html_e( 'Select Store', 'yith-point-of-sale-for-woocommerce' ); ?></option>
										<?php foreach ( $stores as $store ) : ?>
											<option value="<?php echo esc_attr( $store->get_id() ); ?>"><?php echo esc_html( $store->get_name() ); ?></option>
										<?php endforeach; ?>
									</select>
								</span>

								<span><?php esc_html_e( 'set a stock of: ', 'yith-point-of-sale-for-woocommerce' ); ?></span>
								<span class="stock"><input name="<?php echo esc_attr( $name_stock ); ?>" type="number" min="0" step="1" value=""></span>
								<span><?php esc_html_e( 'units', 'yith-point-of-sale-for-woocommerce' ); ?></span>
								<span><i class="yith-icon yith-icon-trash"></i></span>
							</div>
						</script>
					</div>
				</div>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'No Store found!', 'yith-point-of-sale-for-woocommerce' ); ?></p>
		<?php endif ?>
	<?php endif ?>
</div>
