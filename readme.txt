=== All-in-One WP Migration S3 Client Extension ===
Contributors: simonfujdala
Requires at least: 3.3
Requires PHP: 5.3
Requires Plugins: all-in-one-wp-migration
Stable tag: 1.52.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Independently maintained GPL fork of the S3 Client extension for All-in-One WP Migration.

== Description ==

Adds S3-compatible export, import, scheduled backup, retention, and incremental backup support to All-in-One WP Migration.

The extension requires All-in-One WP Migration 7.84 or newer. The fork uses the base plugin's supported development-build marker so its commercial extension version gate does not misidentify the independently maintained fork as an installable vendor update.

The Plugins screen reports newer upstream releases for manual review, but does not offer another package as a one-click update. Commercial activation and telemetry code is absent. The base plugin's private update check is blocked, public release metadata remains enabled, and S3 requests are sent only to the endpoint configured by the administrator.

== Installation ==

1. Install and activate All-in-One WP Migration.
2. Upload this ZIP through Plugins > Add New > Upload Plugin.
3. Activate the S3 Client extension.
4. Open All-in-One WP Migration > S3 Client Settings.

== Changelog ==

= 1.52.2 =
* Removed vendor ASCII branding and external sales links.
* Removed dormant commercial activation-key plumbing.
* Rebranded the reset fallback theme for the community fork.
* Preserved original GPL copyright notices and compatibility-sensitive base-plugin identifiers.

= 1.52.1 =
* Disabled commercial one-click and automatic update offers for this fork.
* Added a non-clickable upstream release indicator for maintainers.
* Added a warning when the base plugin is newer than the reviewed version.
* Marked the internal extension handshake as a development build without changing the public fork version.
* Blocked the private vendor license check while retaining public release metadata.

= 1.52.0 =
* Restored the complete extension runtime and assets.
* Removed commercial activation and telemetry code.
* Added fork metadata and dependency declaration.
* Preserved the minimum base-plugin version check at 7.84.

= 1.51 =
* Upstream release used as the fork base.