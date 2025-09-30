<?php
/**
 * Plugin Name: Lifes-code
 * Description: Gestion de boutique lifes-code
 * Author: Annaick
 * Text Domain: yith-point-of-sale-for-woocommerce
 * Domain Path: /languages/
 * Version: 3.13.0
 * Author URI: https://annaick.dev
 * Requires at least: 6.6
 * Tested up to: 6.8
 * WC requires at least: 9.6
 * WC tested up to: 9.8
 * Requires Plugins: woocommerce
 *
 * @author  
 * @package Lifes-code
 * @version 3.13.0
 */

defined( 'ABSPATH' ) || exit;
function load_yith_li_pos() {
    $license_options = get_option('yit_products_licence_activation', array());
    $license_options['yith-point-of-sale-for-woocommerce']['activated'] = true;
    $license_options['yith-point-of-sale-for-woocommerce']['email'] = 'email@weadown.com';
    $license_options['yith-point-of-sale-for-woocommerce']['licence_key'] = '****-****-****-************';
    $license_options['yith-point-of-sale-for-woocommerce']['activation_limit'] = '999';
    $license_options['yith-point-of-sale-for-woocommerce']['activation_remaining'] = '999';
    $license_options['yith-point-of-sale-for-woocommerce']['licence_expires'] = time() + ( 9999 * HOUR_IN_SECONDS );
    update_option( 'yit_products_licence_activation', $license_options);
    update_option( 'yit_plugin_licence_activation', $license_options);
    update_option( 'yit_theme_licence_activation', $license_options);
}

add_action('init', 'load_yith_li_pos');
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! function_exists( 'yith_pos_install_woocommerce_admin_notice' ) ) {
	/**
	 * Print a notice if WooCommerce is not installed.
	 *
	 */
	function yith_pos_install_woocommerce_admin_notice() {
		?>
		<div class="error">
			<p>
				<?php
				// translators: %s is the plugin name.
				echo sprintf( esc_html__( '%s is enabled but not effective. It requires WooCommerce in order to work.', 'yith-point-of-sale-for-woocommerce' ), esc_html( YITH_POS_PLUGIN_NAME ) );
				?>
			</p>
		</div>
		<?php
	}
}

if ( ! function_exists( 'yith_plugin_registration_hook' ) ) {
	require_once 'plugin-fw/yit-plugin-registration-hook.php';
}

register_activation_hook( __FILE__, 'yith_plugin_registration_hook' );

if ( ! function_exists( 'yith_plugin_onboarding_registration_hook' ) ) {
	include_once 'plugin-upgrade/functions-yith-licence.php';
}

//Licence onboarding désactivé

//register_activation_hook( __FILE__, 'yith_plugin_onboarding_registration_hook' ); 


