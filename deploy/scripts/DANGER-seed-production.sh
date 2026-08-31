#!/usr/bin/env bash
#
# ONE-TIME SEED of production from the local dev site. Read this before running.
#
# This DROPS the production database and replaces it with the local one. It was
# the right tool for go-live, on an empty shop. It is now the wrong tool: from
# the moment the customer added their first watch, order or page in wp-admin,
# production is the source of truth and this destroys it.
#
# The routine deploy is:  ansible-playbook playbooks/site.yml
# That rsyncs the theme and mu-plugins from the repo and touches no content.
#
# - Exports the local DB and wp-content (plugins/uploads/languages; theme and
#   mu-plugins are synced from the repo by Ansible instead).
# - Imports on the server, then runs a serialized-data-safe URL rewrite
#   (wp search-replace) from the local URL to https://opus.watches.
set -euo pipefail

if [ "${I_UNDERSTAND_THIS_WIPES_PRODUCTION:-}" != "yes" ]; then
	echo "Refusing to run: this replaces the production database with the local one."
	echo "If that is genuinely what you want:"
	echo "  I_UNDERSTAND_THIS_WIPES_PRODUCTION=yes $0"
	exit 1
fi

HOST="${HOST:-erik@85.17.236.215}"
SITE_DIR="${SITE_DIR:-/opt/opus-watch}"
LOCAL_URL="${LOCAL_URL:-http://localhost:8080}"
PROD_URL="${PROD_URL:-https://opus.watches}"
REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

cd "$REPO_ROOT"

echo "==> Exporting local database"
docker compose run --rm -T wpcli db export - > "$WORK/db.sql"
echo "    $(du -h "$WORK/db.sql" | cut -f1)"

echo "==> Archiving local wp-content (plugins, uploads, languages)"
docker compose run --rm -T --entrypoint tar wpcli \
  -C /var/www/html/wp-content -czf - plugins uploads languages > "$WORK/wp-content.tgz"
echo "    $(du -h "$WORK/wp-content.tgz" | cut -f1)"

echo "==> Copying to server"
scp -q "$WORK/db.sql" "$WORK/wp-content.tgz" "$HOST:/tmp/"

echo "==> Importing on server"
ssh "$HOST" bash -s <<EOF
set -euo pipefail
cd "$SITE_DIR"

# Unpack wp-content into the wp_data volume (uid 33 = www-data).
docker run --rm -v opus-watch_wp_data:/var/www/html -v /tmp:/host-tmp alpine sh -c '
  mkdir -p /var/www/html/wp-content && cd /var/www/html/wp-content &&
  tar -xzf /host-tmp/wp-content.tgz &&
  chown -R 33:33 plugins uploads languages
'

# Import DB.
docker compose run --rm -T wpcli db import - < /tmp/db.sql

# Rewrite URLs (serialized-safe) and clear caches.
docker compose run --rm -T wpcli search-replace "$LOCAL_URL" "$PROD_URL" --all-tables --report-changed-only
docker compose run --rm -T wpcli cache flush
# Rewrite rules must regenerate from a FRONTEND request (CLI writes an
# incomplete set with WPML active) — delete and let the first hit rebuild.
docker compose run --rm -T wpcli option delete rewrite_rules || true
curl -s -o /dev/null "$PROD_URL/" || true

rm -f /tmp/db.sql /tmp/wp-content.tgz
EOF

echo "==> Done. Check: $PROD_URL"
