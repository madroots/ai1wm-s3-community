# Maintenance

How this fork follows ServMask's S3 Client Extension with almost no manual work.

## Flow

```
upstream-sync.yml (daily 13:00 UTC)
  detect   scripts/upstream.mjs status
           - ServMask changelog table (signal A)
           - base plugin "requires" for S3 on wordpress.org (signal B)
           - any open upstream/* PR? then do nothing
  sync     s3 mode        agent (DeepSeek) edits code + upstream/decisions.md + notes.json
                          -> guard-diff -> php -l -> version bump -> PR
           base mode      deterministic "reviewed against base X" bump -> PR
           heartbeat mode keeps scheduled workflows alive on a quiet repo
  PR       tests.yml: lint, PHPCompatibility 7.4+, build ZIP -> publish as PRERELEASE rc-pr<N>-<sha>,
                    Docker integration (WordPress + S3 server) installs that prerelease asset
           "Required checks" green -> auto-merge (squash)
main     release-on-merge.yml pushes tag vX.Y.Z -> release.yml builds ZIP + sha256, compares it with the
         tested PR prerelease (scripts/zip-diff.sh; any difference aborts), publishes the Release,
         then deletes the prerelease
sites    the plugin's own updater reads the GitHub Release and offers the update in wp-admin
```

Failures (page layout change, agent output rejected, expired token) open or update an
issue titled "Upstream sync failed". A red PR simply stays open and blocks the next sync.

## Versioning

`<upstream S3 version>.<fork patch>`: `1.59.0` means "reviewed through ServMask S3 Client 1.59".
Fork-only changes and base-plugin reviews increment the last number. `scripts/version.mjs`
is the only thing that edits the five version locations.

## Local commands

```
node scripts/upstream.mjs status          # what is new upstream
node scripts/upstream.mjs changelog --since 1.51
node scripts/version.mjs show|check|bump|notes
tests/run.sh                              # full integration suite (needs Docker)
PHP_VERSION=7.4 AI1WM_VERSION=7.84 tests/run.sh 01-compat
```

What the integration suite covers (`tests/cases/`): base-plugin compatibility gate, backup to S3 and restore,
settings authorization, the update channel, upgrade from the last published release (settings, old backups),
incremental backups, scheduled export, the S3 browser endpoint, retention. Not covered: restoring incremental
backups (UI-only flow), real AWS/other providers (the test server is SeaweedFS), the browser UI.

Prereleases are invisible to the plugin's updater and to GitHub's "latest release", so no site is offered a test
build. A PR closed without merging has its prerelease deleted at once (`prerelease-cleanup.yml`); a merged PR that changes `VERSION` keeps it until `release.yml` has verified the release against it, then deletes it. Fork PRs skip the prerelease and use the
workflow artifact instead.

`tests/run.sh` builds the ZIP with `scripts/build-zip.sh` unless `PLUGIN_ZIP` is set, fetches the last release
for the upgrade case (or uses `PREVIOUS_ZIP`), downloads WP-CLI and the base plugin on the host (the containers need no
internet) and copies the plugin into the container rather than bind-mounting it.

## One-time setup

Secrets (Settings > Secrets and variables > Actions):

| Secret | What |
|---|---|
| `DEEPSEEK_API_KEY` | DeepSeek platform API key |
| `BOT_TOKEN` | Fine-grained PAT for this repo: Contents, Pull requests, Workflows = read/write. Expires within a year: renew it |

Then run `docs/setup-repo.sh` once to enable auto-merge and require the **Required checks** status on `main`.

## Offline references for the agent

The agent has no internet. Each run it gets, read-only: the changelog entries, the current base plugin source,
and WordPress core sources for 5.9 (the supported floor), 6.2, 6.5 and the current release
(`scripts/fetch-reference-sources.sh`). The prompt requires it to verify that any WordPress API it uses exists
in both the oldest and newest copy. To change the set, edit the default list in that script.

## Safety model

The ZIP runs as admin PHP on every site that updates it. The agent therefore gets no network,
no git, no `gh`, and no repo token. `scripts/guard-diff.mjs` (run from a pristine copy of `main`) rejects a change
that touches `.github/`, `scripts/`, existing tests, the update channel, adds dangerous calls
or new hosts. Auto-merge still requires the full test matrix to pass.
