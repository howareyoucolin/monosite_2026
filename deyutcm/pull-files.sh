#!/bin/sh
# Pulls wp-content directories down from prod.
#
# uploads/ and plugins/ are gitignored, so a fresh clone starts without them.
# This fetches them from the live site. Prod is only ever read.
#
# Usage:
#   ./pull-files.sh              uploads and plugins
#   ./pull-files.sh uploads      just uploads
#   ./pull-files.sh plugins      just plugins
#   ./pull-files.sh themes       just themes (overwrites tracked files — careful)
#   ./pull-files.sh --delete ... also remove local files that are gone from prod
set -e

cd "$(dirname "$0")"

[ -f .env ] || { echo "missing .env — copy .env.example and fill it in" >&2; exit 1; }
# shellcheck disable=SC1091
. ./.env

DELETE=""
if [ "$1" = "--delete" ]; then
	DELETE="--delete"
	shift
fi

case "${1:-}" in
"") TARGETS="uploads plugins" ;;
uploads | plugins | themes) TARGETS="$1" ;;
all) TARGETS="uploads plugins themes" ;;
*) echo "unknown target: $1 (use uploads, plugins, themes, or all)" >&2; exit 1 ;;
esac

[ -n "$PROD_SSH" ] || { echo "PROD_SSH is not set in .env — file transfer needs SSH" >&2; exit 1; }

SOCK="/tmp/deyutcm-files-$$.sock"
cleanup() { ssh -S "$SOCK" -O exit "$PROD_SSH" 2>/dev/null || true; }
trap cleanup EXIT INT TERM

echo "==> connecting to the remote host"
echo "    (password may be prompted; ssh-copy-id on that host to skip it)"
ssh -M -S "$SOCK" -N -f -o ConnectTimeout=20 "$PROD_SSH"

rsh() { ssh -S "$SOCK" "$PROD_SSH" "$@"; }

# An SFTP-only account authenticates but runs nothing, which otherwise looks
# like a silent hang.
probe=$(rsh 'echo SHELL_OK' 2>&1) || true
if [ "$probe" != "SHELL_OK" ]; then
	echo "    remote command produced: ${probe:-<nothing>}" >&2
	echo "no shell access on that account — enable it in the hosting panel" >&2
	exit 1
fi

# Locate wp-content on the remote side.
if [ -n "$PROD_WP_DIR" ]; then
	REMOTE_WP="$PROD_WP_DIR"
	if ! rsh "test -d '$REMOTE_WP'"; then
		echo "PROD_WP_DIR does not exist on the remote host: $REMOTE_WP" >&2
		exit 1
	fi
	# Accept either the site root or wp-content itself.
	if rsh "test -d '$REMOTE_WP/wp-content'"; then
		REMOTE_WP="$REMOTE_WP/wp-content"
	fi
else
	echo "==> looking for wp-content on the remote host"
	found=$(rsh 'ls -d ~/*/wp-content 2>/dev/null | head -5')
	if [ -z "$found" ]; then
		echo "could not find wp-content — set PROD_WP_DIR in .env to the site root" >&2
		exit 1
	fi
	REMOTE_WP=$(echo "$found" | head -1)
	n=$(echo "$found" | wc -l | tr -d ' ')
	if [ "$n" -gt 1 ]; then
		echo "    found several; using the first:" >&2
		echo "$found" | sed 's/^/      /' >&2
		echo "    set PROD_WP_DIR in .env to pick a different one" >&2
	fi
fi
echo "    remote wp-content: $REMOTE_WP"

# rsync is much better for repeat pulls, but is not on every shared host.
if rsh 'command -v rsync >/dev/null'; then
	MODE=rsync
else
	MODE=tar
	echo "    rsync not available remotely — falling back to tar over ssh"
fi

for t in $TARGETS; do
	if ! rsh "test -d '$REMOTE_WP/$t'"; then
		echo "==> $t: not present on prod, skipping"
		continue
	fi

	echo "==> pulling $t"
	mkdir -p "wp-content/$t"
	if [ "$MODE" = rsync ]; then
		# --progress, not --info=progress2: macOS ships rsync 2.6.9/openrsync,
		# which does not know the newer flag.
		rsync -a --progress --human-readable $DELETE \
			-e "ssh -S $SOCK" \
			"$PROD_SSH:$REMOTE_WP/$t/" "wp-content/$t/"
	else
		[ -n "$DELETE" ] && echo "    (--delete is ignored with the tar fallback)"
		rsh "cd '$REMOTE_WP' && tar czf - '$t'" | tar xzf - -C wp-content
	fi
	echo "    $t: $(find "wp-content/$t" -type f | wc -l | tr -d ' ') files, $(du -sh "wp-content/$t" | cut -f1)"
done

echo
echo "Done. Files land in wp-content/ and are already mounted into the container."
