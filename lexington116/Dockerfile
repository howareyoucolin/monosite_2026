FROM wordpress:php8.2-apache

# The entrypoint seeds /var/www/html from /usr/src/wordpress on first boot. Since
# wp-content is bind-mounted from the host, anything bundled under it would be
# copied into the source tree. Drop the bundled themes/plugins so the mount stays
# exactly what is committed for this site.
RUN rm -rf /usr/src/wordpress/wp-content/themes/* \
           /usr/src/wordpress/wp-content/plugins/*
