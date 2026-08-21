# deyutcm — local WordPress

`wp-content/` is the only part of the site that is committed here. WordPress core
comes from the Docker image, so this folder stays a clean mirror of what gets
deployed.

## The database is production

This container talks to the **live production database** — there is no local one.
Browsing `localhost:8300` reads live data, and anything that writes (activating a
plugin, switching a theme, editing a post, changing a setting in wp-admin) writes
to the live site. Treat wp-admin here as if it were the real thing, and keep local
work to files under `wp-content/`. See `../CLAUDE.md` for the full list of
operations to avoid.

Credentials go in `.env` (gitignored; copy `.env.example`). Compose refuses to
start until they are set, rather than silently coming up broken. Reaching the DB
means either allowing remote MySQL for your IP on the host, or an SSH tunnel:

    ssh -N -L 3307:<db-host>:3306 fatfat@iad1-shared-b8-32.dreamhost.com

then setting `DEYUTCM_DB_HOST=host.docker.internal:3307`. The tunnel has to be up
before `npm run dev`.

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
| `npm run stop` | stop the container |
| `npm run logs` | tail the WordPress container |
| `npm run wp -- <args>` | run a wp-cli command (read-only — hits prod) |
| `npm run reset` | drop the local WordPress core volume (never the database) |

There is no install step — the prod database is already populated. Log in with
the real site's credentials.

## wp-cli

Any wp-cli command works through the `wpcli` service:

    npm run wp -- plugin list
    npm run wp -- option get blogname

Note the `--`, which is what passes the arguments through to wp-cli. Keep these
read-only: the target is the prod database.

## Stop / reset

    npm run stop     # stop the container
    npm run reset    # drop the local WordPress core volume only

Both wrap `docker compose`, so the raw commands still work if you prefer them:

    docker compose -f deyutcm/docker-compose.yml up -d --build

## Layout

- `package.json` — the npm scripts above; no dependencies, nothing to install.
- `wp-content/` — bind-mounted into the container; the site's real files.
- `.env` — prod DB credentials, gitignored.
- `db-data/` — leftover from the old local MariaDB, now unused. Safe to delete.
- `Dockerfile` — strips the themes/plugins bundled in the upstream image so the
  entrypoint cannot copy them into `wp-content/` on first boot.
- WordPress core lives in the `wp-core` Docker volume, not on disk here.

## Notes

The committed plugins are old (Yoast 7.9, Akismet 4.0.8) and emit PHP warnings on
PHP 8.2. Nothing fatal — the front end and admin both render — but that is why
`WORDPRESS_DEBUG` is on. Drop the PHP version in `Dockerfile` if the noise gets in
the way.
