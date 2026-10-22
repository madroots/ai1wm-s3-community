# Upstream decisions

One section per batch of ServMask S3 Client releases that the sync agent reviewed.
Every upstream changelog line is classified and either implemented or skipped, with the reason.
Skipping an unclear, non-security line is intentional; see `.github/agent/prompt.md`.

## Manual changes before the first automated sync (S3 1.52 to 1.59 not yet reviewed)

| Upstream line | Class | Decision | Detail |
|---|---|---|---|
| 1.57: Fixed a permission issue in the Settings form. | security | implemented (manually) | `admin_post_ai1wmne_s3_connection` and `admin_post_ai1wmne_s3_settings` ran for any logged-in user without a nonce. Both handlers now require the `export` capability and a nonce (`Ai1wmne_Settings_Controller::authorize`); the forms carry the nonces. Regression: `tests/cases/03-settings-authorization.sh`. |
| 1.53: Fixed restore WP CLI command. | bug | implemented (manually) | `wp ai1wm s3-client restore` omitted `region_name`, so the import aborted with "S3 Client Region Name is not specified". Covered by `tests/cases/02-backup-restore.sh`. |
