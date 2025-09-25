<?php
/**
 * Receipt class.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Receipt' ) ) {
	/**
	 * Class YITH_POS_Receipt
	 */
	class YITH_POS_Receipt extends YITH_POS_CPT_Object {
		/**
		 * Data.
		 *
		 * @var array
		 */
		protected $data = array(
			'name'                      => '',
			'num_of_copies'             => '',
			'width'                     => '',
			'logo'                      => '',
			'enable_gift_receipt'       => 'yes',
			'show_sku'                  => 'no',
			'sku_label'                 => '',
			'show_prices_including_tax' => 'yes',
			'show_tax_details'          => 'yes',
			'show_taxes'                => 'total',
			'show_store_name'           => 'yes',
			'show_vat'                  => 'yes',
			'vat_label'                 => '',
			'show_address'              => 'yes',
			'show_contact_info'         => 'yes',
			'show_phone'                => 'yes',
			'show_email'                => 'yes',
			'show_fax'                  => 'yes',
			'show_website'              => 'yes',
			'show_social_info'          => 'yes',
			'show_facebook'             => 'yes',
			'show_twitter'              => 'yes',
			'show_instagram'            => 'yes',
			'show_youtube'              => 'yes',
			'show_order_date'           => 'yes',
			'order_date_label'          => '',
			'show_order_number'         => 'yes',
			'order_number_label'        => '',
			'show_order_customer'       => 'yes',
			'order_customer_label'      => '',
			'show_order_register'       => 'yes',
			'order_register_label'      => '',
			'show_cashier'              => 'yes',
			'cashier_label'             => '',
			'show_shipping'             => 'no',
			'shipping_label'            => '',
			'receipt_footer'            => '',
		);

		/**
		 * Object type.
		 *
		 * @var string
		 */
		protected $object_type = 'receipt';

		/**
		 * Post type.
		 *
		 * @var string
		 */
		protected $post_type = 'yith-pos-receipt';

		/**
		 * The constructor.
		 *
		 * @param int|self|WP_Post $obj The object.
		 */
		public function __construct( $obj ) {
			// Set default labels.
			$this->data['sku_label']            = __( 'SKU:', 'yith-point-of-sale-for-woocommerce' );
			$this->data['vat_label']            = __( 'VAT:', 'yith-point-of-sale-for-woocommerce' );
			$this->data['order_date_label']     = __( 'Date:', 'yith-point-of-sale-for-woocommerce' );
			$this->data['order_number_label']   = __( 'Order:', 'yith-point-of-sale-for-woocommerce' );
			$this->data['order_customer_label'] = __( 'Customer:', 'yith-point-of-sale-for-woocommerce' );
			$this->data['shipping_label']       = __( 'Shipping:', 'yith-point-of-sale-for-woocommerce' );
			$this->data['order_register_label'] = __( 'Register:', 'yith-point-of-sale-for-woocommerce' );
			$this->data['cashier_label']        = __( 'Cashier:', 'yith-point-of-sale-for-woocommerce' );

			// Set defaults for tax options based on WooCommerce settings.
			$show_including_tax                      = 'incl' === get_option( 'woocommerce_tax_display_cart' );
			$this->data['show_prices_including_tax'] = wc_bool_to_string( $show_including_tax );
			$this->data['show_tax_details']          = wc_bool_to_string( ! $show_including_tax );

			$this->data['logo'] = YITH_POS_ASSETS_URL . '/images/logo-receipt.png';

			parent::__construct( $obj );
		}

		/*
		|--------------------------------------------------------------------------
		| Getters
		|--------------------------------------------------------------------------
		|
		| Methods for getting data from object.
		*/

		/**
		 * Return the name of the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_name( $context = 'view' ) {
			return $this->get_prop( 'name', $context );
		}

		/**
		 * Return the number of copies of the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return int
		 */
		public function get_num_of_copies( $context = 'view' ) {
			return $this->get_prop( 'num_of_copies', $context );
		}

		/**
		 * Return the width of the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return int
		 */
		public function get_width( $context = 'view' ) {
			return $this->get_prop( 'width', $context );
		}

		/**
		 * Return the enable_gift_receipt of the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 * @since 2.0.0
		 */
		public function get_enable_gift_receipt( $context = 'view' ) {
			return $this->get_prop( 'enable_gift_receipt', $context );
		}

		/**
		 * Return the logo of the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_logo( $context = 'view' ) {
			return $this->get_prop( 'logo', $context );
		}

		/**
		 * Get show_sku param.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 * @since 2.0.0
		 */
		public function get_show_sku( $context = 'view' ) {
			return $this->get_prop( 'show_sku', $context );
		}

		/**
		 * Get sku_label param.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 * @since 2.0.0
		 */
		public function get_sku_label( $context = 'view' ) {
			return $this->get_prop( 'sku_label', $context );
		}

		/**
		 * Get show_prices_including_tax param.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 * @since 2.0.0
		 */
		public function get_show_prices_including_tax( $context = 'view' ) {
			$value = $this->get_prop( 'show_prices_including_tax', $context );

			yith_pos_deprecated_filter( 'yith_pos_show_price_including_tax_in_receipt', '2.0.0' );
			$value = wc_bool_to_string( apply_filters( 'yith_pos_show_price_including_tax_in_receipt', wc_string_to_bool( $value ) ) );

			return $value;
		}

		/**
		 * Get show_tax_details param.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 * @since 2.0.0
		 */
		public function get_show_tax_details( $context = 'view' ) {
			$value = $this->get_prop( 'show_tax_details', $context );

			yith_pos_deprecated_filter( 'yith_pos_show_tax_row_in_receipt', '2.0.0' );
			$value = wc_bool_to_string( apply_filters( 'yith_pos_show_tax_row_in_receipt', wc_string_to_bool( $value ) ) );

			return $value;
		}

		/**
		 * Get show_taxes param.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 * @since 2.0.0
		 */
		public function get_show_taxes( $context = 'view' ) {
			$value = $this->get_prop( 'show_taxes', $context );

			yith_pos_deprecated_filter( 'yith_pos_show_itemized_tax_in_receipt', '2.0.0' );
			if ( has_filter( 'yith_pos_show_itemized_tax_in_receipt' ) ) {
				$show_itemized = apply_filters( 'yith_pos_show_itemized_tax_in_receipt', 'itemized' === $value );
				$value         = ! ! $show_itemized ? 'itemized' : 'total';
			}

			return $value;
		}

		/**
		 * Return if the store name should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_store_name( $context = 'view' ) {
			return $this->get_prop( 'show_store_name', $context );
		}

		/**
		 * Return if the store VAT should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_vat( $context = 'view' ) {
			return $this->get_prop( 'show_vat', $context );
		}

		/**
		 * Return the label of VAT
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_vat_label( $context = 'view' ) {
			return $this->get_prop( 'vat_label', $context );
		}

		/**
		 * Return if the store address should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_address( $context = 'view' ) {
			return $this->get_prop( 'show_address', $context );
		}

		/**
		 * Return if the store contact info should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_contact_info( $context = 'view' ) {
			return $this->get_prop( 'show_contact_info', $context );
		}

		/**
		 * Return if the store phone should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_phone( $context = 'view' ) {
			return $this->get_prop( 'show_phone', $context );
		}

		/**
		 * Return if the store email address should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_email( $context = 'view' ) {
			return $this->get_prop( 'show_email', $context );
		}

		/**
		 * Return if the store fax should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_fax( $context = 'view' ) {
			return $this->get_prop( 'show_fax', $context );
		}

		/**
		 * Return if the store website should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_website( $context = 'view' ) {
			return $this->get_prop( 'show_website', $context );
		}

		/**
		 * Return if the store social info should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_social_info( $context = 'view' ) {
			return $this->get_prop( 'show_social_info', $context );
		}

		/**
		 * Return if the facebook info should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_facebook( $context = 'view' ) {
			return $this->get_prop( 'show_facebook', $context );
		}

		/**
		 * Return if the twitter info should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_twitter( $context = 'view' ) {
			return $this->get_prop( 'show_twitter', $context );
		}

		/**
		 * Return if the instagram info should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_instagram( $context = 'view' ) {
			return $this->get_prop( 'show_instagram', $context );
		}

		/**
		 * Return if the youtube info should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_youtube( $context = 'view' ) {
			return $this->get_prop( 'show_youtube', $context );
		}

		/**
		 * Return if the order date should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_order_date( $context = 'view' ) {
			return $this->get_prop( 'show_order_date', $context );
		}

		/**
		 * Return the label for the date in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_order_date_label( $context = 'view' ) {
			return $this->get_prop( 'order_date_label', $context );
		}

		/**
		 * Return if the order number should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_order_number( $context = 'view' ) {
			return $this->get_prop( 'show_order_number', $context );
		}

		/**
		 * Return order number label in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_order_number_label( $context = 'view' ) {
			return $this->get_prop( 'order_number_label', $context );
		}

		/**
		 * Return if the customer of order should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_order_customer( $context = 'view' ) {
			return $this->get_prop( 'show_order_customer', $context );
		}

		/**
		 * Return the customer label in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_order_customer_label( $context = 'view' ) {
			return $this->get_prop( 'order_customer_label', $context );
		}

		/**
		 * Return if the register should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_order_register( $context = 'view' ) {
			return $this->get_prop( 'show_order_register', $context );
		}

		/**
		 * Return the register label in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_order_register_label( $context = 'view' ) {
			return $this->get_prop( 'order_register_label', $context );
		}

		/**
		 * Return if the cashier should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_show_cashier( $context = 'view' ) {
			return $this->get_prop( 'show_cashier', $context );
		}

		/**
		 * Return the label for the cashier
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_cashier_label( $context = 'view' ) {
			return $this->get_prop( 'cashier_label', $context );
		}

		/**
		 * Return if the shipping should be printed in the Receipt
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 * @since 1.6.0
		 */
		public function get_show_shipping( $context = 'view' ) {
			return $this->get_prop( 'show_shipping', $context );
		}

		/**
		 * Return the label for the shipping
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 * @since 1.6.0
		 */
		public function get_shipping_label( $context = 'view' ) {
			return $this->get_prop( 'shipping_label', $context );
		}

		/**
		 * Return the footer text
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_receipt_footer( $context = 'view' ) {
			return $this->get_prop( 'receipt_footer', $context );
		}

		/**
		 * Return the registers that have set the receipt
		 *
		 * @return array
		 */
		public function get_registers() {

			$args = array(
				'posts_per_page' => - 1,
				'post_type'      => YITH_POS_Post_Types::REGISTER,
				'meta_key'       => '_receipt_id',
				'meta_value'     => $this->id,
				'fields'         => 'ids',
			);

			$registers = get_posts( $args );

			return $registers;
		}

		/*
		|--------------------------------------------------------------------------
		| Setters
		|--------------------------------------------------------------------------
		|
		| Functions for setting object data. These should not update anything in the
		| database itself and should only change what is stored in the class
		| object.
		*/

		/**
		 * Set the name of the Receipt
		 *
		 * @param string $value the value to set.
		 */
		public function set_name( $value ) {
			$this->set_prop( 'name', $value );
		}


		/**
		 * Set the the number of copies of the Receipt
		 *
		 * @param string $value the value to set.
		 */
		public function set_num_of_copies( $value ) {
			$this->set_prop( 'num_of_copies', absint( $value ) );
		}

		/**
		 * Set the width of the Receipt
		 *
		 * @param string $value the value to set.
		 */
		public function set_width( $value ) {
			$this->set_prop( 'width', (float) $value );
		}

		/**
		 * Set the logo of the Receipt
		 *
		 * @param string $value the value to set.
		 */
		public function set_logo( $value ) {
			$this->set_prop( 'logo', $value );
		}

		/**
		 * Set the enable_gift_receipt value.
		 *
		 * @param bool $value the value to set.
		 *
		 * @since 2.0.0
		 */
		public function set_enable_gift_receipt( $value ) {
			$this->set_prop( 'enable_gift_receipt', wc_bool_to_string( $value ) );
		}

		/**
		 * Set the show_sku param
		 *
		 * @param bool $value the value to set.
		 *
		 * @since 2.0.0
		 */
		public function set_show_sku( $value ) {
			$this->set_prop( 'show_sku', wc_bool_to_string( $value ) );
		}

		/**
		 * Set the sku_label param
		 *
		 * @param string $value the value to set.
		 *
		 * @since 2.0.0
		 */
		public function set_sku_label( $value ) {
			$this->set_prop( 'sku_label', $value );
		}

		/**
		 * Set the show_prices_including_tax param
		 *
		 * @param bool $value the value to set.
		 *
		 * @since 2.0.0
		 */
		public function set_show_prices_including_tax( $value ) {
			$this->set_prop( 'show_prices_including_tax', wc_bool_to_string( $value ) );
		}

		/**
		 * Set the show_tax_details param
		 *
		 * @param bool $value the value to set.
		 *
		 * @since 2.0.0
		 */
		public function set_show_tax_details( $value ) {
			$this->set_prop( 'show_tax_details', wc_bool_to_string( $value ) );
		}

		/**
		 * Set the show_taxes param
		 *
		 * @param string $value the value to set.
		 *
		 * @since 2.0.0
		 */
		public function set_show_taxes( $value ) {
			$allowed_values = array( 'total', 'itemized' );
			$value          = in_array( $value, $allowed_values, true ) ? $value : 'total';

			$this->set_prop( 'show_taxes', $value );
		}

		/**
		 * Set if the store name should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_store_name( $value ) {
			$this->set_prop( 'show_store_name', wc_bool_to_string( $value ) );
		}

		/**
		 * Set if the store vat should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_vat( $value ) {
			$this->set_prop( 'show_vat', wc_bool_to_string( $value ) );
		}

		/**
		 * Set the vat label
		 *
		 * @param string $value the value to set.
		 */
		public function set_vat_label( $value ) {
			$this->set_prop( 'vat_label', $value );
		}

		/**
		 * Set if the store address should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_address( $value ) {
			$this->set_prop( 'show_address', wc_bool_to_string( $value ) );
		}

		/**
		 * Set if the store contact info should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_contact_info( $value ) {
			$this->set_prop( 'show_contact_info', wc_bool_to_string( $value ) );
		}

		/**
		 * Set if the store phone should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_phone( $value ) {
			$this->set_prop( 'show_phone', wc_bool_to_string( $value ) );
		}

		/**
		 * Set if the store email address should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_email( $value ) {
			$this->set_prop( 'show_email', wc_bool_to_string( $value ) );
		}

		/**
		 * Set if the store fax should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_fax( $value ) {
			$this->set_prop( 'show_fax', wc_bool_to_string( $value ) );
		}

		/**
		 * Set if the store website should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_website( $value ) {
			$this->set_prop( 'show_website', wc_bool_to_string( $value ) );
		}

		/**
		 * Set if the store social info should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_social_info( $value ) {
			$this->set_prop( 'show_social_info', wc_bool_to_string( $value ) );
		}

		/**
		 * Set if the facebook info should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_facebook( $value ) {
			$this->set_prop( 'show_facebook', wc_bool_to_string( $value ) );
		}

		/**
		 * Set if the twitter info should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_twitter( $value ) {
			$this->set_prop( 'show_twitter', wc_bool_to_string( $value ) );
		}

		/**
		 * Set if the instagram info should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_instagram( $value ) {
			$this->set_prop( 'show_instagram', wc_bool_to_string( $value ) );
		}

		/**
		 * Set if the youtube info should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_youtube( $value ) {
			$this->set_prop( 'show_youtube', wc_bool_to_string( $value ) );
		}


		/**
		 * Set if the order date should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_order_date( $value ) {
			$this->set_prop( 'show_order_date', wc_bool_to_string( $value ) );
		}


		/**
		 * Set the label for the date in the Receipt
		 *
		 * @param string $value the value to set.
		 */
		public function set_order_date_label( $value ) {
			$this->set_prop( 'order_date_label', $value );
		}

		/**
		 * Set if the order number should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_order_number( $value ) {
			$this->set_prop( 'show_order_number', wc_bool_to_string( $value ) );
		}

		/**
		 * Set the order number label in the Receipt
		 *
		 * @param string $value the value to set.
		 */
		public function set_order_number_label( $value ) {
			$this->set_prop( 'order_number_label', $value );
		}

		/**
		 * Set if the customer of order should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_order_customer( $value ) {
			$this->set_prop( 'show_order_customer', wc_bool_to_string( $value ) );
		}

		/**
		 * Set the customer label in the Receipt
		 *
		 * @param string $value the value to set.
		 */
		public function set_order_customer_label( $value ) {
			$this->set_prop( 'order_customer_label', $value );
		}

		/**
		 * Set if the register should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_order_register( $value ) {
			$this->set_prop( 'show_order_register', wc_bool_to_string( $value ) );
		}

		/**
		 * Set the order register label in the Receipt
		 *
		 * @param string $value the value to set.
		 */
		public function set_order_register_label( $value ) {
			$this->set_prop( 'order_register_label', $value );
		}

		/**
		 * Set if the cashier should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 */
		public function set_show_cashier( $value ) {
			$this->set_prop( 'show_cashier', wc_bool_to_string( $value ) );
		}


		/**
		 * Set the label for the cashier
		 *
		 * @param string $value the value to set.
		 */
		public function set_cashier_label( $value ) {
			$this->set_prop( 'cashier_label', $value );
		}

		/**
		 * Set if the shipping should be printed in the Receipt
		 *
		 * @param bool $value the value to set.
		 *
		 * @since 1.6.0
		 */
		public function set_show_shipping( $value ) {
			$this->set_prop( 'show_shipping', wc_bool_to_string( $value ) );
		}


		/**
		 * Set the label for the shipping
		 *
		 * @param string $value the value to set.
		 *
		 * @since 1.6.0
		 */
		public function set_shipping_label( $value ) {
			$this->set_prop( 'shipping_label', $value );
		}

		/**
		 * Set the receipt footer
		 *
		 * @param string $value the value to set.
		 */
		public function set_receipt_footer( $value ) {
			$this->set_prop( 'receipt_footer', $value );
		}

		/*
		|--------------------------------------------------------------------------
		| Conditionals
		|--------------------------------------------------------------------------
		*/

		/**
		 * Is published?
		 *
		 * @return bool
		 */
		public function is_published() {
			return 'publish' === $this->get_post_status();
		}

	}
}

if ( ! function_exists( 'yith_pos_get_receipt' ) ) {
	/**
	 * Get receipt object.
	 *
	 * @param int|YITH_POS_Receipt|WP_Post $receipt The receipt.
	 *
	 * @return YITH_POS_Receipt
	 */
	function yith_pos_get_receipt( $receipt ) {
		return new YITH_POS_Receipt( $receipt );
	}
}
