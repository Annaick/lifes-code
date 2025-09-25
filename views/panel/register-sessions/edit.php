<?php
/**
 * Edit register session
 *
 * @author  YITH <plugins@yithemes.com>
 * @var YITH_POS_Register_Session $register_session The register session.
 * @package YITH\POS\Views
 */

defined( 'YITH_POS' ) || exit();

$reports  = $register_session->is_closed() ? $register_session->get_report() : $register_session->generate_reports();
$list_url = add_query_arg(
	array(
		'page'    => 'yith_pos_panel',
		'tab'     => 'registers',
		'sub_tab' => 'registers-register-sessions',
	),
	admin_url( 'admin.php' )
);
?>
<form method="post">
	<div id="yith-pos-register-session-edit">
		<a class="yith-pos-back-to-list" href="<?php echo esc_url( $list_url ); ?>">
			<i class="yith-pos-back-to-list__icon yith-icon yith-icon-arrow-left-alt"></i>
			<?php
			echo esc_html(
				sprintf(
				// translators: %s is the name of the page.
					__( 'Back to "%s"', 'yith-point-of-sale-for-woocommerce' ),
					__( 'Register Sessions', 'yith-point-of-sale-for-woocommerce' )
				)
			);
			?>
		</a>
		<?php
		if ( ! current_user_can( 'yith_pos_manage_others_pos' ) ) {
			$store = yith_pos_get_store( $register_session->get_store_id() );
			if ( ! $store || ! in_array( get_current_user_id(), $store->get_managers(), true ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to view this register session.', 'yith-point-of-sale-for-woocommerce' ) );
			}
		}
		?>
		<h2 class="yith-pos-register-session__title">
			<?php
			echo esc_html(
				sprintf(
				// translators: %d is the session ID.
					__( 'Register session #%d', 'yith-point-of-sale-for-woocommerce' ),
					$register_session->get_id()
				)
			);
			?>
		</h2>
		<div class="yith-pos-register-session__description">
			<?php
			$edit_register_url  = add_query_arg( array( 'yith-pos-edit-register' => $register_session->get_register_id() ), get_edit_post_link( $register_session->get_store_id() ) );
			$edit_register_link = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $edit_register_url ),
				esc_html( get_the_title( $register_session->get_register_id() ) )
			);

			echo sprintf(
			// translators: %1$s: the name of the register, %2$s: the name of the store.
				esc_html__( 'of %1$s Register in %2$s Store', 'yith-point-of-sale-for-woocommerce' ),
				'<strong>' . wp_kses_post( $edit_register_link ) . '</strong>',
				'<strong>' . wp_kses_post( yith_pos_get_post_edit_link_html( $register_session->get_store_id() ) ) . '</strong>'
			);
			?>
		</div>
		<?php
		if ( ! empty( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$message = sprintf(
				// translators: %d is the session ID.
				__( 'Register session #%d updated!', 'yith-point-of-sale-for-woocommerce' ),
				$register_session->get_id()
			);
			yith_plugin_fw_get_component(
				array(
					'type'        => 'notice',
					'notice_type' => 'success',
					'message'     => $message,
				)
			);
		}
		?>

		<div class="yith-pos-register-session-details">
			<div class="yith-pos-register-session-detail">
				<div class="yith-pos-register-session-detail__label"><?php echo esc_html__( 'Opening Time', 'yith-point-of-sale-for-woocommerce' ); ?></div>
				<div class="yith-pos-register-session-detail__content">
					<?php
					echo esc_html( $register_session->get_open_date()->date_i18n( yith_pos_admin_date_format() ) );
					?>
				</div>

			</div>
			<div class="yith-pos-register-session-detail">
				<div class="yith-pos-register-session-detail__label"><?php echo esc_html__( 'Closing Time', 'yith-point-of-sale-for-woocommerce' ); ?></div>
				<div class="yith-pos-register-session-detail__content">
					<?php
					if ( $register_session->is_closed() ) {
						echo esc_html( $register_session->get_closed_date()->date_i18n( yith_pos_admin_date_format() ) );
					} else {
						esc_html_e( 'Still open', 'yith-point-of-sale-for-woocommerce' );
					}
					?>
				</div>

			</div>
			<div class="yith-pos-register-session-detail">
				<div class="yith-pos-register-session-detail__label"><?php echo esc_html__( 'Cashiers', 'yith-point-of-sale-for-woocommerce' ); ?></div>
				<div class="yith-pos-register-session-detail__content">
					<?php echo esc_html( implode( ', ', $register_session->get_cashier_names() ) ); ?>
				</div>

			</div>
		</div>

		<div class="yith-pos-register-session-contents">
			<?php if ( $register_session->get_cash_in_hand() ) : ?>
				<div class="yith-pos-register-session-section">
					<h3 class="yith-pos-register-session-section__title"><?php echo esc_html__( 'Cash in hand', 'yith-point-of-sale-for-woocommerce' ); ?></h3>
					<div class="yith-pos-register-session-section__content yith-pos-register-session-cash-in-hand-table__wrapper">
						<table class="yith-pos-register-session-cash-in-hand-table">
							<?php foreach ( $register_session->get_cash_in_hand() as $cash_in_hand ) : ?>
								<?php
								$amount     = $cash_in_hand['amount'] ?? 0;
								$cashier_id = $cash_in_hand['cashier'] ?? 0;
								$reason     = $cash_in_hand['reason'] ?? '';
								$timestamp  = $cash_in_hand['timestamp'] ?? 0;
								if ( ! $amount ) {
									continue;
								}
								$extra_class        = $amount > 0 ? 'positive' : 'negative';
								$reason_extra_class = ! ! $reason ? '' : 'no-reason';
								$reason             = ! ! $reason ? $reason : __( 'No reason set', 'yith-point-of-sale-for-woocommerce' );
								$date               = yith_pos_timestamp_to_datetime( $timestamp );
								$local_timestamp    = $date->getOffsetTimestamp();
								?>
								<tr class="yith-pos-register-session-cash-in-hand-table__report <?php echo esc_attr( $extra_class ); ?>">
									<th class="yith-pos-register-session-cash-in-hand-table__report__title">
										<div class="yith-pos-register-session-cash-in-hand-table__report__reason <?php echo esc_attr( $reason_extra_class ); ?>">
											<?php echo esc_html( $reason ); ?>
										</div>
										<small class="yith-pos-register-session-cash-in-hand-table__report__info">
											<?php
											echo sprintf(
											// translators: 1. cashier name, 2. date 3. time.
												esc_html__( 'by %1$s on %2$s at %3$s', 'yith-point-of-sale-for-woocommerce' ),
												esc_html( yith_pos_get_employee_name( $cashier_id ) ),
												esc_html( date_i18n( wc_date_format(), $local_timestamp ) ),
												esc_html( date_i18n( wc_time_format(), $local_timestamp ) )
											);
											?>
										</small>
									</th>
									<td class="yith-pos-register-session-cash-in-hand-table__report__amount"><?php echo wp_kses_post( wc_price( $amount ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</table>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $reports ) : ?>
				<div class="yith-pos-register-session-section">
					<h3 class="yith-pos-register-session-section__title"><?php echo esc_html__( 'Reports', 'yith-point-of-sale-for-woocommerce' ); ?></h3>
					<div class="yith-pos-register-session-section__content yith-pos-register-session-reports-table__wrapper">
						<table class="yith-pos-register-session-reports-table">
							<?php foreach ( $reports as $report_id => $report ) : ?>
								<?php
								$report_title    = $report['title'] ?? $report_id;
								$report_type     = $report['type'] ?? 'price';
								$value           = $report['value'] ?? ( 'price' === $report_type ? 0 : '' );
								$formatted_value = 'price' === $report_type ? wc_price( $value ) : $value;
								?>
								<tr class="yith-pos-register-session-reports-table__report-<?php echo esc_attr( $report_id ); ?>">
									<th class="yith-pos-register-session-reports-table__report__title"><?php echo esc_html( $report_title ); ?></th>
									<td class="yith-pos-register-session-reports-table__report__amount"><?php echo wp_kses_post( $formatted_value ); ?></td>
								</tr>
							<?php endforeach; ?>
						</table>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $register_session->is_closed() ) : ?>
				<div class="yith-pos-register-session-section">
					<h3 class="yith-pos-register-session-section__title"><?php echo esc_html__( 'Note', 'yith-point-of-sale-for-woocommerce' ); ?></h3>
					<div class="yith-pos-register-session-section__content">
						<div class="yith-pos-register-session-info yith-pos-register-session-info--note">
							<textarea id="yith-pos-register-session-note" name="note"><?php echo wp_kses_post( $register_session->get_note() ); ?></textarea>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<div class="yith-pos-register-session-actions">
			<a class="yith-plugin-fw__button yith-plugin-fw__button--secondary yith-plugin-fw__button--with-icon" href="<?php echo esc_url( $register_session->get_download_reports_url() ); ?>">
				<i class="yith-icon yith-icon-download"></i>
				<?php esc_html_e( 'Download reports', 'yith-point-of-sale-for-woocommerce' ); ?>
			</a>
			<?php if ( $register_session->is_closed() ) : ?>
				<input class="yith-plugin-fw__button yith-plugin-fw__button--primary" type="submit" id="submit" value="<?php esc_html_e( 'Update', 'yith-point-of-sale-for-woocommerce' ); ?>"/>
			<?php endif; ?>
		</div>
	</div>
	<input type="hidden" name="yith-pos-register-session-action" value="update">
	<?php wp_nonce_field( 'update', 'yith-pos-register-session-nonce' ); ?>
</form>
