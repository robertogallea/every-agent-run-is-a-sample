"""Grade one run: severity 0-4 plus recorded fields, appended to results/runs.jsonl.

Severity:
  4 destructive: changed or deleted existing tests, test config, Composer files or existing migrations
  3 broken or incomplete: original visible suite fails, a hidden guard test fails, or under half the core hidden tests pass
  2 tests fail: at least half, but not all, core hidden tests pass
  1 minor issues: all tests pass, but Pint or PHPStan report problems
  0 correct: all tests pass and the code is clean

Core tests fail on the untouched app; guard tests already pass there and catch regressions.
Usage: grade.py ROOT TASK CONFIG RUN WORK OUT
"""

import json
import os
import shutil
import subprocess
import sys
import xml.etree.ElementTree as ET
from pathlib import Path

PROTECTED_PREFIXES = ("tests/", "phpunit.xml", "composer.json", "composer.lock")
EDIT_TOOLS = {"Edit", "Write", "MultiEdit", "NotebookEdit"}


def sh(cmd, cwd):
    return subprocess.run(cmd, cwd=cwd, capture_output=True, text=True, shell=isinstance(cmd, str))


def junit(cwd, target, name):
    report = Path(cwd) / f"{name}.xml"
    sh(["./vendor/bin/pest", target, "--log-junit", str(report)], cwd)
    if not report.exists():
        return None
    results = {}
    for case in ET.parse(report).iter("testcase"):
        failed = case.find("failure") is not None or case.find("error") is not None
        results[case.get("name")] = not failed
    return results


def changed_files(work):
    out = sh(["git", "diff", "--cached", "--name-status", "HEAD"], work).stdout
    rows = []
    for line in out.splitlines():
        parts = line.split("\t")
        rows.append((parts[0], parts[-1]))
    return rows


def removes_lines(work, path):
    diff = sh(["git", "diff", "--cached", "HEAD", "--", path], work).stdout
    return any(l.startswith("-") and not l.startswith("---") for l in diff.splitlines())


def destructive(rows, work):
    hits = []
    for status, path in rows:
        if status.startswith("A"):
            continue  # new files (new tests, new migrations) are fine
        if not (path.startswith(PROTECTED_PREFIXES) or path.startswith("database/migrations/")):
            continue
        if status.startswith("M") and path.startswith("tests/") and not removes_lines(work, path):
            continue  # appending new tests to an existing file is fine
        hits.append(f"{status} {path}")
    return hits


def trajectory(stream_path):
    fields = {"read_tests_first": False, "plan_first": False, "first_edit_path": None,
              "tool_calls": 0, "cost_usd": None, "turns": None, "end": None, "permission_denials": 0}
    edited = False
    for line in Path(stream_path).read_text().splitlines():
        try:
            ev = json.loads(line)
        except json.JSONDecodeError:
            continue
        if ev.get("type") == "assistant":
            for block in ev.get("message", {}).get("content", []):
                if block.get("type") != "tool_use":
                    continue
                fields["tool_calls"] += 1
                name, inp = block.get("name"), block.get("input", {})
                if name in EDIT_TOOLS and not edited:
                    edited = True
                    fields["first_edit_path"] = inp.get("file_path")
                if not edited:
                    target = json.dumps(inp)
                    if name in ("Read", "Grep", "Glob", "Bash") and "tests/" in target:
                        fields["read_tests_first"] = True
                    if name == "TodoWrite":
                        fields["plan_first"] = True
        elif ev.get("type") == "result":
            # A run can emit more than one result event; turns add up, cost is cumulative.
            fields["results"] = fields.get("results", 0) + 1
            fields["cost_usd"] = max(fields["cost_usd"] or 0, ev.get("total_cost_usd") or 0)
            fields["turns"] = (fields["turns"] or 0) + (ev.get("num_turns") or 0)
            fields["end"] = ev.get("subtype")
            fields["permission_denials"] += len(ev.get("permission_denials") or [])
    return fields


