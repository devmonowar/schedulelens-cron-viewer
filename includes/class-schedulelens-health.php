<?php
/**
 * Health checks (read-only).
 *
 * @package ScheduleLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ScheduleLens_Health
 */
class ScheduleLens_Health {

	/**
	 * Run checks, cached 5 min.
	 *
	 * @return array
	 */
	public static function check() {
		$cached = get_transient( 'schedulelens_health_cache' );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;

		$reachable = null;
		$code      = 0;
		$ms        = 0;
		$url       = site_url( 'wp-cron.php' );
		$start     = microtime( true );
		$res       = wp_remote_get(
			$url,
			array(
				'timeout'   => 5,
				'blocking'  => true,
				'sslverify' => true,
			)
		);
		if ( is_wp_error( $res ) ) {
			// Retry without verify for self-signed local certs.
			$res = wp_remote_get(
				$url,
				array(
					'timeout'   => 5,
					'blocking'  => true,
					'sslverify' => false,
				)
			);
		}
		$ms = (int) round( ( microtime( true ) - $start ) * 1000 );
		if ( ! is_wp_error( $res ) ) {
			$code      = (int) wp_remote_retrieve_response_code( $res );
			$reachable = ( $code >= 200 && $code < 400 );
		} else {
			$reachable = false;
		}

		$events  = ScheduleLens_Events::get_all();
		$late    = 0;
		$maxlate = 0;
		$now     = time();
		foreach ( $events as $e ) {
			if ( $e['timestamp'] < $now - 60 ) {
				$late++;
				$diff = (int) ( ( $now - $e['timestamp'] ) / 60 );
				if ( $diff > $maxlate ) {
					$maxlate = $diff;
				}
			}
		}

		if ( $disabled || false === $reachable ) {
			$status = 'red';
			$label  = __( 'Not running', 'schedulelens-cron-viewer' );
		} elseif ( $late > 0 ) {
			$status = 'yellow';
			/* translators: %d: minutes */
			$label = sprintf( __( 'Slight delay (%d min)', 'schedulelens-cron-viewer' ), $maxlate );
		} else {
			$status = 'green';
			$label  = __( 'On time', 'schedulelens-cron-viewer' );
		}

		$result = array(
			'disabled'  => $disabled,
			'reachable' => $reachable,
			'code'      => $code,
			'ms'        => $ms,
			'total'     => count( $events ),
			'late'      => $late,
			'maxlate'   => $maxlate,
			'status'    => $status,
			'label'     => $label,
		);
		set_transient( 'schedulelens_health_cache', $result, 5 * MINUTE_IN_SECONDS );
		return $result;
	}

	/**
	 * Clear cache.
	 *
	 * @return void
	 */
	public static function clear_cache() {
		delete_transient( 'schedulelens_health_cache' );
	}
}
