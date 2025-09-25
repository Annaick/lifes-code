<?php
/**
 * Register class.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Register' ) ) {
	/**
	 * Class YITH_POS_Register
	 */
	class YITH_POS_Register extends YITH_POS_CPT_Object {
		/**
		 * Data.
		 *
		 * @var array
		 */
		protected $data = array(
			'name'                        => '',
			'status'                      => 'closed',
			'store_id'                    => 0,
			'enabled'                     => 'yes',
			'scanner_enabled'             => 'yes',
			'guest_enabled'               => 'no',
			'payment_methods'             => array(),
			'what_to_show'                => 'all',
			'show_categories'             => array(),
			'show_products'               => array(),
			'how_to_show_in_dashboard'    => 'products',
			'visibility'                  => 'all',
			'visibility_cashiers'         => array(),
			'receipt_id'                  => 0,
			'cash_hand'                   => 0,
			'closing_report_enabled'      => 'yes',
			'closing_report_note_enabled' => 'yes',
		);

		/**
		 * Object type.
		 *
		 * @var string
		 */
		protected $object_type = 'register';

		/**
		 * Post type.
		 *
		 * @var string
		 */
		protected $post_type = 'yith-pos-register';

		/*
		|--------------------------------------------------------------------------
		| Getters
		|--------------------------------------------------------------------------
		|
		| Methods for getting data from object.
		*/

		/**
		 * Return the name of the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_name( $context = 'view' ) {
			return $this->get_prop( 'name', $context );
		}

		/**
		 * Return the store ID related to the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_store_id( $context = 'view' ) {
			return $this->get_prop( 'store_id', $context );
		}

		/**
		 * Return the "enabled" status of the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_enabled( $context = 'view' ) {
			return $this->get_prop( 'enabled', $context );
		}

		/**
		 * Return the guest enabled value of the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_guest_enabled( $context = 'view' ) {
			return $this->get_prop( 'guest_enabled', $context );
		}

		/**
		 * Return the scanner enabled value of the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_scanner_enabled( $context = 'view' ) {
			return $this->get_prop( 'scanner_enabled', $context );
		}

		/**
		 * Return the payment methods of the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return array
		 */
		public function get_payment_methods( $context = 'view' ) {
			return $this->get_prop( 'payment_methods', $context );
		}

		/**
		 * Return what to show in the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_what_to_show( $context = 'view' ) {
			return $this->get_prop( 'what_to_show', $context );
		}

		/**
		 * Return the "show_categories" array of the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return array
		 */
		public function get_show_categories( $context = 'view' ) {
			return $this->get_prop( 'show_categories', $context );
		}

		/**
		 * Return the "show_products" array of the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return array
		 */
		public function get_show_products( $context = 'view' ) {
			return $this->get_prop( 'show_products', $context );
		}

		/**
		 * Return how to show products in the dashboard of the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_how_to_show_in_dashboard( $context = 'view' ) {
			return $this->get_prop( 'how_to_show_in_dashboard', $context );
		}

		/**
		 * Return the visibility of the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_visibility( $context = 'view' ) {
			return $this->get_prop( 'visibility', $context );
		}

		/**
		 * Return the "visibility cashiers" array of the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return array
		 */
		public function get_visibility_cashiers( $context = 'view' ) {
			return $this->get_prop( 'visibility_cashiers', $context );
		}

		/**
		 * Return the receipt ID of the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return int
		 */
		public function get_receipt_id( $context = 'view' ) {
			return $this->get_prop( 'receipt_id', $context );
		}


		/**
		 * Return the "status" option of the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_status( $context = 'view' ) {
			return $this->get_prop( 'status', $context );
		}

		/**
		 * Return the "closing_report_enabled" meta of the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_closing_report_enabled( $context = 'view' ) {
			return $this->get_prop( 'closing_report_enabled', $context );
		}

		/**
		 * Return the "closing_report_enabled" meta of the Register
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_closing_report_note_enabled( $context = 'view' ) {
			return $this->get_prop( 'closing_report_note_enabled', $context );
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
		 * Set the name of the Register
		 *
		 * @param string $value the value to set.
		 */
		public function set_name( $value ) {
			$this->set_prop( 'name', $value );
		}

		/**
		 * Set the store ID of the Register
		 *
		 * @param int $value the value to set.
		 */
		public function set_store_id( $value ) {
			$this->set_prop( 'store_id', absint( $value ) );
		}

		/**
		 * Set the "enabled" status of the Register
		 *
		 * @param bool $value the value to set.
		 */
		public function set_enabled( $value ) {
			$this->set_prop( 'enabled', wc_bool_to_string( $value ) );
		}

		/**
		 * Set the "guest enabled" status of the Register
		 *
		 * @param bool $value the value to set.
		 */
		public function set_guest_enabled( $value ) {
			$this->set_prop( 'guest_enabled', wc_bool_to_string( $value ) );
		}

		/**
		 * Set the "scanner enabled" status of the Register
		 *
		 * @param bool $value the value to set.
		 */
		public function set_scanner_enabled( $value ) {
			$this->set_prop( 'scanner_enabled', wc_bool_to_string( $value ) );
		}

		/**
		 * Set the payment methods of the Register
		 *
		 * @param array $value the value to set.
		 */
		public function set_payment_methods( $value ) {
			$this->set_prop( 'payment_methods', (array) $value );
		}

		/**
		 * Set what to show of the Register
		 *
		 * @param string $value the value to set.
		 */
		public function set_what_to_show( $value ) {
			$this->set_prop( 'what_to_show', $value );
		}

		/**
		 * Set the "show categories" array of the Register
		 *
		 * @param array $value the value to set.
		 */
		public function set_show_categories( $value ) {
			$this->set_prop( 'show_categories', (array) $value );
		}

		/**
		 * Set the "show products" array of the Register
		 *
		 * @param array $value the value to set.
		 */
		public function set_show_products( $value ) {
			$this->set_prop( 'show_products', (array) $value );
		}

		/**
		 * Set how to show products in dashboard of the Register
		 *
		 * @param string $value the value to set.
		 */
		public function set_how_to_show_in_dashboard( $value ) {
			$this->set_prop( 'how_to_show_in_dashboard', $value );
		}

		/**
		 * Set visibility of the Register
		 *
		 * @param string $value the value to set.
		 */
		public function set_visibility( $value ) {
			$this->set_prop( 'visibility', $value );
		}

		/**
		 * Set the "visibility_cashiers" array of the Register
		 *
		 * @param array $value the value to set.
		 */
		public function set_visibility_cashiers( $value ) {
			$this->set_prop( 'visibility_cashiers', (array) $value );
		}

		/**
		 * Set the receipt ID of the Register
		 *
		 * @param int $value the value to set.
		 */
		public function set_receipt_id( $value ) {
			$this->set_prop( 'receipt_id', absint( $value ) );
		}


		/**
		 * Set the "status" status of the Register
		 *
		 * @param string $value the value to set.
		 */
		public function set_status( $value ) {
			$value = in_array( $value, array_keys( yith_pos_register_statuses() ), true ) ? $value : 'closed';
			$this->set_prop( 'status', $value );
		}

		/**
		 * Set the "closing_report_enabled" meta of the Register
		 *
		 * @param bool $value the value to set.
		 */
		public function set_closing_report_enabled( $value ) {
			$this->set_prop( 'closing_report_enabled', $value );
		}

		/**
		 * Set the "closing_report_note_enabled" meta of the Register
		 *
		 * @param bool $value the value to set.
		 */
		public function set_closing_report_note_enabled( $value ) {
			$this->set_prop( 'closing_report_note_enabled', $value );
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

		/**
		 * Is enabled?
		 *
		 * @return bool
		 */
		public function is_enabled() {
			return 'yes' === $this->get_enabled() && $this->is_published();
		}

		/**
		 * Is guest enabled?
		 *
		 * @return bool
		 */
		public function is_guest_enabled() {
			return false; // todo: remove when adding the Guest Register feature.
		}


		/**
		 * Is scanner enabled?
		 *
		 * @return bool
		 */
		public function is_scanner_enabled() {
			return 'yes' === $this->get_scanner_enabled();
		}


		/**
		 * Is receipt enabled?
		 *
		 * @return bool
		 */
		public function is_receipt_enabled() {
			return ! ! $this->get_receipt_id() && $this->is_published();
		}

		/**
		 * Are notes enabled?
		 *
		 * @return bool
		 */
		public function are_notes_enabled() {
			return 'yes' === $this->get_notes_enabled();
		}

		/**
		 * Check for the status of the register
		 *
		 * @param string $status The status.
		 *
		 * @return bool
		 */
		public function has_status( $status ) {
			return $status === $this->get_status();
		}

		/*
		|--------------------------------------------------------------------------
		| Non-CRUD Getters
		|--------------------------------------------------------------------------
		*/

		/**
		 * Return the current register session
		 *
		 * @return YITH_POS_Register_Session|false
		 */
		public function get_current_session() {
			$sessions = yith_pos_get_register_sessions(
				array(
					'register_id'    => $this->get_id(),
					'status'         => 'opened',
					'items_per_page' => 1,
				)
			);

			return ! ! $sessions ? current( $sessions ) : false;
		}

		/**
		 * Get the related store
		 *
		 * @return bool|YITH_POS_Store
		 */
		public function get_store() {
			return yith_pos_get_store( $this->get_store_id() );
		}

		/**
		 * Get the receipt
		 *
		 * @return bool|YITH_POS_Receipt
		 */
		public function get_receipt() {
			return $this->get_receipt_id() ? yith_pos_get_receipt( $this->get_receipt_id() ) : false;
		}

		/**
		 * Get inclusion query options.
		 *
		 * @return array
		 */
		public function get_inclusion_query_options() {
			$options = array();

			if ( 'all' !== $this->get_what_to_show() ) {
				$show_products = $this->get_show_products();
				if ( $show_products && isset( $show_products['type'], $show_products['products'] ) && $show_products['products'] ) {
					if ( 'include' === $show_products['type'] ) {
						$options['include'] = (array) $show_products['products'];
					} else {
						$options['exclude'] = (array) $show_products['products'];
					}
				}

				$show_categories = $this->get_show_categories();
				if ( $show_categories && isset( $show_categories['type'], $show_categories['categories'] ) && $show_categories['categories'] ) {
					if ( 'include' === $show_categories['type'] ) {
						$options['category'] = $show_categories['categories'];
					} else {
						// exclude_category is added to the product REST by POS.
						$options['exclude_category'] = $show_categories['categories'];
					}
				}

				foreach ( $options as $key => $values ) {
					if ( $values && is_array( $values ) ) {
						$options[ $key ] = implode( ',', $values );
					}
				}
			}

			return $options;
		}

		/**
		 * Get category query options.
		 *
		 * @return array
		 */
		public function get_category_query_options() {
			$options = array();
			if ( 'all' !== $this->get_what_to_show() ) {
				$show_categories = $this->get_show_categories();
				if ( $show_categories && isset( $show_categories['type'], $show_categories['categories'] ) && $show_categories['categories'] ) {
					if ( 'include' === $show_categories['type'] ) {
						$options['include'] = $show_categories['categories'];
					} else {
						$options['exclude'] = $show_categories['categories'];
					}
				}
			}

			return apply_filters( 'yith_pos_get_category_query_options', $options );
		}
	}
}

if ( ! function_exists( 'yith_pos_get_register' ) ) {
	/**
	 * Retrieve a register.
	 *
	 * @param int|YITH_POS_Register|WP_Post $register The register.
	 *
	 * @return false|YITH_POS_Register
	 */
	function yith_pos_get_register( $register ) {
		$the_register = new YITH_POS_Register( $register );

		return $the_register->is_valid() ? $the_register : false;
	}
}