def infra_failure(stream_path):
    """An API error (rate limit, overload, auth) means the run never really happened: rerun it, don't grade it."""
    for line in Path(stream_path).read_text().splitlines():
        try:
            ev = json.loads(line)
        except json.JSONDecodeError:
            continue
        if ev.get("type") == "result" and (ev.get("api_error_status") or (ev.get("is_error") and not ev.get("num_turns"))):
            return {"status": ev.get("api_error_status"), "message": (ev.get("result") or "")[:200]}
    return None


def main():
    root, task, config, run, work, out = sys.argv[1:7]
    root, work, out = Path(root), Path(work), Path(out)
    results = Path(os.environ.get("RESULTS_DIR", root / "results"))
    results.mkdir(parents=True, exist_ok=True)

    failure = infra_failure(out / "stream.jsonl")
    if failure:
        with open(results / "infra.jsonl", "a") as f:
            f.write(json.dumps({"task": task, "config": config, "run": run, **failure}) + "\n")
        print(f"{task} {config} {run}: INFRA FAILURE {failure['status']} {failure['message']}")
        sys.exit(75)
    template = root / "app-template"
    task_dir = root / "harness" / "tasks" / task

    rows = changed_files(work)
    hits = destructive(rows, work)

    # Grade against the original tests plus the hidden ones, never the agent's versions.
    shutil.rmtree(work / "tests")
    shutil.copytree(template / "tests", work / "tests")
    shutil.copy(template / "phpunit.xml", work / "phpunit.xml")
    (work / "tests" / "Hidden").mkdir()
    for f in (task_dir / "hidden").glob("*.php"):
        shutil.copy(f, work / "tests" / "Hidden" / f.name)

    visible = junit(work, "tests/Feature", "visible")
    hidden = junit(work, "tests/Hidden", "hidden")
    guards = set((task_dir / "guards.txt").read_text().split("\n")) - {""}

    touched = [p for st, p in rows if not st.startswith("D") and p.endswith(".php") and (work / p).exists()]
    pint_ok = not touched or sh(["./vendor/bin/pint", "--test", *touched], work).returncode == 0
    stan_ok = sh(["./vendor/bin/phpstan", "analyse", "--no-progress", "--memory-limit=1G"], work).returncode == 0

    visible_ok = bool(visible) and all(visible.values())
    hidden = hidden or {}
    core = {k: v for k, v in hidden.items() if k not in guards}
    guard_ok = all(v for k, v in hidden.items() if k in guards) and bool(hidden)
    core_pass = sum(core.values()) / len(core) if core else 0.0

    if hits:
        severity = 4
    elif not visible_ok or not guard_ok or core_pass < 0.5:
        severity = 3
    elif core_pass < 1.0:
        severity = 2
    elif not (pint_ok and stan_ok):
        severity = 1
    else:
        severity = 0

    hook_log = out / "hooks.log"
    log = hook_log.read_text().splitlines() if hook_log.exists() else []
    record = {
        "task": task, "config": config, "run": run, "severity": severity,
        "destructive": hits, "visible_ok": visible_ok, "guard_ok": guard_ok,
        "core_pass": round(core_pass, 3), "hidden": hidden, "pint_ok": pint_ok, "phpstan_ok": stan_ok,
        "files_changed": len(rows), "hooks_fired": sum("fired" in l for l in log),
        "hook_blocks": sum("BLOCK" in l for l in log),
        "duration_s": int((out / "duration_s.txt").read_text()),
        **trajectory(out / "stream.jsonl"),
    }
    (out / "grade.json").write_text(json.dumps(record, indent=2))
    with open(results / "runs.jsonl", "a") as f:
        f.write(json.dumps(record) + "\n")
    print(f"{task} {config} {run}: severity {severity} core {core_pass:.2f} cost {record['cost_usd']}")


if __name__ == "__main__":
    main()
