<?php
/**
 * Functions
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Functions
 */

defined( 'YITH_POS' ) || exit;

if ( ! function_exists( 'yith_pos_body_class' ) ) {
	/**
	 * Print the POS body class.
	 */
	function yith_pos_body_class() {
		// Separates class names with a single space, collates class names for body element.
		echo 'class="' . esc_attr( join( ' ', yith_pos_get_body_classes() ) ) . '"';
	}
}

if ( ! function_exists( 'yith_pos_get_body_classes' ) ) {
	/**
	 * Get the POS body classes.
	 *
	 * @return array
	 */
	function yith_pos_get_body_classes() {
		$classes = array( 'yith-pos-page' );

		if ( yith_pos_is_ios() ) {
			$classes[] = 'iOS';
		}

		$classes = apply_filters( 'yith_pos_body_classes', $classes );
		$classes = array_map( 'esc_attr', $classes );

		return array_unique( $classes );
	}
}

if ( ! function_exists( 'yith_pos_head' ) ) {
	/**
	 * POS Head.
	 */
	function yith_pos_head() {
		do_action( 'yith_pos_head' );
	}
}

if ( ! function_exists( 'yith_pos_footer' ) ) {
	/**
	 * POS footer.
	 */
	function yith_pos_footer() {
		do_action( 'yith_pos_footer' );
	}
}

if ( ! function_exists( 'yith_pos_print_styles' ) ) {
	/**
	 * Print POS styles.
	 */
	function yith_pos_print_styles() {
		$styles = yith_pos_styles();
		if ( $styles ) {
			wp_print_styles( $styles );
		}
	}
}

if ( ! function_exists( 'yith_pos_print_head_scripts' ) ) {
	/**
	 * Print head scripts.
	 */
	function yith_pos_print_head_scripts() {
		$scripts = apply_filters( 'yith_pos_head_scripts', array() );

		if ( $scripts ) {
			wp_print_scripts( $scripts );
		}
	}
}

if ( ! function_exists( 'yith_pos_print_footer_scripts' ) ) {
	/**
	 * Print footer scripts.
	 */
	function yith_pos_print_footer_scripts() {
		$scripts = yith_pos_scripts();
		if ( $scripts ) {
			wp_print_scripts( $scripts );
		}
	}
}

if ( ! function_exists( 'yith_pos_styles' ) ) {
	/**
	 * Return an array of the POS scripts
	 */
	function yith_pos_styles() {
		global $yith_pos_styles;
		if ( ! $yith_pos_styles || ! is_array( $yith_pos_styles ) ) {
			$yith_pos_styles = array();
		}

		return $yith_pos_styles;
	}
}

if ( ! function_exists( 'yith_pos_enqueue_style' ) ) {
	/**
	 * Enqueue a style to be used in POS
	 * Note: the style has to be already registered through wp_register_style function
	 *
	 * @param string $handle The handle.
	 */
	function yith_pos_enqueue_style( $handle ) {
		$styles = yith_pos_styles();
		if ( is_string( $handle ) && ! in_array( $handle, $styles, true ) ) {
			global $yith_pos_styles;
			$styles[]        = $handle;
			$yith_pos_styles = $styles;
		}
	}
}

if ( ! function_exists( 'yith_pos_scripts' ) ) {
	/**
	 * Return an array of the POS scripts
	 */
	function yith_pos_scripts() {
		global $yith_pos_scripts;
		if ( ! $yith_pos_scripts || ! is_array( $yith_pos_scripts ) ) {
			$yith_pos_scripts = array();
		}

		return $yith_pos_scripts;
	}
}

if ( ! function_exists( 'yith_pos_dequeue_script' ) ) {
	/**
	 * Dequeue a script to be used in POS
	 * Note: the script has to be already registered through wp_register_script function
	 *
	 * @param string $handle The handle.
	 */
	function yith_pos_dequeue_script( $handle ) {
		$scripts = yith_pos_scripts();

		if ( is_string( $handle ) && in_array( $handle, $scripts, true ) ) {
			global $yith_pos_scripts;
			$index = array_search( $handle, $scripts, true );

			if ( $index >= 0 ) {
				unset( $scripts[ $index ] );
			}

			$yith_pos_scripts = $scripts;
		}
	}
}

