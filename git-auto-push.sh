#!/usr/bin/env bash

set -u

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INTERVAL_SECONDS="${GIT_AUTO_PUSH_INTERVAL_SECONDS:-240}"
LOG_FILE="${GIT_AUTO_PUSH_LOG:-$REPO_DIR/storage/logs/git-auto-push.log}"
PID_FILE="${GIT_AUTO_PUSH_PID:-$REPO_DIR/storage/logs/git-auto-push.pid}"

mkdir -p "$(dirname "$LOG_FILE")"

log() {
    printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*" >> "$LOG_FILE"
}

if [[ -f "$PID_FILE" ]]; then
    existing_pid="$(cat "$PID_FILE" 2>/dev/null || true)"

    if [[ -n "$existing_pid" ]] && kill -0 "$existing_pid" 2>/dev/null; then
        log "git auto-push is already running with PID $existing_pid"
        exit 0
    fi
fi

echo "$$" > "$PID_FILE"

cleanup() {
    rm -f "$PID_FILE"
}

trap cleanup EXIT INT TERM

cd "$REPO_DIR" || {
    log "could not enter repo directory: $REPO_DIR"
    exit 1
}

log "git auto-push started for $REPO_DIR with ${INTERVAL_SECONDS}s interval"

while true; do
    branch="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || true)"

    if [[ -z "$branch" || "$branch" == "HEAD" ]]; then
        log "no active git branch found; retrying"
        sleep "$INTERVAL_SECONDS"
        continue
    fi

    upstream="$(git rev-parse --abbrev-ref --symbolic-full-name '@{u}' 2>/dev/null || true)"

    if [[ -z "$upstream" ]]; then
        log "no upstream for $branch; running: git push -u origin $branch"

        if git push -u origin "$branch" >> "$LOG_FILE" 2>&1; then
            log "push completed and upstream set for $branch"
        else
            log "push failed for $branch; will retry"
        fi

        sleep "$INTERVAL_SECONDS"
        continue
    fi

    ahead_count="$(git rev-list --count "$upstream..HEAD" 2>/dev/null || true)"

    if [[ -z "$ahead_count" ]]; then
        log "could not compare $branch with upstream $upstream; running git push anyway"

        if git push >> "$LOG_FILE" 2>&1; then
            log "push completed for $branch"
        else
            log "push failed for $branch; will retry"
        fi
    elif [[ "$ahead_count" -gt 0 ]]; then
        log "$branch is ahead of $upstream by $ahead_count commit(s); running git push"

        if git push >> "$LOG_FILE" 2>&1; then
            log "push completed for $branch"
        else
            log "push failed for $branch; will retry"
        fi
    else
        log "nothing committed to push for $branch; continuing"
    fi

    sleep "$INTERVAL_SECONDS"
done
