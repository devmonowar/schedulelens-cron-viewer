=== ScheduleLens - Cron Viewer & Manager ===
Contributors: kstmonowar
Tags: cron, scheduler, wp-cron, manager, health
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

See all scheduled jobs, catch overdue tasks, run or pause events, and check cron health - lightweight, admin-only, 100% free.

== Description ==

ScheduleLens shows every scheduled job on your WordPress site in plain words. It is a lightweight cron manager for WordPress that helps you fix missed schedule and wp-cron not working issues fast.

* All cron events with repeat, next run, and source (which plugin added it)
* Overdue badge in red, Core badge in gray
* Run now, Pause / Resume, Delete custom jobs
* Add custom job without code (existing hook only, no PHP execution)
* Custom intervals (minimum 60 seconds)
* Health tab: enabled, reachable, late count, total - Green / Yellow / Red
* Last 100 runs log with duration, auto-deleted after 3/7/14 days
* Admin only under Tools > Cron Manager. No frontend load. No tracking.

No external service. 100% free, no upsell.

Learn more: https://github.com/devmonowar/schedulelens-cron-viewer

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install from Plugins > Add New.
2. Activate through the Plugins screen.
3. Go to Tools > Cron Manager.

== Frequently Asked Questions ==

= Why is wp-cron not working / why do posts miss schedule? =
Low traffic, caching, or DISABLE_WP_CRON can stop schedules from firing. Open the Health tab — it shows enabled, reachable, late count and total with Green / Yellow / Red plus fix steps (e.g. set up a real server cron hitting wp-cron.php).

= How do I run a cron job manually? =
Find the event in Tools > Cron Manager and click Run now. The run is logged in the Log tab with duration.

= Can I pause a cron without deleting it? =
Yes. Click Pause — the job is kept as a backup and will not run until you Resume it. Core jobs cannot be deleted (blocked with a warning).

= Will this slow my site? =
No. Admin pages only. No CSS/JS on the frontend.

= Can I break my site? =
Core jobs cannot be deleted (blocked with a warning). Custom jobs can be deleted. Pause keeps a backup to resume.

= Does it send data outside? =
No. Everything stays in wp_options (autoload=no). No remote calls except a loopback check to your own wp-cron.php for the Health tab.

= Can I add PHP code to a job? =
No, and that is intentional for safety and org approval. You can only trigger an existing hook.

== Screenshots ==

1. All cron events - repeat, next run, source plugin.
2. Search and filter - find any job by hook name.
3. Run now, Pause/Resume, Delete safely (core protected).
4. Health tab - Green/Yellow/Red + fix steps.
5. Last 100 runs log with duration.

== Changelog ==

= 1.0.0 =
* Initial release: events list + search/filter, run/pause/resume/delete, add custom job, custom intervals, health lite, 100-row log, settings.

== Privacy ==

Stores paused jobs list, last 100 log rows, custom intervals, and 3 settings in wp_options (autoload=no). Auto-prunes log after 3/7/14 days (default 7). No personal data stored. No external requests except a loopback GET to your own site for health check. All data deleted on uninstall if enabled in Settings.
