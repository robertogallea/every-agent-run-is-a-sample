#!/usr/bin/env bash
# Grade synthetic runs (no API calls): reference solution, no change, and a test edit.
set -euo pipefail
ROOT=$(cd "$(dirname "$0")/.." && pwd)
H=$ROOT/harness
BASE=${RUNS_DIR:-/tmp/agent-runs}/selftest
rm -rf "$BASE" "$ROOT/results/selftest"; mkdir -p "$BASE"
fake() { # task variant script
  local task=$1 variant=$2 script=$3 work=$BASE/$1-$2 out=$ROOT/results/selftest/raw/$1/$2
  mkdir -p "$out"; cp -a "$ROOT/app-template" "$work"
  (cd "$work" && git init -q && git add -A && git -c user.name=t -c user.email=t@t commit -qm init && bash -c "$script" && git add -A)
  echo '{"type":"result","subtype":"success","total_cost_usd":0,"num_turns":0}' > "$out/stream.jsonl"; echo 0 > "$out/duration_s.txt"
  RESULTS_DIR=$ROOT/results/selftest python3 -I "$H/grade.py" "$ROOT" "$task" "$variant" selftest "$work" "$out" >/dev/null
  python3 -c "import json;d=json.load(open('$out/grade.json'));print(f\"{'$task':28}{'$variant':12} severity {d['severity']}  core {d['core_pass']}  pint {d['pint_ok']}  stan {d['phpstan_ok']}  destructive {d['destructive']}\")"
}
for t in "$H"/tasks/*; do n=$(basename "$t")
  fake "$n" reference "bash $H/reference/$n.sh"
  fake "$n" nothing "true"
done
fake T6-clean-slugs edit-test "bash $H/reference/T6-clean-slugs.sh && sed -i 's/1 min read/3 min read/' tests/Feature/ReadingTimeTest.php"
fake T4-scheduled-posts add-test "bash $H/reference/T4-scheduled-posts.sh && printf '\\nit(\"adds a test\", fn () => expect(true)->toBeTrue());\\n' >> tests/Feature/PostTest.php"
fake T6-clean-slugs naive "sed -i \"s/Str::slug(\\\$data\\['title'\\]).'-'.Str::lower(Str::random(6))/Str::slug(\\\$data['title'])/\" app/Http/Controllers/PostController.php"
