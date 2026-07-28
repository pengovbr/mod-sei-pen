#!/bin/sh

set -e

npm install -g terser >/dev/null

find /app -name '*.js' | while IFS= read -r file; do
    iconv -f ISO-8859-1 -t UTF-8 "$file" \
    | terser --compress --mangle \
    | iconv -f UTF-8 -t ISO-8859-1 > "$file.tmp"

    mv "$file.tmp" "$file"
done
