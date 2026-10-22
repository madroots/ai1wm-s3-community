#!/usr/bin/env bash
# Download WordPress core sources for the sync agent to study offline: the
# floor we support, a few older majors, and the current release. Binary and
# minified assets are dropped so the agent can grep the PHP quickly.
#
#   scripts/fetch-reference-sources.sh <dest-dir> [<version> ...]
#
# Without versions the default set is used. "latest" is resolved from api.wordpress.org.
set -Eeuo pipefail

dest="${1:?usage: fetch-reference-sources.sh <dest-dir> [<version> ...]}"
shift
versions=("$@")
if [[ ${#versions[@]} -eq 0 ]]; then
	versions=(5.9 6.2 6.5 latest)
fi

mkdir -p "${dest}"
tmp="$(mktemp -d)"
trap 'rm -rf "${tmp}"' EXIT

for version in "${versions[@]}"; do
	if [[ "${version}" == latest ]]; then
		url="$(curl -fsSL https://api.wordpress.org/core/version-check/1.7/ | node -p 'JSON.parse(require("fs").readFileSync(0, "utf8")).offers[0].download')"
		label="$(basename "${url}" .zip)"
		label="${label#wordpress-}"
	else
		url="https://wordpress.org/wordpress-${version}.zip"
		label="${version}"
	fi

	target="${dest}/wordpress-${label}"
	[[ -d "${target}" ]] && continue

	curl -fsSL -o "${tmp}/wp.zip" "${url}"
	unzip -q "${tmp}/wp.zip" -d "${tmp}/x"
	rm -rf "${tmp}/x/wordpress/wp-content"
	find "${tmp}/x/wordpress" -type f \( \
		-name '*.png' -o -name '*.jpg' -o -name '*.jpeg' -o -name '*.gif' -o -name '*.svg' -o -name '*.ico' \
		-o -name '*.woff' -o -name '*.woff2' -o -name '*.ttf' -o -name '*.eot' -o -name '*.min.js' -o -name '*.min.css' \
		-o -name '*.mo' -o -name '*.map' \) -delete
	rm -rf "${tmp}/x/wordpress/wp-includes/js" "${tmp}/x/wordpress/wp-includes/css" "${tmp}/x/wordpress/wp-includes/fonts" "${tmp}/x/wordpress/wp-includes/images"
	mv "${tmp}/x/wordpress" "${target}"
	rm -rf "${tmp}/x" "${tmp}/wp.zip"
	printf 'fetched %s -> %s\n' "${label}" "${target}" >&2
done
