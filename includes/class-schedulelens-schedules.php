<?php
/**
 * Custom schedules.
 *
 * @package ScheduleLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ScheduleLens_Schedules
 */
class ScheduleLens_Schedules {

	/**
	 * Init filter.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_custom' ) );
	}

	/**
	 * Merge stored custom intervals.
	 *
	 * @param array $schedules Schedules.
	 * @return array
	 */
	public static function add_custom( $schedules ) {
		$custom = get_option( 'schedulelens_schedules', array() );
		if ( ! is_array( $custom ) ) {
			return $schedules;
		}
		foreach ( $custom as $slug => $row ) {
			if ( isset( $schedules[ $slug ] ) ) {
				continue;
			}
			$interval = isset( $row['interval'] ) ? absint( $row['interval'] ) : 0;
			$display  = isset( $row['display'] ) ? sanitize_text_field( $row['display'] ) : $slug;
			if ( $interval < 60 || $interval > 2592000 ) {
				continue;
			}
			$schedules[ $slug ] = array(
				'interval' => $interval,
				/* translators: %s: interval label */
				'display'  => sprintf( __( 'Every %s', 'schedulelens-cron-viewer' ), $display ),
			);
		}
		return $schedules;
	}

	/**
	 * Add one.
	 *
	 * @param string $slug Slug.
	 * @param int    $interval Seconds.
	 * @param string $display Display.
	 * @return bool|string
	 */
	public static function create( $slug, $interval, $display ) {
		$slug     = sanitize_key( $slug );
		$interval = absint( $interval );
		$display  = sanitize_text_field( $display );
		if ( '' === $slug || $interval < 60 || $interval > 2592000 ) {
			return 'bad_input';
		}
		$all = wp_get_schedules();
		if ( isset( $all[ $slug ] ) ) {
			return 'exists';
		}
		$custom = get_option( 'schedulelens_schedules', array() );
		if ( ! is_array( $custom ) ) {
			$custom = array();
		}
		$custom[ $slug ] = array(
			'interval' => $interval,
			'display'  => '' !== $display ? $display : $slug,
		);
		update_option( 'schedulelens_schedules', $custom, 'no' );
		return true;
	}

	/**
	 * Delete one if unused.
	 *
	 * @param string $slug Slug.
	 * @return bool|string
	 */
	public static function delete( $slug ) {
		$slug   = sanitize_key( $slug );
		$custom = get_option( 'schedulelens_schedules', array() );
		if ( ! isset( $custom[ $slug ] ) ) {
			return 'not_custom';
		}
		$cron = _get_cron_array();
		if ( is_array( $cron ) ) {
			foreach ( $cron as $hooks ) {
				if ( ! is_array( $hooks ) ) {
					continue;
				}
				foreach ( $hooks as $instances ) {
					if ( ! is_array( $instances ) ) {
						continue;
					}
					foreach ( $instances as $data ) {
						if ( isset( $data['schedule'] ) && $slug === $data['schedule'] ) {
							return 'in_use';
						}
					}
				}
			}
		}
		unset( $custom[ $slug ] );
		update_option( 'schedulelens_schedules', $custom, 'no' );
		return true;
	}
}
