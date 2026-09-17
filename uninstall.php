<?php
/**
 * Uninstall: always clear own transients; remove data only if user opted in.
 * Multisite: loops every site.
 *
 * @package ScheduleLens
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean one blog.
 *
 * @return void
 */
function schedulelens_uninstall_blog() {
	delete_transient( 'schedulelens_health_cache' );
	delete_transient( 'schedulelens_welcome' );
	delete_transient( 'schedulelens_prune_lock' );

	$schedulelens_settings = get_option( 'schedulelens_settings', array() );
	$schedulelens_delete   = isset( $schedulelens_settings['cleanup_on_uninstall'] ) ? (int) $schedulelens_settings['cleanup_on_uninstall'] : 1;

	if ( 1 !== $schedulelens_delete ) {
		return;
	}

	// Remove cron events that used our custom intervals before dropping the list.
	$schedulelens_custom = get_option( 'schedulelens_schedules', array() );
	if ( is_array( $schedulelens_custom ) && ! empty( $schedulelens_custom ) ) {
		$schedulelens_cron = _get_cron_array();
		if ( is_array( $schedulelens_cron ) ) {
			foreach ( $schedulelens_cron as $schedulelens_ts => $schedulelens_hooks ) {
				if ( ! is_array( $schedulelens_hooks ) ) {
					continue;
				}
				foreach ( $schedulelens_hooks as $schedulelens_hook => $schedulelens_instances ) {
					if ( ! is_array( $schedulelens_instances ) ) {
						continue;
					}
					foreach ( $schedulelens_instances as $schedulelens_sig => $schedulelens_data ) {
						$schedulelens_sched = isset( $schedulelens_data['schedule'] ) ? $schedulelens_data['schedule'] : '';
						if ( '' !== $schedulelens_sched && isset( $schedulelens_custom[ $schedulelens_sched ] ) ) {
							$schedulelens_args = isset( $schedulelens_data['args'] ) ? $schedulelens_data['args'] : array();
							wp_unschedule_event( (int) $schedulelens_ts, $schedulelens_hook, $schedulelens_args );
						}
					}
				}
			}
		}
	}

	delete_option( 'schedulelens_settings' );
	delete_option( 'schedulelens_paused' );
	delete_option( 'schedulelens_log' );
	delete_option( 'schedulelens_schedules' );
	delete_option( 'schedulelens_custom_count' );
	delete_option( 'schedulelens_dismissed' );
}

if ( is_multisite() ) {
	$schedulelens_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $schedulelens_site_ids as $schedulelens_site_id ) {
		switch_to_blog( $schedulelens_site_id );
		schedulelens_uninstall_blog();
		restore_current_blog();
	}
} else {
	schedulelens_uninstall_blog();
}
