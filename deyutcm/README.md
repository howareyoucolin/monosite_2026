# deyutcm — local WordPress

`wp-content/themes/` is the only part of the site committed here. WordPress core
comes from the Docker image, and the database is a local copy pulled from prod.

## First run

    npm run pull:db       # copy the prod database into the local one
    npm run pull:files    # fetch uploads/ and plugins/ from prod
    npm run dev           # start the site

Then open http://localhost:8300 (admin at `/wp-admin`, using the real site's
logins, since the users table came from prod).

### Getting `pull:db` access to prod

The prod DB user's grant rejects connections from arbitrary IPs, so the pull
needs one of two things. The script supports both and tells you which is missing:

1. **Direct** — allow this machine's IP on the database user in the hosting
   panel, then leave `PROD_SSH` empty in `.env`. The dump then runs from here
   through a throwaway `mariadb` container; no SSH, no password prompt.
2. **Over SSH** — set `PROD_SSH=user@host` in `.env`. This needs *real shell
   access* on that account. An SFTP-only account authenticates fine but cannot
   run commands, which looks like a silent hang; the script now detects that and
   says so. `ssh-copy-id` on that host avoids retyping the password.

## Scripts

| script | what it does |
| --- | --- |
| `npm run dev` | build + start, logs in foreground |
| `npm run start` | same, detached |
| `npm run stop` | stop; local database survives |
| `npm run logs` | tail the WordPress container |
| `npm run wp -- <args>` | run any wp-cli command |
| `npm run pull:db` | fresh dump from prod, then import |
| `npm run pull:db <file.gz>` | re-import an earlier dump from `backups/` |
| `npm run pull:files` | fetch `uploads/` and `plugins/` from prod |
| `npm run pull:uploads` | just `uploads/` |
| `npm run pull:plugins` | just `plugins/` |
| `npm run reset` | drop the local database and WP core volumes |

## The database

Local MariaDB, published on **3310** (3307–3309 are already used by other
local containers), so a GUI client can connect:

    host 127.0.0.1   port 3310   user deyutcm   password deyutcm   db deyutcm

Since it is local, edit posts, activate plugins, and change settings freely —
nothing reaches the live site. `pull:db` only ever *reads* prod, with
`--skip-lock-tables` so the live site is never locked by the dump. Dumps are kept
in `backups/` (gitignored) so you can roll back without hitting prod again.

The imported database holds the live domain in `siteurl`/`home`. `WP_HOME` and
`WP_SITEURL` are set as constants in `docker-compose.yml`, which overrides that
so the copy serves on localhost — no search-replace needed, and nothing is
written back.

## wp-cli

    npm run wp -- plugin list
    npm run wp -- option get blogname

Note the `--`, which passes the arguments through to wp-cli.

## What is committed

Only `wp-content/themes/` and the `index.php` guards. Plugins, uploads, and
`upgrade/` are gitignored — they stay on disk and are still mounted, but are not
tracked.

One consequence: a fresh clone has no plugin files while an imported database
still lists them as active, so opening wp-admin → Plugins makes WordPress
deactivate them. Harmless locally (it only drifts the local copy) — run
`npm run pull:files` before touching that screen.

## Pulling files from prod

    npm run pull:files              uploads and plugins
    npm run pull:uploads            just uploads
    npm run pull:plugins            just plugins
    ./pull-files.sh themes          themes (overwrites tracked files — careful)
    ./pull-files.sh --delete ...    also remove local files that are gone on prod

Read-only on the prod side, over SSH. It uses `rsync` when the remote has it and
falls back to `tar` over the SSH pipe when it does not, so repeat pulls only move
what changed. The remote site root is `PROD_WP_DIR` in `.env`; leave it empty and
the script looks for `~/*/wp-content` itself, reporting what it found.

## Layout

- `package.json` — the npm scripts above; no dependencies, nothing to install.
- `pull-db.sh` — what `pull:db` runs.
- `.env` — prod credentials, used only for dumping. Gitignored.
- `backups/` — timestamped dumps. Gitignored.
- `wp-content/` — bind-mounted into the container. Only `themes/` is tracked.
- `Dockerfile` — strips the themes/plugins bundled in the upstream image so the
  entrypoint cannot copy them into `wp-content/` on first boot.
- WordPress core and the database live in Docker volumes, not on disk here.

## Notes

The committed plugins are old (Yoast 7.9, Akismet 4.0.8) and emit PHP warnings on
PHP 8.2. Nothing fatal — the front end and admin both render — but that is why
`WORDPRESS_DEBUG` is on. Drop the PHP version in `Dockerfile` if the noise gets in
the way.
