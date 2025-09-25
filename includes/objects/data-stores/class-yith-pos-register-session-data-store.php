<?php
/**
 * YITH POS Register Session Data Store
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

/**
 * YITH POS Register Session Data Store
 *
 * @since  2.0.0
 */
class YITH_POS_Register_Session_Data_Store implements YITH_POS_Object_Data_Store_Interface {

	/**
	 * Get the DB table name.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;

		return $wpdb->yith_pos_register_sessions;
	}

	/**
	 * Method to create a new record of a YITH_POS_Data based object.
	 *
	 * @param YITH_POS_Register_Session $session The session object.
	 */
	public function create( &$session ) {
		global $wpdb;
		$props_to_update = $session->get_data_keys();

		if ( ! $session->get_open( 'edit' ) ) {
			$session->set_open( time() );
		}

		if ( ! $session->get_cashiers( 'edit' ) ) {
			$session->add_cashier();
		}

		$data = array();
		foreach ( $props_to_update as $prop ) {
			$value = $session->{"get_$prop"}( 'edit' );
			$value = is_array( $value ) ? serialize( $value ) : $value; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			if ( in_array( $prop, array( 'open', 'closed' ), true ) ) {
				$value = $value > 0 ? gmdate( 'Y-m-d H:i:s', $value ) : null;
			}

			$data[ $prop ] = $value;
		}

		$result = $wpdb->insert( $wpdb->yith_pos_register_sessions, $data );
		$id     = $result ? (int) $wpdb->insert_id : 0;

		if ( $id ) {
			$session->set_id( $id );
			$session->apply_changes();

			do_action( 'yith_pos_register_session_update_create', $session );
		}
	}

	/**
	 * Method to read a record. Creates a new YITH_POS_Data based object.
	 *
	 * @param YITH_POS_Register_Session $session The session object.
	 *
	 * @throws Exception If invalid register session.
	 */
	public function read( &$session ) {
		global $wpdb;
		$session->set_defaults();

		if ( ! $session->get_id() ) {
			throw new Exception( __( 'Invalid register session.', 'yith-point-of-sale-for-woocommerce' ) );
		}

		$props = $wpdb->get_row(
			$wpdb->prepare( "SELECT * from {$wpdb->yith_pos_register_sessions} where id=%d", $session->get_id() ),
			ARRAY_A
		);

		if ( ! $props ) {
			throw new Exception( __( 'Invalid register session.', 'yith-point-of-sale-for-woocommerce' ) );
		}

		$props = array_map( 'maybe_unserialize', $props );

		$props['open']   = $props['open'] > 0 ? wc_string_to_timestamp( $props['open'] ) : null;
		$props['closed'] = $props['closed'] > 0 ? wc_string_to_timestamp( $props['closed'] ) : null;

		$session->set_props( $props );
		$session->set_object_read( true );

		do_action( 'yith_pos_register_session_read', $session );
	}

	/**
	 * Updates a record in the database.
	 *
	 * @param YITH_POS_Register_Session $session The session object.
	 */
	public function update( &$session ) {
		global $wpdb;
		$props_to_update = $this->get_props_to_update( $session );

		$data = array();
		foreach ( $props_to_update as $prop ) {
			$value = $session->{"get_$prop"}( 'edit' );
			$value = is_array( $value ) ? serialize( $value ) : $value; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			if ( in_array( $prop, array( 'open', 'closed' ), true ) ) {
				$value = $value > 0 ? gmdate( 'Y-m-d H:i:s', $value ) : null;
			}

			$data[ $prop ] = $value;
		}

		$result = $wpdb->update( $wpdb->yith_pos_register_sessions, $data, array( 'id' => $session->get_id() ) );

		if ( $result ) {
			$session->apply_changes();
			do_action( 'yith_pos_register_session_update', $session );
		}
	}

