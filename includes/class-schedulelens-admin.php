<?php
/**
 * Admin UI.
 *
 * @package ScheduleLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ScheduleLens_Admin
 */
class ScheduleLens_Admin {

	/**
	 * Hook suffix.
	 *
	 * @var string
	 */
	private static $hook = '';

	/**
	 * Init.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle' ) );
		add_action( 'admin_init', array( 'ScheduleLens_Logger', 'maybe_prune' ) );
		add_action( 'admin_notices', array( __CLASS__, 'welcome' ) );
	}

	/**
	 * Menu: Tools > Cron Manager.
	 *
	 * @return void
	 */
	public static function menu() {
		self::$hook = add_management_page(
			__( 'Cron Manager', 'schedulelens-cron-viewer' ),
			__( 'Cron Manager', 'schedulelens-cron-viewer' ),
			'manage_options',
			'schedulelens-cron-viewer',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Assets only on own page.
	 *
	 * @param string $hook Hook.
	 * @return void
	 */
	public static function assets( $hook ) {
		if ( $hook !== self::$hook ) {
			return;
		}
		wp_enqueue_style(
			'schedulelens-admin',
			SCHEDULELENS_URL . 'admin/css/admin.css',
			array(),
			SCHEDULELENS_VERSION
		);
		wp_enqueue_script(
			'schedulelens-admin',
			SCHEDULELENS_URL . 'admin/js/admin.js',
			array(),
			SCHEDULELENS_VERSION,
			true
		);
		wp_localize_script(
			'schedulelens-admin',
			'schedulelensData',
			array(
				'confirmPause'  => __( 'Pause this job? It will not run until resumed.', 'schedulelens-cron-viewer' ),
				'confirmDelete' => __( 'Delete this custom job? This cannot be undone.', 'schedulelens-cron-viewer' ),
				'confirmRun'    => __( 'Run this job now?', 'schedulelens-cron-viewer' ),
			)
		);
	}

	/**
	 * Welcome notice once.
	 *
	 * @return void
	 */
	public static function welcome() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'tools_page_schedulelens-cron-viewer' !== $screen->id ) {
			return;
		}
		if ( get_option( 'schedulelens_dismissed', 0 ) ) {
			return;
		}
		echo '<div class="notice notice-info is-dismissible schedulelens-welcome"><p>';
		echo esc_html__( 'ScheduleLens is ready. See your scheduled jobs below, pause broken ones, and check the Health tab.', 'schedulelens-cron-viewer' );
		echo ' <a href="' . esc_url( wp_nonce_url( admin_url( 'tools.php?page=schedulelens-cron-viewer&dismiss=1' ), 'schedulelens_action' ) ) . '">';
		echo esc_html__( 'Got it', 'schedulelens-cron-viewer' );
		echo '</a></p></div>';
	}

