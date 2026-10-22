#!/usr/bin/env bash
# Installs WordPress, the requested base plugin and this extension, then points
# the extension at the throwaway S3 server.
set -Eeuo pipefail
source /tests/lib.sh

until php -r 'exit(@mysqli_connect("db", "wp", "wp", "wp") ? 0 : 1);'; do sleep 2; done

# Hermetic and stable: WordPress may only talk to localhost and the S3 server, and
# must not update itself (an old image would otherwise jump to the newest core
# in the background and change what is being tested).
wp config set WP_HTTP_BLOCK_EXTERNAL true --raw
wp config set WP_ACCESSIBLE_HOSTS 'localhost,s3'
wp config set AUTOMATIC_UPDATER_DISABLED true --raw
wp config set WP_AUTO_UPDATE_CORE false --raw
wp config set DISABLE_WP_CRON true --raw

wp core install --url=http://localhost --title='ai1wmne tests' \
	--admin_user=admin --admin_password=admin --admin_email=admin@example.com --skip-email

wp plugin install /tmp/ai1wm.zip --activate
wp plugin activate "${PLUGIN_SLUG}"
wp plugin list --fields=name,status,version

wp option update ai1wmne_s3_api_endpoint "${S3_HOST}"
wp option update ai1wmne_s3_bucket_template "${S3_HOST}/%(bucket)s"
wp option update ai1wmne_s3_https_protocol 0
wp option update ai1wmne_s3_access_key "${S3_ACCESS_KEY}"
wp option update ai1wmne_s3_secret_key "${S3_SECRET_KEY}"
wp option update ai1wmne_s3_bucket_name "${S3_BUCKET}"
wp option update ai1wmne_s3_region_name us-east-1

s3_create_bucket; echo
