<?php
/**
 * PDF Invoice integration.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

/**
 * Class YITH_POS_PDF_Invoice_Integration
 *
 * @since   2.0.0
 */
class YITH_POS_PDF_Invoice_Integration extends YITH_POS_Integration {

	/**
	 * YITH_POS_PDF_Invoice_Integration constructor.
	 */
	protected function __construct() {
		parent::__construct();

		add_action( 'yith_pos_enqueue_scripts', array( $this, 'enqueue_scripts_in_pos' ) );
		add_filter( 'yith_pos_add_billing_vat_field', '__return_false' );
	}

	/**
	 * Enqueue scripts in POS.
	 */
	public function enqueue_scripts_in_pos() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$deps = array( 'wp-hooks' );
		wp_register_script( 'yith-pos-pdf-invoice-integration', YITH_POS_ASSETS_URL . '/js/integrations/pdf-invoice' . $suffix . '.js', $deps, YITH_POS_VERSION, true );

		wp_localize_script(
			'yith-pos-pdf-invoice-integration',
			'yith_pos_pdf_invoice_integration_options',
			array(
				'electronicInvoiceEnabled' => get_option( 'ywpi_electronic_invoice_enable', 'no' ),
				'i18n'                     => array(
					'receiverId'   => esc_html( get_option( 'ywpi_electronic_invoice_receiver_id_label', 'Receiver ID' ) ),
					'receiverPec'  => esc_html( get_option( 'ywpi_electronic_invoice_receiver_pec_label', 'PEC Destinatario' ) ),
					'receiverType' => apply_filters( 'ywpi_receiver_type_field_label', esc_html__( 'Tipologia utente', 'yith-woocommerce-pdf-invoice' ) ),
					'vat'          => apply_filters( 'yith_ywpi_vat_field_text', esc_html__( 'VAT', 'yith-woocommerce-pdf-invoice' ) ),
					'ssn'          => apply_filters( 'yith_ywpi_ssn_field_text', esc_html__( 'SSN', 'yith-woocommerce-pdf-invoice' ) ),
				),
				'receiverTypeOptions'      => array(
					array(
						'key'   => 'private',
						'label' => apply_filters( 'ywpi_receiver_type_field_private_label', esc_html__( 'Privato', 'yith-woocommerce-pdf-invoice' ) ),
					),
					array(
						'key'   => 'freelance',
						'label' => apply_filters( 'ywpi_receiver_type_field_freelance_label', esc_html__( 'Libero professionista', 'yith-woocommerce-pdf-invoice' ) ),
					),
					array(
						'key'   => 'company',
						'label' => apply_filters( 'ywpi_receiver_type_field_company_label', esc_html__( 'Azienda', 'yith-woocommerce-pdf-invoice' ) ),
					),
				),
				'receiverTypeDefault'      => 'private',
			)
		);
		yith_pos_enqueue_script( 'yith-pos-pdf-invoice-integration' );
	}
}
