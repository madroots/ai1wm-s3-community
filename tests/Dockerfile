ARG PHP_VERSION=8.3
FROM wordpress:php${PHP_VERSION}-apache

# wp-cli.phar is downloaded on the host by run.sh so the image build needs no
# network access of its own.
COPY .cache/wp-cli.phar /usr/local/bin/wp
RUN chmod +x /usr/local/bin/wp \
 && printf 'memory_limit=512M\n' > /usr/local/etc/php/conf.d/zz-tests.ini
