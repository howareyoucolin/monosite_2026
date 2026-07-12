#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
Usage: ./deploy.sh [options]

Deploy the current site to DreamHost.

By default, this script uses `scp`, which works well with DreamHost SFTP-style
access. You can switch to `rsync` with `--transport rsync` if full SSH shell
access is enabled for the account.

By default, this script loads the current site's settings from ../deploy.config.json
using the site directory name as the site key.

Options:
  --config FILE             Load config from FILE
                            (default: ../deploy.config.json)
  --site NAME               Site key in the JSON config
                            (default: current directory name)
  --source DIR              Local directory to sync (default: ./html)
  --transport NAME          Deploy transport: scp or rsync (default: scp)
  --user USER               DreamHost SSH username
  --host HOST               DreamHost SSH hostname
  --remote-dir DIR          Remote document root on DreamHost
  --port PORT               SSH port (default: 22)
  --identity-file FILE      Optional SSH private key to use
  --delete                  Remove remote files that no longer exist locally
                            (rsync transport only)
  --dry-run                 Preview changes without uploading anything
  --no-progress             Hide per-file upload progress output
  -h, --help                Show this help

JSON config shape:
{
  "sites": {
    "your-site-name": {
      "sourceDir": "html",
      "transport": "scp",
      "user": "ssh-user",
      "host": "yourserver.dreamhost.com",
      "remoteDir": "/home/ssh-user/example.com",
      "port": 22
    }
  }
}

Examples:
  ./deploy.sh
  ./deploy.sh --dry-run
  ./deploy.sh --delete
  ./deploy.sh --transport rsync --dry-run
  ./deploy.sh --site your-site-name
EOF
}

expand_home() {
  local path="$1"

  if [[ -z "$path" ]]; then
    printf '%s\n' "$path"
    return
  fi

  if [[ "$path" == "~/"* ]]; then
    printf '%s\n' "$HOME/${path#~/}"
    return
  fi

  printf '%s\n' "$path"
}

