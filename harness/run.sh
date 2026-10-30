#!/usr/bin/env bash
# Usage: harness/run.sh <task-dir-name> <A|B|C|D> <run-id>
# Runs one agent run in an isolated copy of the app and grades it.
set -euo pipefail
TASK=$1 CONFIG=$2 RUN=$3
ROOT=$(cd "$(dirname "$0")/.." && pwd)
H=$ROOT/harness
MODEL=${MODEL:-claude-haiku-5-5}
EFFORT=${EFFORT:-medium}
BUDGET=${BUDGET:-1.00}
RUNS_DIR=${RUNS_DIR:-/tmp/agent-runs}
OUT=$ROOT/results/raw/$TASK/$CONFIG/$RUN
WORK=$RUNS_DIR/$TASK-$CONFIG-$RUN
[ -f "$ROOT/.env.local" ] && set -a && . "$ROOT/.env.local" && set +a
[[ -n ${ANTHROPIC_API_KEY:-} || -n ${CLAUDE_CODE_OAUTH_TOKEN:-} ]] || { echo "Set ANTHROPIC_API_KEY or CLAUDE_CODE_OAUTH_TOKEN (claude setup-token), e.g. in .env.local" >&2; exit 1; }

[ -e "$OUT/grade.json" ] && { echo "already done: $OUT"; exit 0; }
rm -rf "$WORK" "$OUT"; mkdir -p "$OUT" "$RUNS_DIR"
cp -a "$ROOT/app-template" "$WORK"
(cd "$WORK" && git init -q && git add -A && git -c user.name=harness -c user.email=harness@localhost commit -qm initial)

CFG_DIR=$(mktemp -d)
args=(-p "$(cat "$H/tasks/$TASK/prompt.md")"
  --output-format stream-json --verbose
  --model "$MODEL" --effort "$EFFORT" --max-budget-usd "$BUDGET"
  --permission-mode dontAsk
  --allowedTools "Read Edit Write Glob Grep Bash TodoWrite")

settings='{"sandbox":{"enabled":true,"autoAllowBashIfSandboxed":true}'
if [[ $CONFIG == B || $CONFIG == D ]]; then
  python3 -c 'import sys; s=open(sys.argv[1]).read(); print(s.replace("{{CRITERIA}}", open(sys.argv[2]).read().strip()))' \
    "$H/configs/B/spec.md" "$H/tasks/$TASK/criteria.md" > "$OUT/spec.md"
  args+=(--append-system-prompt-file "$OUT/spec.md")
  mapfile -t deny < "$H/configs/B/disallowed-tools.txt"
  args+=(--disallowedTools "${deny[@]}")
fi
if [[ $CONFIG == C || $CONFIG == D ]]; then
  settings+=',"hooks":{"PostToolUse":[{"matcher":"Edit|Write|MultiEdit","hooks":[{"type":"command","command":"'"$H"'/hooks/post-edit.sh"}]}],"Stop":[{"hooks":[{"type":"command","command":"'"$H"'/hooks/stop.sh"}]}]}'
fi
settings+='}'
echo "$settings" > "$OUT/settings.json"
args+=(--settings "$OUT/settings.json")

printf '%s\n' "$(claude --version)" > "$OUT/claude-version.txt"
start=$(date +%s)
(cd "$WORK" && CLAUDE_CONFIG_DIR=$CFG_DIR CLAUDE_CODE_DISABLE_AUTO_MEMORY=1 DISABLE_AUTOUPDATER=1 \
  HOOK_LOG=$OUT/hooks.log timeout 1800 claude "${args[@]}" > "$OUT/stream.jsonl" 2> "$OUT/stderr.log") || echo "claude exited $?" >> "$OUT/stderr.log"
echo $(( $(date +%s) - start )) > "$OUT/duration_s.txt"
rm -rf "$CFG_DIR"

(cd "$WORK" && git add -A && git diff --cached > "$OUT/diff.patch")
python3 -I "$H/grade.py" "$ROOT" "$TASK" "$CONFIG" "$RUN" "$WORK" "$OUT"
[[ ${KEEP_WORK:-0} == 1 ]] || rm -rf "$WORK"
