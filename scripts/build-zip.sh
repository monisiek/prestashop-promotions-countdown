#!/usr/bin/env bash
set -euo pipefail

MODULE_NAME="promotionscountdown"
VERSION="1.1.0"
WORKDIR="/workspace"
BUILD_DIR="$WORKDIR/build/$MODULE_NAME"
ZIP_PATH="$WORKDIR/${MODULE_NAME}-${VERSION}.zip"

rm -f "$ZIP_PATH"
rm -rf "$WORKDIR/build"
mkdir -p "$BUILD_DIR"

# Copy module files into correct folder structure
cp "$WORKDIR/$MODULE_NAME.php" "$BUILD_DIR/" 2>/dev/null || true
cp "$WORKDIR/index.php" "$BUILD_DIR/"
cp "$WORKDIR/README.md" "$BUILD_DIR/" 2>/dev/null || true
cp "$WORKDIR/migrate_database.php" "$BUILD_DIR/" 2>/dev/null || true

mkdir -p "$BUILD_DIR/sql" "$BUILD_DIR/views"
cp -r "$WORKDIR/sql/." "$BUILD_DIR/sql/" 2>/dev/null || true
cp -r "$WORKDIR/views/." "$BUILD_DIR/views/" 2>/dev/null || true

# Ensure guards exist
for p in "$BUILD_DIR" "$BUILD_DIR/views" "$BUILD_DIR/views/templates" "$BUILD_DIR/views/templates/hook" "$BUILD_DIR/views/js" "$BUILD_DIR/views/css" "$BUILD_DIR/views/img"; do
  if [ ! -f "$p/index.php" ]; then
    mkdir -p "$p"
    cat > "$p/index.php" <<'PHP'
<?php
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Location: ../");
exit;
PHP
  fi
done

cd "$WORKDIR/build" && zip -r -q "$ZIP_PATH" "$MODULE_NAME" && unzip -l "$ZIP_PATH" | cat
echo "\nCreated: $ZIP_PATH"
