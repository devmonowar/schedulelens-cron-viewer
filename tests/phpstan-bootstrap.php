<?php
/**
 * PHPStan bootstrap: constants the plugin defines at runtime.
 *
 * @package ScheduleLens
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- these mirror runtime constants defined elsewhere.

define( 'SCHEDULELENS_FILE', __DIR__ . '/../schedulelens-cron-viewer.php' );
define( 'SCHEDULELENS_PATH', __DIR__ . '/../' );
define( 'SCHEDULELENS_URL', 'https://example.com/wp-content/plugins/schedulelens-cron-viewer/' );
define( 'SCHEDULELENS_SLUG', 'schedulelens-cron-viewer' );
define( 'SCHEDULELENS_VERSION', '0.0.0' );
define( 'WP_UNINSTALL_PLUGIN', 'schedulelens-cron-viewer/schedulelens-cron-viewer.php' );

// WordPress time constants (defined at runtime in wp-includes/default-constants.php).
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'WEEK_IN_SECONDS', 604800 );
define( 'MONTH_IN_SECONDS', 2592000 );
define( 'YEAR_IN_SECONDS', 31536000 );
