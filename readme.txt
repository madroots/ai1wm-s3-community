=== All-in-One WP Migration S3 Client Extension ===
Contributors: simonfujdala
Requires at least: 3.3
Requires PHP: 5.3
Requires Plugins: all-in-one-wp-migration
Stable tag: 1.52.5
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

== Updates ==

Stable updates are distributed from this repository's public GitHub Releases. A release is created only from a version tag such as `v1.52.5`; ordinary branch pushes do not publish WordPress updates.

The release must contain `all-in-one-wp-migration-s3-client-extension-VERSION.zip`. WordPress refreshes GitHub metadata during normal plugin update checks and on each Plugins screen visit, caches the result for ten minutes, and installs that asset through the normal Plugins screen.

Clicking "Check again" on Dashboard > Updates clears both update caches and forces an immediate plugin update check.

Release procedure: update the plugin header, `AI1WMNE_FORK_VERSION`, `VERSION`, and Stable tag; commit and push; then run `git tag -a vVERSION -m "Release vVERSION"` and `git push origin vVERSION`.

== Changelog ==

= 1.52.5 =
* Made new GitHub releases appear on the Plugins screen without waiting for the hourly WordPress check.
* Made "Check again" clear both update caches and force an immediate plugin update check.

= 1.52.4 =
* Published a maintenance release to exercise GitHub-powered WordPress update delivery.

= 1.52.3 =
* Added native WordPress updates from public GitHub Releases.
* Added a tag-driven GitHub Actions workflow that validates versions and publishes an installable ZIP plus SHA-256 checksum.

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