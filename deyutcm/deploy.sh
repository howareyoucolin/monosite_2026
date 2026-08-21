#!/usr/bin/env bash
# Pushes wp-content/themes/ up to prod. Nothing else is ever sent: not plugins,
# not uploads, not the database.
#
# The push is incremental — rsync compares against prod and moves only what
# changed. It prints that list and waits for a yes before writing anything.
#
# Target settings come from ../deploy.config.json under the "deyutcm" site key;
# any of them can be overridden with the flags below.
#
# Usage:
#   ./deploy.sh                  push every theme in wp-content/themes/
#   ./deploy.sh mytheme          push just that theme
#   ./deploy.sh a b              push several themes
#   ./deploy.sh --dry-run        show what would change, then stop
#   ./deploy.sh --delete         also remove theme files that are gone locally
#   ./deploy.sh --yes            skip the confirmation prompt
#
# Config flags: --config FILE, --site NAME, --source DIR, --user, --host,
#               --remote-dir, --port, --identity-file
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd -- "$SCRIPT_DIR/.." && pwd)"
SITE_NAME="$(basename "$SCRIPT_DIR")"
CONFIG_FILE="${DEPLOY_CONFIG:-$REPO_ROOT/deploy.config.json}"

usage() {
	awk 'NR > 1 && /^#/ { sub(/^# ?/, ""); print; next } NR > 1 { exit }' "${BASH_SOURCE[0]}"
}

require_command() {
	if ! command -v "$1" >/dev/null 2>&1; then
		printf 'Error: required command not found: %s\n' "$1" >&2
		exit 1
	fi
}

expand_home() {
	local path="$1"
	if [[ "$path" == "~/"* ]]; then
		printf '%s\n' "$HOME/${path#~/}"
	else
		printf '%s\n' "$path"
	fi
}

# Same reader usaxy/deploy.sh uses, so one config file describes both sites.
load_site_config() {
	local config_file="$1" site_name="$2" config_lines key value

	require_command python3

	if ! config_lines="$(python3 - "$config_file" "$site_name" <<'PY'
import json
import sys
from pathlib import Path

config_path = Path(sys.argv[1])
site_name = sys.argv[2]

try:
    with config_path.open("r", encoding="utf-8") as handle:
        data = json.load(handle)
except FileNotFoundError:
    sys.exit(0)
except json.JSONDecodeError as exc:
    print(f"Error: invalid JSON in {config_path}: {exc}", file=sys.stderr)
    sys.exit(1)

sites = data.get("sites")
if not isinstance(sites, dict):
    print(f'Error: {config_path} must contain a top-level "sites" object.', file=sys.stderr)
    sys.exit(1)

site_config = sites.get(site_name)
if site_config is None:
    available = ", ".join(sorted(sites)) or "(none)"
    print(
        f'Error: site "{site_name}" was not found in {config_path}. '
        f"Available sites: {available}",
        file=sys.stderr,
    )
    sys.exit(1)
if not isinstance(site_config, dict):
    print(f'Error: site "{site_name}" in {config_path} must be a JSON object.', file=sys.stderr)
    sys.exit(1)

for key in ("sourceDir", "transport", "user", "host", "remoteDir", "port", "sshKey"):
    value = site_config.get(key, "")
    print(f"{key}\t{'' if value is None else value}")
PY
	)"; then
		exit 1
	fi

	while IFS=$'\t' read -r key value; do
		case "$key" in
		sourceDir) CONFIG_SOURCE_DIR="$value" ;;
		transport) CONFIG_TRANSPORT="$value" ;;
		user) CONFIG_REMOTE_USER="$value" ;;
		host) CONFIG_REMOTE_HOST="$value" ;;
		remoteDir) CONFIG_REMOTE_DIR="$value" ;;
		port) CONFIG_SSH_PORT="$value" ;;
		sshKey) CONFIG_SSH_KEY="$value" ;;
		esac
	done <<<"$config_lines"
}