	/**
	 * Handle all POST/GET actions.
	 *
	 * @return void
	 */
	public static function handle() {
		if ( ! isset( $_GET['page'] ) || 'schedulelens-cron-viewer' !== $_GET['page'] ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Dismiss banner.
		if ( isset( $_GET['dismiss'] ) ) {
			check_admin_referer( 'schedulelens_action' );
			update_option( 'schedulelens_dismissed', 1, false );
			delete_transient( 'schedulelens_welcome' );
			schedulelens_safe_redirect( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=events' ) );
		}

		if ( ! isset( $_POST['schedulelens_action'] ) && ! isset( $_GET['schedulelens_action'] ) ) {
			return;
		}

		$is_post = isset( $_POST['schedulelens_action'] );
		$action  = $is_post ? sanitize_key( wp_unslash( $_POST['schedulelens_action'] ) ) : sanitize_key( wp_unslash( $_GET['schedulelens_action'] ) );
		check_admin_referer( 'schedulelens_action' );

		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'events';
		$back = admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=' . $tab );

		switch ( $action ) {
			case 'run':
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- unslashed here, sanitized by schedulelens_sanitize_hook().
				$hook = isset( $_GET['hook'] ) ? schedulelens_sanitize_hook( wp_unslash( $_GET['hook'] ) ) : '';
				$ts   = isset( $_GET['ts'] ) ? absint( $_GET['ts'] ) : 0;
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON payload, unslashed here and recursively sanitized in decode_args().
				$raw_args = isset( $_GET['args'] ) ? wp_unslash( $_GET['args'] ) : '';
				$args     = self::decode_args( $raw_args );
				$res      = ( '' !== $hook ) ? ScheduleLens_Events::run_now( $hook, $args, self::decode_args_raw( $raw_args ) ) : array( 'ok' => false );
				ScheduleLens_Health::clear_cache();
				if ( ! empty( $res['ok'] ) ) {
					self::notice( __( 'Event triggered. Check the Log tab.', 'schedulelens-cron-viewer' ) );
					schedulelens_safe_redirect( $back . '&ran=1' );
				} else {
					schedulelens_safe_redirect( $back . '&run_failed=1' );
				}
				break;

			case 'pause':
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- unslashed here, sanitized by schedulelens_sanitize_hook().
				$hook = isset( $_GET['hook'] ) ? schedulelens_sanitize_hook( wp_unslash( $_GET['hook'] ) ) : '';
				$ts   = isset( $_GET['ts'] ) ? absint( $_GET['ts'] ) : 0;
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON payload, unslashed here; raw copy used for exact stored-event matching.
				$args = self::decode_args_raw( isset( $_GET['args'] ) ? wp_unslash( $_GET['args'] ) : '' );
				$ok   = ScheduleLens_Events::pause( $hook, $args, $ts );
				ScheduleLens_Health::clear_cache();
				schedulelens_safe_redirect( $back . ( $ok ? '&paused=1' : '&pause_failed=1' ) );
				break;

			case 'resume':
				$key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
				$ok  = ScheduleLens_Events::resume( $key );
				ScheduleLens_Health::clear_cache();
				if ( $ok ) {
					schedulelens_safe_redirect( $back . '&resumed=1' );
				} else {
					schedulelens_safe_redirect( $back . '&resume_failed=1' );
				}
				break;

			case 'delete':
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- unslashed here, sanitized by schedulelens_sanitize_hook().
				$hook = isset( $_GET['hook'] ) ? schedulelens_sanitize_hook( wp_unslash( $_GET['hook'] ) ) : '';
				$ts   = isset( $_GET['ts'] ) ? absint( $_GET['ts'] ) : 0;
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON payload, unslashed here; raw copy used for exact stored-event matching.
				$args = self::decode_args_raw( isset( $_GET['args'] ) ? wp_unslash( $_GET['args'] ) : '' );
				$res  = ScheduleLens_Events::delete( $hook, $args, $ts );
				ScheduleLens_Health::clear_cache();
				if ( true === $res ) {
					schedulelens_safe_redirect( $back . '&deleted=1' );
				} elseif ( 'core_blocked' === $res ) {
					schedulelens_safe_redirect( $back . '&delete_blocked=1' );
				} else {
					schedulelens_safe_redirect( $back . '&delete_failed=1' );
				}
				break;

			case 'add_event':
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- unslashed here, sanitized by schedulelens_sanitize_hook().
				$hook     = isset( $_POST['hook'] ) ? schedulelens_sanitize_hook( wp_unslash( $_POST['hook'] ) ) : '';
				$schedule = isset( $_POST['schedule'] ) ? sanitize_key( wp_unslash( $_POST['schedule'] ) ) : '';
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON payload, unslashed here and recursively sanitized below via schedulelens_sanitize_args().
				$args_raw = isset( $_POST['args_json'] ) ? trim( wp_unslash( $_POST['args_json'] ) ) : '';
				$args     = array();
				if ( '' !== $args_raw ) {
					$decoded = json_decode( $args_raw, true );
					if ( is_array( $decoded ) ) {
						$clean = schedulelens_sanitize_args( array_slice( $decoded, 0, 5 ) );
						$args  = is_array( $clean ) ? $clean : array();
					}
				}
				$res = ScheduleLens_Events::add( $hook, $schedule, $args );
				ScheduleLens_Health::clear_cache();
				if ( true === $res ) {
					schedulelens_safe_redirect( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=events&added=1' ) );
				} else {
					schedulelens_safe_redirect( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=events&add_error=' . $res ) );
				}
				break;

			case 'add_schedule':
				$slug     = isset( $_POST['sched_slug'] ) ? sanitize_key( wp_unslash( $_POST['sched_slug'] ) ) : '';
				$interval = isset( $_POST['sched_interval'] ) ? absint( $_POST['sched_interval'] ) : 0;
				$display  = isset( $_POST['sched_display'] ) ? sanitize_text_field( wp_unslash( $_POST['sched_display'] ) ) : '';
				$res      = ScheduleLens_Schedules::create( $slug, $interval, $display );
				if ( true === $res ) {
					schedulelens_safe_redirect( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=schedules&added=1' ) );
				} else {
					schedulelens_safe_redirect( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=schedules&sched_error=' . $res ) );
				}
				break;

			case 'delete_schedule':
				$slug = isset( $_GET['slug'] ) ? sanitize_key( wp_unslash( $_GET['slug'] ) ) : '';
				$res  = ScheduleLens_Schedules::delete( $slug );
				if ( true === $res ) {
					schedulelens_safe_redirect( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=schedules&deleted=1' ) );
				} else {
					schedulelens_safe_redirect( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=schedules&sched_del_error=' . $res ) );
				}
				break;

			case 'clear_log':
				ScheduleLens_Logger::clear();
				schedulelens_safe_redirect( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=log&cleared=1' ) );
				break;

			case 'save_settings':
				$keep = isset( $_POST['keep_days'] ) ? absint( $_POST['keep_days'] ) : 7;
				if ( ! in_array( $keep, array( 3, 7, 14 ), true ) ) {
					$keep = 7;
				}
				$show    = isset( $_POST['show_core'] ) ? 1 : 0;
				$cleanup = isset( $_POST['cleanup_on_uninstall'] ) ? 1 : 0;
				update_option(
					'schedulelens_settings',
					array(
						'version'              => SCHEDULELENS_VERSION,
						'keep_days'            => $keep,
						'show_core'            => $show,
						'cleanup_on_uninstall' => $cleanup,
					),
					false
				);
				schedulelens_safe_redirect( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=settings&saved=1' ) );
				break;
		}
	}

	/**
	 * Decode args from URL (JSON, URL-encoded) with recursive sanitization.
	 * Sanitized copy is for firing callbacks; use decode_args_raw() when
	 * matching stored events (sanitize can alter values and break matching).
	 *
	 * @param string $raw Raw.
	 * @return array
	 */
	private static function decode_args( $raw ) {
		if ( '' === $raw ) {
			return array();
		}
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return array();
		}
		$clean = schedulelens_sanitize_args( array_slice( $data, 0, 10 ) );
		return is_array( $clean ) ? $clean : array();
	}

	/**
	 * Decode args from URL without sanitizing, for exact stored-event
	 * matching (pause/delete/run-exists). Caller already passed
	 * manage_options + nonce; values are only compared, never output.
	 *
	 * @param string $raw Raw.
	 * @return array
	 */
	private static function decode_args_raw( $raw ) {
		if ( '' === $raw ) {
			return array();
		}
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Encode args for URL (JSON, safe for query string).
	 * Preserves assoc keys so pause/delete can match the stored event.
	 *
	 * @param array $args Args.
	 * @return string
	 */
	public static function encode_args( $args ) {
		return wp_json_encode( (array) $args );
	}

	/**
	 * Flash notice via transient-free query flag (rendered in render()).
	 *
	 * @param string $msg Msg.
	 * @return void
	 */
	private static function notice( $msg ) {
		add_settings_error( 'schedulelens', 'schedulelens', $msg, 'success' );
	}

	/**
	 * Render page.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'schedulelens-cron-viewer' ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab switch, no state change.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'events';
		if ( ! in_array( $tab, array( 'events', 'schedules', 'health', 'log', 'settings' ), true ) ) {
			$tab = 'events';
		}
		$file = SCHEDULELENS_PATH . 'admin/views/' . $tab . '.php';
		echo '<div class="wrap schedulelens-wrap">';
		echo '<h1>' . esc_html__( 'Cron Manager', 'schedulelens-cron-viewer' ) . '</h1>';
		echo '<nav class="nav-tab-wrapper">';
		foreach ( array( 'events', 'schedules', 'health', 'log', 'settings' ) as $t ) {
			$label = ucfirst( $t );
			if ( 'events' === $t ) {
				$label = __( 'Events', 'schedulelens-cron-viewer' );
			} elseif ( 'schedules' === $t ) {
				$label = __( 'Schedules', 'schedulelens-cron-viewer' );
			} elseif ( 'health' === $t ) {
				$label = __( 'Health', 'schedulelens-cron-viewer' );
			} elseif ( 'log' === $t ) {
				$label = __( 'Log', 'schedulelens-cron-viewer' );
			} else {
				$label = __( 'Settings', 'schedulelens-cron-viewer' );
			}
			$cls = ( $t === $tab ) ? ' nav-tab-active' : '';
			echo '<a class="nav-tab' . esc_attr( $cls ) . '" href="' . esc_url( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=' . $t ) ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</nav>';
		settings_errors( 'schedulelens' );
		$flags = array(
			'ran'           => __( 'Event triggered. Check the Log tab.', 'schedulelens-cron-viewer' ),
			'paused'        => __( 'Job paused. See Paused jobs below to resume.', 'schedulelens-cron-viewer' ),
			'resumed'       => __( 'Job resumed.', 'schedulelens-cron-viewer' ),
			'resume_failed' => __( 'Could not resume. The schedule may no longer exist.', 'schedulelens-cron-viewer' ),
			'deleted'       => __( 'Job deleted.', 'schedulelens-cron-viewer' ),
			'added'         => __( 'Job added.', 'schedulelens-cron-viewer' ),
			'saved'         => __( 'Settings saved.', 'schedulelens-cron-viewer' ),
			'cleared'       => __( 'Log cleared.', 'schedulelens-cron-viewer' ),
		);
		foreach ( $flags as $flag => $msg ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only success flag, no state change.
			if ( isset( $_GET[ $flag ] ) ) {
				$cls = ( 'resume_failed' === $flag ) ? 'notice-error' : 'notice-success';
				echo '<div class="notice ' . esc_attr( $cls ) . ' is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
				break;
			}
		}
		$errors = array(
			'run_failed'     => __( 'Could not run. The job may no longer be scheduled, or it failed — see the Log tab.', 'schedulelens-cron-viewer' ),
			'pause_failed'   => __( 'Could not pause. It may have already run, or the pause list (50) is full.', 'schedulelens-cron-viewer' ),
			'delete_blocked' => __( 'Core jobs cannot be deleted.', 'schedulelens-cron-viewer' ),
			'delete_failed'  => __( 'Could not delete. It may already be gone.', 'schedulelens-cron-viewer' ),
		);
		foreach ( $errors as $flag => $msg ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only error flag, no state change.
			if ( isset( $_GET[ $flag ] ) ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
				break;
			}
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only error code, allowlisted below.
		$add_error = isset( $_GET['add_error'] ) ? sanitize_key( wp_unslash( $_GET['add_error'] ) ) : '';
		$add_msgs  = array(
			'bad_hook'       => __( 'Could not add: invalid hook name.', 'schedulelens-cron-viewer' ),
			'limit'          => __( 'Could not add: custom job limit (50) reached.', 'schedulelens-cron-viewer' ),
			'bad_schedule'   => __( 'Could not add: unknown schedule.', 'schedulelens-cron-viewer' ),
			'schedule_failed' => __( 'Could not add: scheduling failed.', 'schedulelens-cron-viewer' ),
		);
		if ( isset( $add_msgs[ $add_error ] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $add_msgs[ $add_error ] ) . '</p></div>';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only error code, allowlisted below.
		$sched_error = isset( $_GET['sched_error'] ) ? sanitize_key( wp_unslash( $_GET['sched_error'] ) ) : '';
		$sched_msgs  = array(
			'bad_input' => __( 'Could not add schedule: slug required, interval 60s–30d.', 'schedulelens-cron-viewer' ),
			'exists'    => __( 'Could not add schedule: that slug already exists.', 'schedulelens-cron-viewer' ),
		);
		if ( isset( $sched_msgs[ $sched_error ] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $sched_msgs[ $sched_error ] ) . '</p></div>';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only error code, allowlisted below.
		$sched_del_error = isset( $_GET['sched_del_error'] ) ? sanitize_key( wp_unslash( $_GET['sched_del_error'] ) ) : '';
		$sched_del_msgs  = array(
			'not_custom' => __( 'Could not delete schedule: not a custom schedule.', 'schedulelens-cron-viewer' ),
			'in_use'     => __( 'Could not delete schedule: events still use it.', 'schedulelens-cron-viewer' ),
		);
		if ( isset( $sched_del_msgs[ $sched_del_error ] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $sched_del_msgs[ $sched_del_error ] ) . '</p></div>';
		}
		if ( file_exists( $file ) ) {
			include $file;
		}
		echo '</div>';
	}
}
