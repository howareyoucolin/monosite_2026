#!/bin/sh
# Pulls the prod database down and loads it into the local container.
#
# Prod is only ever read: mysqldump runs on the remote shell host (the prod DB
# user's grant rejects other IPs), and the result is streamed back here. Nothing
# is written to prod. Every pull is kept in backups/ so an older one can be
# reloaded without touching prod again.
#
# Usage:
#   ./pull-db.sh              pull a fresh dump from prod, then import it
#   ./pull-db.sh <file.gz>    import a dump already in backups/
set -e

cd "$(dirname "$0")"

if [ ! -f .env ]; then
	echo "missing .env — copy .env.example and fill it in" >&2
	exit 1
fi
# shellcheck disable=SC1091
. ./.env

LOCAL_DB=deyutcm
BACKUP_DIR=backups

import_dump() {
	dump="$1"

	echo "==> checking $dump"
	gzip -t "$dump" || { echo "dump is not valid gzip" >&2; exit 1; }
	if ! gzip -dc "$dump" | head -c 200000 | grep -q "CREATE TABLE"; then
		echo "dump contains no CREATE TABLE — refusing to import" >&2
		exit 1
	fi
	echo "    ok ($(du -h "$dump" | cut -f1) compressed)"

	echo "==> starting local database"
	docker compose up -d --wait db

	echo "==> replacing local '$LOCAL_DB' (local only — prod is untouched)"
	docker compose exec -T db mariadb -uroot -proot \
		-e "DROP DATABASE IF EXISTS \`$LOCAL_DB\`; CREATE DATABASE \`$LOCAL_DB\` CHARACTER SET utf8;"

	echo "==> importing"
	gzip -dc "$dump" | docker compose exec -T db mariadb -uroot -proot --default-character-set=utf8 "$LOCAL_DB"

	tables=$(docker compose exec -T db mariadb -uroot -proot -N -B \
		-e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$LOCAL_DB';" | tr -d '\r')
	echo "==> imported $tables tables"
	if [ "$tables" -lt 1 ]; then
		echo "no tables after import — something went wrong" >&2
		exit 1
	fi
}

# ---- import an existing dump -------------------------------------------------

if [ -n "$1" ]; then
	[ -f "$1" ] && dump="$1" || dump="$BACKUP_DIR/$1"
	[ -f "$dump" ] || { echo "no such dump: $1" >&2; exit 1; }
	import_dump "$dump"
	echo
	echo "Done. Local database is a copy of prod; edits here stay here."
	exit 0
fi

# ---- pull a fresh dump ------------------------------------------------------

# PROD_SSH is optional: empty means dump straight from this machine.
for v in PROD_DB_HOST PROD_DB_NAME PROD_DB_USER PROD_DB_PASSWORD; do
	eval "val=\$$v"
	[ -n "$val" ] || { echo "$v is not set in .env" >&2; exit 1; }
done

mkdir -p "$BACKUP_DIR"
dump="$BACKUP_DIR/$PROD_DB_NAME-$(date +%Y%m%d-%H%M%S).sql.gz"
errlog="$dump.err"

MYSQLDUMP_OPTS="--default-character-set=utf8 --single-transaction --skip-lock-tables --quick --no-tablespaces"
COUNT_SQL="SELECT COUNT(*), IFNULL(ROUND(SUM(data_length+index_length)/1048576),0) FROM information_schema.tables WHERE table_schema='$PROD_DB_NAME';"

# Streams the dump into $dump in the background, printing progress. Callers set
# $DUMP_CMD_DESC for the log line and pass the command to run.
run_with_progress() {
	"$@" > "$dump" 2> "$errlog" &
	dumppid=$!
	waited=0
	while kill -0 "$dumppid" 2>/dev/null; do
		printf '\r    %-12s received (%ss)' "$(du -h "$dump" | cut -f1)" "$waited"
		sleep 2
		waited=$((waited + 2))
		if [ "$waited" = "60" ] && [ ! -s "$dump" ]; then
			printf '\n    nothing after 60s — the remote command is probably not running\n'
		fi
	done
	printf '\r    %-12s received (%ss)\n' "$(du -h "$dump" | cut -f1)" "$waited"

	rc=0
	wait "$dumppid" || rc=$?
	if [ "$rc" != "0" ] || [ ! -s "$dump" ]; then
		echo "dump failed (exit $rc). Error output:" >&2
		sed 's/^/    /' "$errlog" >&2
		rm -f "$dump"
		exit 1
	fi
	# mysqldump warnings are common and harmless; show without failing.
	[ -s "$errlog" ] && sed 's/^/    note: /' "$errlog"
	rm -f "$errlog"
}

if [ -n "$PROD_SSH" ]; then
	# --- via the remote shell host (needs real shell access, not SFTP-only) ---
	SOCK="/tmp/deyutcm-pull-$$.sock"
	cleanup() { ssh -S "$SOCK" -O exit "$PROD_SSH" 2>/dev/null || true; }
	trap cleanup EXIT INT TERM

	echo "==> connecting to the remote shell host"
	echo "    (password may be prompted; ssh-copy-id on that host to skip it)"
	ssh -M -S "$SOCK" -N -f -o ConnectTimeout=20 "$PROD_SSH"

	rsh() { ssh -S "$SOCK" "$PROD_SSH" "$@"; }

	echo "==> checking remote shell access"
	# An SFTP-only account authenticates but cannot run commands, which otherwise
	# shows up as a silent hang with a 0-byte dump.
	if ! probe=$(rsh 'echo SHELL_OK' 2>&1) || [ "$probe" != "SHELL_OK" ]; then
		echo "    remote command produced: ${probe:-<nothing>}" >&2
		echo "" >&2
		echo "No shell access on that account (SFTP-only, most likely)." >&2
		echo "Either enable shell access for the user in the hosting panel, or" >&2
		echo "leave PROD_SSH empty in .env and allow this machine's IP to reach" >&2
		echo "the database directly." >&2
		exit 1
	fi
	echo "    shell access ok"

	echo "==> checking remote tools"
	tools=$(rsh 'for t in mysqldump mysql gzip; do command -v $t >/dev/null && echo "$t ok" || echo "$t MISSING"; done')
	echo "$tools" | sed 's/^/    /'
	echo "$tools" | grep -q MISSING && { echo "a required tool is missing on the remote host" >&2; exit 1; }

	echo "==> checking database access"
	probe=$(printf '%s' "$COUNT_SQL" | rsh "MYSQL_PWD='$PROD_DB_PASSWORD' mysql --connect-timeout=20 -h '$PROD_DB_HOST' -u '$PROD_DB_USER' -N -B" 2>&1) || {
		echo "$probe" | sed 's/^/    /' >&2
		echo "could not query the prod database — check the credentials in .env" >&2
		exit 1
	}
	echo "    $(echo "$probe" | awk '{print $1" tables, about "$2" MB uncompressed"}')"

	echo "==> dumping over ssh"
	run_with_progress ssh -S "$SOCK" "$PROD_SSH" \
		"MYSQL_PWD='$PROD_DB_PASSWORD' mysqldump $MYSQLDUMP_OPTS -h '$PROD_DB_HOST' -u '$PROD_DB_USER' '$PROD_DB_NAME' | gzip -c"
else
	# --- straight from this machine (needs the DB grant to allow this IP) ---
	# Uses the mariadb image so no client has to be installed locally.
	echo "==> checking database access from this machine"
	probe=$(printf '%s' "$COUNT_SQL" | docker run --rm -i -e MYSQL_PWD="$PROD_DB_PASSWORD" mariadb:11 \
		mariadb --connect-timeout=20 -h "$PROD_DB_HOST" -u "$PROD_DB_USER" -N -B 2>&1) || {
		echo "$probe" | sed 's/^/    /' >&2
		echo "" >&2
		echo "Cannot reach the prod database from here. Allow this machine's IP on" >&2
		echo "the database user in the hosting panel, or set PROD_SSH in .env to a" >&2
		echo "shell host that is already allowed." >&2
		exit 1
	}
	echo "    $(echo "$probe" | awk '{print $1" tables, about "$2" MB uncompressed"}')"

	echo "==> dumping directly"
	run_with_progress sh -c "docker run --rm -e MYSQL_PWD='$PROD_DB_PASSWORD' mariadb:11 mariadb-dump $MYSQLDUMP_OPTS -h '$PROD_DB_HOST' -u '$PROD_DB_USER' '$PROD_DB_NAME' | gzip -c"
fi

import_dump "$dump"
echo "==> saved $dump"
echo
echo "Done. Local database is a copy of prod; edits here stay here."
echo "Start the site with: npm run dev"
