# Shared helpers, sourced by bootstrap.sh and every case.
export WP_CLI_CACHE_DIR=/tmp/wpcli
export WP_PATH=/var/www/html
export S3_HOST=s3:8333
export S3_ACCESS_KEY=testaccess
export S3_SECRET_KEY=testsecret1234
export S3_BUCKET=ai1wmne-test
export PLUGIN_SLUG=all-in-one-wp-migration-s3-client-extension

wp() { command wp --path="${WP_PATH}" "$@"; }

# S3 helpers use the extension's own client (php curl), so the harness does not
# depend on the container's curl version. s3_php <php snippet using $s3>.
s3_php() {
	wp eval '
		$s3 = new Ai1wmne_S3_Client( get_option( "ai1wmne_s3_access_key" ), get_option( "ai1wmne_s3_secret_key" ), false );
		$s3->set_api_endpoint( get_option( "ai1wmne_s3_api_endpoint" ) );
		$s3->set_bucket_template( get_option( "ai1wmne_s3_bucket_template" ) );
		$bucket = get_option( "ai1wmne_s3_bucket_name" );
		$region = get_option( "ai1wmne_s3_region_name" );
		'"$1"
}

s3_create_bucket() { s3_php 'try { $s3->create_bucket( $bucket, $region ); echo "created"; } catch ( Exception $e ) { echo "exists: ", $e->getMessage(); }'; }

# Names of all objects in the test bucket, one per line.
s3_list() { s3_php 'foreach ( $s3->get_objects_by_bucket( $bucket, $region ) as $item ) { echo $item["name"], "\n"; }'; }

pass() { printf '  ok: %s\n' "$*"; }
fail() { printf '  FAIL: %s\n' "$*" >&2; exit 1; }

assert_eq() { # expected actual message
	[[ "$1" == "$2" ]] || fail "$3 (expected '$1', got '$2')"
	pass "$3"
}

assert_contains() { # haystack needle message
	[[ "$1" == *"$2"* ]] || fail "$3 ('$2' not found in: $1)"
	pass "$3"
}

# HTTP status of a request made with a given cookie jar: http_status <jar> <curl args...>
http_status() {
	local jar="$1"
	shift
	curl -sS -o /dev/null -w '%{http_code}' -b "${jar}" "$@"
}

# Log in over HTTP and write a cookie jar: wp_login <jar> <user> <password>
wp_login() {
	curl -sS -o /dev/null -c "$1" -b "$1" --data-urlencode "log=$2" --data-urlencode "pwd=$3" \
		-H 'Cookie: wordpress_test_cookie=WP%20Cookie%20check' http://localhost/wp-login.php
}
