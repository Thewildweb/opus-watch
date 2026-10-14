Full backup of opus.watches — 2026-10-14 08:10 UTC, server 85.17.236.215 (/opt/opus-watch)

mariadb-all.sql.gz      All MariaDB databases (WordPress = db "wordpress"), --single-transaction
wordpress-files.tar.gz  Entire /var/www/html: WP core, wp-config.php, plugins, themes, uploads
umami-postgres.sql.gz   Umami analytics (Postgres, pg_dumpall)
stack-config.tar.gz     /opt/opus-watch: docker-compose.yml, Caddyfile, .env, wp-salts.env (SECRETS)

Not included (rebuildable): Elasticsearch index (wp elasticpress sync), Redis cache, Caddy certs.

Restore outline (fresh server, after Ansible provisioning from the repo's deploy/):
  tar xzf stack-config.tar.gz -C /opt
  cd /opt/opus-watch && docker compose up -d db umami-db
  zcat mariadb-all.sql.gz | docker compose exec -T db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD"'
  zcat umami-postgres.sql.gz | docker compose exec -T umami-db sh -c 'psql -U "$POSTGRES_USER" postgres'
  docker compose up -d wordpress && docker compose exec -T wordpress tar xzf - -C /var/www < wordpress-files.tar.gz
  docker compose up -d && docker compose run --rm wpcli elasticpress sync --setup
  docker compose exec redis redis-cli FLUSHALL
Check integrity: sha256sum -c SHA256SUMS
