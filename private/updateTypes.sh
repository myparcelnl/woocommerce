#!/usr/bin/env bash

###
# Clones WooCommerce repository and copies all relevant files to enable a decent TypeScript experience.
###

REPO_URL="https://github.com/woocommerce/woocommerce.git"
BRANCH="trunk"
DEST_DIR="private/wc-types"

# Create a temporary directory for cloning
TEMP_DIR=$(mktemp -d)

echo "Cloning woocommerce/woocommerce to $TEMP_DIR"
git clone -b $BRANCH --depth 1 $REPO_URL "$TEMP_DIR"

echo "Searching for files to copy..."

# copy all ts files from the repository to the destination directory, preserving the directory structure.
FILES=$(find -E \
  "$TEMP_DIR/plugins" \
  "$TEMP_DIR/packages" \
  -type f \
  \( \
    -regex '.*\.(ts|tsx|js|jsx)' -o \
    -name 'tsconfig*.json' \
  \) \
  -not \( \
    -name '*.min.*' -o \
    -name '*.spec.*' -o \
    -name '*.test.*' -o \
    -name '*Grunt*' -o \
    -name '*babel*' -o \
    -name '*eslint*' -o \
    -name '*jest*' -o \
    -name '*prettier*' -o \
    -name '*stylelint*' -o \
    -name '*webpack*' -o \
    -path '*/*-tests/*' -o \
    -path '*/.wireit/*' -o \
    -path '*/bin/*' -o \
    -path '*/build/*' -o \
    -path '*/cache/*' -o \
    -path '*/dist/*' -o \
    -path '*/docs/*' -o \
    -path '*/includes/*' -o \
    -path '*/spec/*' -o \
    -path '*/specs/*' -o \
    -path '*/test/*' -o \
    -path '*/tests/*' -o \
    -path '*/userscripts/*' \
  \))

echo "Copying files... This can take a while."

for FILE in $FILES; do
  DEST_FILE="$DEST_DIR/$(echo "$FILE" | sed "s|$TEMP_DIR/||")"
  DEST_DIRNAME=$(dirname "$DEST_FILE")

  mkdir -p "$DEST_DIRNAME"
  cp "$FILE" "$DEST_FILE"
done

cp "$TEMP_DIR"/tsconfig*.json "$DEST_DIR"

echo "Cleaning up..."
rm -rf "$TEMP_DIR"

echo "Files copied successfully."

