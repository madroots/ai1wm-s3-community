# Changelog

## 1.52.11

- Apply the cached GitHub release on every update-transient read, so security or update-manager plugins that strip or short-circuit the transient can no longer hide the update.
- Add this fork's update to transient reads another plugin short-circuited.

## 1.52.10

- Published a maintenance release for verifying the manual update check from an installed 1.52.9.

## 1.52.9

- Added a nonce-protected "Check for updates" action to the plugin row that clears both caches and checks immediately.
- Show the last GitHub lookup result or failure reason permanently in the plugin row.
- Documented the plugin-row check as the primary troubleshooting step.

## 1.52.8

- Published a maintenance release for verifying update delivery from an installed 1.52.7.

## 1.52.7

- Added a public release-page fallback when the GitHub API is unavailable or rate-limited.
- Added an admin notice explaining failed update checks and unregistered releases.
- Reconcile updates on the Updates screen as well as the Plugins screen.
- Create the update transient when WordPress has none, so a failed WordPress.org check no longer hides the release.

## 1.52.6

- Published a maintenance release for verifying the GitHub-powered update flow from an installed 1.52.5.

## 1.52.5

- Reconcile the Plugins screen with the latest GitHub release on each visit instead of waiting for the hourly WordPress check.
- Clear the plugin update transient and release cache when an administrator requests a fresh update check.
- Reduced the successful GitHub release cache from one hour to ten minutes.

## 1.52.4

- Published a maintenance release to exercise GitHub-powered WordPress update delivery.

## 1.52.3

- Added native WordPress updates from public GitHub Releases.
- Added a tag-driven GitHub Actions workflow that validates versions and publishes an installable ZIP plus SHA-256 checksum.

## 1.52.2

- Removed vendor ASCII branding and external sales links.
- Removed dormant commercial activation-key plumbing.
- Rebranded the reset fallback theme for the community fork.
- Preserved original GPL copyright notices and compatibility-sensitive base-plugin identifiers.

## 1.52.1

- Disabled commercial one-click and automatic update offers for this fork.
- Preserved public upstream release discovery as non-clickable maintenance information.
- Blocked the private license-check request that includes site and administrator details.
- Used the base plugin's supported `develop` marker to avoid treating this fork as an outdated commercial binary.
- Added a compatibility-review indicator for base-plugin versions newer than 7.112.

## 1.52.0

- Rebuilt the fork from the complete 1.51 extension package.
- Preserved the concrete S3 controller, shared extension framework, UI assets, CA bundle, schedules, reset tools, and WP-CLI integration.
- Removed commercial activation and telemetry code.
- Added Simon Fujdala / best-web.sk fork metadata.
- Declared the All-in-One WP Migration dependency.
- Preserved the existing minimum base-plugin version requirement of 7.84.

## 1.51

- Upstream release used as the fork base.
