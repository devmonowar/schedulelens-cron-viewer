<?php
/**
 * Cron events model: list, run, pause, delete, add.
 *
 * @package ScheduleLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ScheduleLens_Events
 */
class ScheduleLens_Events {

	/**
	 * Get all events flattened.
	 *
	 * @return array[]
	 */
	public static function get_all() {
		$cron = _get_cron_array();
		if ( ! is_array( $cron ) ) {
			return array();
		}
		$paused    = get_option( 'schedulelens_paused', array() );
		$events    = array();
		$schedules = wp_get_schedules();

		foreach ( $cron as $timestamp => $hooks ) {
			if ( ! is_array( $hooks ) ) {
				continue;
			}
			foreach ( $hooks as $hook => $instances ) {
				if ( ! is_array( $instances ) ) {
					continue;
				}
				foreach ( $instances as $sig => $data ) {
					// Single (non-recurring) events store schedule as false - normalize to ''.
					$schedule = isset( $data['schedule'] ) && is_string( $data['schedule'] ) ? $data['schedule'] : '';
					$interval = isset( $data['interval'] ) ? (int) $data['interval'] : 0;
					if ( '' !== $schedule && 0 === $interval && isset( $schedules[ $schedule ]['interval'] ) ) {
						$interval = (int) $schedules[ $schedule ]['interval'];
					}
					$args      = isset( $data['args'] ) ? $data['args'] : array();
					$key       = self::event_key( $hook, $args, $timestamp );
					$is_paused = isset( $paused[ $key ] );
					$events[]  = array(
						'hook'      => $hook,
						'timestamp' => (int) $timestamp,
						'schedule'  => $schedule,
						'interval'  => $interval,
						'args'      => is_array( $args ) ? $args : array(),
						'sig'       => $sig,
						'key'       => $key,
						'source'    => schedulelens_detect_source( $hook ),
						'is_core'   => schedulelens_is_core_hook( $hook ),
						'is_paused' => $is_paused,
						'overdue'   => ( (int) $timestamp < time() - 60 ),
					);
				}
			}
		}

		usort(
			$events,
			function ( $a, $b ) {
				return $a['timestamp'] <=> $b['timestamp'];
			}
		);

		return $events;
	}

	/**
	 * Stable key for pause map.
	 *
	 * @param string $hook Hook.
	 * @param array  $args Args.
	 * @param int    $timestamp Timestamp.
	 * @return string
	 */
	public static function event_key( $hook, $args, $timestamp ) {
		return md5( $hook . '|' . wp_json_encode( $args ) . '|' . (int) $timestamp );
	}

	/**
	 * Run event now (fires callbacks without changing schedule).
	 * Refuses unknown hooks: without this, an edited URL could fire any
	 * action hook (admin_init, shutdown, ...) via do_action_ref_array().
	 * Fires with the matched stored args as-is: is_scheduled() already
	 * proved they equal a real cron event, so sanitizing here would only
	 * silently run the job with different args.
	 *
	 * @param string $hook Hook.
	 * @param array  $args Unused, kept for backward compatibility.
	 * @param array  $match_args Args to match and fire (raw stored args).
	 * @return array Result with ok + time.
	 */
	public static function run_now( $hook, $args, $match_args = null ) {
		if ( null === $match_args ) {
			$match_args = $args;
		}
		if ( ! self::is_scheduled( $hook, $match_args ) ) {
			ScheduleLens_Logger::add( $hook, false, 0, 'not_scheduled' );
			return array(
				'ok'    => false,
				'ms'    => 0,
				'error' => 'not_scheduled',
			);
		}
		$start = microtime( true );
		$ok    = false;
		$error = '';
		try {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- intentional: fire the user-selected registered cron hook for "Run now".
			do_action_ref_array( $hook, $match_args );
			$ok = true;
		} catch ( Exception $e ) {
			$error = $e->getMessage();
		} catch ( Error $e ) {
			$error = $e->getMessage();
		}
		$ms = (int) round( ( microtime( true ) - $start ) * 1000 );
		ScheduleLens_Logger::add( $hook, $ok, $ms, $error );
		return array(
			'ok'    => $ok,
			'ms'    => $ms,
			'error' => $error,
		);
	}

