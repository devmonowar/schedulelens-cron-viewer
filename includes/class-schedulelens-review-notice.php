<?php
/**
 * A polite "enjoying this plugin?" review request.
 *
 * @package ScheduleLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Review prompts, confined to the plugin's own Tools screen:
 *
 * - A small, always-visible rating link in the admin footer.
 * - A notice that first appears after ~two weeks of real use, and returns
 *   once a month until the user actually rates the plugin.
 * - Once "Rate it" is clicked, every prompt (notice and footer) disappears
 *   for good.
 */
final class ScheduleLens_Review_Notice {

	const OPTION     = 'schedulelens_review';
	const REVIEW_URL = 'https://wordpress.org/support/plugin/schedulelens-cron-viewer/reviews/#new-post';

	/**
	 * Days of use before the notice first appears.
	 */
	const WAIT_DAYS = 15;

	/**
	 * Days between repeat appearances (once a month).
	 */
	const SNOOZE_DAYS = 30;

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'start_clock' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_render' ) );
		add_filter( 'admin_footer_text', array( __CLASS__, 'footer_text' ) );
	}

	/**
	 * A small, permanent rating link in the admin footer — only on this
	 * plugin's own screen, and only until the user has rated.
	 *
	 * @param string $text Default footer text.
	 * @return string
	 */
	public static function footer_text( $text ) {
		if ( ! self::on_own_screen() ) {
			return $text;
		}

		$state = (array) get_option( self::OPTION, array() );
		if ( ! empty( $state['rated'] ) ) {
			return $text;
		}

		$link = '<a href="' . esc_url( self::REVIEW_URL ) . '" target="_blank" rel="noopener noreferrer">';

		return sprintf(
			/* translators: 1: opening link tag to the WordPress.org review form, 2: closing link tag. */
			esc_html__( 'Enjoying ScheduleLens? Leave us a %1$s&#9733;&#9733;&#9733;&#9733;&#9733; review%2$s — it keeps development going.', 'schedulelens-cron-viewer' ),
			$link,
			'</a>'
		);
	}

	/**
	 * Record when the plugin was first seen in the admin, so existing installs
	 * also wait a full period after updating to a version with this notice.
	 *
	 * @return void
	 */
	public static function start_clock() {
		$state = get_option( self::OPTION );
		if ( ! is_array( $state ) || empty( $state['since'] ) ) {
			update_option( self::OPTION, array( 'since' => time() ), 'no' );
		}
	}

	/**
	 * Process the notice's action links.
	 *
	 * @return void
	 */
	public static function handle_actions() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the nonce is checked immediately below, once we know this is our request.
		if ( empty( $_GET['sl_review'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'sl_review_notice' );

		$state  = (array) get_option( self::OPTION, array() );
		$action = sanitize_key( wp_unslash( $_GET['sl_review'] ) );

		if ( 'rated' === $action ) {
			$state['rated'] = true;
		} elseif ( 'rate' === $action ) {
			// Opened the review form: ask for confirmation on the next visit.
			$state['asked'] = true;
			unset( $state['snooze_until'] );
		} else {
			$state['snooze_until'] = time() + self::SNOOZE_DAYS * DAY_IN_SECONDS;
		}
		update_option( self::OPTION, $state, 'no' );

		wp_safe_redirect( remove_query_arg( array( 'sl_review', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Render the notice when every polite condition is met.
	 *
	 * @return void
	 */
	public static function maybe_render() {
		if ( ! current_user_can( 'manage_options' ) || ! self::should_show() ) {
			return;
		}

		$state = (array) get_option( self::OPTION, array() );
		$later = wp_nonce_url( add_query_arg( 'sl_review', 'later' ), 'sl_review_notice' );
		$rate  = wp_nonce_url( add_query_arg( 'sl_review', 'rate' ), 'sl_review_notice' );
		$rated = wp_nonce_url( add_query_arg( 'sl_review', 'rated' ), 'sl_review_notice' );

		if ( empty( $state['asked'] ) ) {
			$message = '<strong>' . esc_html__( 'Enjoying ScheduleLens?', 'schedulelens-cron-viewer' ) . '</strong> '
				. esc_html__( 'A quick 5-star review helps other people find the plugin and keeps development going. Thank you!', 'schedulelens-cron-viewer' );
		} else {
			$message = '<strong>' . esc_html__( 'Did you get a chance to leave that review?', 'schedulelens-cron-viewer' ) . '</strong> '
				. esc_html__( 'If you did — thank you! Confirm below and we will never ask again.', 'schedulelens-cron-viewer' );
		}
		?>
		<div class="notice notice-info" style="padding:12px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
			<p style="margin:0;"><?php echo wp_kses( $message, array( 'strong' => array() ) ); ?></p>
			<p style="margin:0;white-space:nowrap;">
				<?php if ( ! empty( $state['asked'] ) ) : ?>
					<a class="button" href="<?php echo esc_url( $rated ); ?>" style="margin-right:6px;">
						<?php esc_html_e( 'Yes, I left a review', 'schedulelens-cron-viewer' ); ?>
					</a>
				<?php endif; ?>
				<a class="button" href="<?php echo esc_url( $later ); ?>" style="margin-right:6px;">
					<?php esc_html_e( 'Maybe later', 'schedulelens-cron-viewer' ); ?>
				</a>
				<a class="button button-primary" href="<?php echo esc_url( self::REVIEW_URL ); ?>" target="_blank" rel="noopener noreferrer" onclick="window.location='<?php echo esc_js( $rate ); ?>';return true;">
					<?php esc_html_e( 'Rate it ★★★★★', 'schedulelens-cron-viewer' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * All the polite conditions in one place.
	 *
	 * @return bool
	 */
	private static function should_show() {
		if ( ! self::on_own_screen() ) {
			return false;
		}

		$state = (array) get_option( self::OPTION, array() );
		if ( ! empty( $state['rated'] ) ) {
			return false;
		}
		if ( ! empty( $state['snooze_until'] ) && time() < (int) $state['snooze_until'] ) {
			return false;
		}
		if ( empty( $state['since'] ) || time() < (int) $state['since'] + self::WAIT_DAYS * DAY_IN_SECONDS ) {
			return false;
		}

		// Only ask people who actually use the plugin: a Run-now in the log,
		// a custom event added, or a job paused. All three are autoload=no
		// options, so this costs three cached get_option() calls.
		$log    = get_option( 'schedulelens_log', array() );
		$custom = (int) get_option( 'schedulelens_custom_count', 0 );
		$paused = get_option( 'schedulelens_paused', array() );

		return ! empty( $log ) || $custom > 0 || ! empty( $paused );
	}

	/**
	 * Whether the current admin screen is this plugin's Tools page.
	 *
	 * @return bool
	 */
	private static function on_own_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return $screen && 'tools_page_schedulelens-cron-viewer' === $screen->id;
	}
}
