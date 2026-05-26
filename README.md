# Tender A Library

Library management plugin for Local Anarquista Magdalena: custom post types, Carbon Fields metadata, reservations, lendings, profile pages, and custom Gutenberg blocks.

## Description

Tender A Library extends WordPress with:

- Custom post types for books and events
- Custom taxonomies (sections, languages, etc.)
- Carbon Fields powered metadata
- Library operations: lendings, reservations, profile and dashboard pages
- REST endpoints for frontend search and filters
- Custom Gutenberg blocks for book/event display
- Email notifications related to reservations and overdue returns
- CSV migration tools for importing legacy library data (books, users, lendings, calls, sections, languages, media)

Carbon Fields is included as a Composer dependency in `vendor/`, so users do not need to install a second Carbon Fields plugin.

## Installation

### Option 1: Install from ZIP

1. Get the release ZIP.
2. In WordPress admin go to `Plugins > Add New > Upload Plugin`.
3. Upload the ZIP and activate **Plugin Biblioteca (A)**.
4. Go to `Settings > Permalinks` and click `Save` once.

### Option 2: Install from Source

1. Copy or clone this plugin to `wp-content/plugins/tender-a-library`.
2. Install PHP dependencies:

```bash
composer install
```

3. Activate the plugin in WordPress admin.
4. If frontend assets changed, run:

```bash
npm install
npm run build
```

Notes:

- Production/shared builds must include the `vendor/` directory.
- `node_modules/` is not required in production.

## Development

Use npm to compile JavaScript and CSS assets.

Available scripts:

- `npm run build`: builds block assets and the custom webpack bundle
- `npm run build:blocks`: builds Gutenberg block assets into `build/`
- `npm run start:blocks`: watches and rebuilds Gutenberg block assets
- `npm run webpack`: builds the custom frontend/admin bundle into `dist/`
- `npm run webpack:watch`: watches and rebuilds the custom webpack bundle

For a full production-style asset build:

```bash
npm run build
```

## Release Flow

1. Install production PHP dependencies:

```bash
composer install --no-dev --optimize-autoloader
```

2. Build assets:

```bash
npm ci
npm run build
```

3. Build a clean ZIP from the plugin root:

```bash
mkdir -p /tmp/tender-a-library-release/tender-a-library
rsync -a ./ /tmp/tender-a-library-release/tender-a-library/ \
  --exclude-from=.distignore \
  --exclude '.git/'
cd /tmp/tender-a-library-release && zip -r tender-a-library-0.1.0.zip tender-a-library
```

## Project Structure

- `tender-a-library.php`: plugin bootstrap and module loading
- `modules/`: domain logic (books, lendings, reservations, profile, emails, search)
- `modules/migration/`: CSV migration UI, import logic, and templates
- `modules/tender-book/`: book fields, templates, signature autofill module
- `assets/`: custom JS and SCSS sources for the webpack bundle
- `src/`: Gutenberg block source files
- `build/`: compiled Gutenberg block assets
- `dist/`: compiled custom webpack assets
- `vendor/`: Composer dependencies, including Carbon Fields
- `.distignore`: excluded files/folders for release packaging

## FAQ

### Do users need to install Carbon Fields separately?

No. Carbon Fields is bundled through Composer in `vendor/`.

### Why does this repo not ship `node_modules/`?

Because only compiled assets are needed in production. `node_modules/` is development-only.

### How do I import legacy data from CSV?

Go to **Dashboard -> Biblioteca -> CSV Migration**.

Features:

- Dry-run mode (no data written) to preview counts and missing mappings
- Upload CSV files directly in the UI
- Downloadable CSV templates for each entity
- Media import supports full `url` downloads or local file paths

Recommended import order:

1. Sections
2. Languages
3. Media
4. Users
5. Books
6. Lendings
7. Calls

CSV templates live in `modules/migration/templates/`.

### What if plugin activation works but fields are missing?

Check that `vendor/` exists in the deployed plugin. Without it, Carbon Fields cannot boot.

## Changelog

### 0.1.0

- Initial public project structure and module set
- Carbon Fields loading migrated to Composer-bundled dependency
- Signature fields workflow improved with admin autofill behavior
