<?php
/**
 * POS Data class.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Data' ) ) {
	/**
	 * Class YITH_POS_Data
	 *
	 * @since  2.0.0
	 */
	abstract class YITH_POS_Data {
		/**
		 * Object data.
		 *
		 * @var array
		 */
		protected $data = array();

		/**
		 * Changes.
		 *
		 * @var array
		 */
		protected $changes = array();

		/**
		 * The data store.
		 *
		 * @var YITH_POS_Object_Data_Store_Interface
		 */
		protected $data_store;

		/**
		 *  The ID.
		 *
		 * @var int
		 */
		protected $id;

		/**
		 * The type.
		 *
		 * @var string
		 */
		protected $object_type = 'yith_pos_data';

		/**
		 * Object read flag.
		 *
		 * @var bool
		 */
		protected $object_read = false;

		/**
		 * Default data.
		 *
		 * @var array
		 */
		protected $default_data = array();

		/**
		 * YITH_POS_Data constructor.
		 *
		 * @param array|self $obj The object.
		 */
		public function __construct( $obj ) {
			$this->default_data = $this->data;
		}

		/**
		 * Prefix for action and filter hooks on data.
		 *
		 * @return string
		 */
		protected function get_hook_prefix() {
			return 'yith_pos_' . $this->object_type . '_get_';
		}

		/**
		 * Prefix for action and filter hooks on data.
		 *
		 * @return string
		 */
		protected function get_hook() {
			return 'yith_pos_' . $this->object_type . '_get';
		}

		/**
		 * Get the data store.
		 *
		 * @return object
		 */
		public function get_data_store() {
			return $this->data_store;
		}

		/**
		 * Return data changes only
		 *
		 * @return array
		 */
		public function get_changes() {
			return $this->changes;
		}

		/**
		 * Get object property.
		 *
		 * @param string $prop    The property.
		 * @param string $context What the value is for. Valid values are view and edit.
		 *
		 * @return mixed
		 */
		protected function get_prop( $prop, $context = 'view' ) {
			$value = null;

			if ( array_key_exists( $prop, $this->data ) ) {
				$value = array_key_exists( $prop, $this->changes ) ? $this->changes[ $prop ] : $this->data[ $prop ];

				if ( 'view' === $context ) {
					$value = apply_filters( $this->get_hook_prefix() . $prop, $value, $this );
					$value = apply_filters( $this->get_hook(), $value, $prop, $this );
				}
			}

			return $value;
		}

		/**
		 * Set an object property
		 *
		 * @param string $prop  The property.
		 * @param mixed  $value The value.
		 */
		protected function set_prop( $prop, $value ) {
			if ( array_key_exists( $prop, $this->data ) ) {
				if ( true === $this->object_read ) {
					if ( $value !== $this->data[ $prop ] || array_key_exists( $prop, $this->changes ) ) {
						$this->changes[ $prop ] = $value;
					}
				} else {
					$this->data[ $prop ] = $value;
				}
			}
		}

		/**
		 * Set object properties.
		 *
		 * @param array $props Properties.
		 */
		public function set_props( $props ) {
			foreach ( $props as $key => $value ) {
				$setter = 'set_' . $key;
				if ( is_callable( array( $this, $setter ) ) ) {
					$this->$setter( $value );
				}
			}
		}

		/**
		 * Merge changes with data and clear.
		 */
		public function apply_changes() {
			$this->data    = array_replace_recursive( $this->data, $this->changes );
			$this->changes = array();
		}

		/**
		 * Store options in DB
		 *
		 * @return int
		 */
		public function save() {
			if ( ! $this->data_store ) {
				return $this->get_id();
			}

			do_action( 'yith_pos_before_' . $this->object_type . '_object_save', $this, $this->data_store );

			if ( $this->get_id() ) {
				$this->data_store->update( $this );
			} else {
				$this->data_store->create( $this );
			}

			do_action( 'yith_pos_after_' . $this->object_type . '_object_save', $this, $this->data_store );

			return $this->get_id();
		}

		/**
		 * Get the object ID.
		 *
		 * @return int
		 */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Set the object ID.
		 *
		 * @param int $id The ID.
		 */
		public function set_id( $id ) {
			$this->id = absint( $id );
		}

		/**
		 * Set Defaults data
		 */
		public function set_defaults() {
			$this->data    = $this->default_data;
			$this->changes = array();
			$this->set_object_read( false );
		}

		/**
		 * Set object read.
		 *
		 * @param bool $read The read flag.
		 */
		public function set_object_read( $read = true ) {
			$this->object_read = (bool) $read;
		}

		/**
		 * Get object read.
		 *
		 * @return bool
		 */
		public function get_object_read() {
			return (bool) $this->object_read;
		}

		/**
		 * Delete me.
		 */
		public function delete() {
			if ( $this->data_store ) {
				$this->data_store->delete( $this );
				$this->set_id( 0 );

				return true;
			}

			return false;
		}

		/**
		 * Return the data.
		 *
		 * @return array
		 */
		public function get_data() {
			return array_merge( $this->data, array( 'id' => $this->get_id() ) );
		}

		/**
		 * Returns array of expected data keys for this object.
		 *
		 * @return array
		 */
		public function get_data_keys() {
			return array_keys( $this->data );
		}

		/**
		 * Return the current data.
		 *
		 * @return array
		 */
		public function get_current_data() {
			$current_data = array_replace_recursive( $this->data, $this->changes );

			return array_merge( $current_data, array( 'id' => $this->get_id() ) );
		}
	}
}
