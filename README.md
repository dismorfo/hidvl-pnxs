# hidvl-pnxs

A lightweight PHP application that renders a public video landing/player page
for items in the **Hemispheric Institute Digital Video Library (HIDVL)**,
part of NYU Libraries. Given a NOID (a short opaque identifier), the app:

1. Looks up the item's bibliographic record in NYU's **PNXS** (Primo)
   discovery API.
2. Fetches the corresponding video embed/thumbnail from a media streaming
   service.
3. Renders a permalink-friendly HTML page (title, creators, credits, rights,
   subjects, `schema.org`/JSON-LD metadata, Open Graph tags, etc.) using Twig
   templates, or a JSON representation of the same data via `?wt=json`.

Example: `https://<host>/47d7wmjw` → an item's title, video embed, and
metadata pulled live from PNXS.

## How it works

- **Routing** (`index.php`, [`bramus/router`](https://github.com/bramus/router)):
  - `GET /` → home page (`routes/home.php`)
  - `GET /item-withdrawn` → static "item withdrawn" notice (`routes/itemwithdrawn.php`)
  - `GET /{noid}` → metadata/player page (`routes/hidvl-metadata-player.php`)
  - Any other path → 404
- **Rendering** (`include/common.php`): builds a `[template, data]` pair from
  each route, then renders it with [Twig](https://twig.symfony.com/)
  (`templates/*.html`), or as JSON when the request includes `?wt=json`.
- **Data fetching** (`routes/hidvl-metadata-player.php`): queries the PNXS API
  for the record matching the NOID, transforms the raw PNXS record into the
  page's data model (title, authors, credits, rights, language, summary,
  `schema.org` VideoObject JSON-LD, etc.) via `build_player_data()`, and
  writes a copy of the raw API response to `cache/<noid>.json` for
  inspection/debugging.
- **Front controller** (`.htaccess`): rewrites all non-file/non-directory
  requests to `index.php`, and sets a couple of baseline security headers.

## Requirements

- PHP 8+
- [Composer](https://getcomposer.org/doc/00-intro.md#locally)
- Network access to the PNXS service and media service configured in `.env`
  (there's no local/offline mode — the app always calls the live PNXS API)

## Setup

```bash
composer install
cp .env.example .env
# then edit .env with real service URLs and an API key — see below
```

### Environment variables

Copy [`.env.example`](.env.example) to `.env` and fill in the values:

| Variable             | Description                                                                 |
|----------------------|-------------------------------------------------------------------------------|
| `APP_ROOT`           | Base path the app is served from (`/` if served from the domain root).        |
| `GA` / `GAUA`        | Google Analytics toggle (`0`/`1`) and UA tracking ID.                          |
| `media_service`      | Base URL of the media/streaming service serving thumbnails and video embeds.   |
| `bobcat_url`         | Base URL of the NYU Libraries discovery ("Bobcat") site, used for nav links.   |
| `pnxs_service`       | PNXS (Primo) search API endpoint used to look up item metadata by NOID.        |
| `primo_service`      | Primo permalink service base, used to build citation/permalink URLs.          |
| `pnxs_query_prefix`  | Prefix prepended to the NOID when querying PNXS (e.g. `hidvl` + noid).        |
| `pnxs_search_field`  | PNXS field searched for the prefixed NOID value (e.g. `lsr12`).               |
| `apikey`             | API key used to authenticate requests to the PNXS service.                    |

## Running locally

Using PHP's built-in server:

```bash
php --server localhost:5000
```

Then open http://localhost:5000/47d7wmjw — you should see a video embed, the
item's title, and other metadata fetched live from PNXS.

## Running with Docker

```bash
# Build and run via docker-compose (bind-mounts the repo, good for local dev)
docker compose up --build

# ...or build/run the image standalone
docker build -t dismorfo/hidvl:latest .
docker run -t --name hidvl -p 5050:80 -v "$(pwd)/.env:/var/www/html/.env:ro" dismorfo/hidvl:latest
```

Then open http://localhost:5050/47d7wmjw.

## Testing

Unit tests (PHPUnit) cover the PNXS-record-to-page-data transformation in
`build_player_data()` using fixture API responses — no network calls or live
credentials required:

```bash
composer test
```

Syntax-check all PHP files:

```bash
find . -path ./vendor -prune -o -name '*.php' -print | xargs -n1 php -l
```

A GitHub Actions workflow (`.github/workflows/ci.yml`) runs both on every
push/PR to `main`.

## Project structure

```
index.php                          Front controller / route definitions
include/common.php                 Rendering, header, and helper functions
include/ISO639.php                 ISO 639 language code lookup table
routes/home.php                    Home page route
routes/itemwithdrawn.php           "Item withdrawn" notice route
routes/hidvl-metadata-player.php   PNXS lookup + player page data model
templates/                         Twig templates (base, player, error, ...)
tests/                             PHPUnit tests + fixtures
datasource/                        Reference CSVs (NOID lists, subject/field mappings)
cache/                             Raw PNXS API responses, written per request
```
