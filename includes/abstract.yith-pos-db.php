<?php
/**
 * Database Class
 * handle DB custom tables
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_DB' ) ) {
	/**
	 * Class YITH_POS_DB
	 * handle DB custom tables
	 *
	 */
	abstract class YITH_POS_DB {

		const REGISTER_SESSIONS_TABLE = 'yith_pos_register_sessions';

		/**
		 * DB version
		 *
		 * @var string
		 * @deprecated 2.0.0
		 */
		public static $version = '1.0.0';

		/**
		 * Register Session Table name.
		 *
		 * @var string
		 * @deprecated 2.0.0 | use YITH_POS_DB::REGISTER_SESSIONS_TABLE instead
		 */
		public static $register_session = 'yith_pos_register_sessions';

		/**
		 * Install
		 *
		 * @deprecated 2.0.0
		 */
		public static function install() {
			// Do nothing.
		}

		/**
		 * Register custom tables within $wpdb object.
		 */
		public static function define_tables() {
			global $wpdb;

			// List of tables without prefixes.
			$tables = array(
				self::REGISTER_SESSIONS_TABLE => self::REGISTER_SESSIONS_TABLE,
			);

			foreach ( $tables as $name => $table ) {
				$wpdb->$name    = $wpdb->prefix . $table;
				$wpdb->tables[] = $table;
			}
		}

		/**
		 * Create tables
		 *
		 * @noinspection SqlNoDataSourceInspection
		 */
		public static function create_db_tables() {
			global $wpdb;

			$wpdb->hide_errors();

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$register_session_table_name = $wpdb->prefix . self::REGISTER_SESSIONS_TABLE;
			$collate                     = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}

			$sql = "CREATE TABLE $register_session_table_name (
                    id bigint(20) NOT NULL AUTO_INCREMENT,
                    store_id bigint(20) NOT NULL,
                    register_id bigint(20) NOT NULL,
                    open datetime NOT NULL,
                    closed datetime,
                    cashiers longtext,
                    total varchar(255),
                    cash_in_hand longtext,
                    note text,
                    report longtext,
                    PRIMARY KEY (id)
                    ) $collate;";

			dbDelta( $sql );
		}
	}
}
