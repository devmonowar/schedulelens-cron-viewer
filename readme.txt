=== ScheduleLens - Cron Viewer & Manager ===
Contributors: kstmonowar
Tags: cron, wp-cron, cron job manager, missed schedule, scheduled posts
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

**[Plugin page](https://devmonowar.github.io/schedulelens-cron-viewer/)** — what ScheduleLens does and why it exists · **[Development on GitHub](https://github.com/devmonowar/schedulelens-cron-viewer)** — report issues or contribute.

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

= Why are my scheduled posts not publishing? =
Usually low traffic (nothing triggers WP-Cron), a caching layer serving pages without running PHP, or DISABLE_WP_CRON with no server cron replacing it. Open the Health tab — Green / Yellow / Red plus fix steps tell you which one it is.

= How do I disable WP-Cron and use a real server cron? =
Define DISABLE_WP_CRON as true in wp-config.php, then add a server cron job that calls wp-cron.php every few minutes (your host's control panel usually has a Cron Jobs screen). ScheduleLens keeps working — its Health tab will show the system cron driving schedules.

= What is the difference between WP-Cron and a system cron job? =
WP-Cron only runs when someone visits your site; on a quiet site, scheduled jobs fire late or never. A system cron runs on the clock regardless of traffic. That is the whole difference, and the reason quiet sites miss schedules.

= Which plugin added this cron job? =
The Source column on the events list names the plugin (or theme, or WordPress core) that registered each job. An unknown source usually means custom code in functions.php or a must-use plugin.

== External services ==

ScheduleLens makes one kind of outbound request: a loopback GET to your own site's `wp-cron.php` for the Health tab. Nothing leaves your server — the request goes to your own domain and back.

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
