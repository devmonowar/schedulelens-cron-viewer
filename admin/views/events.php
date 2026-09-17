<?php
/**
 * Events view.
 *
 * @package ScheduleLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$schedulelens_all_events = ScheduleLens_Events::get_all();
$schedulelens_settings   = get_option( 'schedulelens_settings', array() );
$schedulelens_show_core  = ! isset( $schedulelens_settings['show_core'] ) || (int) $schedulelens_settings['show_core'] === 1;

// Filters (read-only display, no state change; actions verify nonce in handler).
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter.
$schedulelens_search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter.
$schedulelens_filter = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : 'all';

$schedulelens_filtered = array();
foreach ( $schedulelens_all_events as $schedulelens_e ) {
	if ( ! $schedulelens_show_core && $schedulelens_e['is_core'] ) {
		continue;
	}
	if ( '' !== $schedulelens_search && false === stripos( $schedulelens_e['hook'], $schedulelens_search ) ) {
		continue;
	}
	if ( 'overdue' === $schedulelens_filter && ! $schedulelens_e['overdue'] ) {
		continue;
	}
	if ( 'core' === $schedulelens_filter && ! $schedulelens_e['is_core'] ) {
		continue;
	}
	if ( 'custom' === $schedulelens_filter && $schedulelens_e['is_core'] ) {
		continue;
	}
	$schedulelens_filtered[] = $schedulelens_e;
}

$schedulelens_overdue_count = 0;
foreach ( $schedulelens_all_events as $schedulelens_e ) {
	if ( $schedulelens_e['overdue'] ) {
		$schedulelens_overdue_count++;
	}
}
$schedulelens_next = null;
foreach ( $schedulelens_all_events as $schedulelens_candidate ) {
	if ( $schedulelens_candidate['timestamp'] >= time() ) {
		$schedulelens_next = $schedulelens_candidate;
		break;
	}
}
$schedulelens_schedules = wp_get_schedules();
$schedulelens_total     = count( $schedulelens_all_events );
$schedulelens_shown     = min( count( $schedulelens_filtered ), 100 );
?>

<div class="schedulelens-statusbar">
	<?php if ( $schedulelens_overdue_count > 0 ) : ?>
		<span class="schedulelens-dot red"></span>
		<?php
		/* translators: 1: total, 2: overdue count */
		printf( esc_html__( '%1$d events • %2$d overdue', 'schedulelens-cron-viewer' ), absint( count( $schedulelens_all_events ) ), absint( $schedulelens_overdue_count ) );
		?>
	<?php else : ?>
		<span class="schedulelens-dot green"></span>
		<?php
		/* translators: %d: total events */
		printf( esc_html__( '%d events • All on time', 'schedulelens-cron-viewer' ), absint( count( $schedulelens_all_events ) ) );
		?>
	<?php endif; ?>
	<?php if ( $schedulelens_next ) : ?>
		<span class="schedulelens-next">
		<?php
		/* translators: 1: hook, 2: human time */
		printf( esc_html__( 'Next: %1$s %2$s', 'schedulelens-cron-viewer' ), esc_html( $schedulelens_next['hook'] ), esc_html( human_time_diff( time(), $schedulelens_next['timestamp'] ) ) );
		?>
		</span>
	<?php endif; ?>
	<a class="button" href="<?php echo esc_url( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=health' ) ); ?>"><?php esc_html_e( 'Check health', 'schedulelens-cron-viewer' ); ?></a>
</div>

<form method="get" class="schedulelens-filters">
	<input type="hidden" name="page" value="schedulelens-cron-viewer" />
	<input type="hidden" name="tab" value="events" />
	<select name="filter" onchange="this.form.submit()">
		<?php
		foreach ( array( 'all' => __( 'All', 'schedulelens-cron-viewer' ), 'overdue' => __( 'Overdue', 'schedulelens-cron-viewer' ), 'core' => __( 'Core', 'schedulelens-cron-viewer' ), 'custom' => __( 'Custom', 'schedulelens-cron-viewer' ) ) as $schedulelens_k => $schedulelens_label ) {
			echo '<option value="' . esc_attr( $schedulelens_k ) . '"' . selected( $schedulelens_filter, $schedulelens_k, false ) . '>' . esc_html( $schedulelens_label ) . '</option>';
		}
		?>
	</select>
	<input type="search" name="s" value="<?php echo esc_attr( $schedulelens_search ); ?>" placeholder="<?php esc_attr_e( 'Search hook…', 'schedulelens-cron-viewer' ); ?>" />
	<button class="button"><?php esc_html_e( 'Filter', 'schedulelens-cron-viewer' ); ?></button>
</form>

