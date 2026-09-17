# ScheduleLens — Cron Viewer & Manager for WordPress

> Cron manager for WordPress — see all scheduled jobs, fix missed schedule and wp-cron not working, run or pause events, and check cron health. Lightweight, admin-only, 100% free.

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/schedulelens-cron-viewer?label=wordpress.org)](https://wordpress.org/plugins/schedulelens-cron-viewer/)
[![WordPress Plugin Active Installs](https://img.shields.io/wordpress/plugin/installs/schedulelens-cron-viewer)](https://wordpress.org/plugins/schedulelens-cron-viewer/)
[![License: GPL v2+](https://img.shields.io/badge/license-GPLv2%2B-blue.svg)](LICENSE)

![ScheduleLens events list](https://raw.githubusercontent.com/devmonowar/schedulelens-cron-viewer/main/.wordpress-org/screenshot-1.png)

Fix **missed schedule** posts, debug **wp-cron not working**, and manage every scheduled job in plain words — no code, no tracking, no upsell.

## Features

- All cron events with repeat, next run, and source (which plugin added it)
- Overdue badge in red, Core badge in gray — catch missed schedule fast
- Run now, Pause / Resume, Delete custom jobs (core jobs protected)
- Add custom job without code (existing hook only, no PHP execution)
- Custom intervals (minimum 60 seconds)
- Health tab: enabled, reachable, late count, total — Green / Yellow / Red + fix steps
- Last 100 runs log with duration, auto-deleted after 3/7/14 days (default 7)
- Admin only under Tools > Cron Manager. No frontend load. No tracking.

## Installation

**From WordPress.org (recommended)**

Search for *ScheduleLens* in **Plugins → Add New**, or [download it](https://wordpress.org/plugins/schedulelens-cron-viewer/).

**Via Composer**

```bash
composer require devmonowar/schedulelens-cron-viewer
```

**Manual**

1. Download this repository as a ZIP.
2. Upload it via **Plugins → Add New → Upload Plugin**.
3. Activate, then go to **Tools → Cron Manager**.

## Usage

1. Open **Tools → Cron Manager** — overdue jobs show a red badge.
2. Use **Run now** to fire a job manually, **Pause** to stop it without deleting.
3. Open the **Health** tab when posts miss schedule — follow the Green/Yellow/Red fix steps.
4. Check the **Log** tab for the last 100 runs with duration.

## Screenshots

| Events list | Event actions | Health status |
| --- | --- | --- |
| ![](https://raw.githubusercontent.com/devmonowar/schedulelens-cron-viewer/main/.wordpress-org/screenshot-1.png) | ![](https://raw.githubusercontent.com/devmonowar/schedulelens-cron-viewer/main/.wordpress-org/screenshot-3.png) | ![](https://raw.githubusercontent.com/devmonowar/schedulelens-cron-viewer/main/.wordpress-org/screenshot-4.png) |

| Health tab | Run log |
| --- | --- |
| ![](https://raw.githubusercontent.com/devmonowar/schedulelens-cron-viewer/main/.wordpress-org/screenshot-2.png) | ![](https://raw.githubusercontent.com/devmonowar/schedulelens-cron-viewer/main/.wordpress-org/screenshot-5.png) |

## Links

- WordPress.org: https://wordpress.org/plugins/schedulelens-cron-viewer/
- Support forum: https://wordpress.org/support/plugin/schedulelens-cron-viewer/
- Source: https://github.com/devmonowar/schedulelens-cron-viewer

## Development

This is the development repository. Releases are cut with a git tag (e.g. `1.0.0`) — GitHub Actions deploys the tag to WordPress.org SVN (`trunk` + `tags/<version>`) and syncs `.wordpress-org/` assets. The same tag auto-updates Packagist. Quality gate in CI: PHP lint on 7.4–8.3.

## License

[GPLv2 or later](LICENSE).
