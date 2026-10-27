#!/usr/bin/env bash
# PR builds write to dist/; release builds write to the repository root.
set -Eeuo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
tmp="$(mktemp -d)"
trap 'rm -rf "${tmp}"' EXIT
mkdir -p "${tmp}/source/scripts" "${tmp}/source/dist"
cp "${root}/scripts/build-zip.sh" "${root}/scripts/zip-diff.sh" "${tmp}/source/scripts/"
printf 'plugin content\n' > "${tmp}/source/plugin.php"
printf 'stale build\n' > "${tmp}/source/dist/stale.txt"

(cd "${tmp}/source" && scripts/build-zip.sh 1.0.0 dist && scripts/build-zip.sh 1.0.0 .)
asset='all-in-one-wp-migration-s3-client-extension-1.0.0.zip'
"${tmp}/source/scripts/zip-diff.sh" "${tmp}/source/dist/${asset}" "${tmp}/source/${asset}"
if unzip -Z1 "${tmp}/source/dist/${asset}" | grep -q '^all-in-one-wp-migration-s3-client-extension/dist/'; then
	printf 'build output was included in the plugin ZIP\n' >&2
	exit 1
fi
