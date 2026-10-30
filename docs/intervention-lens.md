# Intervention lens (rough draft for reaction)

Each intervention is a change to the configuration. The lens asks two things: which part of a run it acts on, and what it does to the outcome distribution.

Parts of a run: **choices** (what the agent decides to do), **environment** (how the world responds to an action), **observation** (what the agent sees of that response), **acceptance** (which result is kept as the outcome).

Effects on the distribution: **range** (which outcomes are possible at all), **concentration** (how tightly runs cluster), **bias** (a systematic shift, possibly toward the wrong place), **tails** (how likely the severe outcomes are). "Gain" says whether it mainly buys correctness, predictability, or only observability.

Evidence column: L = literature (see `literature.md`), S = synthetic sim (`sim/scenarios.py`), E = the planned experiment, — = reasoning only.

| Intervention | Acts on | Range | Concentration | Bias | Tails | Gain | Cost | Makes it worse when | Evidence |
|---|---|---|---|---|---|---|---|---|---|
| Explicit spec / acceptance criteria | choices | narrows | ↑ | can add bias if spec is wrong | ↓ if prohibitions target severe acts | correctness + predictability | writing time | the spec is wrong or incomplete: runs concentrate confidently on the wrong outcome | S (scenario D), E (config B) |
| Context engineering (CLAUDE.md, examples, retrieval) | observation (of the task) | narrows | ↑ | follows the examples' bias | ~ | correctness | tokens, upkeep | stale or irrelevant context; long context degrades later steps | L (context degradation, Mittal) |
| Structured outputs / schemas | choices | removes malformed outcomes | ↑ | ~ | ↓ for format failures only | predictability | low | the schema forces a wrong shape onto a task that needs another | — |
| Tool restrictions, permissions, sandbox | choices + environment | removes destructive outcomes | ~ | can block the right path | ↓↓ for severity 4 | tail safety | friction, denied calls | the needed action is blocked, so the agent finds a worse workaround | E (config B, permission denials) |
| Decomposition / planning step | choices | ~ | ↑ per stage | an early wrong plan propagates | can ↑ if early errors cascade | predictability | turns | the plan is wrong and later steps follow it faithfully | L (root-cause step, inherited uncertainty: UProp/SAUP) |
| Lint / static analysis hooks | observation | ~ | ↑ | ~ | ↓ for the classes it detects | correctness (narrow) | seconds per edit | the agent games the checker (suppressions, ignores) | E (config C) |
| Visible tests as a Stop gate | observation + acceptance | ~ | ↑ | toward "passes visible tests" | ↓ unless the agent edits tests | correctness, bounded by test quality | test time, turns | tests are weak or editable: silent wrong outcomes, test tampering | S (scenario C), E (config C/D, trap task) |
| Self-correction without external signal | choices | ~ | ~ | can drift | ~ | none reliably | turns | often: it can turn right answers into wrong ones | L (Huang 2024, Kamoi 2024) |
| Retries with a verifier | acceptance | ~ | ↑ | toward what the verifier accepts | ↑ silent wrong outcomes if the verifier leaks | correctness, capped by verifier precision | k × run cost | the verifier's false-positive rate is high; tasks differ in difficulty so retries are correlated | S (scenario C), L (Stroebl et al.) |
| Best-of-n / self-consistency | acceptance | ~ | ↑ | toward the reward model's preference | ~ | correctness up to a point | n × run cost | selection pressure overoptimizes an imperfect scorer | L (Gao et al., Snell et al.) |
| Independent verification (second agent / human review) | acceptance | ~ | ~ | ~ | ↓ if reviewer errors are independent | correctness + observability | reviewer time | errors are correlated (same model, same blind spot) | L (common-cause failures), — |
| Human approval checkpoints | acceptance (before irreversible steps) | removes unapproved actions | ~ | ~ | ↓↓ for irreversible acts | tail safety | human attention, latency | approval fatigue: rubber-stamping | L (KnowNo: ask only when uncertain) |
| Runtime monitoring | observation (of the operator) | ~ | ~ | ~ | ~ by itself | observability only | infra | treated as prevention when it only detects | L (Pro2Guard: predictive monitoring) |
| Formal invariants / runtime assurance (Simplex) | acceptance + environment | removes invariant-violating outcomes | ↑ | conservative fallback | ↓↓ within the invariant's scope | tail safety | spec effort | the invariant misses the actual failure mode | L (Simplex, shielding) |

## Patterns the table shows

1. Interventions on **choices** mostly buy concentration. Whether that is correctness depends on whether the constraint points the right way (scenario D).
2. Interventions on **acceptance** buy correctness up to the precision of whatever does the accepting; past that they grow silent failures (scenario C).
3. The **tails** are moved most by restrictions on what the environment allows (permissions, sandbox, approvals, invariants), not by better reasoning.
4. **Observation-only** interventions (monitoring) change nothing about the distribution until someone acts on them.

## Proposal for the post vs the dossier

Post: a condensed 6-row table (spec, tool restrictions, test gate, retries with verifier, self-correction, human checkpoint) with columns "acts on", "what it does to the distribution", "fails when", plus the four patterns. Dossier: this full table.
