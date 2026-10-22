#!/usr/bin/env bash
# Exit 0 only if two plugin ZIPs contain exactly the same files with the same
# contents and permissions. Timestamps and compression are ignored.
#
#   scripts/zip-diff.sh <a.zip> <b.zip>
set -Eeuo pipefail

a="${1:?usage: zip-diff.sh <a.zip> <b.zip>}"
b="${2:?usage: zip-diff.sh <a.zip> <b.zip>}"

tmp="$(mktemp -d)"
trap 'rm -rf "${tmp}"' EXIT
mkdir "${tmp}/a" "${tmp}/b"
unzip -q "${a}" -d "${tmp}/a"
unzip -q "${b}" -d "${tmp}/b"

manifest() { (cd "$1" && find . -type f -printf '%m %p\n' | sort -k2); }

if ! diff <(manifest "${tmp}/a") <(manifest "${tmp}/b") >&2; then
	echo 'ZIPs differ in file list or permissions' >&2
	exit 1
fi
if ! diff -r "${tmp}/a" "${tmp}/b" >&2; then
	echo 'ZIPs differ in file contents' >&2
	exit 1
fi
echo 'ZIPs are identical'
