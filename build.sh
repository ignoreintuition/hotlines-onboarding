#!/bin/bash
set -euo pipefail
cd "$(dirname "$0")"
PLUGIN_NAME="hotlines-onboarding"
# Package runtime files only; never ship site exports, tests, or local context.
mkdir -p build
STAGING_DIR=$(mktemp -d)
trap 'rm -rf "$STAGING_DIR"' EXIT
mkdir -p "$STAGING_DIR/$PLUGIN_NAME/includes"
cp hotlines-onboarding.php "$STAGING_DIR/$PLUGIN_NAME/"
cp includes/*.php "$STAGING_DIR/$PLUGIN_NAME/includes/"
cp docs/SETUP.md "$STAGING_DIR/$PLUGIN_NAME/README.md"
(cd "$STAGING_DIR" && zip -qr "$PLUGIN_NAME.zip" "$PLUGIN_NAME")
mv "$STAGING_DIR/$PLUGIN_NAME.zip" "build/$PLUGIN_NAME.zip"
echo "Created build/$PLUGIN_NAME.zip"
