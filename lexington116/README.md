# Lexington116 — local WordPress

A fresh WordPress site with its own standalone `lexington116_2026` theme. The
home page displays one line: **HELLO WORLD**. No production access is needed.
WordPress core comes from Docker; the database and core use separate Docker
volumes belonging to this project.

## First run

```sh
cd lexington116
npm run setup
```

Setup starts WordPress, MariaDB, and phpMyAdmin, installs WordPress if needed,
and activates the custom theme. Running it again preserves existing content and
admin credentials and reactivates this theme. Docker must be running. There are
no npm dependencies to install.

- Site: http://localhost:8400
- WordPress admin: http://localhost:8400/wp-admin
- Default local login: `admin` / `admin`
- phpMyAdmin: http://localhost:8402 (automatically signs into the local database)
- Database: `127.0.0.1:8401`, database/user/password all `lexington116`

To choose local admin credentials, copy `.env.example` to `.env` and edit it
before the first setup. These defaults are for local use only. All published
ports bind to loopback. The new ports let this site run alongside `deyutcm`.

## Daily commands

| Command | Purpose |
| --- | --- |
| `npm run dev` | Build/start with logs in the foreground |
| `npm run start` | Build/start in the background |
| `npm run stop` | Stop containers, keeping the database |
| `npm run logs` | Follow WordPress logs |
| `npm run wp -- <args>` | Run WP-CLI against this local site |
| `npm run setup` | Install if needed and activate the custom theme |
| `npm run seed:local` | Seed local pages, homepage, and pretty permalinks |
| `npm run seed:prod` | Seed production over SSH using `deploy.config.json` |
| `npm run deploy:dry` | Preview theme deployment once configured |
| `npm run deploy` | Preview changes and ask before uploading themes |
| `npm run reset` | Delete local database/core volumes; run setup to reinstall |

## Theme

Edit `wp-content/themes/lexington116_2026/`. This is an independent theme, with
no dependency on `deyutcm` or its templates. `index.php` renders the placeholder,
`functions.php` registers WordPress support and loads `style.css`.
WordPress hooks remain available for future plugins and theme development.

Only themes and index guards are tracked under `wp-content`. Plugins, uploads,
update scratch files, `.env`, and backups are ignored.

## Deployment when hosting details are available

`deploy.sh` follows `deyutcm`'s theme-only deployment workflow. It uploads
`wp-content/themes/` over SSH/rsync (with a tar fallback). It does not upload
WordPress core, a database, plugins, uploads, or local credentials. Hosting must
already have WordPress installed; activate **Lexington116 2026** in wp-admin
after its first upload. Theme activation is not part of deployment.

Add the following entry inside `sites` in the repository-root
`deploy.config.json` (gitignored). If that file does not exist, start from
`../deploy.config.example.json`. Preserve entries for existing sites.

```json
"lexington116": {
  "sourceDir": "wp-content/themes",
  "transport": "rsync",
  "user": "",
  "host": "",
  "remoteDir": "",
  "port": 22
}
```

Fill in the SSH user, host, and remote WordPress site directory. `remoteDir`
accepts the site root, `wp-content`, or `wp-content/themes`. Optionally add
`"sshKey": "~/.ssh/your_key"`. Shell access is required.

```sh
npm run deploy:dry
npm run deploy
```

Missing connection settings stop the script before it connects. Remote-only
files are kept unless `--delete` is explicitly passed. Use `./deploy.sh --help`
for all options, including `--config` to select a different configuration file.
No deployment target is committed to the repository; local configuration stays
in the gitignored `../deploy.config.json`.

## Site seeder

The idempotent seeder activates the Lexington theme, creates or republishes Home,
About Lumi, Massage Menu, Contact Us, Book an Appointment, and the legacy Visit
Us page, sets Home as the static front page, and changes permalinks to
`/%postname%/`. Existing page content is never overwritten, and rerunning the
command does not duplicate pages.

```sh
npm run seed:local
npm run seed:prod
```

Standard argument forms are also available as `npm run seed -- --local` and
`npm run seed -- --prod`. A bare `npm run seed` prints these choices and exits
without changing either site. The production command uses the Lexington entry in
`../deploy.config.json`, connects over SSH, and requires the deployed theme plus
WP-CLI on the server.
