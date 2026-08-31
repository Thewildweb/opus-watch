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

# 3. Migrate content from the local dev stack (DB + plugins/uploads/languages)
scripts/migrate-from-local.sh
```

Re-running any step is safe (idempotent). Theme + mu-plugins deploy from the
repo via `site.yml` (rsync) — that is also the routine "deploy a theme change"
command.

## Cloudflare

- Single `A opus.watches → 85.17.236.215` (proxied) + `www` CNAME (proxied).
- SSL/TLS mode: **Full (strict)** once Caddy has issued certificates.

## Security model

- SSH: keys only, root login disabled, fail2ban, UFW allows 22/80/443 only.
- Unattended security upgrades on.
- WordPress: `DISALLOW_FILE_EDIT` (no code editor in admin) but plugin
  installs from the backend stay possible; xmlrpc blocked at Caddy;
  DB not exposed outside the compose network; unique salts per server.
- After go-live: install a login rate limiter (e.g. Limit Login Attempts
  Reloaded) from the backend, and re-register WPML with a production key
  for opus.watches.
