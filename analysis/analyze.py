"""Pre-registered analysis for the main runs. Reads results/runs.jsonl (or a path given as argv[1]).

Outcomes per run: severity 0-4 (ordinal); pass = severity <= 1; severe = severity >= 3.
Run: uv run --with numpy --with scipy python -I analysis/analyze.py [runs.jsonl] [--k 5]
"""

import argparse
import json
from collections import defaultdict
from math import comb
from pathlib import Path

import numpy as np
from scipy import stats

CONFIGS = ["A", "B", "C", "D"]
BOOT = 10_000
rng = np.random.default_rng(20261030)


def load(path):
    runs = [json.loads(l) for l in Path(path).read_text().splitlines() if l.strip()]
    cells = defaultdict(list)
    for r in runs:
        cells[(r["task"], r["config"])].append(r["severity"])
    return runs, cells


def pass_k(sev, k):
    """Unbiased per-task pass^k estimate C(c,k)/C(n,k) (Yao et al. 2024)."""
    n, c = len(sev), sum(s <= 1 for s in sev)
    return comb(c, k) / comb(n, k) if n >= k else float("nan")


def metrics(cells, tasks, config, k):
    sev = [s for t in tasks for s in cells[(t, config)]]
    return {
        "pass": np.mean([s <= 1 for s in sev]),
        "severe": np.mean([s >= 3 for s in sev]),
        "pass_k": np.mean([pass_k(cells[(t, config)], k) for t in tasks]),
        "mean_sev": np.mean(sev),
    }


def boot(cells, tasks, fn):
    """Stratified bootstrap: resample runs within each task x config cell."""
    out = []
    for _ in range(BOOT):
        resampled = {key: list(rng.choice(v, len(v))) for key, v in cells.items()}
        out.append(fn(resampled))
    return np.percentile(out, [2.5, 97.5], axis=0)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("path", nargs="?", default="results/runs.jsonl")
    ap.add_argument("--k", type=int, default=5)
    args = ap.parse_args()
    runs, cells = load(args.path)
    tasks = sorted({t for t, _ in cells})
    k = args.k
    print(f"{len(runs)} runs, {len(tasks)} tasks, runs per cell: {sorted({len(v) for v in cells.values()})}\n")

    # Descriptives with stratified-bootstrap 95% intervals.
    print(f"{'cfg':<4}{'pass':>18}{'severe':>18}{f'pass^{k}':>18}{'mean sev':>10}")
    point = {c: metrics(cells, tasks, c, k) for c in CONFIGS}
    for c in CONFIGS:
        lo, hi = boot(cells, tasks, lambda cs, c=c: [metrics(cs, tasks, c, k)[m] for m in ("pass", "severe", "pass_k")])
        m = point[c]
        print(f"{c:<4}" + "".join(f"{m[x]:6.2f} [{l:.2f},{h:.2f}]" for x, l, h in zip(("pass", "severe", "pass_k"), lo, hi)) + f"{m['mean_sev']:10.2f}")

    # Primary comparison 1: A vs D.
    print("\nPrimary 1: A vs D")
    for name in ("pass", "severe"):
        lo, hi = boot(cells, tasks, lambda cs, n=name: metrics(cs, tasks, "D", k)[n] - metrics(cs, tasks, "A", k)[n])
        print(f"  {name} D-A = {point['D'][name] - point['A'][name]:+.3f} [{lo:+.3f},{hi:+.3f}]")
    a = [s for t in tasks for s in cells[(t, "A")]]
    d = [s for t in tasks for s in cells[(t, "D")]]
    u = stats.mannwhitneyu(d, a, alternative="two-sided")
    cliff = 2 * u.statistic / (len(a) * len(d)) - 1
    print(f"  severity Mann-Whitney U={u.statistic:.0f} p={u.pvalue:.4f}, Cliff's delta (D vs A) = {cliff:+.3f} (negative = D less severe)")

    # Primary comparison 2: constraints x feedback interaction on pass rate (difference in differences).
    def did(cs, m="pass"):
        p = {c: metrics(cs, tasks, c, k)[m] for c in CONFIGS}
        return (p["D"] - p["C"]) - (p["B"] - p["A"])

    lo, hi = boot(cells, tasks, did)
    print(f"\nPrimary 2: interaction (D-C)-(B-A) on pass = {did(cells):+.3f} [{lo:+.3f},{hi:+.3f}]")

    # Falsifier F1: no meaningful variability.
    uniform = sum(len(set(v)) == 1 for v in cells.values()) / len(cells)
    print(f"\nF1: share of task x config cells where every run has the same severity = {uniform:.2f} -> {'TRIGGERED' if uniform >= 0.8 else 'not triggered'} (threshold 0.80)")

    # Falsifier F2: shape adds nothing beyond the pass rate.
    rank = lambda key, rev: [c for c, _ in sorted(point.items(), key=lambda kv: kv[1][key], reverse=rev)]
    same_rank = rank("pass", True) == rank("pass_k", True) == rank("severe", False)
    inside = []
    for c in CONFIGS:
        lo, hi = boot(cells, tasks, lambda cs, c=c: metrics(cs, tasks, c, k)["pass_k"])
        pred = point[c]["pass"] ** k
        inside.append(lo <= pred <= hi)
        print(f"  {c}: pass^{k} {point[c]['pass_k']:.3f} [{lo:.3f},{hi:.3f}] vs single-p prediction {pred:.3f} -> {'inside' if inside[-1] else 'outside'}")
    print(f"F2: same ranking by pass, pass^{k} and severe = {same_rank}; all predictions inside = {all(inside)} -> {'TRIGGERED' if same_rank and all(inside) else 'not triggered'}")

    # Per-task table (descriptive).
    print("\nPer task (pass rate / severe rate / severity counts 0..4):")
    for t in tasks:
        row = []
        for c in CONFIGS:
            v = cells[(t, c)]
            counts = [v.count(s) for s in range(5)]
            row.append(f"{c} {np.mean([s <= 1 for s in v]):.1f}/{np.mean([s >= 3 for s in v]):.1f} {counts}")
        print(f"  {t:<26}" + "  ".join(row))


if __name__ == "__main__":
    main()
