<?php
/**
 * Shared helpers.
 *
 * @package ScheduleLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core hooks that should never be deleted (WordPress will reschedule anyway).
 *
 * @return string[]
 */
function schedulelens_core_hooks() {
	return array(
		'wp_version_check',
		'wp_update_plugins',
		'wp_update_themes',
		'wp_scheduled_delete',
		'wp_scheduled_auto_draft_delete',
		'delete_expired_transients',
		'recovery_mode_clean_expired_keys',
		'wp_privacy_delete_old_export_files',
		'wp_site_health_scheduled_check',
		'wp_https_detection',
	);
}

/**
 * Check if hook is protected core hook.
 *
 * @param string $hook Hook name.
 * @return bool
 */
function schedulelens_is_core_hook( $hook ) {
	return in_array( $hook, schedulelens_core_hooks(), true );
}

/**
 * Guess which plugin/theme added a hook by scanning active plugins for the string.
 * Lightweight: runs once per page load, result cached in static.
 *
 * @param string $hook Hook name.
 * @return string Source label.
 */
function schedulelens_detect_source( $hook ) {
	static $schedulelens_cache = array();
	static $schedulelens_plugins = null;
	if ( isset( $schedulelens_cache[ $hook ] ) ) {
		return $schedulelens_cache[ $hook ];
	}

	if ( schedulelens_is_core_hook( $hook ) ) {
		$schedulelens_cache[ $hook ] = 'WP Core';
		return 'WP Core';
	}

	if ( null === $schedulelens_plugins ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$schedulelens_plugins = array(
			'active' => get_option( 'active_plugins', array() ),
			'all'    => get_plugins(),
		);
	}
	$active = $schedulelens_plugins['active'];
	$all    = $schedulelens_plugins['all'];

	foreach ( $active as $plugin_file ) {
		if ( ! isset( $all[ $plugin_file ] ) ) {
			continue;
		}
		$slug = dirname( $plugin_file );
		if ( '' !== $slug && false !== stripos( $hook, str_replace( '-', '_', $slug ) ) ) {
			$schedulelens_cache[ $hook ] = $all[ $plugin_file ]['Name'];
			return $schedulelens_cache[ $hook ];
		}
		$name_part = isset( $all[ $plugin_file ]['Name'] ) ? $all[ $plugin_file ]['Name'] : '';
		if ( '' !== $name_part ) {
			$first = strtok( $name_part, ' ' );
			if ( $first && false !== stripos( $hook, strtolower( $first ) ) ) {
				$schedulelens_cache[ $hook ] = $name_part;
				return $name_part;
			}
		}
	}

	$theme = wp_get_theme();
	if ( $theme && $theme->exists() ) {
		$stylesheet = get_stylesheet();
		if ( '' !== $stylesheet && false !== stripos( $hook, str_replace( '-', '_', $stylesheet ) ) ) {
			$schedulelens_cache[ $hook ] = $theme->get( 'Name' ) . ' (theme)';
			return $schedulelens_cache[ $hook ];
		}
	}

	$schedulelens_cache[ $hook ] = 'Unknown';
	return 'Unknown';
}

/**
 * Sanitize cron hook name without forcing lowercase.
 * Allows letters, numbers, underscore, hyphen, dot, slash.
 * Returns empty string on invalid.
 *
 * @param string $hook Raw hook.
 * @return string
 */
function schedulelens_sanitize_hook( $hook ) {
	$hook = is_string( $hook ) ? trim( $hook ) : '';
	if ( '' === $hook || strlen( $hook ) > 200 ) {
		return '';
	}
	if ( ! preg_match( '/^[A-Za-z0-9_\-\.\/:]+$/', $hook ) ) {
		return '';
	}
	return $hook;
}

/**
 * Human interval.
 *
 * @param int $seconds Seconds.
 * @return string
 */
function schedulelens_human_interval( $seconds ) {
	$seconds = (int) $seconds;
	if ( $seconds < 60 ) {
		/* translators: %d: seconds */
		return sprintf( __( '%d sec', 'schedulelens-cron-viewer' ), $seconds );
	}
	if ( $seconds < 3600 ) {
		/* translators: %d: minutes */
		return sprintf( __( '%d min', 'schedulelens-cron-viewer' ), (int) round( $seconds / 60 ) );
	}
	if ( $seconds < 86400 ) {
		/* translators: %d: hours */
		return sprintf( __( '%d hour', 'schedulelens-cron-viewer' ), (int) round( $seconds / 3600 ) );
	}
	/* translators: %d: days */
	return sprintf( __( '%d day', 'schedulelens-cron-viewer' ), (int) round( $seconds / 86400 ) );
}

/**
 * Recursively sanitize decoded cron args.
 * Keeps scalars, drops objects/resources, caps breadth and depth.
 *
 * @param mixed $value Value.
 * @param int   $depth Depth.
 * @return mixed
 */
function schedulelens_sanitize_args( $value, $depth = 0 ) {
	if ( $depth > 3 ) {
		return null;
	}
	if ( is_array( $value ) ) {
		$out = array();
		$i   = 0;
		foreach ( $value as $k => $v ) {
			if ( $i >= 10 ) {
				break;
			}
			$clean_k = is_string( $k ) ? sanitize_text_field( $k ) : $k;
			$out[ $clean_k ] = schedulelens_sanitize_args( $v, $depth + 1 );
			$i++;
		}
		return $out;
	}
	if ( is_string( $value ) ) {
		return sanitize_text_field( $value );
	}
	if ( is_int( $value ) || is_float( $value ) || is_bool( $value ) || is_null( $value ) ) {
		return $value;
	}
	return null;
}

/**
 * Safe redirect with fallback link (avoids headers-sent blank pages).
 *
 * @param string $url URL.
 * @return void
 */
function schedulelens_safe_redirect( $url ) {
	wp_safe_redirect( $url );
	exit;
}
