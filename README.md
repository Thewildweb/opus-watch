# Maison & Opus — storefront

Heritage horlogerie boutique built on **WordPress + WooCommerce**, from the
`01-heritage.html` design study.

## Local stack

Requires Docker + Docker Compose.

```bash
cp .env.example .env      # already done on first checkout
docker compose up -d      # wordpress, mariadb, phpmyadmin
./scripts/install.sh      # installs WP core + WooCommerce, activates the theme
```

| Service     | URL                      | Notes                         |
|-------------|--------------------------|-------------------------------|
| Storefront  | http://localhost:8080    | `admin` / `admin`             |
| Admin       | http://localhost:8080/wp-admin |                         |
| phpMyAdmin  | http://localhost:8081    |                               |
| Elasticsearch | http://localhost:9201  | only with `--profile search`  |

Ports are configurable in `.env`. Elasticsearch host port is **9201**
(9200 is taken by other local infra).

### wp-cli

```bash
docker compose run --rm wpcli plugin list
docker compose run --rm wpcli option get woocommerce_currency
```

### Elasticsearch (planned — ElasticPress)

```bash
docker compose --profile search up -d elasticsearch
```

## Layout

- `docker-compose.yml`, `.env` — local stack
- `scripts/install.sh` — idempotent first-run setup
- `wp-content/themes/maison-opus/` — the custom theme (version controlled)
- `wp-content/mu-plugins/` — must-use plugins / snippets
- `*.html` — the original design studies; `01-heritage.html` is the chosen one

WordPress core, WooCommerce, and other plugins live in the `wp_data`
Docker volume and are **not** committed.