if ( ! function_exists( 'yith_pos_enqueue_script' ) ) {
	/**
	 * Enqueue a script to be used in POS
	 * Note: the script has to be already registered through wp_register_script function
	 *
	 * @param string $handle The handle.
	 */
	function yith_pos_enqueue_script( $handle ) {
		$scripts = yith_pos_scripts();
		if ( is_string( $handle ) && ! in_array( $handle, $scripts, true ) ) {
			global $yith_pos_scripts;
			$scripts[]        = $handle;
			$yith_pos_scripts = $scripts;
		}
	}
}

if ( ! function_exists( 'yith_pos_pwa_header_meta' ) ) {
	/**
	 * Add meta for PWA
	 */
	function yith_pos_pwa_header_meta() {
		$meta = array(
			array(
				'name'    => 'viewport',
				'content' => 'user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1, width=device-width, height=device-height, target-densitydpi=device-dpi',
			),
			array(
				'name'    => 'apple-mobile-web-app-capable',
				'content' => 'yes',
			),
			array(
				'name'    => 'apple-mobile-web-app-status-bar-style',
				'content' => 'black',
			),
			array(
				'name'    => 'mobile-web-app-capable',
				'content' => 'yes',
			),
			array(
				'property' => 'og:url',
				'content'  => yith_pos_get_pos_page_url(),
			),
			array(
				'property' => 'og:type',
				'content'  => 'website',
			),
			array(
				'property' => 'og:title',
				'content'  => wp_get_document_title(),
			),
		);

		$meta      = apply_filters( 'yith_pos_pwa_header_meta', $meta );
		$meta_html = '';
		foreach ( $meta as $item ) {
			$meta_html .= '<meta ';
			foreach ( $item as $key => $value ) {
				$key   = esc_attr( $key );
				$value = esc_attr( $value );

				$meta_html .= "{$key}=\"{$value}\"";
			}
			$meta_html .= ' />';
		}

		echo apply_filters( 'yith_pos_pwa_header_meta_html', $meta_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'yith_pos_is_ios' ) ) {
	/**
	 * Return true if the current device is an iOS device.
	 *
	 * @return bool
	 */
	function yith_pos_is_ios() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		return isset( $_SERVER['HTTP_USER_AGENT'] ) && preg_match( '/iPad|iPod|iPhone|webOS/', $_SERVER['HTTP_USER_AGENT'] );
	}
}

if ( ! function_exists( 'yith_pos_robots_sensitive_page' ) ) {
	/**
	 * Set noindex and noarchive in robots.
	 *
	 * @since 1.0.18
	 */
	function yith_pos_robots_sensitive_page() {
		add_filter( 'wp_robots', 'wp_robots_sensitive_page' );
	}
}


add_action( 'yith_pos_head', '_wp_render_title_tag', 1 );
if ( function_exists( 'wp_strict_cross_origin_referrer' ) ) { // since WP 5.7.
	add_action( 'yith_pos_head', 'wp_strict_cross_origin_referrer' );
}

if ( function_exists( 'wp_robots' ) ) { // WP >= 5.7, noindex was deprecated.
	add_action( 'yith_pos_head', 'yith_pos_robots_sensitive_page', 1 );
	add_action( 'yith_pos_head', 'wp_robots', 1 );
} elseif ( function_exists( 'noindex' ) ) { // WP < 5.7.
	add_action( 'yith_pos_head', 'noindex', 1 );
}

add_action( 'yith_pos_head', 'wp_enqueue_scripts', 1 );
add_action( 'yith_pos_head', 'yith_pos_pwa_header_meta', 5 );
add_action( 'yith_pos_head', 'yith_pos_print_styles', 8 );
add_action( 'yith_pos_head', 'yith_pos_print_head_scripts', 9 );
add_action( 'yith_pos_head', 'wp_site_icon', 99 );
add_action( 'yith_pos_footer', 'yith_pos_print_footer_scripts', 20 );
