# monosites_2026

A collection of independent small sites, one folder each. Each site owns its own
tooling; there is no shared build. `scripts/mirror_site.py` and the per-site
`deploy.sh` handle deployment (config in `deploy.config.json`, gitignored).

- `usaxy/` — static PHP site, served by `php:8.2-apache` on port 8600.
- `deyutcm/` — WordPress, served on port 8300. Only `wp-content/` is committed;
  core comes from the Docker image.

## deyutcm connects to the PRODUCTION database

The local WordPress container at `localhost:8300` is wired to the **live
production database**. There is no local database. This is deliberate.

The consequence: local is not a sandbox. Anything that writes to the DB while
you are on `localhost:8300` writes to the live site. That includes actions that
feel local and harmless:

- activating or deactivating a plugin or switching themes (`active_plugins`,
  `stylesheet` are DB options)
- editing posts, pages, menus, widgets, or users
- changing any setting in wp-admin
- plugin setup wizards and first-run routines that seed their own options

So, when working in this repo:

- **Never run write-mode wp-cli against it.** No `wp option update`, `wp
  search-replace`, `wp plugin activate/deactivate`, `wp theme activate`, `wp db
  import`, `wp db reset`, `wp post`/`term`/`user` mutations. Read-only commands
  (`wp plugin list`, `wp option get`, `wp db export`) are fine.
- **Never touch `home` or `siteurl`.** `WP_HOME`/`WP_SITEURL` are set as
  constants in `docker-compose.yml`, which is what keeps the container serving on
  localhost instead of redirecting to the live domain. They override the DB
  without writing to it. Rewriting those options would change the live site's
  URLs.
- `npm run reset` only drops the local WordPress core volume. It does not and
  must not touch the database.
- Treat `wp-content/` edits (theme/plugin code) as the safe way to work locally,
  since those are files, not database rows.

Credentials live in `deyutcm/.env` (gitignored, template in `.env.example`).
Reaching the DB usually means either allowing remote MySQL for your IP on the
host, or an SSH tunnel plus `DEYUTCM_DB_HOST=host.docker.internal:<port>`.

Ask before any operation that writes to the deyutcm database.