<?php if ( count( $schedulelens_filtered ) > 100 ) : ?>
	<p class="description">
		<?php
		/* translators: 1: shown, 2: total */
		printf( esc_html__( 'Showing %1$d of %2$d events (soonest first). Use search/filter to narrow down.', 'schedulelens-cron-viewer' ), absint( $schedulelens_shown ), absint( count( $schedulelens_filtered ) ) );
		?>
	</p>
<?php endif; ?>
<div class="schedulelens-table-wrap">
<table class="wp-list-table widefat striped">
	<thead>
	<tr>
		<th scope="col"><?php esc_html_e( 'Job', 'schedulelens-cron-viewer' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Repeat', 'schedulelens-cron-viewer' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Next run', 'schedulelens-cron-viewer' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Source', 'schedulelens-cron-viewer' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Status', 'schedulelens-cron-viewer' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Actions', 'schedulelens-cron-viewer' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php if ( empty( $schedulelens_filtered ) ) : ?>
		<tr><td colspan="6">🎉 <?php esc_html_e( 'No events found. Try clear search.', 'schedulelens-cron-viewer' ); ?></td></tr>
	<?php else : ?>
		<?php foreach ( array_slice( $schedulelens_filtered, 0, 100 ) as $schedulelens_e ) : ?>
			<?php
			$schedulelens_args_enc = ScheduleLens_Admin::encode_args( $schedulelens_e['args'] );
			$schedulelens_base     = admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=events' );
			$schedulelens_run_url    = wp_nonce_url( $schedulelens_base . '&schedulelens_action=run&hook=' . rawurlencode( $schedulelens_e['hook'] ) . '&ts=' . $schedulelens_e['timestamp'] . '&args=' . rawurlencode( $schedulelens_args_enc ), 'schedulelens_action' );
			$schedulelens_pause_url  = wp_nonce_url( $schedulelens_base . '&schedulelens_action=pause&hook=' . rawurlencode( $schedulelens_e['hook'] ) . '&ts=' . $schedulelens_e['timestamp'] . '&args=' . rawurlencode( $schedulelens_args_enc ), 'schedulelens_action' );
			$schedulelens_delete_url = wp_nonce_url( $schedulelens_base . '&schedulelens_action=delete&hook=' . rawurlencode( $schedulelens_e['hook'] ) . '&ts=' . $schedulelens_e['timestamp'] . '&args=' . rawurlencode( $schedulelens_args_enc ), 'schedulelens_action' );
			?>
			<tr>
				<td><code><?php echo esc_html( $schedulelens_e['hook'] ); ?></code>
					<?php if ( ! empty( $schedulelens_e['args'] ) ) : ?>
						<span class="schedulelens-badge"><?php /* translators: %d: arg count */ printf( esc_html__( '%d args', 'schedulelens-cron-viewer' ), absint( count( $schedulelens_e['args'] ) ) ); ?></span>
					<?php endif; ?>
				</td>
			<td>
				<?php
				if ( empty( $schedulelens_e['schedule'] ) ) {
					esc_html_e( 'Once', 'schedulelens-cron-viewer' );
					} else {
						echo esc_html( isset( $schedulelens_schedules[ $schedulelens_e['schedule'] ]['display'] ) ? $schedulelens_schedules[ $schedulelens_e['schedule'] ]['display'] : $schedulelens_e['schedule'] );
						echo ' <span class="description">(' . esc_html( $schedulelens_e['schedule'] ) . ')</span>';
					}
					?>
				</td>
				<td>
					<?php
					if ( $schedulelens_e['timestamp'] < time() ) {
						/* translators: %s: time ago */
						printf( esc_html__( '%s ago', 'schedulelens-cron-viewer' ), esc_html( human_time_diff( $schedulelens_e['timestamp'], time() ) ) );
					} else {
						/* translators: %s: time remaining */
						printf( esc_html__( 'in %s', 'schedulelens-cron-viewer' ), esc_html( human_time_diff( time(), $schedulelens_e['timestamp'] ) ) );
					}
					?>
					<br /><span class="description"><?php echo esc_html( wp_date( 'Y-m-d H:i:s', $schedulelens_e['timestamp'] ) ); ?></span>
				</td>
				<td><?php echo esc_html( $schedulelens_e['source'] ); ?></td>
				<td>
					<?php if ( $schedulelens_e['overdue'] ) : ?>
						<span class="schedulelens-pill red"><?php esc_html_e( 'Overdue', 'schedulelens-cron-viewer' ); ?></span>
					<?php else : ?>
						<span class="schedulelens-pill green"><?php esc_html_e( 'OK', 'schedulelens-cron-viewer' ); ?></span>
					<?php endif; ?>
					<?php if ( $schedulelens_e['is_core'] ) : ?>
						<span class="schedulelens-pill gray"><?php esc_html_e( 'Core', 'schedulelens-cron-viewer' ); ?></span>
					<?php endif; ?>
				</td>
				<td>
					<a href="<?php echo esc_url( $schedulelens_run_url ); ?>" class="schedulelens-act" data-confirm="run"><?php esc_html_e( 'Run now', 'schedulelens-cron-viewer' ); ?></a> |
					<a href="<?php echo esc_url( $schedulelens_pause_url ); ?>" class="schedulelens-act" data-confirm="pause"><?php esc_html_e( 'Pause', 'schedulelens-cron-viewer' ); ?></a>
					<?php if ( ! $schedulelens_e['is_core'] ) : ?>
						| <a href="<?php echo esc_url( $schedulelens_delete_url ); ?>" class="schedulelens-act" data-confirm="delete"><?php esc_html_e( 'Delete', 'schedulelens-cron-viewer' ); ?></a>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	<?php endif; ?>
	</tbody>
</table>
</div>

<h2><?php esc_html_e( 'Add custom job', 'schedulelens-cron-viewer' ); ?></h2>
<p class="description"><?php esc_html_e( 'Trigger an existing hook on a schedule. No PHP code allowed (safe).', 'schedulelens-cron-viewer' ); ?></p>
<form method="post" action="<?php echo esc_url( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=events' ) ); ?>">
	<?php wp_nonce_field( 'schedulelens_action' ); ?>
	<input type="hidden" name="schedulelens_action" value="add_event" />
	<table class="form-table">
		<tr>
			<th scope="row"><label for="schedulelens-hook"><?php esc_html_e( 'Hook name', 'schedulelens-cron-viewer' ); ?></label></th>
			<td><input id="schedulelens-hook" name="hook" type="text" class="regular-text" required pattern="[A-Za-z0-9_\-\.\/:]+" title="<?php esc_attr_e( 'Letters, numbers, _ - . / : only', 'schedulelens-cron-viewer' ); ?>" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="schedulelens-schedule"><?php esc_html_e( 'Repeat', 'schedulelens-cron-viewer' ); ?></label></th>
			<td>
				<select id="schedulelens-schedule" name="schedule">
					<option value="single"><?php esc_html_e( 'Once', 'schedulelens-cron-viewer' ); ?></option>
					<?php foreach ( $schedulelens_schedules as $schedulelens_slug => $schedulelens_s ) : ?>
						<option value="<?php echo esc_attr( $schedulelens_slug ); ?>"><?php echo esc_html( $schedulelens_s['display'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="schedulelens-args"><?php esc_html_e( 'Args (JSON, optional)', 'schedulelens-cron-viewer' ); ?></label></th>
			<td><input id="schedulelens-args" name="args_json" type="text" class="regular-text" placeholder='["a","b"]' /></td>
		</tr>
	</table>
	<?php submit_button( __( 'Add job', 'schedulelens-cron-viewer' ) ); ?>
</form>

<?php
$schedulelens_paused = get_option( 'schedulelens_paused', array() );
if ( ! empty( $schedulelens_paused ) && is_array( $schedulelens_paused ) ) :
	?>
<h2><?php esc_html_e( 'Paused jobs', 'schedulelens-cron-viewer' ); ?></h2>
<table class="wp-list-table widefat striped">
	<thead>
	<tr>
		<th scope="col"><?php esc_html_e( 'Job', 'schedulelens-cron-viewer' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Repeat', 'schedulelens-cron-viewer' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Actions', 'schedulelens-cron-viewer' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ( $schedulelens_paused as $schedulelens_pkey => $schedulelens_p ) : ?>
		<?php
		$schedulelens_resume_url = wp_nonce_url( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=events&schedulelens_action=resume&key=' . rawurlencode( $schedulelens_pkey ) ), 'schedulelens_action' );
		?>
		<tr>
			<td><code><?php echo esc_html( isset( $schedulelens_p['hook'] ) ? $schedulelens_p['hook'] : '' ); ?></code> <span class="schedulelens-pill yellow"><?php esc_html_e( 'Paused', 'schedulelens-cron-viewer' ); ?></span></td>
			<td><?php echo esc_html( ! empty( $schedulelens_p['schedule'] ) ? $schedulelens_p['schedule'] : __( 'Once', 'schedulelens-cron-viewer' ) ); ?></td>
			<td><a href="<?php echo esc_url( $schedulelens_resume_url ); ?>"><?php esc_html_e( 'Resume', 'schedulelens-cron-viewer' ); ?></a></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
<?php endif; ?>
