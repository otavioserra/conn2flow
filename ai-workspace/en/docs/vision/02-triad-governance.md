# 02 — Triad Governance

## Why not a single autonomous agent

Letting one AI agent read a request and freely rewrite code (or content)
tends to produce unreviewed regressions and architectural drift, because
the same actor plans, executes, and grades its own work.

## The Triad: Architect, Executor, Reviewer

Conn2Flow's governance model — Spec-Driven Development (SDD) — splits the
work across three roles that share a single source of truth, `sdd/`:

- **Architect (macro-orchestrator)**: turns human intent into normative
  specs, decision records (`sdd/decisions/`), and formal requests
  (`sdd/human-requests/req-XXX.md`). Never commits or pushes code directly.
- **Executor (micro-operator)**: reads the active request, implements the
  smallest reviewable slice, runs the tests, and records evidence in
  `sdd/implementation/` and `sdd/validation/`.
- **Reviewer**: audits the diff findings-first — spec drift, batch drift,
  missing validation — before the batch is considered closed.
- **Human-in-the-Loop**: directs the Architect and inspects the Executor's
  diff before anything is consolidated.

## Explicit, auditable boundaries

The model draws a hard line between what an agent may write on its own and
what always needs a human or a formal change request:

- 🟢 **Operational area** (agent writes freely): implementation progress
  (`sdd/implementation/`) and validation evidence (`sdd/validation/`).
- 🟡 **Shared, reserved area**: new `sdd/human-requests/req-XXX.md` files,
  created under an atomic reservation protocol to avoid numbering
  collisions between concurrent agents.
- 🔴 **Normative area** (agent reads only): `sdd/SPEC.md`, numbered specs,
  and `sdd/decisions/DECISION-LOG.md`. A spec-level disagreement is raised
  as a Change Request, never edited directly.

## The autonomy spectrum

Not every session needs the same amount of supervision. The framework
recognizes three explicit tiers — **Supervised** (default; no autonomous
commit/push/deploy), **Monitored Autonomous** (full pipeline visible live
in chat, deploy restricted to local test environments), and **Headless
Autonomous** (background execution with a consolidated report at the end)
— so the level of trust granted to an agent is a deliberate choice, not an
accident of how a prompt was phrased.

## Memory instead of repetition

Two engineering diaries prevent context from being re-derived every
session: a Chief memory (style, conventions, architectural boundaries —
read-only for the Executor) and an Execution memory (dependency quirks,
compiler behavior, resolved bugs — read/write for the Executor, pruned
periodically so it never becomes prompt bloat).
