<?php
declare( strict_types=1 );
/**
 * Security Utility Class
 *
 * @package Dr_Subs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security utility class for input validation and sanitization.
 *
 * @since 1.0.0
 */
class WCST_Security {

	/**
	 * Verify nonce for AJAX requests.
	 *
	 * @since 1.0.0
	 * @param string $nonce Nonce value to verify.
	 * @param string $action Nonce action name.
	 * @throws Exception If nonce verification fails.
	 */
	public static function verify_nonce( $nonce, $action ) {
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			throw new Exception( __( 'Security check failed. Please refresh the page and try again.', 'dr-subs' ) );
		}
	}

	/**
	 * Check user permissions.
	 *
	 * @since 1.0.0
	 * @param string $capability Required capability.
	 * @throws Exception If user doesn't have required permission.
	 */
	public static function check_permissions( $capability ) {
		if ( ! current_user_can( $capability ) ) {
			throw new Exception( __( 'You do not have permission to perform this action.', 'dr-subs' ) );
		}
	}

	/**
	 * Validate subscription ID.
	 *
	 * @since 1.0.0
	 * @param mixed $subscription_id Subscription ID to validate.
	 * @return int Valid subscription ID.
	 * @throws Exception If subscription ID is invalid.
	 */
	public static function validate_subscription_id( $subscription_id ) {
		$id = absint( $subscription_id );

		if ( 0 === $id ) {
			throw new Exception( __( 'Invalid subscription ID provided.', 'dr-subs' ) );
		}

		return $id;
	}

	/**
	 * Sanitize text input.
	 *
	 * @since 1.0.0
	 * @param string $input Text input to sanitize.
	 * @return string Sanitized text.
	 */
	public static function sanitize_text( $input ) {
		return sanitize_text_field( $input );
	}

	/**
	 * Escape output for display.
	 *
	 * @since 1.0.0
	 * @param string $output Output to escape.
	 * @return string Escaped output.
	 */
	public static function escape_html( $output ) {
		return esc_html( $output );
	}

	/**
	 * Check rate limiting for actions.
	 *
	 * @since 1.0.0
	 * @param string $action Action being performed.
	 * @param int    $limit Number of requests allowed per time period.
	 * @param int    $time_window Time window in seconds (default: 60).
	 * @throws Exception If rate limit is exceeded.
	 */
	public static function check_rate_limit( $action, $limit = 10, $time_window = 60 ) {
		$user_id   = get_current_user_id();
		$cache_key = "wcst_rate_limit_{$action}_{$user_id}";

		$current_count = get_transient( $cache_key );

		if ( false === $current_count ) {
			set_transient( $cache_key, 1, $time_window );
		} else {
			$current_count = (int) $current_count;

			if ( $current_count >= $limit ) {
				throw new Exception(
					sprintf(
						/* translators: 1: action name, 2: time window in seconds */
						__( 'Rate limit exceeded for %1$s. Please wait %2$d seconds before trying again.', 'dr-subs' ),
						$action,
						$time_window
					)
				);
			}

			set_transient( $cache_key, $current_count + 1, $time_window );
		}
	}
}
