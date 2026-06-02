#!/usr/bin/env bash
set -euo pipefail

VERSION="${1:-}"
if [[ -z "$VERSION" ]]; then
  echo "Usage: ./build-release.sh <version>" >&2
  exit 1
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR_NAME="tender-library"
BUILD_ROOT="${ROOT_DIR}/release-build"
PACKAGE_DIR="${BUILD_ROOT}/${PLUGIN_DIR_NAME}"
ZIP_PATH="${ROOT_DIR}/${PLUGIN_DIR_NAME}-${VERSION}.zip"
MAIN_FILE="${ROOT_DIR}/tender-library.php"

if [[ ! -f "$MAIN_FILE" ]]; then
  echo "Missing main plugin file: tender-library.php" >&2
  exit 1
fi

HEADER_VERSION="$(grep -E '^ \* Version:' "$MAIN_FILE" | awk -F': ' '{print $2}' | xargs)"
CONSTANT_VERSION="$(grep -E "define\('TENDER_LIBRARY_VERSION'" "$MAIN_FILE" | sed -E "s/.*'([^']+)'.*/\1/")"

if [[ "$HEADER_VERSION" != "$VERSION" || "$CONSTANT_VERSION" != "$VERSION" ]]; then
  echo "Version mismatch. Requested ${VERSION}, header ${HEADER_VERSION}, constant ${CONSTANT_VERSION}." >&2
  exit 1
fi

rm -rf "$BUILD_ROOT" "$ZIP_PATH"
mkdir -p "$PACKAGE_DIR"

rsync -a "$ROOT_DIR/" "$PACKAGE_DIR/" \
  --exclude '.git/' \
  --exclude '.github/' \
  --exclude '.gitignore' \
  --exclude '.distignore' \
  --exclude '.editorconfig' \
  --exclude '.babelrc' \
  --exclude '.phpcs.xml.dist' \
  --exclude 'node_modules/' \
  --exclude 'src/' \
  --exclude 'tests/' \
  --exclude 'bin/' \
  --exclude 'temp/' \
  --exclude 'release-build/' \
  --exclude '*.zip' \
  --exclude '*.tar.gz' \
  --exclude '*.sql' \
  --exclude '.env' \
  --exclude '.DS_Store' \
  --exclude 'Thumbs.db' \
  --exclude 'phpunit.xml*' \
  --exclude 'wp-cli.local.yml' \
  --exclude 'webpack.config.js' \
  --exclude 'webpack.blocks.config.js' \
  --exclude 'package.json' \
  --exclude 'package-lock.json' \
  --exclude 'composer.json' \
  --exclude 'composer.lock' \
  --exclude 'README.md' \
  --exclude 'readme.txt' \
  --exclude 'build-release.sh' \
  --exclude 'update.example.json' \
  --exclude 'CHANGELOG.md' \
  --exclude 'docs/' \
  --exclude 'assets/scss/'

rm -rf "$PACKAGE_DIR/vendor/bin"

(
  cd "$BUILD_ROOT"
  zip -qr "$ZIP_PATH" "$PLUGIN_DIR_NAME"
)

rm -rf "$BUILD_ROOT"
echo "$ZIP_PATH"