	/**
	 * Deletes a record from the database.
	 *
	 * @param @param YITH_POS_Register_Session $session The session object.
	 *
	 * @return bool result
	 */
	public function delete( &$session ) {
		global $wpdb;
		$table  = self::get_table_name();
		$result = false;
		$id     = $session->get_id();

		if ( $id ) {
			$object = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->yith_pos_register_sessions WHERE id = %d", $id ) );

			if ( $object ) {
				do_action( 'yith_pos_register_session_before_delete', $session );
				$result = $wpdb->delete( $table, array( 'id' => $id ) );

				$session->set_id( 0 );
				do_action( 'yith_pos_register_session_delete', $id );
			}
		}

		return $result;
	}

	/**
	 * Get props to update.
	 *
	 * @param YITH_POS_Register_Session $session The session.
	 *
	 * @return array
	 */
	protected function get_props_to_update( $session ) {
		$props_to_update = array();
		$changed_props   = $session->get_changes();
		$props           = $session->get_data_keys();

		// Props should be updated if they are a part of the $changed array.
		foreach ( $props as $prop ) {
			if ( array_key_exists( $prop, $changed_props ) ) {
				$props_to_update[] = $prop;
			}
		}

		return $props_to_update;
	}

	/**
	 * Query items
	 *
	 * @param array $args Arguments.
	 *
	 * @return array|object
	 */
	public static function query( $args = array() ) {
		global $wpdb;
		$defaults = array(
			'items_per_page' => 5,
			'status'         => 'any',
			'paginate'       => false,
			'page'           => 1,
			'store_id'       => false,
			'register_id'    => false,
			'order'          => 'DESC',
			'order_by'       => 'id',
			'return'         => 'register-session',
		);
		$args     = wp_parse_args( $args, $defaults );
		$table    = self::get_table_name();

		$select        = "SELECT SQL_CALC_FOUND_ROWS id FROM {$table} as register_sessions ";
		$where         = '';
		$where_clauses = array();

		foreach ( array( 'store_id', 'register_id' ) as $id_key ) {
			if ( false !== $args[ $id_key ] ) {
				if ( is_array( $args[ $id_key ] ) && isset( $args[ $id_key ]['value'] ) ) {
					$ids             = (array) $args[ $id_key ]['value'];
					$allowed_compare = array(
						'IN'     => 'IN',
						'NOT IN' => 'NOT IN',
						'='      => 'IN',
						'!='     => 'NOT IN',
					);
					$compare         = $args[ $id_key ]['compare'] ?? 'IN';
					$compare         = array_key_exists( $compare, $allowed_compare ) ? $allowed_compare[ $compare ] : 'IN';
				} else {
					$ids     = (array) $args[ $id_key ];
					$compare = 'IN';
				}
				if ( $ids ) {
					$ids             = implode( ',', array_map( 'absint', $ids ) );
					$where_clauses[] = "register_sessions.$id_key $compare ($ids)";
				} else {
					$where_clauses[] = '0 == 1';
				}
			}
		}

		if ( 'opened' === $args['status'] ) {
			$where_clauses[] = '( register_sessions.closed IS NULL OR register_sessions.closed <= 0 )';
		} elseif ( 'closed' === $args['status'] ) {
			$where_clauses[] = 'register_sessions.closed > 0';
		}

		if ( $where_clauses ) {
			$where = ' WHERE ' . implode( ' AND ', $where_clauses ) . ' ';
		}

		$args['order'] = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$order         = " ORDER BY register_sessions.{$args[ 'order_by' ]} {$args[ 'order' ]} ";

		$limits = '';
		if ( $args['items_per_page'] >= 0 ) {
			$offset = $args['page'] > 1 ? absint( ( $args['page'] - 1 ) * $args['items_per_page'] ) . ', ' : '';
			$limits = ' LIMIT ' . $offset . $args['items_per_page'];
		}

		$query = $select . $where . $order . $limits;

		$items = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$total = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
		$items = array_map( 'intval', $items );

		$items = ( 'ids' === $args['return'] ) ? $items : array_filter( array_map( 'yith_pos_get_register_session', $items ) );

		if ( $args['paginate'] ) {
			$items = (object) array(
				'items'         => $items,
				'total'         => $total,
				'max_num_pages' => $args['items_per_page'] > 0 ? ceil( $total / $args['items_per_page'] ) : 1,
			);
		}

		return $items;
	}
}