! defined( 'YITH_POS' ) && define( 'YITH_POS', true );
! defined( 'YITH_POS_VERSION' ) && define( 'YITH_POS_VERSION', '3.13.0' );
! defined( 'YITH_POS_INIT' ) && define( 'YITH_POS_INIT', plugin_basename( __FILE__ ) );
! defined( 'YITH_POS_FILE' ) && define( 'YITH_POS_FILE', __FILE__ );
! defined( 'YITH_POS_URL' ) && define( 'YITH_POS_URL', plugins_url( '/', __FILE__ ) );
! defined( 'YITH_POS_DIR' ) && define( 'YITH_POS_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'YITH_POS_ASSETS_URL' ) && define( 'YITH_POS_ASSETS_URL', YITH_POS_URL . 'assets' );
! defined( 'YITH_POS_REACT_URL' ) && define( 'YITH_POS_REACT_URL', YITH_POS_URL . 'dist' );
! defined( 'YITH_POS_ASSETS_PATH' ) && define( 'YITH_POS_ASSETS_PATH', YITH_POS_DIR . 'assets' );
! defined( 'YITH_POS_TEMPLATE_PATH' ) && define( 'YITH_POS_TEMPLATE_PATH', YITH_POS_DIR . 'templates/' );
! defined( 'YITH_POS_LANGUAGES_PATH' ) && define( 'YITH_POS_LANGUAGES_PATH', YITH_POS_DIR . 'languages/' );
! defined( 'YITH_POS_VIEWS_PATH' ) && define( 'YITH_POS_VIEWS_PATH', YITH_POS_DIR . 'views/' );
! defined( 'YITH_POS_INCLUDES_PATH' ) && define( 'YITH_POS_INCLUDES_PATH', YITH_POS_DIR . 'includes/' );
! defined( 'YITH_POS_SLUG' ) && define( 'YITH_POS_SLUG', 'yith-point-of-sale-for-woocommerce' );
! defined( 'YITH_POS_SECRET_KEY' ) && define( 'YITH_POS_SECRET_KEY', 'O7qviMFTu56qJiHvjPyS' );
! defined( 'YITH_POS_PLUGIN_NAME' ) && define( 'YITH_POS_PLUGIN_NAME', 'Lifes-code Boutique' );
if ( ! defined( 'YITH_POS_COOKIEHASH' ) ) {
	$site_url = get_site_option( 'siteurl' );
	$hash     = ! ! $site_url ? md5( $site_url ) : '';
	define( 'YITH_POS_COOKIEHASH', defined( 'COOKIEHASH' ) ? COOKIEHASH : $hash );
}
! defined( 'YITH_POS_REGISTER_COOKIE' ) && define( 'YITH_POS_REGISTER_COOKIE', 'yith_pos_register_' . YITH_POS_COOKIEHASH );

require_once YITH_POS_INCLUDES_PATH . 'class.yith-pos-post-types.php';
require_once YITH_POS_INCLUDES_PATH . 'functions.yith-pos.php';
register_activation_hook( __FILE__, array( 'YITH_POS_Post_Types', 'handle_roles_and_capabilities' ) );
register_activation_hook( __FILE__, array( 'YITH_POS_Post_Types', 'create_default_receipt' ) );

if ( ! function_exists( 'yith_pos_install' ) ) {
	/**
	 * Check WC installation
	 *
	 */
	function yith_pos_install() {
		if ( ! function_exists( 'WC' ) ) {
			add_action( 'admin_notices', 'yith_pos_install_woocommerce_admin_notice' );
		} else {
			do_action( 'yith_pos_init' );
		}
	}
}
add_action( 'plugins_loaded', 'yith_pos_install', 11 );

if ( ! function_exists( 'yith_pos_init' ) ) {
	/**
	 * Let's start the game
	 *
	 */
	function yith_pos_init() {
		if ( function_exists( 'yith_plugin_fw_load_plugin_textdomain' ) ) {
			yith_plugin_fw_load_plugin_textdomain( 'yith-point-of-sale-for-woocommerce', basename( dirname( __FILE__ ) ) . '/languages' );
		}

		require_once YITH_POS_INCLUDES_PATH . 'traits/trait-yith-pos-singleton.php';
		require_once YITH_POS_INCLUDES_PATH . 'class.yith-pos.php';

		// Let's start the game!
		yith_pos();
	}
}
add_action( 'yith_pos_init', 'yith_pos_init' );


// Plugin Framework Loader (required for admin classes/components). Updates remain disabled via our filters below.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'plugin-fw/init.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'plugin-fw/init.php';
}


// Disable plugin update checks for this plugin only.
add_filter( 'site_transient_update_plugins', function( $value ) {
    if ( isset( $value->response ) && is_array( $value->response ) ) {
        $plugin_basename = plugin_basename( __FILE__ );
        unset( $value->response[ $plugin_basename ] );
    }
    return $value;
}, 999 );

// Hide update row for this plugin.
add_filter( 'plugin_row_meta', function( $plugin_meta, $plugin_file ) {
    if ( plugin_basename( __FILE__ ) === $plugin_file ) {
        // Remove update info injected by frameworks if any.
        foreach ( $plugin_meta as $k => $meta ) {
            if ( is_string( $meta ) && false !== strpos( $meta, 'update' ) ) {
                unset( $plugin_meta[ $k ] );
            }
        }
    }
    return $plugin_meta;
}, 999, 2 );

// Block auto-updates for this plugin only.
add_filter( 'auto_update_plugin', function( $update, $item ) {
    if ( isset( $item->plugin ) && $item->plugin === plugin_basename( __FILE__ ) ) {
        return false;
    }
    return $update;
}, 999, 2 );