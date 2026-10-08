#!/usr/bin/env sh
# Render seluruh Mermaid ke PNG skala tinggi (latar putih) dan SVG.
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
INPUT_DIR="$SCRIPT_DIR/diagram"
OUTPUT_DIR="$INPUT_DIR/out"
mkdir -p "$OUTPUT_DIR"

for input in "$INPUT_DIR"/*.mmd; do
  [ -f "$input" ] || continue
  name=$(basename "$input" .mmd)
  npx --yes @mermaid-js/mermaid-cli -i "$input" -o "$OUTPUT_DIR/$name.png" -b white -s 3
  npx --yes @mermaid-js/mermaid-cli -i "$input" -o "$OUTPUT_DIR/$name.svg" -b white -s 3
done
