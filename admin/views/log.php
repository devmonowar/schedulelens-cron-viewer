<?php
/**
 * Log view.
 *
 * @package ScheduleLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$schedulelens_log      = ScheduleLens_Logger::get();
$schedulelens_settings = get_option( 'schedulelens_settings', array() );
$schedulelens_keep     = isset( $schedulelens_settings['keep_days'] ) ? absint( $schedulelens_settings['keep_days'] ) : 7;
$schedulelens_clear_url = wp_nonce_url( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=log&schedulelens_action=clear_log' ), 'schedulelens_action' );
?>
<p>
	<a class="button" href="<?php echo esc_url( $schedulelens_clear_url ); ?>"><?php esc_html_e( 'Clear log', 'schedulelens-cron-viewer' ); ?></a>
	<span class="description"><?php /* translators: %d: days */ printf( esc_html__( 'Keep: %d days', 'schedulelens-cron-viewer' ), absint( $schedulelens_keep ) ); ?></span>
	<a class="button" href="<?php echo esc_url( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=log' ) ); ?>"><?php esc_html_e( 'Refresh', 'schedulelens-cron-viewer' ); ?></a>
</p>
<table class="wp-list-table widefat striped">
	<thead>
	<tr>
		<th scope="col"><?php esc_html_e( 'Time', 'schedulelens-cron-viewer' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Job', 'schedulelens-cron-viewer' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Duration', 'schedulelens-cron-viewer' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Status', 'schedulelens-cron-viewer' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php if ( empty( $schedulelens_log ) ) : ?>
		<tr><td colspan="4"><?php esc_html_e( 'No runs yet. Use “Run now” from Events.', 'schedulelens-cron-viewer' ); ?></td></tr>
	<?php else : ?>
		<?php foreach ( $schedulelens_log as $schedulelens_row ) : ?>
			<tr>
				<td><?php echo esc_html( wp_date( 'Y-m-d H:i:s', isset( $schedulelens_row['time'] ) ? (int) $schedulelens_row['time'] : time() ) ); ?></td>
				<td><code><?php echo esc_html( isset( $schedulelens_row['hook'] ) ? $schedulelens_row['hook'] : '' ); ?></code></td>
				<td><?php echo absint( isset( $schedulelens_row['ms'] ) ? $schedulelens_row['ms'] : 0 ); ?> ms</td>
				<td>
					<?php if ( ! empty( $schedulelens_row['ok'] ) ) : ?>
						<span class="schedulelens-pill green">OK</span>
					<?php else : ?>
						<span class="schedulelens-pill red">Failed</span>
						<?php if ( ! empty( $schedulelens_row['error'] ) ) : ?>
							<span class="description"><?php echo esc_html( $schedulelens_row['error'] ); ?></span>
						<?php endif; ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	<?php endif; ?>
	</tbody>
</table>
