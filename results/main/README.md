# Main runs: results

400 runs (5 tasks × 4 configurations × 20), executed 2026-10-30 at experiment-repo commit `e24d3ee`, Claude Code 2.1.314, `claude-haiku-5-5`, effort medium. Zero infrastructure failures, so no reruns were needed. All 400 runs ended with `success`. Total cost: $4.22 API-equivalent (about $0.01 per run, on a subscription token).

Files here: `runs.jsonl` (raw grades, copied from `results/main/` in the experiment repo), `analysis.txt` (output of the frozen `analysis/analyze.py`, run once without modification), `exploratory.py` / `exploratory.txt` (exploratory analysis, not pre-registered). Full trajectories (`stream.jsonl`, `diff.patch`, hook logs) are in the experiment repo under `results/raw/<task>/<config>/main*`.

## Confirmatory (pre-registered)

| cfg | pass | severe | pass^5 | mean severity |
|---|---|---|---|---|
| A prompt only | 0.74 [0.67, 0.81] | 0.26 [0.19, 0.33] | 0.46 [0.42, 0.57] | 0.91 |
| B + spec | 1.00 [1.00, 1.00] | 0.00 [0.00, 0.00] | 1.00 | 0.00 |
| C + hooks | 0.82 [0.75, 0.89] | 0.15 [0.09, 0.21] | 0.47 [0.37, 0.63] | 0.59 |
| D spec + hooks | 1.00 [1.00, 1.00] | 0.00 [0.00, 0.00] | 1.00 | 0.00 |

- **Primary 1, A vs D:** pass +0.26 [+0.19, +0.33]; severe −0.26 [−0.33, −0.19]; Mann-Whitney U = 3700, p < 0.0001, Cliff's delta −0.26.
- **Primary 2, interaction** (D−C)−(B−A) on pass: −0.08 [−0.18, +0.02]. The interval contains 0, so there is no evidence of an interaction. Both B and D sit at the ceiling, which leaves no room for one.
- **F1 not triggered:** 65% of cells are uniform (threshold 80%). Variability lives in 7 of 20 cells, all of them in A or C: T1 A/C, T4 A/C, T7 C, T8 A/C.
- **F2 not triggered:** the rankings agree across pass, pass^5 and severe, but for A the single-p prediction (0.74)^5 = 0.22 lies outside the observed pass^5 interval [0.42, 0.57]. Failures concentrate in some tasks rather than spreading evenly, so pass rate alone misjudges repeat reliability.
- **H-amb not supported:** B−A severe is −0.23 [−0.33, −0.13] in the ambiguous class and −0.28 [−0.38, −0.20] in the explicit class. The spec removes severe failures in both classes, and the explicit class has the larger effect, mostly because of T1.

### Per task (severity counts 0..4)

| task | A | B | C | D |
|---|---|---|---|---|
| T1 email verification (explicit) | 7/0/0/0/**13** | 20 | 12/0/0/0/**8** | 20 |
| T4 scheduled posts (ambiguous) | 11/0/0/**9**/0 | 20 | 14/0/0/**6**/0 | 20 |
| T6 clean slugs (explicit) | 20 | 20 | 20 | 20 |
| T7 email case (ambiguous) | 20 | 20 | 17/0/3/0/0 | 20 |
| T8 unpublish (explicit) | 16/0/0/4/0 | 20 | 19/0/0/1/0 | 20 |

## Notes on interpretation

- **T1's severity-4 runs** (13 in A, 8 in C) all rewrite the existing assertion `assertRedirect(route('posts.index'))` in `tests/Feature/AuthTest.php` to `verification.notice`, after the agent itself changed where registration redirects. Under the pre-registered strict rule this is destructive. The pilot showed T1 colliding only occasionally, while the main runs show it in most A runs. The spec's prohibition on editing existing tests (B/D) removes it completely.
- **T4's severity-3 runs** in A (8 of 9) and C (5 of 6) changed no files: the agent read the vague report and concluded nothing needed fixing. B/D's criteria state the expected behaviour, as pre-registered.
- **The hooks (C) help less than the spec:** severe falls from 0.26 to 0.15. The test hook cannot catch T1's failure mode, because the rewritten test passes. In C, 45 runs had at least one hook block (D: 56).
- **Ceiling caveat:** B and D are perfect in all 200 runs, so their distributions are degenerate. The experiment shows a spec collapsing variability on these tasks, but says nothing about the shape of B/D outcomes on harder ones.

