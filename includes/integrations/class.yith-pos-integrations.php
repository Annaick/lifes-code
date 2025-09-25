<?php
/**
 * Integrations class.
 * handle plugin integrations.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Classes
 */

defined( 'YITH_POS' ) || exit;

/**
 * Class YITH_POS_Integrations
 *
 * @since   1.0.6
 */
class YITH_POS_Integrations {
	use YITH_POS_Singleton_Trait;

	/**
	 * Plugins list.
	 *
	 * @var array
	 */
	protected $plugins = array();

	/**
	 * YITH_POS_Integrations constructor.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_plugins' ), 15 );
	}

	/**
	 * Load plugins
	 */
	public function load_plugins() {
		$this->plugins = require_once 'plugins-list.php';
		$this->load();
	}

	/**
	 * Load Integration classes
	 */
	private function load() {
		require_once YITH_POS_INCLUDES_PATH . 'integrations/abstract.yith-pos-integration.php';

		foreach ( $this->plugins as $slug => $plugin_info ) {
			$filename  = YITH_POS_INCLUDES_PATH . 'integrations/class.yith-pos-' . $slug . '-integration.php';
			$classname = $this->get_class_name_from_slug( $slug );

			$var = str_replace( '-', '_', $slug );
			if ( file_exists( $filename ) && ! class_exists( $classname ) ) {
				require_once $filename;
			}

			if ( $this->has_plugin( $slug ) && method_exists( $classname, 'get_instance' ) ) {
				$this->$var = $classname::get_instance();
			}
		}
	}

	/**
	 * Get the class name from slug.
	 *
	 * @param string $slug The slug.
	 *
	 * @return string
	 */
	public function get_class_name_from_slug( $slug ) {
		$class_slug = str_replace( '-', ' ', $slug );
		$class_slug = ucwords( $class_slug );
		$class_slug = str_replace( ' ', '_', $class_slug );

		return 'YITH_POS_' . $class_slug . '_Integration';
	}

	/**
	 * Check if user has a plugin
	 *
	 * @param string $slug The slug.
	 *
	 * @return bool
	 */
	public function has_plugin( $slug ) {
		if ( ! empty( $this->plugins[ $slug ] ) ) {
			$plugin = $this->plugins[ $slug ];

			if ( isset( $plugin['premium'] ) && defined( $plugin['premium'] ) && constant( $plugin['premium'] ) ) {
				if ( ! isset( $plugin['version'] ) || ! isset( $plugin['min_version'] ) ) {
					return true;
				}

				$compare = $plugin['compare'] ?? '>=';

				if (
					defined( $plugin['version'] ) && constant( $plugin['version'] ) &&
					version_compare( constant( $plugin['version'] ), $plugin['min_version'], $compare )
				) {
					return true;
				}
			}
		}

		return false;
	}
}
