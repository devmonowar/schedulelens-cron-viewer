<?php
/**
 * Health view.
 *
 * @package ScheduleLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$schedulelens_health = ScheduleLens_Health::check();
$schedulelens_dot    = 'green';
if ( 'yellow' === $schedulelens_health['status'] ) {
	$schedulelens_dot = 'yellow';
} elseif ( 'red' === $schedulelens_health['status'] ) {
	$schedulelens_dot = 'red';
}
?>
<div class="schedulelens-cards">
	<div class="schedulelens-card">
		<h2><?php esc_html_e( 'Status', 'schedulelens-cron-viewer' ); ?></h2>
		<p><span class="schedulelens-dot <?php echo esc_attr( $schedulelens_dot ); ?>"></span> <strong><?php echo esc_html( $schedulelens_health['label'] ); ?></strong></p>
		<?php if ( 'green' === $schedulelens_health['status'] ) : ?>
			<p class="description"><?php esc_html_e( 'Your scheduled posts will publish on time.', 'schedulelens-cron-viewer' ); ?></p>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'Some jobs are late. See steps below.', 'schedulelens-cron-viewer' ); ?></p>
		<?php endif; ?>
	</div>
	<div class="schedulelens-card">
		<h2><?php esc_html_e( 'Checks', 'schedulelens-cron-viewer' ); ?></h2>
		<ul>
			<li><?php esc_html_e( 'WP-Cron enabled:', 'schedulelens-cron-viewer' ); ?> <strong><?php echo $schedulelens_health['disabled'] ? esc_html__( 'No', 'schedulelens-cron-viewer' ) : esc_html__( 'Yes', 'schedulelens-cron-viewer' ); ?></strong></li>
			<li><?php esc_html_e( 'Reachable:', 'schedulelens-cron-viewer' ); ?> <strong><?php echo $schedulelens_health['reachable'] ? esc_html__( 'Yes', 'schedulelens-cron-viewer' ) . ' (' . absint( $schedulelens_health['code'] ) . ', ' . absint( $schedulelens_health['ms'] ) . 'ms)' : esc_html__( 'No', 'schedulelens-cron-viewer' ); ?></strong></li>
			<li><?php /* translators: %d: late count */ printf( esc_html__( 'Late events: %d', 'schedulelens-cron-viewer' ), absint( $schedulelens_health['late'] ) ); ?></li>
			<li><?php /* translators: %d: total */ printf( esc_html__( 'Total events: %d', 'schedulelens-cron-viewer' ), absint( $schedulelens_health['total'] ) ); ?></li>
		</ul>
		<p class="description"><?php esc_html_e( 'Note: the reachability check above loads your own wp-cron.php, which may run jobs that are due.', 'schedulelens-cron-viewer' ); ?></p>
	</div>
	<div class="schedulelens-card">
		<h2><?php esc_html_e( 'What to do', 'schedulelens-cron-viewer' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Check if a caching plugin blocks cron.', 'schedulelens-cron-viewer' ); ?></li>
			<li><?php esc_html_e( 'Ask your host about loopback requests.', 'schedulelens-cron-viewer' ); ?></li>
			<li><?php esc_html_e( 'Pause broken jobs from the Events tab.', 'schedulelens-cron-viewer' ); ?></li>
		</ol>
	</div>
</div>
