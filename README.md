# Every agent run is a sample: experiment

A small, rerunnable experiment for the post "Every agent run is a sample". One coding agent (Claude Code, headless) runs five tasks on a small Laravel 13 app under four configurations, many times each, and every run is graded on a 0–4 severity scale.

## Layout

- `app-template/`: the app every run starts from (hand-written auth, posts, Pest tests, Pint, PHPStan level 6). The skeleton's `CLAUDE.md`/`AGENTS.md` (Laravel Boost setup instructions) are removed, so no configuration gets instructions beyond its own.
- `harness/tasks/<task>/`: `prompt.md` (what the agent is told), `criteria.md` (acceptance criteria, shown only in configs B and D), `hidden/` (acceptance tests the agent never sees), `guards.txt` (hidden tests that already pass on the untouched app).
- `harness/configs/B/`: the spec appended to the system prompt and the disallowed tools (configs B and D).
- `harness/hooks/`: feedback hooks (configs C and D): Pint and PHPStan after every PHP edit, the test suite before the agent may stop.
- `harness/run.sh`: one run in an isolated copy, then grading.
- `harness/grade.py`: severity and recorded fields, appended to `results/runs.jsonl`.
- `harness/reference/`: reference solutions, used only by `harness/selftest.sh` to check the grader.
- `harness/blind-sample.py`: draws the blind sample for manual grading.

## Configurations

| Config | Constraints (spec + tool limits) | Feedback (hooks) |
|---|---|---|
| A | no | no |
| B | yes | no |
| C | no | yes |
| D | yes | yes |

All configs run with the Bash sandbox on, a fresh empty `CLAUDE_CONFIG_DIR` and auto-memory off. They don't use `--bare`, because `--bare` skips hooks defined in settings.

## Severity

0 correct · 1 minor issues (Pint/PHPStan) · 2 some core hidden tests fail · 3 broken or incomplete (original suite fails, a guard fails, or under half the core tests pass) · 4 destructive (changed existing tests, test config, Composer files or existing migrations).

## Running

```bash
harness/selftest.sh                                    # grader check, no API calls
echo CLAUDE_CODE_OAUTH_TOKEN=... > .env.local   # from `claude setup-token`, or ANTHROPIC_API_KEY=... for API billing
harness/run.sh T4-scheduled-posts A 001                # one run
```

Environment: `MODEL` (default `claude-haiku-5-5`), `EFFORT` (`medium`), `BUDGET` (USD cap per run, `0.25`), `RUNS_DIR` (`/tmp/agent-runs`), `KEEP_WORK=1` to keep the run copy.
