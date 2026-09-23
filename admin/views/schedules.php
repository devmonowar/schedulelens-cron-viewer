<?php
/**
 * Schedules view.
 *
 * @package ScheduleLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$schedulelens_schedules = wp_get_schedules();
$schedulelens_custom    = get_option( 'schedulelens_schedules', array() );
$schedulelens_cron      = _get_cron_array();
$schedulelens_usage     = array();
if ( is_array( $schedulelens_cron ) ) {
	foreach ( $schedulelens_cron as $schedulelens_hooks ) {
		if ( ! is_array( $schedulelens_hooks ) ) {
			continue;
		}
		foreach ( $schedulelens_hooks as $schedulelens_instances ) {
			if ( ! is_array( $schedulelens_instances ) ) {
				continue;
			}
			foreach ( $schedulelens_instances as $schedulelens_data ) {
				if ( isset( $schedulelens_data['schedule'] ) && '' !== $schedulelens_data['schedule'] ) {
					$schedulelens_sched                        = $schedulelens_data['schedule'];
					$schedulelens_usage[ $schedulelens_sched ] = isset( $schedulelens_usage[ $schedulelens_sched ] ) ? $schedulelens_usage[ $schedulelens_sched ] + 1 : 1;
				}
			}
		}
	}
}
?>
<table class="wp-list-table widefat striped">
	<thead>
	<tr>
		<th scope="col"><?php esc_html_e( 'Name', 'schedulelens-cron-viewer' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Interval', 'schedulelens-cron-viewer' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Used by', 'schedulelens-cron-viewer' ); ?></th>
		<th scope="col"><?php esc_html_e( 'Actions', 'schedulelens-cron-viewer' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ( $schedulelens_schedules as $schedulelens_slug => $schedulelens_sched ) : ?>
		<?php
		$schedulelens_is_custom = isset( $schedulelens_custom[ $schedulelens_slug ] );
		$schedulelens_count     = isset( $schedulelens_usage[ $schedulelens_slug ] ) ? $schedulelens_usage[ $schedulelens_slug ] : 0;
		$schedulelens_del_url   = wp_nonce_url( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=schedules&schedulelens_action=delete_schedule&slug=' . rawurlencode( $schedulelens_slug ) ), 'schedulelens_action' );
		?>
		<tr>
			<td><code><?php echo esc_html( $schedulelens_slug ); ?></code><br /><span class="description"><?php echo esc_html( $schedulelens_sched['display'] ); ?></span></td>
			<td><?php echo esc_html( schedulelens_human_interval( $schedulelens_sched['interval'] ) ); ?> <span class="description">(<?php echo absint( $schedulelens_sched['interval'] ); ?>s)</span></td>
			<td><?php echo absint( $schedulelens_count ); ?></td>
			<td>
				<?php if ( $schedulelens_is_custom && 0 === $schedulelens_count ) : ?>
					<a href="<?php echo esc_url( $schedulelens_del_url ); ?>"><?php esc_html_e( 'Delete', 'schedulelens-cron-viewer' ); ?></a>
				<?php elseif ( $schedulelens_is_custom ) : ?>
					<span class="description"><?php esc_html_e( 'In use', 'schedulelens-cron-viewer' ); ?></span>
				<?php else : ?>
					<span class="description">—</span>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>

<h2><?php esc_html_e( 'Add interval', 'schedulelens-cron-viewer' ); ?></h2>
<form method="post" action="<?php echo esc_url( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=schedules' ) ); ?>">
	<?php wp_nonce_field( 'schedulelens_action' ); ?>
	<input type="hidden" name="schedulelens_action" value="add_schedule" />
	<table class="form-table">
		<tr>
			<th scope="row"><label for="schedulelens-slug"><?php esc_html_e( 'Slug', 'schedulelens-cron-viewer' ); ?></label></th>
			<td><input id="schedulelens-slug" name="sched_slug" type="text" class="regular-text" required pattern="[a-z0-9_]+" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="schedulelens-interval"><?php esc_html_e( 'Seconds (60-2592000)', 'schedulelens-cron-viewer' ); ?></label></th>
			<td><input id="schedulelens-interval" name="sched_interval" type="number" min="60" max="2592000" required /></td>
		</tr>
		<tr>
			<th scope="row"><label for="schedulelens-display"><?php esc_html_e( 'Label', 'schedulelens-cron-viewer' ); ?></label></th>
			<td><input id="schedulelens-display" name="sched_display" type="text" class="regular-text" placeholder="Every 5 min" /></td>
		</tr>
	</table>
	<?php submit_button( __( 'Add interval', 'schedulelens-cron-viewer' ) ); ?>
</form>
