# Pre-registration: constraints, feedback and the shape of agent outcomes

Status: FROZEN on 2026-10-30. Frozen copy: `PREREGISTRATION.md` in the experiment repo, committed before any main run (the commit hash is recorded in the wayfinder ticket "Freeze the design and commit the pre-registration"). No main run started before that commit.

## Purpose

Test two claims of the post "Every agent run is a sample" on real agent runs:

1. Runs of the same configuration on the same task vary in ways that matter.
2. Interventions change the shape of the outcome distribution in ways the mean hides.

Design decisions are recorded in the wayfinder tickets "Experiment design: tasks, configs, metrics and the falsification test", "Pilot run, design freeze and pre-registration" and "Ceiling effect: proceed, harden, or reframe?"; the harness is described in the experiment repo README.

## Design

- Agent: Claude Code `2.1.314`, headless (`claude -p`), model `claude-haiku-5-5`, effort `medium`, per-run budget cap `0.25` USD. Not `--bare` (it skips settings hooks); each run has a fresh empty config dir, auto-memory off, Bash sandbox on (no network).
- Codebase: the experiment repo's `app-template/` at commit `4ebd0e8`.
- Tasks: T1 email verification, T4 scheduled posts, T6 clean slugs, T7 email case, T8 unpublish (prompts, criteria and hidden tests in `harness/tasks/`). Task classes for H-amb: **ambiguous** = T4, T7 (vague reports describing a symptom); **explicit** = T1, T6, T8 (stated features).
- Configurations: A (task prompt only); B (A + spec with acceptance criteria and prohibitions appended to the system prompt, plus disallowed tools); C (A + hooks: Pint and PHPStan after every PHP edit, test suite before stopping); D (B + C). Neither constraints nor feedback expose the hidden acceptance tests.
- Runs: 20 per task × configuration, 400 in total, executed in a shuffled order, at most 4 in parallel. `run.sh` refuses to run if Claude Code is not 2.1.314 (`EXPECT_CLAUDE_VERSION`).

## Outcome

Severity per run, graded automatically (`harness/grade.py`, self-tested):

- 4 destructive: removed or rewritten lines in existing tests; any change to `phpunit.xml`, `tests/Pest.php`, Composer files or existing migrations.
- 3 broken or incomplete: the original visible suite fails, a guard test fails, or fewer than half the core hidden tests pass.
- 2 tests fail: at least half, but not all, core hidden tests pass.
- 1 minor issues: all tests pass, Pint or PHPStan report problems in changed files.
- 0 correct: all tests pass and the code is clean.

Derived: pass = severity ≤ 1; severe failure = severity ≥ 3.

## Metrics per configuration (pooled over tasks)

Pass rate, severe-failure rate, pass^5 (per-task unbiased estimator C(c,5)/C(20,5), averaged over tasks), mean severity, and the severity distribution per task. Intervals: 95% stratified bootstrap (runs resampled within each task × configuration cell, 10,000 replicates, seed 20261030).

## Primary comparisons

1. **A vs D**: difference in pass rate and in severe-failure rate with bootstrap intervals; ordinal severity compared with a two-sided Mann-Whitney U test and Cliff's delta.
2. **Constraints × feedback interaction** on pass rate: (D − C) − (B − A), with bootstrap interval.

## Secondary hypothesis (pilot-suggested)

**H-amb: outcome variability concentrates in ambiguous requests, and the spec removes it.** Suggested by the pilot (T4: A failed 2 of 3, B 0 of 3) and stated here before any main run; it is tested on main-run data only, never on pilot data.

- Test: severe-failure rate, B − A, within the ambiguous class and within the explicit class, with stratified-bootstrap 95% intervals.
- Supported if the ambiguous-class interval lies entirely below 0 **and** the explicit-class interval contains 0. Not supported otherwise; reported either way.
- Known weakness: the ambiguous class has two tasks, and in the pilot only T4 varied much.

Everything else is descriptive or exploratory and reported as such.

## Falsifiers

- **F1 (no meaningful variability)**: triggered if, in at least 80% of task × configuration cells, all 20 runs have the same severity. Expected to trigger given the pilot; kept unchanged.
- **F2 (shape adds nothing beyond the mean)**: triggered if the four configurations rank identically by pass rate, by pass^5 and by severe-failure rate (ascending), **and** for every configuration the single-p prediction (pass rate)^5 lies inside the bootstrap interval of the observed pass^5.

If either triggers, the post says so and narrows its scope (variability only, or measurement only).

## Manual grading check

A stratified 20% sample (`harness/blind-sample.py`, seed 20261030) is graded by hand without seeing the configuration. Report agreement with the automatic grade as exact agreement and quadratic-weighted Cohen's kappa. Automatic grades remain the primary data.

## Exclusions and reruns

- Infrastructure failures (an API error such as the subscription usage limit, or no assistant turn) are not graded; they are logged in `infra.jsonl` and rerun with the same run id until they complete. Nothing else is excluded.
- Runs stopped by the budget cap or by repeated hook blocks are kept and graded as they are.

## Exploratory (not confirmatory)

- Severity conditional on early decisions (read a test before the first edit; first edited path; todo list before the first edit). The agent picks these itself, so any association is not causal.
- Cost, turns, duration, hook blocks and permission denials per configuration.

## Pilot changes

Pilot: 96 graded runs (60 on the original five tasks, 36 on the replacements), ~$1 API-equivalent.

- T2 login throttle, T3 soft delete, T5 reading-time refactor were perfect in all 12 pilot runs each and were replaced (pre-registered swap rule, once per task) by T6, T7, T8. T6 and T8 were also perfect in their re-pilot; per the rule they are reported, not swapped again.
- Grader fixes: appending tests to an existing test file is not destructive (only removed or rewritten lines are); Pint checks only files the agent changed; API failures are infrastructure, not severity 3; turns are summed across result events.
- Hidden-test fairness fixes: T7 dropped a test that a valid data-migration fix cannot pass; T8's unchecked-box test sends `publish=0` so designs with a hidden input pass.
- Harness: the Laravel skeleton's `CLAUDE.md`/`AGENTS.md` (Boost setup instructions) removed from the template; scheduling and background tools disallowed in all configurations; budget cap lowered from 1.00 to 0.25 USD; runs per cell raised from 10 to 20.
- T4's criteria (B/D only) state that future-dated posts are scheduled; kept deliberately, as stating the expected behaviour is what a spec does.
- Rewriting an existing assertion stays severity 4 (strict rule), even when it follows a behaviour change the agent chose.

## Analysis code

`analysis/analyze.py` in the experiment repo at commit `d45e46a` implements this plan; it is run once on the main data without modification.
