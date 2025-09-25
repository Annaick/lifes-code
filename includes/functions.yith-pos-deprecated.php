<?php
/**
 * Functions
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Functions
 */

defined( 'YITH_POS' ) || exit;

if ( ! function_exists( 'yith_pos_doing_it_wrong' ) ) {
	/**
	 * Wrapper for _doing_it_wrong().
	 *
	 * @param string $function Function used.
	 * @param string $message  Message to log.
	 * @param string $version  Version the message was added in.
	 *
	 * @since  2.0.0
	 */
	function yith_pos_doing_it_wrong( $function, $message, $version ) {
		$message .= ' Backtrace: ' . wp_debug_backtrace_summary(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_wp_debug_backtrace_summary

		if ( wp_doing_ajax() || WC()->is_rest_api_request() ) {
			do_action( 'doing_it_wrong_run', $function, $message, $version );
			$log_string = sprintf(
				'%s was called incorrectly. %s. This message was added in version %s.',
				$function,
				$message,
				$version
			);
			error_log( $log_string ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		} else {
			_doing_it_wrong( esc_html( $function ), esc_html( $message ), esc_html( $version ) );
		}
	}
}

if ( ! function_exists( 'yith_pos_deprecated_function' ) ) {
	/**
	 * Wrapper for deprecated functions, so we can apply some extra logic.
	 *
	 * @param string $function    Function used.
	 * @param string $version     Version the message was added in.
	 * @param string $replacement Replacement for the called function.
	 *
	 * @since 2.0.0
	 */
	function yith_pos_deprecated_function( $function, $version, $replacement = null ) {
		if ( wp_doing_ajax() || WC()->is_rest_api_request() ) {
			do_action( 'deprecated_function_run', $function, $replacement, $version );

			if ( $replacement ) {
				$log_string = sprintf(
					'%1$s is deprecated since version %2$s! Use %3$s instead.',
					$function,
					$version,
					$replacement
				);
			} else {
				$log_string = sprintf(
					'%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.',
					$function,
					$version
				);
			}
			error_log( $log_string ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		} else {
			_deprecated_function( esc_html( $function ), esc_html( $version ), esc_html( $replacement ) );
		}
	}
}

if ( ! function_exists( 'yith_pos_deprecated_hook' ) ) {
	/**
	 * Wrapper for deprecated hooks, so we can apply some extra logic.
	 *
	 * @param string $hook        Hook used.
	 * @param string $version     Version the message was added in.
	 * @param string $replacement Replacement for the called function.
	 * @param string $message     The message.
	 *
	 * @since 2.0.0
	 */
	function yith_pos_deprecated_hook( $hook, $version, $replacement = '', $message = '' ) {
		if ( wp_doing_ajax() || WC()->is_rest_api_request() ) {
			do_action( 'deprecated_hook_run', $hook, $replacement, $version, $message );

			$message .= ' Backtrace: ' . wp_debug_backtrace_summary(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_wp_debug_backtrace_summary

			if ( $replacement ) {
				$log_string = sprintf(
					'%1$s is deprecated since version %2$s! Use %3$s instead. %4$s',
					$hook,
					$version,
					$replacement,
					$message
				);
			} else {
				$log_string = sprintf(
					'%1$s is <strong>deprecated</strong> since version %2$s with no alternative available. %3$s',
					$hook,
					$version,
					$message
				);
			}
			error_log( $log_string ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		} else {
			_deprecated_hook( esc_html( $hook ), esc_html( $version ), esc_html( $replacement ), esc_html( $message ) );
		}
	}
}

if ( ! function_exists( 'yith_pos_deprecated_filter' ) ) {
	/**
	 * Wrapper for deprecated filter hook, so we can apply some extra logic.
	 *
	 * @param string $hook        The hook that was used.
	 * @param string $version     The Booking plugin version that deprecated the hook.
	 * @param string $replacement The hook that should have been used.
	 * @param string $message     A message regarding the change.
	 *
	 * @since 2.0.0
	 */
	function yith_pos_deprecated_filter( $hook, $version, $replacement = '', $message = '' ) {
		if ( has_filter( $hook ) ) {
			yith_pos_deprecated_hook( $hook . ' filter', $version, $replacement, $message );
		}
	}
}
