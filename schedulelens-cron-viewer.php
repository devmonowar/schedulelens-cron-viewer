<?php
/**
 * Plugin Name: ScheduleLens - Cron Viewer & Manager
 * Plugin URI: https://devmonowar.github.io/schedulelens-cron-viewer/
 * Description: See all scheduled jobs, catch overdue tasks, run or pause events, and check cron health - lightweight, admin-only, 100% free.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Monowar Hossain
 * Author URI: https://devmonowar.github.io/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: schedulelens-cron-viewer
 * Domain Path: /languages
 *
 * @package ScheduleLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SCHEDULELENS_VERSION', '1.0.0' );
define( 'SCHEDULELENS_SLUG', 'schedulelens-cron-viewer' );
define( 'SCHEDULELENS_FILE', __FILE__ );
define( 'SCHEDULELENS_PATH', plugin_dir_path( __FILE__ ) );
define( 'SCHEDULELENS_URL', plugin_dir_url( __FILE__ ) );

require_once SCHEDULELENS_PATH . 'includes/helpers.php';
require_once SCHEDULELENS_PATH . 'includes/class-schedulelens-events.php';
require_once SCHEDULELENS_PATH . 'includes/class-schedulelens-schedules.php';
require_once SCHEDULELENS_PATH . 'includes/class-schedulelens-health.php';
require_once SCHEDULELENS_PATH . 'includes/class-schedulelens-logger.php';

ScheduleLens_Schedules::init();

/* Translations under the plugin slug are auto-loaded by WordPress.org (WP 4.6+), so no manual load_plugin_textdomain() call. */

/**
 * Backfill missing options after an update (activation does not re-run on update).
 *
 * @return void
 */
function schedulelens_ensure_options() {
	if ( false === get_option( 'schedulelens_settings', false ) ) {
		add_option( 'schedulelens_settings', schedulelens_get_default_settings(), '', 'no' );
	}
	if ( false === get_option( 'schedulelens_paused', false ) ) {
		add_option( 'schedulelens_paused', array(), '', 'no' );
	}
	if ( false === get_option( 'schedulelens_log', false ) ) {
		add_option( 'schedulelens_log', array(), '', 'no' );
	}
	if ( false === get_option( 'schedulelens_schedules', false ) ) {
		add_option( 'schedulelens_schedules', array(), '', 'no' );
	}
	if ( false === get_option( 'schedulelens_custom_count', false ) ) {
		add_option( 'schedulelens_custom_count', 0, '', 'no' );
	}
}
add_action( 'admin_init', 'schedulelens_ensure_options' );

/**
 * Add Settings link on Plugins list (next to Deactivate).
 *
 * @param string[] $links Existing links.
 * @return string[]
 */
function schedulelens_action_links( $links ) {
	$base = function_exists( 'is_network_admin' ) && is_network_admin()
		? network_admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=settings' )
		: admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=settings' );
	array_unshift(
		$links,
		'<a href="' . esc_url( $base ) . '">' . esc_html__( 'Settings', 'schedulelens-cron-viewer' ) . '</a>'
	);
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( SCHEDULELENS_FILE ), 'schedulelens_action_links' );
add_filter( 'network_admin_plugin_action_links_' . plugin_basename( SCHEDULELENS_FILE ), 'schedulelens_action_links' );

if ( is_admin() ) {
	require_once SCHEDULELENS_PATH . 'includes/class-schedulelens-admin.php';
	ScheduleLens_Admin::init();
}

/**
 * Default settings.
 *
 * @return array
 */
function schedulelens_get_default_settings() {
	return array(
		'version'             => SCHEDULELENS_VERSION,
		'keep_days'           => 7,
		'show_core'           => 1,
		'cleanup_on_uninstall' => 1,
	);
}

/**
 * Activation: set defaults.
 *
 * @param bool $network_wide Whether network-activated.
 * @return void
 */
function schedulelens_activate( $network_wide = false ) {
	if ( is_multisite() && $network_wide ) {
		$site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);
		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			schedulelens_activate_blog();
			restore_current_blog();
		}
		return;
	}
	schedulelens_activate_blog();
}
register_activation_hook( __FILE__, 'schedulelens_activate' );

/**
 * Activate single blog.
 *
 * @return void
 */
function schedulelens_activate_blog() {
	$settings = get_option( 'schedulelens_settings', false );
	if ( false === $settings ) {
		add_option( 'schedulelens_settings', schedulelens_get_default_settings(), '', 'no' );
	}
	if ( false === get_option( 'schedulelens_paused', false ) ) {
		add_option( 'schedulelens_paused', array(), '', 'no' );
	}
	if ( false === get_option( 'schedulelens_log', false ) ) {
		add_option( 'schedulelens_log', array(), '', 'no' );
	}
	if ( false === get_option( 'schedulelens_schedules', false ) ) {
		add_option( 'schedulelens_schedules', array(), '', 'no' );
	}
	if ( false === get_option( 'schedulelens_custom_count', false ) ) {
		add_option( 'schedulelens_custom_count', 0, '', 'no' );
	}
	set_transient( 'schedulelens_welcome', 1, 30 );
}

/**
 * Deactivation: nothing scheduled persistently, no-op (kept for symmetry).
 *
 * @return void
 */
function schedulelens_deactivate() {
	delete_transient( 'schedulelens_health_cache' );
}
register_deactivation_hook( __FILE__, 'schedulelens_deactivate' );

/**
 * New site after network activation.
 *
 * @param WP_Site $site New site.
 * @return void
 */
function schedulelens_new_site( $site ) {
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if ( ! is_plugin_active_for_network( plugin_basename( SCHEDULELENS_FILE ) ) ) {
		return;
	}
	switch_to_blog( $site->blog_id );
	schedulelens_activate_blog();
	restore_current_blog();
}
add_action( 'wp_initialize_site', 'schedulelens_new_site' );
