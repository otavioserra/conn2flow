# 03 — One Governance Fleet, Many Repositories

## The cost of reinventing discipline per codebase

Every new codebase an agent touches usually means re-explaining, from
scratch, how that project works: its conventions, its footguns, its
release process. That cost is paid again every time, in every repository.

## A shared skill catalog, propagated on purpose

**Conn2Flow AI Workspace** centralizes a catalog of Core Skills (product
and infrastructure knowledge: database access, resource compilation,
Tailwind architecture, shell/Windows pitfalls, and more) plus SDD workflow
skills (how to start a slice, continue a batch, raise a spec change, review
a batch, prune memory). The same catalog — not a copy reinvented per
project — is installed into every repository that adopts the framework,
across every supported AI tool (Claude Code, GitHub Copilot, Cursor,
Antigravity/Gemini, OpenAI Codex).

## Local memory, shared shape

Propagation does not mean every repository behaves identically: each one
keeps its **own** local Chief and Execution memories, its own `sdd/`
history, and its own backlog. What is shared is the *shape* of governance —
the same Triad roles, the same intake gates, the same boundary between
normative and operational writes — so an agent moving between
repositories does not have to relearn how decisions are supposed to flow,
only what is specific to that codebase.

## One-click onboarding

The **Conn2Flow Dev Tools** VS Code extension turns adopting this model
into a one-click action: cloning a repository and scaffolding a new
satellite project with the SDD structure and skill catalog already wired
in, instead of a manual, error-prone bootstrap every time a new project
starts.

## Why this matters for a private overlay model

A private client project is typically a thin overlay on top of the core
CMS: its own content, its own customizations, sometimes its own private
skills — but the same governance and the same core. Propagating the
framework, rather than duplicating it by hand, is what keeps that overlay
model sustainable as the number of projects grows.
