#!/bin/bash

set -e

PLUGIN_NAME="hotlines-onboarding"

rm -rf build
mkdir -p build

rsync -av \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='.gitignore' \
  --exclude='build' \
  --exclude='.DS_Store' \
  --exclude='.vscode' \
  --exclude='.idea' \
  ./ "build/$PLUGIN_NAME"

cd build
zip -r "${PLUGIN_NAME}.zip" "$PLUGIN_NAME"

echo "Created build/${PLUGIN_NAME}.zip"
