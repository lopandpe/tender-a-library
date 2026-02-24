=== Tender A Library ===
Contributors: pedrolopez
Tags: library, books, lending, reservations, carbon-fields, custom-post-type, gutenberg
Requires at least: 6.0
Tested up to: 6.7.1
Requires PHP: 5.6
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Library management plugin for Local Anarquista Magdalena: custom post types, Carbon Fields metadata, reservations, lendings, profile pages, and custom Gutenberg blocks.

== Description ==

Tender A Library extends WordPress with:

* Custom post types for books and events
* Custom taxonomies (sections, languages, etc.)
* Carbon Fields powered metadata
* Library operations: lendings, reservations, profile and dashboard pages
* REST endpoints for frontend search and filters
* Custom Gutenberg blocks for book/event display
* Email notifications related to reservations and overdue returns

Carbon Fields is included as a Composer dependency in `vendor/`, so users do not need to install a second Carbon Fields plugin.

== Installation ==

= Option 1: Install from ZIP (recommended for site admins) =

1. Get the release ZIP (see "How to Share the Plugin").
2. In WordPress admin go to `Plugins > Add New > Upload Plugin`.
3. Upload the ZIP and activate **Plugin Biblioteca (A)**.
4. Go to `Settings > Permalinks` and click `Save` once (flush rewrite rules).

= Option 2: Install from source (recommended for developers) =

1. Copy/clone this plugin to:
`wp-content/plugins/tender-a-library`
2. Install PHP dependencies:
`composer install`
3. Activate the plugin in WordPress admin.
4. If frontend assets were changed, also run:
`npm install`
`npm run build`

Notes:
* Production/shared builds must include the `vendor/` directory.
* `node_modules/` is not required in production.

== How to Share the Plugin ==

Use this flow when delivering to another site/server.

1. Ensure dependencies are installed for distribution:
`composer install --no-dev --optimize-autoloader`
2. Ensure built assets are up to date (only if source assets changed):
`npm ci`
`npm run build`
3. Build a clean ZIP (from plugin root):

`mkdir -p /tmp/tender-a-library-release/tender-a-library`

`rsync -a ./ /tmp/tender-a-library-release/tender-a-library/ \
  --exclude-from=.distignore \
  --exclude '.git/'`

`cd /tmp/tender-a-library-release && zip -r tender-a-library-0.1.0.zip tender-a-library`

4. Share `tender-a-library-0.1.0.zip` and install via WordPress Upload Plugin.

== Project Management Notes ==

Directory overview:

* `tender-a-library.php`: plugin bootstrap and module loading
* `modules/`: domain logic (books, lendings, reservations, profile, emails, search)
* `modules/tender-book/`: book fields, templates, signature autofill module
* `assets/`, `src/`, `build/`, `dist/`: frontend sources and compiled assets
* `vendor/`: Composer dependencies (includes Carbon Fields)
* `.distignore`: excluded files/folders for release packaging

Recommended branch/release workflow:

1. Feature branch per change.
2. Validate locally:
`php -l` on changed PHP files, plus manual wp-admin flow tests.
3. Update version in plugin header and this readme for release.
4. Build release ZIP with steps above.
5. Tag release in Git (example: `v0.1.0`).

== Frequently Asked Questions ==

= Do users need to install Carbon Fields separately? =

No. Carbon Fields is bundled through Composer in `vendor/`.

= Why does this repo not ship `node_modules/`? =

Because only compiled assets are needed in production. `node_modules/` is development-only.

= What if plugin activation works but fields are missing? =

Check that `vendor/` exists in the deployed plugin. Without it, Carbon Fields cannot boot.

== Changelog ==

= 0.1.0 =
* Initial public project structure and module set.
* Carbon Fields loading migrated to Composer-bundled dependency.
* Signature fields workflow improved with admin autofill behavior.

== Upgrade Notice ==

= 0.1.0 =
Initial stable baseline for deployment and collaborative maintenance.
