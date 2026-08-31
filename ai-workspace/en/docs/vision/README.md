# Vision: From CMS to Backend-for-Agents

Conn2Flow started as a Content Management System. It is evolving into a
**backend framework designed to be operated from inside the IDE** — VS Code,
Antigravity, Cursor, Claude Code, Codex — with full, controlled API access
to everything the CMS manages: pages, widgets, variables, media, users.

This is not about autonomous agents as an end in themselves. It is about
**content operations — create, edit, review, publish, deploy — becoming a
shared surface between humans and AI agents**, governed with the same
discipline a CMS already applies to human editors: identity, scope,
audit trail.

## Pages

1. [Content as an API Surface](01-content-as-api.md) — Personal Access
   Tokens, the `_api/` layer, and the `c2f` CLI as the single automation
   contract shared by humans and agents.
2. [Triad Governance](02-triad-governance.md) — the Architect / Executor /
   Reviewer model, `sdd/` as the single source of truth, and the autonomy
   spectrum that bounds what an agent may decide alone.
3. [One Governance Fleet, Many Repositories](03-multi-repo-fleet.md) — how
   the same skill catalog and agent topology propagate, with independent
   local memory, across the core and every project built on top of it.
4. [AI Gateway & Production Proof](04-ai-gateway-and-production.md) — the
   emerging vendor-agnostic AI Gateway, the mobile agent architecture, and
   evidence that this model already runs in production.

---
**Status:** living document, expect revisions as the model matures.