resolve_from_base() {
  local path="$1"
  local base_dir="$2"

  path="$(expand_home "$path")"
  if [[ -z "$path" ]]; then
    printf '%s\n' "$path"
    return
  fi

  if [[ "$path" == /* ]]; then
    printf '%s\n' "$path"
    return
  fi

  printf '%s\n' "$base_dir/$path"
}

require_command() {
  local name="$1"

  if ! command -v "$name" >/dev/null 2>&1; then
    printf 'Error: required command not found: %s\n' "$name" >&2
    exit 1
  fi
}

load_site_config() {
  local config_file="$1"
  local site_name="$2"
  local config_lines

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
if sites is None:
    print(f"Error: {config_path} must contain a top-level \"sites\" object.", file=sys.stderr)
    sys.exit(1)
if not isinstance(sites, dict):
    print(f"Error: \"sites\" in {config_path} must be a JSON object.", file=sys.stderr)
    sys.exit(1)

site_config = sites.get(site_name)
if site_config is None:
    available = ", ".join(sorted(sites)) or "(none)"
    print(
        f"Error: site \"{site_name}\" was not found in {config_path}. "
        f"Available sites: {available}",
        file=sys.stderr,
    )
    sys.exit(1)
if not isinstance(site_config, dict):
    print(
        f"Error: site \"{site_name}\" in {config_path} must be a JSON object.",
        file=sys.stderr,
    )
    sys.exit(1)

for key in ("sourceDir", "transport", "user", "host", "remoteDir", "port", "sshKey"):
    value = site_config.get(key, "")
    if value is None:
        value = ""
    print(f"{key}\t{value}")
PY
)"; then
    exit 1
  fi

  while IFS=$'\t' read -r key value; do
    case "$key" in
      sourceDir)
        CONFIG_SOURCE_DIR="$value"
        ;;
      transport)
        CONFIG_TRANSPORT="$value"
        ;;
      user)
        CONFIG_REMOTE_USER="$value"
        ;;
      host)
        CONFIG_REMOTE_HOST="$value"
        ;;
      remoteDir)
        CONFIG_REMOTE_DIR="$value"
        ;;
      port)
        CONFIG_SSH_PORT="$value"
        ;;
      sshKey)
        CONFIG_SSH_KEY="$value"
        ;;
    esac
  done <<<"$config_lines"
}

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd -- "$SCRIPT_DIR/.." && pwd)"
SITE_NAME="$(basename "$SCRIPT_DIR")"
CONFIG_FILE="${DREAMHOST_CONFIG:-$REPO_ROOT/deploy.config.json}"

ARGS=("$@")
INDEX=0
while [[ $INDEX -lt ${#ARGS[@]} ]]; do
  case "${ARGS[$INDEX]}" in
    --config)
      INDEX=$((INDEX + 1))
      if [[ $INDEX -ge ${#ARGS[@]} ]]; then
        printf 'Error: --config requires a value.\n' >&2
        exit 1
      fi
      CONFIG_FILE="${ARGS[$INDEX]}"
      ;;
    --config=*)
      CONFIG_FILE="${ARGS[$INDEX]#*=}"
      ;;
    --site)
      INDEX=$((INDEX + 1))
      if [[ $INDEX -ge ${#ARGS[@]} ]]; then
        printf 'Error: --site requires a value.\n' >&2
        exit 1
      fi
      SITE_NAME="${ARGS[$INDEX]}"
      ;;
    --site=*)
      SITE_NAME="${ARGS[$INDEX]#*=}"
      ;;
    --transport)
      INDEX=$((INDEX + 1))
      if [[ $INDEX -ge ${#ARGS[@]} ]]; then
        printf 'Error: --transport requires a value.\n' >&2
        exit 1
      fi
      ;;
    --transport=*)
      ;;
  esac
  INDEX=$((INDEX + 1))
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

SOURCE_DIR="${CONFIG_SOURCE_DIR:-html}"
TRANSPORT="${CONFIG_TRANSPORT:-scp}"
REMOTE_USER="${CONFIG_REMOTE_USER:-}"
REMOTE_HOST="${CONFIG_REMOTE_HOST:-}"
REMOTE_DIR="${CONFIG_REMOTE_DIR:-}"
SSH_PORT="${CONFIG_SSH_PORT:-22}"
SSH_KEY="${CONFIG_SSH_KEY:-}"
DELETE_REMOTE=0
DRY_RUN=0
SHOW_PROGRESS=1

while [[ $# -gt 0 ]]; do
  case "$1" in
    --config)
      if [[ $# -lt 2 ]]; then
        printf 'Error: --config requires a value.\n' >&2
        exit 1
      fi
      shift 2
      ;;
    --config=*)
      shift
      ;;
    --site)
      if [[ $# -lt 2 ]]; then
        printf 'Error: --site requires a value.\n' >&2
        exit 1
      fi
      shift 2
      ;;
    --site=*)
      shift
      ;;
    --source)
      if [[ $# -lt 2 ]]; then
        printf 'Error: --source requires a value.\n' >&2
        exit 1
      fi
      SOURCE_DIR="$2"
      shift 2
      ;;
    --source=*)
      SOURCE_DIR="${1#*=}"
      shift
      ;;
    --transport)
      if [[ $# -lt 2 ]]; then
        printf 'Error: --transport requires a value.\n' >&2
        exit 1
      fi
      TRANSPORT="$2"
      shift 2
      ;;
    --transport=*)
      TRANSPORT="${1#*=}"
      shift
      ;;
    --user)
      if [[ $# -lt 2 ]]; then
        printf 'Error: --user requires a value.\n' >&2
        exit 1
      fi
      REMOTE_USER="$2"
      shift 2
      ;;
    --user=*)
      REMOTE_USER="${1#*=}"
      shift
      ;;
    --host)
      if [[ $# -lt 2 ]]; then
        printf 'Error: --host requires a value.\n' >&2
        exit 1
      fi
      REMOTE_HOST="$2"
      shift 2
      ;;
    --host=*)
      REMOTE_HOST="${1#*=}"
      shift
      ;;
    --remote-dir)
      if [[ $# -lt 2 ]]; then
        printf 'Error: --remote-dir requires a value.\n' >&2
        exit 1
      fi
      REMOTE_DIR="$2"
      shift 2
      ;;
    --remote-dir=*)
      REMOTE_DIR="${1#*=}"
      shift
      ;;
    --port)
      if [[ $# -lt 2 ]]; then
        printf 'Error: --port requires a value.\n' >&2
        exit 1
      fi
      SSH_PORT="$2"
      shift 2
      ;;
    --port=*)
      SSH_PORT="${1#*=}"
      shift
      ;;
    --identity-file)
      if [[ $# -lt 2 ]]; then
        printf 'Error: --identity-file requires a value.\n' >&2
        exit 1
      fi
      SSH_KEY="$2"
      shift 2
      ;;
    --identity-file=*)
      SSH_KEY="${1#*=}"
      shift
      ;;
    --delete)
      DELETE_REMOTE=1
      shift
      ;;
    --dry-run)
      DRY_RUN=1
      shift
      ;;
    --no-progress)
      SHOW_PROGRESS=0
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      printf 'Error: unknown option: %s\n\n' "$1" >&2
      usage >&2
      exit 1
      ;;
  esac
done

SOURCE_DIR="$(resolve_from_base "$SOURCE_DIR" "$SCRIPT_DIR")"
SSH_KEY="$(expand_home "$SSH_KEY")"
TRANSPORT="$(printf '%s' "$TRANSPORT" | tr '[:upper:]' '[:lower:]')"

case "$TRANSPORT" in
  scp|rsync)
    ;;
  *)
    printf 'Error: unsupported transport: %s. Use "scp" or "rsync".\n' "$TRANSPORT" >&2
    exit 1
    ;;
esac

if [[ "$TRANSPORT" == "scp" ]]; then
  require_command scp
else
  require_command rsync
  require_command ssh
fi

if [[ ! -d "$SOURCE_DIR" ]]; then
  printf 'Error: source directory not found: %s\n' "$SOURCE_DIR" >&2
  exit 1
fi

if [[ -n "$SSH_KEY" && ! -f "$SSH_KEY" ]]; then
  printf 'Error: SSH key not found: %s\n' "$SSH_KEY" >&2
  exit 1
fi

MISSING_SETTINGS=()
if [[ -z "$REMOTE_USER" ]]; then
  MISSING_SETTINGS+=("user/--user")
fi
if [[ -z "$REMOTE_HOST" ]]; then
  MISSING_SETTINGS+=("host/--host")
fi
if [[ -z "$REMOTE_DIR" ]]; then
  MISSING_SETTINGS+=("remoteDir/--remote-dir")
fi

if [[ ${#MISSING_SETTINGS[@]} -gt 0 ]]; then
  printf 'Error: missing required setting(s): %s\n' "${MISSING_SETTINGS[*]}" >&2
  printf 'Create %s from %s and add a "%s" site entry, or pass the values with CLI flags.\n' \
    "$REPO_ROOT/deploy.config.json" \
    "$REPO_ROOT/deploy.config.example.json" \
    "$SITE_NAME" >&2
  exit 1
fi

REMOTE_DIR="${REMOTE_DIR%/}"
REMOTE_DESTINATION="${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}/"

AUTH_OPTIONS=(-o ConnectTimeout=15)
if [[ -n "$SSH_KEY" ]]; then
  AUTH_OPTIONS+=(-i "$SSH_KEY")
else
  AUTH_OPTIONS+=(
    -o PubkeyAuthentication=no
    -o PreferredAuthentications=password,keyboard-interactive
  )
fi

printf 'Deploying site "%s"\n' "$SITE_NAME"
printf 'Source: %s/\n' "$SOURCE_DIR"
printf 'Destination: %s\n' "$REMOTE_DESTINATION"
printf 'Transport: %s\n' "$TRANSPORT"
if [[ $DRY_RUN -eq 1 ]]; then
  printf 'Dry run enabled; no remote files will be changed.\n'
fi
if [[ "$TRANSPORT" == "rsync" && $DELETE_REMOTE -eq 0 ]]; then
  printf 'Remote-only files will be kept. Pass --delete to remove them.\n'
fi
if [[ "$TRANSPORT" == "scp" ]]; then
  printf 'scp transport uploads the current site tree to the remote directory.\n'
fi
if [[ -z "$SSH_KEY" && $DRY_RUN -eq 0 ]]; then
  printf 'SSH key not configured. Type your DreamHost password, press Enter, and note that nothing will appear while you type.\n'
fi

if [[ "$TRANSPORT" == "scp" ]]; then
  if [[ $DELETE_REMOTE -eq 1 ]]; then
    printf 'Error: --delete is only supported with the rsync transport.\n' >&2
    exit 1
  fi

  SCP_SOURCES=()
  while IFS= read -r -d '' source_path; do
    SCP_SOURCES+=("$source_path")
  done < <(find "$SOURCE_DIR" -mindepth 1 -maxdepth 1 ! -name '.DS_Store' -print0)

  if [[ ${#SCP_SOURCES[@]} -eq 0 ]]; then
    printf 'Error: nothing to upload from %s\n' "$SOURCE_DIR" >&2
    exit 1
  fi

  if [[ $DRY_RUN -eq 1 ]]; then
    FILE_COUNT="$(find "$SOURCE_DIR" -type f ! -name '.DS_Store' | wc -l | tr -d ' ')"
    printf 'Dry run for scp: this transport uploads the current site tree without remote diffing.\n'
    printf 'Would upload %s top-level entries and %s files.\n' "${#SCP_SOURCES[@]}" "$FILE_COUNT"
    printf 'Top-level entries:\n'
    for source_path in "${SCP_SOURCES[@]}"; do
      printf '  %s\n' "${source_path#$SOURCE_DIR/}"
    done
  else
    SCP_CMD=(scp -r -p -P "$SSH_PORT")
    if [[ $SHOW_PROGRESS -eq 0 ]]; then
      SCP_CMD+=(-q)
    fi
    SCP_CMD+=("${AUTH_OPTIONS[@]}")
    "${SCP_CMD[@]}" "${SCP_SOURCES[@]}" "$REMOTE_DESTINATION"
  fi
else
  SSH_CMD=(ssh -p "$SSH_PORT" "${AUTH_OPTIONS[@]}")
  printf -v SSH_TRANSPORT '%q ' "${SSH_CMD[@]}"
  SSH_TRANSPORT="${SSH_TRANSPORT% }"

  RSYNC_ARGS=(
    -azh
    --itemize-changes
    --exclude=.DS_Store
    -e "$SSH_TRANSPORT"
    --rsync-path="mkdir -p -- $(printf '%q' "$REMOTE_DIR") && rsync"
  )

  if [[ $DELETE_REMOTE -eq 1 ]]; then
    RSYNC_ARGS+=(--delete)
  fi

  if [[ $DRY_RUN -eq 1 ]]; then
    RSYNC_ARGS+=(--dry-run)
  fi

  if [[ $SHOW_PROGRESS -eq 1 && $DRY_RUN -eq 0 ]]; then
    RSYNC_ARGS+=(--progress)
  fi

  rsync "${RSYNC_ARGS[@]}" "$SOURCE_DIR/" "$REMOTE_DESTINATION"
fi

if [[ $DRY_RUN -eq 1 ]]; then
  printf 'Dry run complete.\n'
else
  printf 'Deploy complete.\n'
fi