# --config and --site decide which config to read, so they are scanned first.
# --help goes here too: asking for usage should not need a config file.
ARGS=("$@")
for ((i = 0; i < ${#ARGS[@]}; i++)); do
	case "${ARGS[$i]}" in
	-h | --help)
		usage
		exit 0
		;;
	--config)
		i=$((i + 1))
		[[ $i -lt ${#ARGS[@]} ]] || { printf 'Error: --config requires a value.\n' >&2; exit 1; }
		CONFIG_FILE="${ARGS[$i]}"
		;;
	--config=*) CONFIG_FILE="${ARGS[$i]#*=}" ;;
	--site)
		i=$((i + 1))
		[[ $i -lt ${#ARGS[@]} ]] || { printf 'Error: --site requires a value.\n' >&2; exit 1; }
		SITE_NAME="${ARGS[$i]}"
		;;
	--site=*) SITE_NAME="${ARGS[$i]#*=}" ;;
	esac
done

CONFIG_FILE="$(expand_home "$CONFIG_FILE")"

CONFIG_SOURCE_DIR=""
CONFIG_TRANSPORT=""
CONFIG_REMOTE_USER=""
CONFIG_REMOTE_HOST=""
CONFIG_REMOTE_DIR=""
CONFIG_SSH_PORT=""
CONFIG_SSH_KEY=""

if [[ -f "$CONFIG_FILE" ]]; then
	load_site_config "$CONFIG_FILE" "$SITE_NAME"
fi

SOURCE_DIR="${CONFIG_SOURCE_DIR:-wp-content/themes}"
TRANSPORT="${CONFIG_TRANSPORT:-rsync}"
REMOTE_USER="${CONFIG_REMOTE_USER:-}"
REMOTE_HOST="${CONFIG_REMOTE_HOST:-}"
REMOTE_DIR="${CONFIG_REMOTE_DIR:-}"
SSH_PORT="${CONFIG_SSH_PORT:-22}"
SSH_KEY="${CONFIG_SSH_KEY:-}"
DELETE_REMOTE=0
DRY_RUN=0
ASSUME_YES=0
THEMES=()

while [[ $# -gt 0 ]]; do
	case "$1" in
	--config | --site)
		shift 2
		;;
	--config=* | --site=*)
		shift
		;;
	--source | --user | --host | --remote-dir | --port | --identity-file)
		[[ $# -ge 2 ]] || { printf 'Error: %s requires a value.\n' "$1" >&2; exit 1; }
		case "$1" in
		--source) SOURCE_DIR="$2" ;;
		--user) REMOTE_USER="$2" ;;
		--host) REMOTE_HOST="$2" ;;
		--remote-dir) REMOTE_DIR="$2" ;;
		--port) SSH_PORT="$2" ;;
		--identity-file) SSH_KEY="$2" ;;
		esac
		shift 2
		;;
	--source=*) SOURCE_DIR="${1#*=}"; shift ;;
	--user=*) REMOTE_USER="${1#*=}"; shift ;;
	--host=*) REMOTE_HOST="${1#*=}"; shift ;;
	--remote-dir=*) REMOTE_DIR="${1#*=}"; shift ;;
	--port=*) SSH_PORT="${1#*=}"; shift ;;
	--identity-file=*) SSH_KEY="${1#*=}"; shift ;;
	--transport) TRANSPORT="$2"; shift 2 ;;
	--transport=*) TRANSPORT="${1#*=}"; shift ;;
	--delete) DELETE_REMOTE=1; shift ;;
	-n | --dry-run) DRY_RUN=1; shift ;;
	-y | --yes) ASSUME_YES=1; shift ;;
	-h | --help) usage; exit 0 ;;
	-*)
		printf 'Error: unknown option: %s\n\n' "$1" >&2
		usage >&2
		exit 1
		;;
	*) THEMES+=("$1"); shift ;;
	esac
done

