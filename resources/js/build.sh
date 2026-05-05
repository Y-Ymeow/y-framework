#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"

echo "Installing dependencies..."
bun install

echo "Building static assets..."
bunx vite build

echo "Build complete. Output: ../dist/"