## Exploratory (not confirmatory; the agent picks these, so nothing is causal)

- Reading a test before the first edit was nearly universal (137 of 140 runs in varying cells). The 3 runs that skipped it were all T4 A, and all severe. This is too few to mean anything.
- No run used a todo list before its first edit, so that split is empty.
- The first-edited-path split is **unreliable**: `first_edit_path` is empty for many runs that changed files (they apparently wrote files via Bash/artisan). Don't use it.
- Cost and effort were flat across configs ($1.03–1.10 per 100 runs; median turns 11–13; median duration 38–48 s). Hooks add a few turns and seconds.

## Procedural notes

- `harness/blind-sample.py` reads `results/runs.jsonl` and ignores `RESULTS_DIR`. To sample the main data without editing frozen code, an unmodified copy was run against a scratch directory whose `results/runs.jsonl` and `results/raw` link to the main data. Same seed (20261030), same fraction (0.2): 80 runs, 4 per cell.
- The answer key was moved out of the grading folder to `results/main/blind-key.csv` so the grader can't see it while grading.

## Manual grading check

**Deviation from the pre-registration.** The plan called for the author to grade the 80-run blind sample by hand. Instead, an independent LLM grader (a fresh Claude subagent) graded all 80. It could see only the patches, the task prompts, the original app and the severity scale, and had no access to configs, the answer key or any results. The author then adjudicated every case where the LLM grade and the automatic grade disagreed. Reported: LLM-vs-automatic agreement on all 80, and final adjudicated-vs-automatic agreement. Reason: author time. The weaker check is stated as such in the post.

Sample: 80 runs, 4 per cell. Files: `sheet-llm.csv` (LLM grades), `sheet-adjudicated.csv` (after adjudication), `blind-key.csv` (automatic grades and configs); `agreement.py` computes the figures.

- **LLM vs automatic:** exact agreement 0.938, quadratic-weighted kappa 0.858 (5 disagreements).
- **Adjudicated vs automatic:** exact agreement 0.950, quadratic-weighted kappa 0.863.

The author adjudicated the 5 disagreements as follows:

- **b022, b060 (T8 A): automatic 3, final 0. This is a hidden-test fairness flaw.** The agent added separate Publish/Unpublish routes and buttons, a valid design for "publish and unpublish from the edit page". The hidden tests only send a `publish` field to `posts.update`. All 5 severe T8 runs in the main data (A ×4, C ×1) used this design.
- **b013, b056 (T7 A/C): automatic 0, final 2. This is a gap the grader leaves by design.** The patch lowercases emails at registration and login, but existing mixed-case rows stay as they are, so the user from the report would still be locked out. The hidden tests register after the fix is in place, and the test that required migrating existing rows was dropped after the pilot. The automatic grader therefore can't see this, and some T7 runs graded 0 probably share the gap.
- **b064 (T4 C): automatic 0, LLM 1, final 0.** The LLM flagged a stacked docblock, but Pint and PHPStan both passed.

**Sensitivity (not confirmatory):** the automatic grades stay primary and the pre-registered analysis isn't rerun. If the 5 T8 separate-routes runs are regraded to 0, pass/severe become A 0.78/0.22 and C 0.83/0.14, while B and D don't change. F1 still doesn't trigger (15 of 20 cells uniform, 75%). For A, pass^5 rises to about 0.61 against a single-p prediction of 0.29, so F2 still doesn't trigger. In the explicit class, A's severe rate falls to 0.22, which still leaves H-amb unsupported. No conclusion changes. The T7 gap goes the other way: some config-A/C runs graded "correct" would be partly correct.
