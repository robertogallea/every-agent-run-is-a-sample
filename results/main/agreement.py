"""Agreement between manual (blind) and automatic severity grades: exact agreement and quadratic-weighted Cohen's kappa.

Run: python3 -I agreement.py <experiment-repo>/results/main/blind/sheet.csv <experiment-repo>/results/main/blind-key.csv
"""

import csv
import sys

sheet = {r["id"]: r for r in csv.DictReader(open(sys.argv[1]))}
key = {r["id"]: r for r in csv.DictReader(open(sys.argv[2]))}
missing = [i for i, r in sheet.items() if not r["severity_0_4"].strip()]
if missing:
    sys.exit(f"{len(missing)} rows not graded yet, e.g. {missing[:5]}")

pairs = [(int(sheet[i]["severity_0_4"]), int(key[i]["auto_severity"])) for i in sheet]
n, K = len(pairs), 5
exact = sum(m == a for m, a in pairs) / n

obs = [[0] * K for _ in range(K)]
for m, a in pairs:
    obs[m][a] += 1
rows = [sum(obs[i]) for i in range(K)]
cols = [sum(obs[i][j] for i in range(K)) for j in range(K)]
w = lambda i, j: (i - j) ** 2 / (K - 1) ** 2
num = sum(w(i, j) * obs[i][j] for i in range(K) for j in range(K))
den = sum(w(i, j) * rows[i] * cols[j] / n for i in range(K) for j in range(K))
kappa = 1 - num / den if den else float("nan")

print(f"n={n}  exact agreement {exact:.3f}  quadratic-weighted kappa {kappa:.3f}")
print("disagreements (id, task, config, manual, auto):")
for i in sheet:
    m, a = int(sheet[i]["severity_0_4"]), int(key[i]["auto_severity"])
    if m != a:
        print(f"  {i} {key[i]['task']} {key[i]['config']} manual {m} auto {a}  {sheet[i]['notes']}")
