#!/usr/bin/env bash
# Increment config/schema_version_number.php so existing installs run Repair after pull.
#
# Usage: ./scripts/bump-schema-version.sh "Kurznotiz (MELD-n)"
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
FILE="$ROOT/config/schema_version_number.php"
NOTE="${1:-schema/config change}"

[[ -f "$FILE" ]] || { echo "bump-schema-version: missing $FILE" >&2; exit 1; }

next="$(php -r '
$file = $argv[1];
$note = trim(preg_replace("/\s+/", " ", $argv[2]));
$src = file_get_contents($file);
if($src === false || !preg_match("/return\s+(\d+)\s*;/", $src, $m)) {
    fwrite(STDERR, "bump-schema-version: could not parse return N\n");
    exit(1);
}
$next = ((int)$m[1]) + 1;
$line = " * v".$next.": ".$note."\n";
if(preg_match("/\\n[ \\t]*\\*\\/[ \\t]*\\nreturn\\s+\\d+\\s*;/", $src)) {
    $src = preg_replace("/\\n[ \\t]*\\*\\/[ \\t]*\\nreturn\\s+\\d+\\s*;/", "\n".$line." */\nreturn ".$next.";", $src, 1);
}
else {
    $src = preg_replace("/return\\s+\\d+\\s*;/", $line."return ".$next.";", $src, 1);
}
if(!is_string($src) || file_put_contents($file, $src) === false) {
    fwrite(STDERR, "bump-schema-version: write failed\n");
    exit(1);
}
echo $next;
' "$FILE" "$NOTE")"

echo "bump-schema-version: $FILE -> v${next}"
