#!/usr/bin/env bash
# Meldeliste: pull / push / PR / merge helpers.
#
# Branch names live ONLY here. Agents must call this script instead of typing
# "origin" + branch as one token (e.g. never "origin_dev" / "--base_dev").
#
# Usage:
#   ./scripts/git-flow.sh sync-dev
#   ./scripts/git-flow.sh push
#   ./scripts/git-flow.sh pr-dev --title "MELD-n: …" --body "## Summary\n…"
#   ./scripts/git-flow.sh merge-pr [NUMBER]
#   ./scripts/git-flow.sh release-master
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

REMOTE="${MELDELISTE_REMOTE:-origin}"
BRANCH_DEV="${MELDELISTE_BRANCH_DEV:-dev}"
BRANCH_MASTER="${MELDELISTE_BRANCH_MASTER:-master}"

die() { echo "git-flow: $*" >&2; exit 1; }

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "missing command: $1"
}

current_branch() {
  git rev-parse --abbrev-ref HEAD
}

ensure_clean_or_warn() {
  if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "git-flow: warning — working tree has uncommitted changes" >&2
  fi
}

cmd_sync_dev() {
  ensure_clean_or_warn
  git fetch "$REMOTE" "$BRANCH_DEV"
  git merge "${REMOTE}/${BRANCH_DEV}" -m "merge ${REMOTE}/${BRANCH_DEV}"
  echo "git-flow: synced $(current_branch) with ${REMOTE}/${BRANCH_DEV}"
}

cmd_fetch_dev() {
  git fetch "$REMOTE" "$BRANCH_DEV"
  git log -1 --oneline "${REMOTE}/${BRANCH_DEV}"
}

cmd_push() {
  local br
  br="$(current_branch)"
  [[ "$br" != "$BRANCH_DEV" && "$br" != "$BRANCH_MASTER" && "$br" != "HEAD" ]] \
    || die "refusing to push protected/detached ref: $br"
  git push -u "$REMOTE" HEAD
  echo "git-flow: pushed $br"
}

cmd_pr_dev() {
  need_cmd gh
  local title="" body="" body_file=""
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --title) title="${2:-}"; shift 2 ;;
      --body) body="${2:-}"; shift 2 ;;
      --body-file) body_file="${2:-}"; shift 2 ;;
      *) die "unknown pr-dev arg: $1" ;;
    esac
  done
  [[ -n "$title" ]] || die "pr-dev requires --title"
  cmd_push
  if [[ -n "$body_file" ]]; then
    [[ -f "$body_file" ]] || die "body file not found: $body_file"
    gh pr create --base="$BRANCH_DEV" --title="$title" --body-file="$body_file"
  elif [[ -n "$body" ]]; then
    gh pr create --base="$BRANCH_DEV" --title="$title" --body="$body"
  else
    gh pr create --base="$BRANCH_DEV" --title="$title" --fill
  fi
}

cmd_merge_pr() {
  need_cmd gh
  local num="${1:-}"
  if [[ -z "$num" ]]; then
    num="$(gh pr view --json number -q .number 2>/dev/null || true)"
  fi
  [[ -n "$num" ]] || die "no PR number (pass NUMBER or run on a branch with an open PR)"
  # Match recent repo style: merge commit into BRANCH_DEV.
  gh pr merge "$num" --merge --delete-branch=false
  echo "git-flow: merged PR #$num"
  git fetch "$REMOTE" "$BRANCH_DEV"
  git log -1 --oneline "${REMOTE}/${BRANCH_DEV}"
}

cmd_release_master() {
  need_cmd gh
  ensure_clean_or_warn
  local cur
  cur="$(current_branch)"
  git fetch "$REMOTE" "$BRANCH_DEV" "$BRANCH_MASTER"
  git checkout "$BRANCH_MASTER"
  git pull --ff-only "$REMOTE" "$BRANCH_MASTER"
  git merge "${REMOTE}/${BRANCH_DEV}" -m "merge ${BRANCH_DEV} into ${BRANCH_MASTER}"
  ./makeVersion.sh
  git push "$REMOTE" "$BRANCH_MASTER"
  # Keep BRANCH_DEV release files in sync.
  git checkout "$BRANCH_DEV"
  git pull --ff-only "$REMOTE" "$BRANCH_DEV"
  git merge "$BRANCH_MASTER" -m "sync release files from ${BRANCH_MASTER}"
  git push "$REMOTE" "$BRANCH_DEV"
  git checkout "$cur"
  echo "git-flow: released ${BRANCH_MASTER}; synced ${BRANCH_DEV}"
}

cmd_help() {
  cat <<EOF
Usage: ./scripts/git-flow.sh <command> [args]

  sync-dev              fetch + merge ${REMOTE}/${BRANCH_DEV} into current branch
  fetch-dev             fetch ${BRANCH_DEV} and show tip
  push                  push -u current feature branch
  pr-dev --title T [--body B | --body-file F]
                        push + open PR into ${BRANCH_DEV}
  merge-pr [N]          merge PR N (or current branch PR) into its base
  release-master        ${BRANCH_DEV} → ${BRANCH_MASTER}, makeVersion, push, sync ${BRANCH_DEV}

Env overrides: MELDELISTE_REMOTE, MELDELISTE_BRANCH_DEV, MELDELISTE_BRANCH_MASTER
EOF
}

main() {
  local cmd="${1:-help}"
  shift || true
  case "$cmd" in
    sync-dev|pull-dev) cmd_sync_dev "$@" ;;
    fetch-dev) cmd_fetch_dev "$@" ;;
    push) cmd_push "$@" ;;
    pr-dev) cmd_pr_dev "$@" ;;
    merge-pr) cmd_merge_pr "$@" ;;
    release-master) cmd_release_master "$@" ;;
    help|-h|--help) cmd_help ;;
    *) die "unknown command: $cmd (try help)" ;;
  esac
}

main "$@"
