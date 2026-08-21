# deyutcm — local WordPress

`wp-content/` is the only part of the site that is committed here. WordPress core
and the database come from Docker, so this folder stays a clean mirror of what
gets deployed.

## Run

From this folder:

    npm run dev

Streams logs in the foreground; Ctrl-C stops it. Use `npm run start` to leave it
running in the background instead.

- Site: http://localhost:8300
- Admin: http://localhost:8300/wp-admin

| script | what it does |
| --- | --- |
| `npm run dev` | build + start, logs in foreground |
| `npm run start` | same, detached |
| `npm run stop` | stop; database survives |
| `npm run logs` | tail the WordPress container |
| `npm run wp -- <args>` | run any wp-cli command |
| `npm run reset` | drop core + database, back to a fresh install |

First boot lands on the install screen. Either fill it in through the browser or
run:

    npm run wp -- core install \
      --url=http://localhost:8300 --title=deyutcm \
      --admin_user=admin --admin_password=admin \
      --admin_email=dev@example.com --skip-email

## wp-cli

Any wp-cli command works through the `wpcli` service:

    npm run wp -- plugin list
    npm run wp -- theme activate twentytwentythree

Note the `--`, which is what passes the arguments through to wp-cli.

## Stop / reset

    npm run stop     # stop, keep the database
    npm run reset    # drop WordPress core and wipe the database

Both wrap `docker compose`, so the raw commands still work if you prefer them:

    docker compose -f deyutcm/docker-compose.yml up -d --build

## Layout

- `package.json` — the npm scripts above; no dependencies, nothing to install.
- `wp-content/` — bind-mounted into the container; the site's real files.
- `db-data/` — MariaDB data, gitignored. Delete it to start from a blank database.
- `Dockerfile` — strips the themes/plugins bundled in the upstream image so the
  entrypoint cannot copy them into `wp-content/` on first boot.
- WordPress core lives in the `wp-core` Docker volume, not on disk here.

## Notes

The committed plugins are old (Yoast 7.9, Akismet 4.0.8) and emit PHP warnings on
PHP 8.2. Nothing fatal — the front end and admin both render — but that is why
`WORDPRESS_DEBUG` is on. Drop the PHP version in `Dockerfile` if the noise gets in
the way.
