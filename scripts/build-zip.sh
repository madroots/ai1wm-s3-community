#!/usr/bin/env bash
# Build the installable plugin ZIP and its checksum. Used by release.yml, the PR
# prerelease job, and tests/run.sh, so every consumer packages identically.
#
#   scripts/build-zip.sh <version> <output-dir>     # prints the ZIP path
set -Eeuo pipefail

version="${1:?usage: build-zip.sh <version> <output-dir>}"
out="${2:?usage: build-zip.sh <version> <output-dir>}"

slug='all-in-one-wp-migration-s3-client-extension'
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
mkdir -p "${out}"
out="$(cd "${out}" && pwd)"
asset="${slug}-${version}.zip"

stage="$(mktemp -d)"
trap 'rm -rf "${stage}"' EXIT

mkdir -p "${stage}/${slug}"
rsync -a \
	--exclude='.git/' --exclude='.github/' \
	--exclude='scripts/' --exclude='tests/' --exclude='upstream/' --exclude='docs/' \
	"${root}/" "${stage}/${slug}/"

rm -f "${out}/${asset}" "${out}/${asset}.sha256"
(cd "${stage}" && zip -qrX "${out}/${asset}" "${slug}")
unzip -tqq "${out}/${asset}"
(cd "${out}" && sha256sum "${asset}" > "${asset}.sha256")

printf '%s\n' "${out}/${asset}"