	/**
	 * Is this exact hook+args combination currently scheduled?
	 *
	 * @param string $hook Hook.
	 * @param array  $args Args.
	 * @return bool
	 */
	public static function is_scheduled( $hook, $args ) {
		$cron = _get_cron_array();
		if ( ! is_array( $cron ) ) {
			return false;
		}
		$wanted = wp_json_encode( $args );
		foreach ( $cron as $hooks ) {
			if ( ! is_array( $hooks ) || ! isset( $hooks[ $hook ] ) || ! is_array( $hooks[ $hook ] ) ) {
				continue;
			}
			foreach ( $hooks[ $hook ] as $data ) {
				$data_args = isset( $data['args'] ) && is_array( $data['args'] ) ? $data['args'] : array();
				if ( wp_json_encode( $data_args ) === $wanted ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Pause event: unschedule + store for restore.
	 *
	 * @param string $hook Hook.
	 * @param array  $args Args.
	 * @param int    $timestamp Timestamp.
	 * @return bool
	 */
	public static function pause( $hook, $args, $timestamp ) {
		$paused = get_option( 'schedulelens_paused', array() );
		if ( count( $paused ) >= 50 ) {
			return false;
		}
		$key  = self::event_key( $hook, $args, $timestamp );
		$cron = _get_cron_array();
		if ( ! isset( $cron[ $timestamp ][ $hook ] ) ) {
			return false;
		}
		$found  = false;
		$wanted = wp_json_encode( $args );
		foreach ( $cron[ $timestamp ][ $hook ] as $sig => $data ) {
			$data_args = isset( $data['args'] ) ? $data['args'] : array();
			if ( wp_json_encode( $data_args ) === $wanted ) {
				$paused[ $key ] = array(
					'hook'      => $hook,
					'args'      => $args,
					'timestamp' => (int) $timestamp,
					'schedule'  => isset( $data['schedule'] ) && is_string( $data['schedule'] ) ? $data['schedule'] : '',
					'interval'  => isset( $data['interval'] ) ? (int) $data['interval'] : 0,
				);
				wp_unschedule_event( (int) $timestamp, $hook, $args );
				$found = true;
				break;
			}
		}
		if ( $found ) {
			update_option( 'schedulelens_paused', $paused, false );
		}
		return $found;
	}

	/**
	 * Resume paused event.
	 *
	 * @param string $key Pause key.
	 * @return bool
	 */
	public static function resume( $key ) {
		$paused = get_option( 'schedulelens_paused', array() );
		if ( ! isset( $paused[ $key ] ) ) {
			return false;
		}
		$item     = $paused[ $key ];
		$hook     = $item['hook'];
		$args     = $item['args'];
		$schedule = $item['schedule'];
		if ( '' === $schedule ) {
			$res = wp_schedule_single_event( time() + 60, $hook, $args );
		} else {
			$schedules = wp_get_schedules();
			if ( ! isset( $schedules[ $schedule ] ) ) {
				return false;
			}
			$res = wp_schedule_event( time() + 60, $schedule, $hook, $args );
		}
		if ( false === $res ) {
			return false;
		}
		unset( $paused[ $key ] );
		update_option( 'schedulelens_paused', $paused, false );
		return true;
	}

	/**
	 * Delete custom event only.
	 *
	 * @param string $hook Hook.
	 * @param array  $args Args.
	 * @param int    $timestamp Timestamp.
	 * @return bool|string True or error code.
	 */
	public static function delete( $hook, $args, $timestamp ) {
		if ( schedulelens_is_core_hook( $hook ) ) {
			return 'core_blocked';
		}
		$timestamp = (int) $timestamp;
		$cron      = _get_cron_array();
		if ( ! isset( $cron[ $timestamp ][ $hook ] ) || ! is_array( $cron[ $timestamp ][ $hook ] ) ) {
			return 'not_found';
		}
		$wanted = wp_json_encode( $args );
		$found  = false;
		foreach ( $cron[ $timestamp ][ $hook ] as $data ) {
			$data_args = isset( $data['args'] ) && is_array( $data['args'] ) ? $data['args'] : array();
			if ( wp_json_encode( $data_args ) === $wanted ) {
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			return 'not_found';
		}
		wp_unschedule_event( $timestamp, $hook, $args );
		self::untrack_custom( $hook, $args );
		return true;
	}

	/**
	 * Add custom event for existing hook.
	 *
	 * @param string $hook Hook.
	 * @param string $schedule Schedule slug or 'single'.
	 * @param array  $args Args.
	 * @return bool|string True or error code.
	 */
	public static function add( $hook, $schedule, $args ) {
		$hook = schedulelens_sanitize_hook( $hook );
		if ( '' === $hook ) {
			return 'bad_hook';
		}
		if ( self::custom_live_count() >= 50 ) {
			return 'limit';
		}
		if ( 'single' === $schedule ) {
			$res = wp_schedule_single_event( time() + 60, $hook, $args );
		} else {
			$schedules = wp_get_schedules();
			if ( ! isset( $schedules[ $schedule ] ) ) {
				return 'bad_schedule';
			}
			$res = wp_schedule_event( time() + 60, $schedule, $hook, $args );
		}
		if ( false === $res ) {
			return 'schedule_failed';
		}
		self::track_custom( $hook, $args );
		return true;
	}

	/**
	 * How many plugin-added jobs are still scheduled.
	 * Reconciled live: single events expire on their own and anything can
	 * be deleted outside the plugin, so a plain counter would drift
	 * (and eventually block adds forever at 50).
	 *
	 * @return int
	 */
	public static function custom_live_count() {
		$tracked = get_option( 'schedulelens_custom_jobs', array() );
		if ( ! is_array( $tracked ) ) {
			$tracked = array();
		}
		$live = array();
		foreach ( $tracked as $job ) {
			if ( ! is_array( $job ) || ! isset( $job['hook'], $job['args'] ) ) {
				continue;
			}
			if ( self::is_scheduled( $job['hook'], $job['args'] ) ) {
				$live[] = $job;
			}
		}
		if ( count( $live ) !== count( $tracked ) ) {
			update_option( 'schedulelens_custom_jobs', $live, false );
		}
		return count( $live );
	}

	/**
	 * Remember a plugin-added job for the live limit count.
	 *
	 * @param string $hook Hook.
	 * @param array  $args Args.
	 * @return void
	 */
	private static function track_custom( $hook, $args ) {
		$tracked = get_option( 'schedulelens_custom_jobs', array() );
		if ( ! is_array( $tracked ) ) {
			$tracked = array();
		}
		$tracked[] = array(
			'hook' => $hook,
			'args' => $args,
		);
		update_option( 'schedulelens_custom_jobs', array_slice( $tracked, -100 ), false );
	}

	/**
	 * Forget a plugin-added job after delete.
	 *
	 * @param string $hook Hook.
	 * @param array  $args Args.
	 * @return void
	 */
	private static function untrack_custom( $hook, $args ) {
		$tracked = get_option( 'schedulelens_custom_jobs', array() );
		if ( ! is_array( $tracked ) || empty( $tracked ) ) {
			return;
		}
		$wanted = wp_json_encode( $args );
		foreach ( $tracked as $i => $job ) {
			if ( isset( $job['hook'], $job['args'] ) && $job['hook'] === $hook && wp_json_encode( $job['args'] ) === $wanted ) {
				unset( $tracked[ $i ] );
				break;
			}
		}
		update_option( 'schedulelens_custom_jobs', array_values( $tracked ), false );
	}
}
