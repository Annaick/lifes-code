<?php
/**
 * YITH POS Object Data Store Interface
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

/**
 * YITH POS Object Data Store Interface
 *
 * @since   2.0.0
 */
interface YITH_POS_Object_Data_Store_Interface {
	/**
	 * Method to create a new record of a YITH_POS_Data based object.
	 *
	 * @param YITH_POS_Data $data Data object.
	 */
	public function create( &$data );

	/**
	 * Method to read a record. Creates a new YITH_POS_Data based object.
	 *
	 * @param YITH_POS_Data $data Data object.
	 */
	public function read( &$data );

	/**
	 * Updates a record in the database.
	 *
	 * @param YITH_POS_Data $data Data object.
	 */
	public function update( &$data );

	/**
	 * Deletes a record from the database.
	 *
	 * @param YITH_POS_Data $data Data object.
	 *
	 * @return bool result
	 */
	public function delete( &$data );
}
