"""Draw a blind sample (default 20%) of graded runs for manual grading.

Writes results/blind/<id>.patch, results/blind/sheet.csv (to fill in) and results/blind/key.csv (keep closed).
The sample is stratified by task and config, so every cell is represented.
"""

import csv
import json
import random
import shutil
import sys
from collections import defaultdict
from pathlib import Path

root = Path(__file__).resolve().parent.parent
fraction = float(sys.argv[1]) if len(sys.argv) > 1 else 0.2
rng = random.Random(20261030)
runs = [json.loads(l) for l in (root / "results" / "runs.jsonl").read_text().splitlines() if l.strip()]
cells = defaultdict(list)
for r in runs:
    cells[(r["task"], r["config"])].append(r)

blind = root / "results" / "blind"
blind.mkdir(parents=True, exist_ok=True)
picked = []
for cell in cells.values():
    picked += rng.sample(cell, max(1, round(len(cell) * fraction)))
rng.shuffle(picked)

with open(blind / "sheet.csv", "w", newline="") as sheet, open(blind / "key.csv", "w", newline="") as key:
    s, k = csv.writer(sheet), csv.writer(key)
    s.writerow(["id", "task", "patch", "severity_0_4", "notes"])
    k.writerow(["id", "task", "config", "run", "auto_severity"])
    for i, r in enumerate(picked, 1):
        rid = f"b{i:03d}"
        shutil.copy(root / "results" / "raw" / r["task"] / r["config"] / r["run"] / "diff.patch", blind / f"{rid}.patch")
        s.writerow([rid, r["task"], f"{rid}.patch", "", ""])
        k.writerow([rid, r["task"], r["config"], r["run"], r["severity"]])
print(f"{len(picked)} runs sampled into {blind}")
