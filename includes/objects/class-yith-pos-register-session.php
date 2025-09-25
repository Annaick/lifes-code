<?php
/**
 * Register session.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Register_Session' ) ) {
	/**
	 * YITH_POS_Register_Session class.
	 *
	 * @since  2.0.0
	 */
	class YITH_POS_Register_Session extends YITH_POS_Data {
		/**
		 * The object type.
		 *
		 * @var string
		 */
		protected $object_type = 'register-session';

		/**
		 * The object data.
		 *
		 * @var array
		 */
		protected $data = array(
			'store_id'     => 0,
			'register_id'  => 0,
			'open'         => false,
			'closed'       => false,
			'cashiers'     => array(),
			'total'        => 0,
			'cash_in_hand' => array(),
			'report'       => array(),
			'note'         => '',
		);

		/**
		 * The constructor
		 *
		 * @param false|array|self|int $obj The object.
		 *
		 * @throws Exception If invalid register session.
		 */
		public function __construct( $obj = false ) {
			parent::__construct( $obj );

			if ( is_numeric( $obj ) && $obj > 0 ) {
				$this->set_id( $obj );
			} elseif ( $obj instanceof self ) {
				$this->set_id( absint( $obj->get_id() ) );
			} elseif ( ! empty( $obj->id ) ) {
				$this->set_id( absint( $obj->id ) );
			} else {
				$this->set_object_read( true );
			}

			$this->data_store = new YITH_POS_Register_Session_Data_Store();
			if ( $this->get_id() > 0 ) {
				$this->data_store->read( $this );
			}
		}

		/**
		 * |--------------------------------------------------------------------------
		 * | Getters
		 * |--------------------------------------------------------------------------
		 * |
		 * | Methods for getting data from object.
		 */

		/**
		 * Return the store_id
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_store_id( $context = 'view' ) {
			return $this->get_prop( 'store_id', $context );
		}

		/**
		 * Return the register_id
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_register_id( $context = 'view' ) {
			return $this->get_prop( 'register_id', $context );
		}

		/**
		 * Return the open
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_open( $context = 'view' ) {
			return $this->get_prop( 'open', $context );
		}

		/**
		 * Return the closed
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_closed( $context = 'view' ) {
			return $this->get_prop( 'closed', $context );
		}

		/**
		 * Return the cashiers
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return array
		 */
		public function get_cashiers( $context = 'view' ) {
			return $this->get_prop( 'cashiers', $context );
		}

		/**
		 * Return the total
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_total( $context = 'view' ) {
			return $this->get_prop( 'total', $context );
		}

		/**
		 * Return the cash_in_hand
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return array
		 */
		public function get_cash_in_hand( $context = 'view' ) {
			return $this->get_prop( 'cash_in_hand', $context );
		}

		/**
		 * Return the report
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return array
		 */
		public function get_report( $context = 'view' ) {
			return $this->get_prop( 'report', $context );
		}

		/**
		 * Return the note
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_note( $context = 'view' ) {
			return $this->get_prop( 'note', $context );
		}


		/**
		 * |--------------------------------------------------------------------------
		 * | Setters
		 * |--------------------------------------------------------------------------
		 * |
		 * | Methods for setting object data.
		 */

		/**
		 * Set the store_id
		 *
		 * @param int $value The value to set.
		 */
		public function set_store_id( $value ) {
			$this->set_prop( 'store_id', absint( $value ) );
		}

		/**
		 * Set the register_id
		 *
		 * @param int $value The value to set.
		 */
		public function set_register_id( $value ) {
			$this->set_prop( 'register_id', absint( $value ) );
		}

		/**
		 * Set the open
		 *
		 * @param int|string $value The value to set.
		 */
		public function set_open( $value ) {
			$value = ! ! $value && is_string( $value ) ? strtotime( $value ) : $value;
			$this->set_prop( 'open', $value );
		}

		/**
		 * Set the closed
		 *
		 * @param int|string $value The value to set.
		 */
		public function set_closed( $value ) {
			$value = ! ! $value && is_string( $value ) ? strtotime( $value ) : $value;
			$this->set_prop( 'closed', $value );
		}

		/**
		 * Set the cashiers
		 *
		 * @param array $value The value to set.
		 */
		public function set_cashiers( $value ) {
			$value = is_array( $value ) ? $value : array();
			$value = array_filter( array_map( array( $this, 'filter_cashier' ), $value ) );
			$this->set_prop( 'cashiers', $value );
		}

		/**
		 * Set the total
		 *
		 * @param string $value The value to set.
		 */
		public function set_total( $value ) {
			$this->set_prop( 'total', $value );
		}

		/**
		 * Set the cash_in_hand
		 *
		 * @param array $value The value to set.
		 */
		public function set_cash_in_hand( $value ) {
			$this->set_prop( 'cash_in_hand', is_array( $value ) ? $value : array() );
		}

		/**
		 * Set the report
		 *
		 * @param array $value The value to set.
		 */
		public function set_report( $value ) {
			$value = is_array( $value ) ? $value : array();

			// Map single reports, since in POS 2.0 the 'amount' key was replaced with 'value'.
			$value = array_map(
				function ( $single ) {
					if ( isset( $single['amount'] ) ) {
						$single['value'] = $single['amount'];
						unset( $single['amount'] );
					}

					return $single;
				},
				$value
			);

			$this->set_prop( 'report', is_array( $value ) ? $value : array() );
		}

		/**
		 * Set the note
		 *
		 * @param string $value The value to set.
		 */
		public function set_note( $value ) {
			$this->set_prop( 'note', $value );
		}

		/*
		|--------------------------------------------------------------------------
		| Conditionals
		|--------------------------------------------------------------------------
		*/

		/**
		 * Is closed?
		 *
		 * @return bool
		 */
		public function is_closed() {
			return ! ! $this->get_closed();
		}

		/**
		 * |--------------------------------------------------------------------------
		 * | Non-CRUD methods
		 * |--------------------------------------------------------------------------
		 * |
		 */

		/**
		 * Get open date.
		 *
		 * @return false|WC_DateTime
		 */
		public function get_open_date() {
			$timestamp = $this->get_open();

			return yith_pos_timestamp_to_datetime( $timestamp );
		}

		/**
		 * Get closed date.
		 *
		 * @return false|WC_DateTime
		 */
		public function get_closed_date() {
			$timestamp = $this->get_closed();

			return yith_pos_timestamp_to_datetime( $timestamp );
		}


		/**
		 * Return the list of the cashier names.
		 *
		 * @return array
		 */
		public function get_cashier_names(): array {
			$cashiers = $this->get_cashiers();
			$cashiers = array_unique( wp_list_pluck( $cashiers, 'id' ) );

			return array_map(
				function ( $cashier_id ) {
					return yith_pos_get_employee_name( $cashier_id, array( 'hide_nickname' => true ) );
				},
				$cashiers
			);
		}

		/**
		 * Add a cashier to the session
		 *
		 * @param int $cashier_id The cashier id.
		 *
		 * @return bool Return false if cashier id is equal to zero.
		 */
		public function add_cashier( $cashier_id = 0 ) {
			$cashier_id = ! ! $cashier_id ? $cashier_id : get_current_user_id();
			if ( ! $cashier_id ) {
				return false;
			}
			$now = gmdate( 'Y-m-d H:i:s' );

			$cashiers   = $this->get_cashiers();
			$cashiers[] = array(
				'id'    => $cashier_id,
				'login' => $now,
			);
			$this->set_cashiers( $cashiers );

			return true;
		}

		/**
		 * Add cash in hand to the session
		 *
		 * @param array $to_add The cash in hand.
		 */
		public function add_cash_in_hand( $to_add = array() ) {
			$defaults       = array(
				'amount'    => '0',
				'cashier'   => 0,
				'reason'    => '',
				'timestamp' => time(),
			);
			$to_add         = wp_parse_args( $to_add, $defaults );
			$cash_in_hand   = $this->get_cash_in_hand();
			$cash_in_hand[] = $to_add;
			$this->set_cash_in_hand( $cash_in_hand );
		}

		/**
		 * Filter the cashier allow coherence
		 *
		 * @param array $cashier The cashier info.
		 *
		 * @return array|false
		 */
		private function filter_cashier( $cashier ) {
			if ( ! isset( $cashier['id'], $cashier['login'] ) ) {
				return false;
			}
			$cashier['id'] = absint( $cashier['id'] );
			if ( is_numeric( $cashier['login'] ) ) {
				$cashier['login'] = gmdate( 'Y-m-d H:i:s', $cashier['login'] );
			}

			return $cashier;
		}

		/**
		 * Generate Reports
		 *
		 * @param array $args Arguments.
		 *
		 * @return array
		 */
		public function generate_reports( $args = array() ) {
			$cash_in_hands       = $this->get_cash_in_hand();
			$cash_in_hands_total = array_sum( array_column( $cash_in_hands, 'amount' ) );

			$reports = array();

			$order_args = array(
				'register' => $this->get_register_id(),
			);

			if ( $this->is_closed() ) {
				$order_args['date_created'] = $this->get_open_date()->getTimestamp() . '...' . $this->get_closed_date()->getTimestamp();
			} else {
				$order_args['date_created'] = '>' . $this->get_open_date()->getTimestamp();
			}

			$order_args = wp_parse_args( $order_args, $args );

			$stats_query = new YITH_POS_Order_Stats_Query( $order_args );
			$stats       = $stats_query->get_stats();

			if ( isset( $stats['orders_count'] ) ) {
				$reports['orders_count'] = $stats['orders_count'];
				unset( $stats['orders_count'] );
			}

			if ( isset( $stats['num_items_sold'] ) ) {
				$reports['num_items_sold'] = $stats['num_items_sold'];
				unset( $stats['num_items_sold'] );
			}

			$reports['cash_in_hand'] = array(
				'title' => __( 'Cash in hand', 'yith-point-of-sale-for-woocommerce' ),
				'type'  => 'price',
				'value' => yith_pos_format_price( $cash_in_hands_total ),
			);

			$reports = array_merge( $reports, $stats );

			$cash_method_total = $stats['payment_yith_pos_cash_gateway']['value'] ?? 0;
			$cash_total        = $cash_in_hands_total + $cash_method_total;

			$reports['cash_total'] = array(
				'title' => __( 'Cash Total', 'yith-point-of-sale-for-woocommerce' ),
				'type'  => 'price',
				'value' => yith_pos_format_price( $cash_total ),
			);

			return $reports;
		}

		/**
		 * Update reports and total
		 */
		public function update_reports_and_total() {
			$report = $this->generate_reports();
			$total  = $report['total_sales']['value'] ?? 0;

			$this->set_report( $report );
			$this->set_total( $total );
		}

		/**
		 * Close the session
		 */
		public function close() {
			$this->set_closed( time() );
		}

		/**
		 * Close the session
		 */
		public function close_and_save() {
			$this->close();
			$this->save();
		}

		/**
		 * Get the Edit link
		 *
		 * @return string
		 */
		public function get_edit_link() {
			return add_query_arg(
				array(
					'page'       => 'yith_pos_panel',
					'tab'        => 'registers',
					'sub_tab'    => 'registers-register-sessions',
					'session_id' => $this->get_id(),
				),
				admin_url( 'admin.php' )
			);
		}

		/**
		 * Retrieve a link for a specific action.
		 *
		 * @param string $action The action.
		 * @param array  $args   Extra arguments.
		 *
		 * @return string
		 */
		private function get_action_url( string $action, array $args = array() ): string {

			return add_query_arg(
				array_merge(
					$args,
					array(
						'yith-pos-register-session-action' => $action,
						'yith-pos-register-session-nonce'  => wp_create_nonce( $action ),
						'session_id'                       => $this->get_id(),
					)
				),
				site_url()
			);
		}

		/**
		 * Get the URL to download reports.
		 *
		 * @return string
		 */
		public function get_download_reports_url() {
			return $this->get_action_url( 'download_reports' );
		}

		/**
		 * Get the URL to download cashier reports.
		 *
		 * @param int $cashier_id Cashier ID.
		 *
		 * @return string
		 */
		public function get_download_cashier_reports_url( int $cashier_id = 0 ) {
			$cashier_id = ! ! $cashier_id ? absint( $cashier_id ) : get_current_user_id();

			return $this->get_action_url( 'download_cashier_reports', compact( 'cashier_id' ) );
		}

		/**
		 * Return the data.
		 *
		 * @return array
		 */
		public function get_data() {
			$data = parent::get_data();

			$data['formatted_open']   = gmdate( 'Y-m-d H:i:s', $this->get_open() );
			$data['formatted_closed'] = $this->is_closed() ? gmdate( 'Y-m-d H:i:s', $this->get_closed() ) : false;

			return $data;
		}
	}
}