SSH_KEY="$(expand_home "$SSH_KEY")"
TRANSPORT="$(printf '%s' "$TRANSPORT" | tr '[:upper:]' '[:lower:]')"
[[ "$SOURCE_DIR" == /* ]] || SOURCE_DIR="$SCRIPT_DIR/$SOURCE_DIR"

case "$TRANSPORT" in
rsync) ;;
tar)
	# Forcing tar is for hosts without a remote rsync; it cannot diff or delete.
	;;
*)
	printf 'Error: unsupported transport for this site: %s. Use "rsync" or "tar".\n' "$TRANSPORT" >&2
	printf 'A themes push needs shell access on the remote host; scp cannot diff.\n' >&2
	exit 1
	;;
esac

require_command ssh
[[ "$TRANSPORT" == "tar" ]] || require_command rsync

if [[ ! -d "$SOURCE_DIR" ]]; then
	printf 'Error: source directory not found: %s\n' "$SOURCE_DIR" >&2
	exit 1
fi

if [[ -n "$SSH_KEY" && ! -f "$SSH_KEY" ]]; then
	printf 'Error: SSH key not found: %s\n' "$SSH_KEY" >&2
	exit 1
fi

MISSING_SETTINGS=()
[[ -n "$REMOTE_USER" ]] || MISSING_SETTINGS+=("user/--user")
[[ -n "$REMOTE_HOST" ]] || MISSING_SETTINGS+=("host/--host")
[[ -n "$REMOTE_DIR" ]] || MISSING_SETTINGS+=("remoteDir/--remote-dir")

if [[ ${#MISSING_SETTINGS[@]} -gt 0 ]]; then
	printf 'Error: missing required setting(s): %s\n' "${MISSING_SETTINGS[*]}" >&2
	printf 'Create %s from %s and add a "%s" site entry, or pass the values with CLI flags.\n' \
		"$REPO_ROOT/deploy.config.json" \
		"$REPO_ROOT/deploy.config.example.json" \
		"$SITE_NAME" >&2
	exit 1
fi

REMOTE_DIR="${REMOTE_DIR%/}"

# A typo'd theme name would otherwise look like an empty push.
if [[ ${#THEMES[@]} -gt 0 ]]; then
	for t in "${THEMES[@]}"; do
		if [[ ! -d "$SOURCE_DIR/$t" ]]; then
			printf 'Error: no such local theme: %s\n' "$t" >&2
			exit 1
		fi
	done
else
	while IFS= read -r theme_dir; do
		THEMES+=("$(basename "$theme_dir")")
	done < <(find "$SOURCE_DIR" -mindepth 1 -maxdepth 1 -type d ! -name '.*' | sort)
fi

if [[ ${#THEMES[@]} -eq 0 ]]; then
	printf 'Error: no themes found in %s\n' "$SOURCE_DIR" >&2
	exit 1
fi

confirm() {
	[[ $ASSUME_YES -eq 1 ]] && return 0
	if [[ ! -r /dev/tty ]]; then
		printf 'Error: not a terminal — rerun with --yes to push.\n' >&2
		exit 1
	fi
	local reply
	printf '%s [y/N] ' "$1" >/dev/tty
	read -r reply </dev/tty
	case "$reply" in
	y | Y | yes | YES) return 0 ;;
	*)
		printf 'Aborted; prod untouched.\n'
		exit 0
		;;
	esac
}

AUTH_OPTIONS=(-o ConnectTimeout=20)
if [[ -n "$SSH_KEY" ]]; then
	AUTH_OPTIONS+=(-i "$SSH_KEY")
fi

printf 'Deploying themes for site "%s"\n' "$SITE_NAME"
printf 'Source: %s/\n' "$SOURCE_DIR"
printf 'Themes: %s\n' "${THEMES[*]}"
printf 'Destination: %s@%s:%s\n' "$REMOTE_USER" "$REMOTE_HOST" "$REMOTE_DIR"
if [[ $DELETE_REMOTE -eq 0 ]]; then
	printf 'Remote-only files will be kept. Pass --delete to remove them.\n'
fi

# One control connection for every ssh and rsync call below, so a password host
# prompts once instead of per theme.
SOCK="/tmp/${SITE_NAME}-deploy-$$.sock"
cleanup() { ssh -S "$SOCK" -O exit "$REMOTE_USER@$REMOTE_HOST" 2>/dev/null || true; }
trap cleanup EXIT INT TERM

printf '==> connecting to %s\n' "$REMOTE_HOST"
if [[ -z "$SSH_KEY" ]]; then
	printf '    (password may be prompted; ssh-copy-id on that host to skip it)\n'
fi
ssh -M -S "$SOCK" -N -f -p "$SSH_PORT" "${AUTH_OPTIONS[@]}" "$REMOTE_USER@$REMOTE_HOST"

rsh() { ssh -S "$SOCK" "$REMOTE_USER@$REMOTE_HOST" "$@"; }

# An SFTP-only account authenticates but runs nothing, which otherwise looks
# like a silent hang.
probe="$(rsh 'echo SHELL_OK' 2>&1)" || true
if [[ "$probe" != "SHELL_OK" ]]; then
	printf '    remote command produced: %s\n' "${probe:-<nothing>}" >&2
	printf 'Error: no shell access on that account — enable it in the hosting panel.\n' >&2
	exit 1
fi

# remoteDir may point at the site root, at wp-content, or at themes itself.
REMOTE_THEMES="$REMOTE_DIR"
if rsh "test -d '$REMOTE_THEMES/wp-content/themes'"; then
	REMOTE_THEMES="$REMOTE_THEMES/wp-content/themes"
elif rsh "test -d '$REMOTE_THEMES/themes'"; then
	REMOTE_THEMES="$REMOTE_THEMES/themes"
elif ! rsh "test -d '$REMOTE_THEMES'"; then
	printf 'Error: remote directory not found: %s\n' "$REMOTE_DIR" >&2
	printf 'Point remoteDir at the site root, its wp-content, or wp-content/themes.\n' >&2
	exit 1
fi
printf '    remote themes: %s\n' "$REMOTE_THEMES"

# A theme prod has never seen is a new install, not an edit. Say so up front.
for t in "${THEMES[@]}"; do
	rsh "test -d '$REMOTE_THEMES/$t'" || printf '    new on prod: %s\n' "$t"
done

if [[ "$TRANSPORT" == "rsync" ]] && ! rsh 'command -v rsync >/dev/null'; then
	printf '    rsync not available remotely — falling back to tar over ssh\n'
	TRANSPORT=tar
fi

if [[ "$TRANSPORT" == "tar" ]]; then
	if [[ $DELETE_REMOTE -eq 1 ]]; then
		printf 'Error: --delete needs rsync on the remote host.\n' >&2
		exit 1
	fi
	FILE_COUNT=0
	for t in "${THEMES[@]}"; do
		FILE_COUNT=$((FILE_COUNT + $(find "$SOURCE_DIR/$t" -type f | wc -l | tr -d ' ')))
	done
	printf '    tar cannot diff: all %s files get overwritten on prod\n' "$FILE_COUNT"
	if [[ $DRY_RUN -eq 1 ]]; then
		printf 'Dry run; nothing sent.\n'
		exit 0
	fi
	confirm "Overwrite those files on prod?"
	tar czf - -C "$SOURCE_DIR" --exclude=.DS_Store --exclude=.git "${THEMES[@]}" |
		rsh "mkdir -p -- '$REMOTE_THEMES' && cd '$REMOTE_THEMES' && tar xzf -"
	printf 'Deploy complete. Only themes were touched.\n'
	exit 0
fi

# --no-owner/--no-group: shared hosting rejects chown, and one failed chown
# would fail the whole transfer.
RSYNC_BASE=(
	-rlptD
	--no-owner
	--no-group
	--human-readable
	--exclude=.DS_Store
	--exclude=.git
	--exclude=node_modules
	-e "ssh -S $SOCK"
)
if [[ $DELETE_REMOTE -eq 1 ]]; then
	RSYNC_BASE+=(--delete)
fi

printf '==> checking what differs from prod\n'
CHANGES=""
for t in "${THEMES[@]}"; do
	# The itemize flags come first on each line; splice the theme name into the
	# path that follows so one flat list reads across several themes.
	# ^\.d drops directories whose only difference is a timestamp — real noise
	# in the list, and rsync fixes them either way.
	out="$(rsync "${RSYNC_BASE[@]}" --dry-run --itemize-changes \
		"$SOURCE_DIR/$t/" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_THEMES/$t/" |
		sed -e '/^\.d/d' -e "s#^\([^ ]*\) #  \1 $t/#")"
	if [[ -n "$out" ]]; then
		CHANGES+="$out"$'\n'
	fi
done

CHANGES="$(printf '%s' "$CHANGES" | sed '/^[[:space:]]*$/d')"
if [[ -z "$CHANGES" ]]; then
	printf '    prod already matches these themes; nothing to push.\n'
	exit 0
fi

printf '%s\n' "$CHANGES"
printf '    %s item(s) to change\n' "$(printf '%s\n' "$CHANGES" | wc -l | tr -d ' ')"
if [[ $DELETE_REMOTE -eq 1 ]]; then
	printf '    --delete is on: lines starting with *deleting are removed from prod\n'
fi

if [[ $DRY_RUN -eq 1 ]]; then
	printf 'Dry run; nothing sent.\n'
	exit 0
fi

confirm "Push these changes to prod?"

for t in "${THEMES[@]}"; do
	printf '==> uploading %s\n' "$t"
	# --progress, not --info=progress2: macOS ships openrsync, which does not
	# know the newer flag.
	rsync "${RSYNC_BASE[@]}" --progress \
		"$SOURCE_DIR/$t/" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_THEMES/$t/"
done

printf '\nDeploy complete. Only wp-content/themes/ was touched.\n'
