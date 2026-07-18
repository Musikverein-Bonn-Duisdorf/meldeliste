#!/bin/bash
# Wrapper: generate CHANGELOG.md from release commits.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
exec python3 "$ROOT/scripts/generateChangelog.py" "$@"
