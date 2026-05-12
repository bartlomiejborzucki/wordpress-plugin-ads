#!/usr/bin/env bash

set -euo pipefail

PLUGIN_SLUG="ads-shortcode-plugin"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_DIR="$ROOT_DIR/dist/build"
PACKAGE_DIR="$BUILD_DIR/$PLUGIN_SLUG"
ZIP_PATH="$ROOT_DIR/dist/$PLUGIN_SLUG.zip"

rm -rf "$BUILD_DIR"
mkdir -p "$PACKAGE_DIR"

cp "$ROOT_DIR/ads-shortcode-plugin.php" "$PACKAGE_DIR/"
cp "$ROOT_DIR/readme.txt" "$PACKAGE_DIR/"
cp "$ROOT_DIR/README.md" "$PACKAGE_DIR/"
cp "$ROOT_DIR/index.php" "$PACKAGE_DIR/"
cp "$ROOT_DIR/uninstall.php" "$PACKAGE_DIR/"
cp -R "$ROOT_DIR/includes" "$PACKAGE_DIR/"

mkdir -p "$ROOT_DIR/dist"
rm -f "$ZIP_PATH"

if command -v zip >/dev/null 2>&1; then
    (
        cd "$BUILD_DIR"
        zip -rq "$ZIP_PATH" "$PLUGIN_SLUG"
    )
else
    python3 - "$BUILD_DIR" "$PLUGIN_SLUG" "$ZIP_PATH" <<'PY'
import pathlib
import sys
import zipfile

build_dir = pathlib.Path(sys.argv[1])
plugin_slug = sys.argv[2]
zip_path = pathlib.Path(sys.argv[3])
plugin_dir = build_dir / plugin_slug

with zipfile.ZipFile(zip_path, "w", compression=zipfile.ZIP_DEFLATED) as archive:
    for path in plugin_dir.rglob("*"):
        if path.is_file():
            archive.write(path, path.relative_to(build_dir))
PY
fi

echo "Created package: $ZIP_PATH"
