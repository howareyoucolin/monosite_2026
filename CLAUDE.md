# monosites_2026

A collection of independent small sites, one folder each. Each site owns its own
tooling; there is no shared build. `scripts/mirror_site.py` and the per-site
`deploy.sh` handle deployment (config in `deploy.config.json`, gitignored).

- `usaxy/` — static PHP site, served by `php:8.2-apache` on port 8600.
- `deyutcm/` — WordPress, served on port 8300. Only `wp-content/` is committed;
  core comes from the Docker image. The local MariaDB is published on 8301 and
  phpMyAdmin on 8302.

## deyutcm uses a local copy of the prod database

`localhost:8300` runs against a **local MariaDB**, not production. The local copy
is refreshed on demand:

    cd deyutcm && npm run pull:db

That dumps prod into `deyutcm/backups/` and replaces the local database with it.
Prod is only ever read — `--skip-lock-tables` keeps the live site unlocked.
`npm run pull:db <file.gz>` reloads an earlier dump without touching prod.

The prod DB grant rejects arbitrary IPs, so the dump runs one of two ways,
selected by whether `PROD_SSH` is set in `.env`: over SSH on a shell host that
the grant already allows (requires real shell access — an SFTP-only account
authenticates but silently runs nothing), or directly from this machine through a
throwaway `mariadb` container once the machine's IP is allowed in the hosting
panel.

Because the database is local, editing posts, activating plugins, and changing
settings are all safe. Write-mode wp-cli is fine here.

Two things to keep in mind:

- **Never point the running site at prod.** The connection details in
  `docker-compose.yml` are local-only by design. `.env` holds the prod
  credentials for dumping and nothing else.
- **Do not rewrite `home`/`siteurl`.** `WP_HOME`/`WP_SITEURL` are constants in
  `docker-compose.yml`, which is what makes an imported prod database serve on
  localhost. They override the DB values without writing to it, so no
  search-replace is needed after a pull.

The prod schema uses table prefix `wp_` and charset `utf8` (not `utf8mb4` — the
WordPress image's default would garble the site's existing Chinese content, so
`WORDPRESS_DB_CHARSET` is pinned in `docker-compose.yml`).

## deyutcm deploys themes only

`wp-content/themes/` holds two themes, both site code — the Dockerfile strips
the image's bundled themes, so nothing in there is ever core:

- `twentysixteen` — the site's own fork of Twenty Sixteen 1.1, with the custom
  `front-page.php`, `page-appointment.php`, and the rest of the Chinese page
  templates. **This is what prod serves.** Leave it alone unless a change is
  meant for the live site.
- `dr_cheung_2026` — a full copy of that fork with a reworked masthead. It adds
  `css/header.css`, `images/paper-script.png`, and a `README.md`, rewrites
  `header.php`, and changes only the theme-header comment in `style.css`; every
  other file is byte identical, so templates and content behave the same. Active
  on localhost:8300. The old dark contact bar is gone — address and phone now sit
  in the masthead itself, over the site's watermark script re-derived as real ink
  (the theme's README documents how to regenerate it).

Two consequences of the active theme living in the database:

- `npm run pull:db` overwrites the local copy with prod's, which still says
  `twentysixteen`. Re-run `npm run wp -- theme activate dr_cheung_2026` after a
  pull to get back to the new header locally.
- Deploying `dr_cheung_2026` uploads the files but does **not** switch prod to
  it — activation is a database setting, and the deploy never touches prod's
  database. Prod keeps serving `twentysixteen` until someone activates the new
  theme in wp-admin there.

`deyutcm/deploy.sh` (`npm run deploy`) pushes `wp-content/themes/` to prod over
rsync and nothing else — plugins, uploads, and the database never go up. It
prints the rsync diff and waits for a `y` before writing, so it is safe to run to
inspect; `npm run deploy:dry` stops after the diff. Remote-only files survive
unless `--delete` is passed. Target settings come from the repo-root
`deploy.config.json` under the `deyutcm` key, not from `.env` — `.env` stays
read-only prod credentials for the pull scripts.

`wp-content/plugins/` and `wp-content/uploads/` are gitignored, so a fresh clone
has no plugin files while an imported database still lists them in
`active_plugins`. Opening wp-admin → Plugins in that state makes WordPress
deactivate them (`validate_active_plugins()` → `deactivate_plugins()`). Harmless
locally, but it drifts the local DB from prod until the next pull. `npm run
pull:files` fetches both directories from prod (rsync over SSH, `tar` fallback),
so run it alongside `pull:db` when setting up.
