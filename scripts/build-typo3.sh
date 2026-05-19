#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
if command -v ddev >/dev/null 2>&1 && [ -d .ddev ]; then
  exec ddev init "$@"
fi
echo "DDEV required. Run: ddev init"
exit 1
