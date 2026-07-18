#!/usr/bin/env python3
"""Generate CHANGELOG.md from git commits with subject 'release VERSION'."""
from __future__ import annotations

import argparse
import re
import subprocess
import sys
from datetime import date
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def run(args: list[str]) -> str:
    return subprocess.check_output(args, cwd=ROOT, text=True).strip()


def release_commits() -> list[tuple[str, str, str]]:
    out = run(["git", "log", "--grep=^release ", "--pretty=format:%H\t%s\t%ci"])
    rows = []
    for line in out.splitlines():
        if not line.strip():
            continue
        h, s, d = line.split("\t", 2)
        m = re.match(r"release\s+(\S+)", s)
        if not m:
            continue
        rows.append((h, m.group(1), d[:10]))
    return rows


def collect_notes(newer: str, older: str | None) -> list[str]:
    rng = f"{older}..{newer}" if older else newer
    try:
        # Not first-parent: releases often FF from_dev, so ticket notes live on into_dev merges.
        out = run(["git", "log", "--pretty=format:%s%n%b%n==END==", rng])
    except subprocess.CalledProcessError:
        return []
    merge_notes: list[str] = []
    subject_notes: list[str] = []
    seen_merge: set[str] = set()
    seen_subj: set[str] = set()

    def add(bucket: list[str], seen: set[str], ln: str) -> None:
        if ln in seen:
            return
        if re.match(r"^Merge ", ln):
            return
        if ln.startswith("Co-authored-by:"):
            return
        if re.fullmatch(r"[0-9a-f]{7,40}", ln):
            return
        if not re.search(r"MELD-\d+", ln) and len(ln) < 12:
            return
        seen.add(ln)
        bucket.append(ln)

    for block in out.split("==END=="):
        block = block.strip()
        if not block:
            continue
        lines = [ln.strip() for ln in block.splitlines() if ln.strip()]
        if not lines:
            continue
        subj = lines[0]
        body = lines[1:]
        if re.match(r"^release\s+\S+", subj):
            continue
        if re.match(r"^Merge (branch|pull request) ", subj):
            for ln in body:
                add(merge_notes, seen_merge, ln)
        elif re.match(r"^MELD-\d+", subj):
            add(subject_notes, seen_subj, subj)
    return merge_notes if merge_notes else subject_notes


def write_changelog(pending_version: str | None = None) -> Path:
    out_path = ROOT / "CHANGELOG.md"
    parts = [
        "# Changelog",
        "",
        "Automatisch aus Git-Release-Commits erzeugt.",
        "",
    ]
    if pending_version:
        last = release_commits()
        older = last[0][0] if last else None
        notes = collect_notes("HEAD", older)
        parts.append(f"## {pending_version} ({date.today().isoformat()})")
        parts.append("")
        if notes:
            parts.extend(f"- {n}" for n in notes)
        else:
            parts.append(f"- Release {pending_version}")
        parts.append("")

    releases = release_commits()
    for i, (h, version, day) in enumerate(releases):
        older = releases[i + 1][0] if i + 1 < len(releases) else None
        notes = collect_notes(h, older)
        parts.append(f"## {version} ({day})")
        parts.append("")
        if notes:
            parts.extend(f"- {n}" for n in notes)
        else:
            parts.append("- (keine weiteren Notizen)")
        parts.append("")

    out_path.write_text("\n".join(parts), encoding="utf-8")
    return out_path


def main() -> int:
    p = argparse.ArgumentParser()
    p.add_argument("--pending", metavar="VERSION", help="Prepend pending release (for makeVersion)")
    args = p.parse_args()
    path = write_changelog(args.pending)
    print(f"CHANGELOG written: {path}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
