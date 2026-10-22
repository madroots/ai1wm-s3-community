#!/usr/bin/env bash
# Run the integration suite.
#   tests/run.sh                         # PHP 8.3, latest base plugin
#   PHP_VERSION=7.4 AI1WM_VERSION=7.84 tests/run.sh
#   tests/run.sh 02-backup-restore       # a single case
set -Eeuo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"
export PHP_VERSION="${PHP_VERSION:-8.3}"
export AI1WM_VERSION="${AI1WM_VERSION:-latest}"

compose() { docker compose "$@"; }
in_wp() { compose exec -T -u www-data wp "$@"; }

cleanup() {
	status=$?
	if [[ $status -ne 0 ]]; then
		compose logs --tail=60 wp s3 >&2 || true
	fi
	if [[ -z "${KEEP:-}" ]]; then
		compose down -v --remove-orphans >/dev/null 2>&1 || true
	fi
	exit "$status"
}
trap cleanup EXIT

# Downloads happen on the host and are mounted into the containers, which keeps
# the containers hermetic (they never need outbound network access).
mkdir -p .cache
if [[ ! -s .cache/wp-cli.phar ]]; then
	curl -fsSL -o .cache/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
fi
if [[ "${AI1WM_VERSION}" == latest ]]; then
	curl -fsSL -o .cache/ai1wm.zip https://downloads.wordpress.org/plugin/all-in-one-wp-migration.latest-stable.zip
else
	curl -fsSL -o .cache/ai1wm.zip "https://downloads.wordpress.org/plugin/all-in-one-wp-migration.${AI1WM_VERSION}.zip"
fi

printf '== PHP %s / base plugin %s ==\n' "${PHP_VERSION}" "${AI1WM_VERSION}"
compose up -d --build --wait --quiet-pull

# Files are copied in rather than bind-mounted: bind mounts are unreadable on
# SELinux hosts without relabeling the working tree.
slug=all-in-one-wp-migration-s3-client-extension
plugin_dir="/var/www/html/wp-content/plugins/${slug}"
compose exec -T wp mkdir -p "${plugin_dir}" /tests
tar -C .. --exclude=.git --exclude=.github --exclude=tests --exclude=scripts --exclude=upstream --exclude=docs -cf - . \
	| compose exec -T wp tar -xf - -C "${plugin_dir}" --no-same-owner
tar --exclude=.cache -cf - . | compose exec -T wp tar -xf - -C /tests --no-same-owner
compose cp .cache/ai1wm.zip wp:/tmp/ai1wm.zip
compose exec -T wp chown -R www-data:www-data /var/www/html/wp-content
in_wp bash /tests/bootstrap.sh

failed=0
if [[ $# -gt 0 ]]; then
	cases=("$@")
else
	mapfile -t cases < <(cd cases && ls *.sh | sed 's/\.sh$//')
fi

for name in "${cases[@]}"; do
	printf '\n-- %s\n' "${name}"
	if ! in_wp bash "/tests/cases/${name}.sh"; then
		printf 'FAILED: %s\n' "${name}" >&2
		failed=1
	fi
done

exit "${failed}"
