# Upstream decisions

One section per batch of ServMask S3 Client releases that the sync agent reviewed.
Every upstream changelog line is classified and either implemented or skipped, with the reason.
Skipping an unclear, non-security line is intentional; see `.github/agent/prompt.md`.

## Manual changes before the first automated sync (S3 1.52 to 1.59 not yet reviewed)

| Upstream line | Class | Decision | Detail |
|---|---|---|---|
| 1.57: Fixed a permission issue in the Settings form. | security | implemented (manually) | `admin_post_ai1wmne_s3_connection` and `admin_post_ai1wmne_s3_settings` ran for any logged-in user without a nonce. Both handlers now require the `export` capability and a nonce (`Ai1wmne_Settings_Controller::authorize`); the forms carry the nonces. Regression: `tests/cases/03-settings-authorization.sh`. |
| 1.53: Fixed restore WP CLI command. | bug | implemented (manually) | `wp ai1wm s3-client restore` omitted `region_name`, so the import aborted with "S3 Client Region Name is not specified". Covered by `tests/cases/02-backup-restore.sh`. |

## S3 1.52 to 1.59

| Upstream line | Class | Decision | Detail |
|---|---|---|---|
| 1.52: Allow users to set AWS connection details. | feature | already implemented | `Ai1wmne_Settings_Controller::connection()` and `lib/view/settings/index.php` already expose endpoint, bucket template, region, access key, secret key and HTTPS protocol. |
| 1.53: Fixed list backups WP CLI command. Fixed restore WP CLI command. | bug | already implemented | Restore's missing `region_name` was added in 2e03142; `list-backups` verified working and asserted by `tests/cases/02-backup-restore.sh`. |
| 1.54: Added support for WordPress Twenty Twenty-Five Theme in reset functionality. | feature | implemented | Added `twentytwentyfive` => `6.7` as the newest entry in `Ai1wmve_Reset_Themes::$default_themes`. |
| 1.55: Added support for custom db prefixed tables on export. | vague | skipped | No identifiable code path in this extension; table enumeration and prefix filters are handled by the base plugin (`Ai1wm_Export_Enumerate_Tables` already adds `wbk_`/`civicrm_`). |
| 1.56: Support for All-in-One WP Migration 7.104 | compatibility | nothing to do | CI runs against base plugin 7.112; every base-plugin API the extension calls was verified present. |
| 1.57: Fixed a permission issue in the Settings form. | security | already implemented | `Ai1wmne_Settings_Controller::authorize()` requires the `export` capability and a nonce; both forms carry nonces. Regression: `tests/cases/03-settings-authorization.sh`. |
| 1.58: Fix browser file upload broken by missing chunked upload handler | vague | skipped | The fork has no extension-owned browser uploader; the base plugin's `Ai1wm_Import_Upload` handler is left intact for non-S3 imports, so browser uploads are not broken here. |
| 1.58: Fix the "I need help" contact form not opening on the reset and schedules pages | vendor-only | skipped | Support form was removed from the fork on purpose. |
| 1.59: Fix file upload imports not being recognized as manual imports | vague | skipped | Relates to the upstream browser-upload feature that is not present in this fork; the base plugin's `ai1wm_manual_import` flow is untouched and intact. |
