# opus.watches — production deployment

Target: Leaseweb VPS 12532632 (`85.17.236.215`), Ubuntu 24.04, Docker stack
(MariaDB + WordPress + Caddy for Let's Encrypt TLS), Cloudflare in front.

Secrets come from the local password store (`pass`), no vault file:
`projects/opus-watch/{mysql-root, mysql-wordpress, erik-password-hash}`.
The sudo password is the shared one at `pass projects/ODC-infra/erik-sudo`.

## Fresh server → live site

```bash
cd deploy

# 0. Reimage via Leaseweb (from ODC-scraping-infra):
#    python3 scripts/leaseweb_reinstall.py reinstall OpenDataCollection --yes

# 1. Bootstrap (root SSH still open): create erik + keys
ansible-playbook playbooks/bootstrap.yml

# 2. Provision: hardening (root SSH closed, ufw, fail2ban, unattended
#    upgrades), docker, site stack
ansible-playbook playbooks/site.yml \
  --become-password-file <(pass projects/ODC-infra/erik-sudo | head -1)

# 3. ONE TIME ONLY — seed content from the local dev stack. This drops the
#    production database, so it is only ever correct on an empty shop.
I_UNDERSTAND_THIS_WIPES_PRODUCTION=yes scripts/DANGER-seed-production.sh
```

## Deploying a change

```bash
ansible-playbook playbooks/site.yml \
  --become-password-file <(pass projects/ODC-infra/erik-sudo | head -1)
```

That rsyncs the theme and mu-plugins from the repo and reloads the stack. It
does not touch content. **Content lives in production**: the customer edits it
in wp-admin, and the flow for refreshing a development copy is prod → local,
never local → prod.

Re-running any step is safe (idempotent). Theme + mu-plugins deploy from the
repo via `site.yml` (rsync) — that is also the routine "deploy a theme change"
command.

## Search & caching

- **Redis** (`redis` service): WordPress object cache via the Redis Object
  Cache plugin (Predis). `wp redis status` to check; disable with
  `wp redis disable` if it ever misbehaves.
- **Elasticsearch** (`elasticsearch` service, internal-only): ElasticPress
  powers the shop search (`wp elasticpress sync` after bulk content
  changes); the watch database's text search queries the `opus_watches`
  index with SQL-LIKE fallback — rebuild it after an import with
  `wp opus watchdb-reindex`.

## Cloudflare

- Single `A opus.watches → 85.17.236.215` (proxied) + `www` CNAME (proxied).
- SSL/TLS mode: **Full (strict)** once Caddy has issued certificates.

## Security model

- SSH: keys only, root login disabled, fail2ban, UFW allows 22/80/443 only.
- Unattended security upgrades on.
- WordPress: `DISALLOW_FILE_EDIT` (no code editor in admin) but plugin
  installs from the backend stay possible; xmlrpc blocked at Caddy;
  DB not exposed outside the compose network; unique salts per server.
- Login rate limiting: Limit Login Attempts Reloaded was installed after
  go-live and removed again on 2026-10-13 (its admin notices broke). No
  limiter is in place now; wp-login.php is only behind Cloudflare.
- After go-live: re-register WPML with a production key for opus.watches.

## Backups

- Nightly 02:30 UTC on the server: `~/bin/opus-backup` (template
  `roles/opuswatch/templates/opus-backup.sh.j2`) writes
  `~/backups/full-<date>/`: MariaDB dump, the whole WordPress install,
  the Umami database and `/opt/opus-watch` (with `.env` — secrets).
  7 days kept; log in `~/backups/backup.log`.
- Off-site: big pulls them at 04:00 with `deploy/scripts/pull-backups.sh`
  into `/mnt/sas14tb/backups/opus-watch/` (30 days, checksums verified).
  Its key may only read `~/backups` (rrsync -ro). To be replaced by S3.
- Restore steps: `deploy/RESTORE.md`.
