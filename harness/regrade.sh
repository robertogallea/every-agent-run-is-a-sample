#!/usr/bin/env bash
# Usage: RESULTS_DIR=results/<set> harness/regrade.sh <run-id-glob>
# Rebuilds each matching run from its saved diff.patch and grades it again (e.g. after a grader or hidden-test fix).
# Rewrites RESULTS_DIR/runs.jsonl for the matching runs only.
set -euo pipefail
GLOB=$1
ROOT=$(cd "$(dirname "$0")/.." && pwd)
RESULTS_DIR=${RESULTS_DIR:-$ROOT/results}
export RESULTS_DIR
W=${RUNS_DIR:-/tmp/agent-runs}/regrade
for out in "$ROOT"/results/raw/*/*/$GLOB; do
  [ -f "$out/diff.patch" ] && [ -f "$out/grade.json" ] || continue
  run=$(basename "$out"); config=$(basename "$(dirname "$out")"); task=$(basename "$(dirname "$(dirname "$out")")")
  python3 - "$RESULTS_DIR/runs.jsonl" "$task" "$config" "$run" <<'PY'
import json, sys
p, t, c, r = sys.argv[1:]
try:
    rows = [l for l in open(p).read().splitlines() if l.strip()]
except FileNotFoundError:
    rows = []
keep = [l for l in rows if (json.loads(l)["task"], json.loads(l)["config"], json.loads(l)["run"]) != (t, c, r)]
open(p, "w").write("".join(l + "\n" for l in keep))
PY
  rm -rf "$W" && cp -a "$ROOT/app-template" "$W"
  (cd "$W" && git init -q && git add -A && git -c user.name=h -c user.email=h@h commit -qm init && { [ -s "$out/diff.patch" ] && git apply "$out/diff.patch" || true; } && git add -A)
  mv "$out/grade.json" "$out/grade.prev.json"
  python3 -I "$ROOT/harness/grade.py" "$ROOT" "$task" "$config" "$run" "$W" "$out"
done
rm -rf "$W"
