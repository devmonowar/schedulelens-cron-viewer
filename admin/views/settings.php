<?php
/**
 * Settings view.
 *
 * @package ScheduleLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$schedulelens_settings = get_option( 'schedulelens_settings', schedulelens_get_default_settings() );
$schedulelens_keep     = isset( $schedulelens_settings['keep_days'] ) ? absint( $schedulelens_settings['keep_days'] ) : 7;
$schedulelens_show     = ! isset( $schedulelens_settings['show_core'] ) || 1 === (int) $schedulelens_settings['show_core'];
$schedulelens_cleanup  = ! isset( $schedulelens_settings['cleanup_on_uninstall'] ) || 1 === (int) $schedulelens_settings['cleanup_on_uninstall'];
?>
<form method="post" action="<?php echo esc_url( admin_url( 'tools.php?page=schedulelens-cron-viewer&tab=settings' ) ); ?>">
	<?php wp_nonce_field( 'schedulelens_action' ); ?>
	<input type="hidden" name="schedulelens_action" value="save_settings" />
	<table class="form-table">
		<tr>
			<th scope="row"><label for="schedulelens-keep"><?php esc_html_e( 'Keep log', 'schedulelens-cron-viewer' ); ?></label></th>
			<td>
				<select id="schedulelens-keep" name="keep_days">
					<option value="3"<?php selected( $schedulelens_keep, 3 ); ?>><?php esc_html_e( '3 days', 'schedulelens-cron-viewer' ); ?></option>
					<option value="7"<?php selected( $schedulelens_keep, 7 ); ?>><?php esc_html_e( '7 days', 'schedulelens-cron-viewer' ); ?></option>
					<option value="14"<?php selected( $schedulelens_keep, 14 ); ?>><?php esc_html_e( '14 days', 'schedulelens-cron-viewer' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Show core jobs', 'schedulelens-cron-viewer' ); ?></th>
			<td><label><input type="checkbox" name="show_core" value="1"<?php checked( $schedulelens_show ); ?> /> <?php esc_html_e( 'Show WordPress core jobs', 'schedulelens-cron-viewer' ); ?></label></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Cleanup', 'schedulelens-cron-viewer' ); ?></th>
			<td><label><input type="checkbox" name="cleanup_on_uninstall" value="1"<?php checked( $schedulelens_cleanup ); ?> /> <?php esc_html_e( 'Delete all data on uninstall', 'schedulelens-cron-viewer' ); ?></label></td>
		</tr>
	</table>
	<?php submit_button(); ?>
</form>
