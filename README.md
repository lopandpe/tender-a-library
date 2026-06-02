# Tender Library

Private WordPress plugin for small local libraries. It provides book/event custom post types, Carbon Fields metadata, lending and reservation workflows, user/profile pages, search blocks, event feeds, email reminders, and CSV migration tools.

This plugin is **not** distributed through WordPress.org. The first install is done manually from a ZIP file. Future releases are delivered through a private update metadata endpoint and can be installed from the WordPress dashboard.

## Requirements

- WordPress 6.4 or newer
- PHP 8.1 or newer
- Composer for PHP dependency installation during release builds
- Node/npm for asset builds during development/release

## Important Slug Details

The distributed plugin slug is:

```text
tender-library
```

The distributed main plugin file is:

```text
tender-library.php
```

Release ZIPs must contain this top-level folder:

```text
tender-library/
```

The plugin header includes `Update URI: https://example.com/tender-library`. This prevents WordPress from matching this private plugin with any WordPress.org plugin that might use the same slug.

## Local Development

Install PHP dependencies:

```bash
composer install
```

Install JS dependencies and build assets:

```bash
npm install
npm run build
```

Useful asset commands:

```bash
npm run build
npm run build:blocks
npm run webpack
npm run start:blocks
npm run webpack:watch
```

## Private Update Configuration

The private update metadata URL is defined in `tender-library.php`:

```php
define('TENDER_LIBRARY_UPDATE_METADATA_URL', 'https://example.com/tender-library/update.json');
```

It can be changed without editing plugin files:

```php
add_filter('tender_library_update_metadata_url', function () {
    return 'https://your-domain.example/tender-library/update.json';
});
```

The default placeholder must be replaced before production distribution.

## update.json Format

A minimal metadata file looks like this:

```json
{
  "name": "Tender Library",
  "slug": "tender-library",
  "version": "1.0.1",
  "download_url": "https://example.com/tender-library/releases/tender-library-1.0.1.zip",
  "requires": "6.4",
  "tested": "6.6",
  "requires_php": "8.1",
  "last_updated": "2026-06-02",
  "homepage": "https://example.com/tender-library",
  "author": "Luis Gómez",
  "sections": {
    "description": "Private library/tender management plugin.",
    "changelog": "<h4>1.0.1</h4><ul><li>Describe changes here.</li></ul>"
  }
}
```

An example lives in `update.example.json`.

## Creating a Production ZIP

1. Update the plugin version in `tender-library.php`:
   - plugin header `Version`
   - `TENDER_LIBRARY_VERSION`
2. Update `CHANGELOG.md`.
3. Install production PHP dependencies:

```bash
composer install --no-dev --optimize-autoloader
```

4. Build final assets:

```bash
npm ci
npm run build
```

5. Build the release ZIP:

```bash
./build-release.sh 1.0.0
```

The script creates:

```text
tender-library-1.0.0.zip
```

The ZIP contains a top-level `tender-library/` folder and excludes development files such as `.git`, `node_modules`, `src`, SCSS sources, test/config files, temp files, documentation, and release scripts.

## Publishing a New Version

Example for `1.0.1`:

1. Update `Version` and `TENDER_LIBRARY_VERSION` to `1.0.1` in `tender-library.php`.
2. Update `CHANGELOG.md`.
3. Run:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
./build-release.sh 1.0.1
```

4. Upload the ZIP to:

```text
https://example.com/tender-library/releases/tender-library-1.0.1.zip
```

5. Update and upload `update.json` to:

```text
https://example.com/tender-library/update.json
```

6. In a WordPress installation with the plugin already installed, go to:

```text
WordPress Admin > Dashboard > Updates
```

or:

```text
WordPress Admin > Plugins
```

7. Confirm that Tender Library shows the update and install it from the dashboard.

## First Manual Installation

1. Build `tender-library-1.0.0.zip`.
2. In WordPress Admin, go to `Plugins > Add New > Upload Plugin`.
3. Upload the ZIP.
4. Activate **Tender Library**.
5. Go to `Settings > Permalinks` and click `Save Changes` once.
6. Confirm the plugin creates its library pages/tables and the admin menu appears for allowed roles.

## How Dashboard Updates Work

The plugin registers a private updater class in `includes/class-tender-library-updater.php`.

It:

- Fetches update metadata with `wp_remote_get()`.
- Caches valid metadata in a site transient.
- Validates and sanitizes remote fields before use.
- Compares remote and installed versions with `version_compare()`.
- Injects update data into WordPress' plugin update transient.
- Supports the plugin details modal through the `plugins_api` filter.
- Fails silently if the update server is unreachable or returns invalid JSON.

## Version Storage and Migrations

The source of truth for runtime code is:

```php
define('TENDER_LIBRARY_VERSION', '1.0.0');
```

The plugin stores the installed version in:

```text
tender_library_version
```

Current migration logic only records the installed version. Add version-gated migrations there when schema/data changes are needed.

## Manual Test Checklist

See `docs/release-checklist.md`.

## Notes

- Do not commit real license keys, tokens, server credentials, or private endpoint secrets.
- Replace `https://example.com/tender-library` before distributing production builds.
- Keep the ZIP folder name, plugin slug, and update metadata slug as `tender-library`.
