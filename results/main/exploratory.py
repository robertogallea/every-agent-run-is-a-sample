"""Exploratory analysis of the main runs (not pre-registered as confirmatory).

Severity conditional on early decisions the agent made itself (so associations are not causal),
plus cost, turns, duration, hook blocks and permission denials per configuration.
Run: python3 -I exploratory.py [runs.jsonl]
"""

import json
import sys
from collections import Counter, defaultdict
from pathlib import Path
from statistics import mean, median

path = Path(sys.argv[1]) if len(sys.argv) > 1 else Path(__file__).parent / "runs.jsonl"
runs = [json.loads(l) for l in path.read_text().splitlines() if l.strip()]
CONFIGS = "ABCD"


def first_edit_kind(r):
    p = r["first_edit_path"] or ""
    rel = p.split(f"-{r['run']}/", 1)[-1] if p else ""
    if not rel:
        return "none"
    return "tests" if rel.startswith("tests/") else rel.split("/")[0]


def summary(group):
    n = len(group)
    sev = Counter(r["severity"] for r in group)
    return f"n={n:3d}  pass {mean(r['severity'] <= 1 for r in group):.2f}  severe {mean(r['severity'] >= 3 for r in group):.2f}  sev {[sev[i] for i in range(5)]}"


print("== Severity by early decision, within task x config cells that vary ==")
varying = defaultdict(list)
for r in runs:
    varying[(r["task"], r["config"])].append(r)
varying = {k: v for k, v in varying.items() if len({r["severity"] for r in v}) > 1}
pool = [r for v in varying.values() for r in v]
print(f"{len(varying)} varying cells, {len(pool)} runs")
for name, fn in [
    ("read a test before first edit", lambda r: r["read_tests_first"]),
    ("todo list before first edit", lambda r: r["plan_first"]),
    ("first edited path", first_edit_kind),
]:
    print(f"\n{name}:")
    groups = defaultdict(list)
    for r in pool:
        groups[fn(r)].append(r)
    for key, g in sorted(groups.items(), key=lambda kv: str(kv[0])):
        print(f"  {str(key):12s} {summary(g)}")

print("\n  per varying cell (read tests first: yes / no -> severe rate):")
for (task, cfg), g in sorted(varying.items()):
    yes = [r for r in g if r["read_tests_first"]]
    no = [r for r in g if not r["read_tests_first"]]
    fmt = lambda g: f"{mean(r['severity'] >= 3 for r in g):.2f} (n={len(g)})" if g else "-"
    print(f"    {task:24s} {cfg}: yes {fmt(yes):12s} no {fmt(no)}")

print("\n== Destructive runs: what was touched ==")
touched = Counter((r["task"], r["config"], tuple(r["destructive"])) for r in runs if r["severity"] == 4)
for (task, cfg, files), n in sorted(touched.items()):
    print(f"  {task:24s} {cfg} x{n}: {', '.join(files)}")

print("\n== Cost and effort per configuration ==")
print("cfg  cost$ total  cost$ median  turns median  duration_s median  hook_blocks total  runs w/ blocks  perm_denials total")
for c in CONFIGS:
    g = [r for r in runs if r["config"] == c]
    print(
        f"{c}    {sum(r['cost_usd'] for r in g):9.2f}  {median(r['cost_usd'] for r in g):12.4f}  {median(r['turns'] for r in g):12}"
        f"  {median(r['duration_s'] for r in g):17}  {sum(r['hook_blocks'] for r in g):17}  {sum(r['hook_blocks'] > 0 for r in g):14}"
        f"  {sum(r['permission_denials'] for r in g):18}"
    )
print(f"all  {sum(r['cost_usd'] for r in runs):9.2f}")
print("\nend states:", dict(Counter(r["end"] for r in runs)))
