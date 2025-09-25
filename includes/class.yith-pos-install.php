<?php
/**
 * Install Class.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Install' ) ) {
	/**
	 * Class YITH_POS_Install
	 * Main Class
	 *
	 */
	class YITH_POS_Install {
		use YITH_POS_Singleton_Trait;

		/**
		 * The updates to fire.
		 *
		 * @var callable[][]
		 */
		private $db_updates = array(
			'2.0.0' => array(
				'yith_pos_update_200_update_product_catalog_visibility',
				'yith_pos_update_200_remove_yith_pos_catalog_visibility',
				'yith_pos_update_200_db_version',
			),
			'3.2.0' => array(
				'yith_pos_update_320_db_version',
			),
		);

		/**
		 * Callbacks to be fired soon, instead of being scheduled.
		 *
		 * @var callable[]
		 */
		private $soon_callbacks = array();

		/**
		 * The version option.
		 */
		const VERSION_OPTION = 'yith_pos_version';

		/**
		 * The DB version option.
		 */
		const DB_VERSION_OPTION = 'yith_pos_db_version';

		/**
		 * The update scheduled option.
		 */
		const DB_UPDATE_SCHEDULED_OPTION = 'yith_pos_db_update_scheduled_for';

		/**
		 * The constructor.
		 */
		private function __construct() {
			YITH_POS_DB::define_tables();

			add_action( 'init', array( $this, 'check_version' ), 5 );
			add_action( 'yith_pos_run_update_callback', array( $this, 'run_update_callback' ) );

			add_action( 'init', array( $this, 'add_rewrite_rules' ), 0 );
			add_filter( 'redirect_canonical', array( $this, 'allow_pos_subpages' ), 10, 2 );
			add_filter( 'update_option_page_on_front', array( $this, 'schedule_flush_rewrite_rules' ), 10, 2 );
			add_filter( 'update_option_show_on_front', array( $this, 'schedule_flush_rewrite_rules' ), 10, 2 );
			add_action( 'yith_pos_flush_rewrite_rules', 'flush_rewrite_rules' );
		}

		/**
		 * Check the plugin version and run the updater is required.
		 * This check is done on all requests and runs if the versions do not match.
		 */
		public function check_version() {
			if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( self::VERSION_OPTION, '1.0.0' ), YITH_POS_VERSION, '<' ) ) {
				$this->install();
				do_action( 'yith_pos_updated' );
			}
		}

		/**
		 * Get list of DB update callbacks.
		 *
		 * @return array
		 */
		public function get_db_update_callbacks() {
			return $this->db_updates;
		}

		/**
		 * Install.
		 */
		private function install() {
			// Check if we are not already running this routine.
			if ( 'yes' === get_transient( 'yith_pos_installing' ) ) {
				return;
			}

			set_transient( 'yith_pos_installing', 'yes', MINUTE_IN_SECONDS * 10 );
			if ( ! defined( 'YITH_POS_INSTALLING' ) ) {
				define( 'YITH_POS_INSTALLING', true );
			}

			$this->create_tables();

			$this->update_version();
			$this->maybe_update_db_version();

			wp_schedule_single_event( time(), 'yith_pos_flush_rewrite_rules' );

			delete_transient( 'yith_pos_installing' );

			do_action( 'yith_pos_installed' );
		}

		/**
		 * Update version to current.
		 */
		private function update_version() {
			delete_option( self::VERSION_OPTION );
			add_option( self::VERSION_OPTION, YITH_POS_VERSION );
		}


		/**
		 * The DB needs to be updated?
		 *
		 * @return bool
		 */
		public function needs_db_update() {
			$current_db_version = get_option( self::DB_VERSION_OPTION, null );

			return ! is_null( $current_db_version ) && version_compare( $current_db_version, $this->get_greatest_db_version_in_updates(), '<' );
		}

		/**
		 * Update DB version to current.
		 *
		 * @param string|null $version New DB version or null.
		 */
		public static function update_db_version( $version = null ) {
			delete_option( self::DB_VERSION_OPTION );
			add_option( self::DB_VERSION_OPTION, is_null( $version ) ? YITH_POS_VERSION : $version );

			// Delete "update scheduled for" option, to allow future update scheduling.
			delete_option( self::DB_UPDATE_SCHEDULED_OPTION );
		}

		/**
		 * Get DB Version
		 *
		 * @return string
		 */
		public static function get_db_version() {
			return get_option( self::DB_VERSION_OPTION );
		}

		/**
		 * Maybe update db
		 */
		private function maybe_update_db_version() {
			if ( $this->needs_db_update() ) {
				$this->update();
			}
		}

		/**
		 * Retrieve the major version in update callbacks.
		 *
		 * @return string
		 */
		private function get_greatest_db_version_in_updates() {
			$update_callbacks = $this->get_db_update_callbacks();
			$update_versions  = array_keys( $update_callbacks );
			usort( $update_versions, 'version_compare' );

			return end( $update_versions );
		}

		/**
		 * Return true if the callback needs to be fired soon, instead of being scheduled.
		 *
		 * @param string $callback The callback name.
		 *
		 * @return bool
		 */
		private function is_soon_callback( $callback ) {
			return in_array( $callback, $this->soon_callbacks, true );
		}

		/**
		 * Push all needed DB updates to the queue for processing.
		 */
		private function update() {
			$current_db_version   = get_option( self::DB_VERSION_OPTION );
			$loop                 = 0;
			$greatest_version     = $this->get_greatest_db_version_in_updates();
			$is_already_scheduled = get_option( self::DB_UPDATE_SCHEDULED_OPTION, '' ) === $greatest_version;

			if ( ! $is_already_scheduled ) {
				foreach ( $this->get_db_update_callbacks() as $version => $update_callbacks ) {
					if ( version_compare( $current_db_version, $version, '<' ) ) {
						foreach ( $update_callbacks as $update_callback ) {
							if ( $this->is_soon_callback( $update_callback ) ) {
								$this->run_update_callback( $update_callback );
							} else {
								WC()->queue()->schedule_single(
									time() + $loop,
									'yith_pos_run_update_callback',
									array(
										'update_callback' => $update_callback,
									),
									'yith-pos-db-updates'
								);
								$loop++;
							}
						}
					}
				}
				update_option( self::DB_UPDATE_SCHEDULED_OPTION, $greatest_version );
			}
		}

		/**
		 * Run an update callback when triggered by ActionScheduler.
		 *
		 * @param string $callback Callback name.
		 */
		public function run_update_callback( $callback ) {
			include_once YITH_POS_INCLUDES_PATH . 'functions.yith-pos-update.php';

			if ( is_callable( $callback ) ) {
				self::run_update_callback_start( $callback );
				$result = (bool) call_user_func( $callback );
				self::run_update_callback_end( $callback, $result );
			}
		}

		/**
		 * Triggered when a callback will run.
		 *
		 * @param string $callback Callback name.
		 */
		protected function run_update_callback_start( $callback ) {
			if ( ! defined( 'YITH_POS_UPDATING' ) ) {
				define( 'YITH_POS_UPDATING', true );
			}
		}

		/**
		 * Triggered when a callback has ran.
		 *
		 * @param string $callback Callback name.
		 * @param bool   $result   Return value from callback. Non-false need to run again.
		 */
		protected function run_update_callback_end( $callback, $result ) {
			if ( $result ) {
				WC()->queue()->add(
					'yith_pos_run_update_callback',
					array(
						'update_callback' => $callback,
					),
					'yith-pos-db-updates'
				);
			}
		}

		/**
		 * Create DB Tables
		 */
		private function create_tables() {
			YITH_POS_DB::create_db_tables();
		}

		/**
		 * Get rewrite rule regex for the POS sub-pages.
		 *
		 * @return string
		 * @since 2.0.0
		 */
		private function get_pos_subpages_rewrite_rule_regex() {
			static $regex = null;
			if ( is_null( $regex ) ) {
				$pos_url  = yith_pos_get_pos_page_url();
				$site_url = strtok( get_home_url(), '?' );
				$base_url = str_replace( $site_url, '', $pos_url );
				$start    = stripos( $base_url, '/' );
				$base_url = 0 === $start ? substr( $base_url, 1 ) : $base_url;

				if ( ! ! $base_url ) {
					$regex = '^' . untrailingslashit( $base_url ) . '/';
				} else {
					// Handle specific pages, if POS is set as Home Page.
					$regex = '^(history[^/]*)';
				}
			}

			return $regex;
		}

		/**
		 * Add rewrite rules.
		 *
		 * @since 2.0.0
		 */
		public function add_rewrite_rules() {
			$pos_page_id = yith_pos_get_pos_page_id();
			$regex       = $this->get_pos_subpages_rewrite_rule_regex();
			add_rewrite_rule( $regex, 'index.php?page_id=' . $pos_page_id, 'top' );
		}

		/**
		 * Allow POS sub-pages if POS page is set as Home Page.
		 *
		 * @param string $redirect_url  Redirect URL.
		 * @param string $requested_url Requested URL.
		 *
		 * @return string
		 * @since 2.0.1
		 */
		public function allow_pos_subpages( $redirect_url, $requested_url ) {
			$pos_page_id   = yith_pos_get_pos_page_id();
			$page_on_front = absint( get_option( 'page_on_front', 0 ) );
			$show_on_front = get_option( 'show_on_front', false );

			if ( 'page' === $show_on_front && $page_on_front === $pos_page_id ) {
				$requested_url = untrailingslashit( $requested_url );
				$allowed       = array(
					home_url( 'history' ),
				);

				if ( in_array( $requested_url, $allowed, true ) ) {
					$redirect_url = $requested_url;
				}
			}

			return $redirect_url;
		}

		/**
		 * Schedule flushing rewrite rules.
		 *
		 * @since 2.0.1
		 */
		public function schedule_flush_rewrite_rules() {
			static $scheduled = false;

			if ( ! $scheduled ) {
				$scheduled = true;
				wp_schedule_single_event( time(), 'yith_pos_flush_rewrite_rules' );
			}
		}

	}
}
