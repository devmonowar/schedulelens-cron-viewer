<?php
/**
 * Lightweight logger (option, max 100, autoload no).
 *
 * @package ScheduleLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ScheduleLens_Logger
 */
class ScheduleLens_Logger {

	/**
	 * Max rows.
	 *
	 * @var int
	 */
	const MAX_ROWS = 100;

	/**
	 * Add entry.
	 *
	 * @param string $hook Hook.
	 * @param bool   $ok Ok.
	 * @param int    $ms Ms.
	 * @param string $error Error.
	 * @return void
	 */
	public static function add( $hook, $ok, $ms, $error = '' ) {
		$log = get_option( 'schedulelens_log', array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		array_unshift(
			$log,
			array(
				'time'  => time(),
				'hook'  => sanitize_key( $hook ),
				'ok'    => $ok ? 1 : 0,
				'ms'    => absint( $ms ),
				'error' => sanitize_text_field( $error ),
			)
		);
		$log = array_slice( $log, 0, self::MAX_ROWS );
		update_option( 'schedulelens_log', $log, 'no' );
	}

	/**
	 * Get log.
	 *
	 * @return array
	 */
	public static function get() {
		$log = get_option( 'schedulelens_log', array() );
		return is_array( $log ) ? $log : array();
	}

	/**
	 * Clear.
	 *
	 * @return void
	 */
	public static function clear() {
		update_option( 'schedulelens_log', array(), 'no' );
	}

	/**
	 * Prune by days (called max once daily via transient).
	 *
	 * @return void
	 */
	public static function maybe_prune() {
		if ( get_transient( 'schedulelens_prune_lock' ) ) {
			return;
		}
		set_transient( 'schedulelens_prune_lock', 1, DAY_IN_SECONDS );
		$settings = get_option( 'schedulelens_settings', array() );
		$days     = isset( $settings['keep_days'] ) ? absint( $settings['keep_days'] ) : 7;
		if ( $days < 3 ) {
			$days = 3;
		}
		if ( $days > 14 ) {
			$days = 14;
		}
		$cutoff = time() - ( $days * DAY_IN_SECONDS );
		$log    = self::get();
		$kept   = array();
		foreach ( $log as $row ) {
			if ( isset( $row['time'] ) && (int) $row['time'] >= $cutoff ) {
				$kept[] = $row;
			}
		}
		if ( count( $kept ) !== count( $log ) ) {
			update_option( 'schedulelens_log', array_slice( $kept, 0, self::MAX_ROWS ), 'no' );
		}
	}
}
