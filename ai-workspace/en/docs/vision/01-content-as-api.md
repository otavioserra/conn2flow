# 01 — Content as an API Surface

## The problem with "admin panel only"

A traditional CMS treats the web admin panel as the only door into its
content. Anything an agent needs to read or change has to go through
screen-scraping, direct database access, or a bespoke integration — none of
which carry the CMS's own validation, permissions, or audit trail.

## Personal Access Tokens as agent identity

Conn2Flow's `_api/` layer is reachable by Personal Access Tokens (PAT) that:

- are scoped to explicit profiles (`AUTH_API_ALLOWED_PROFILES`), never a
  blanket admin credential;
- are rate-limited per token, so a runaway agent cannot exhaust the system;
- can be revoked without touching the user's password or session;
- are validated by the same permission pipeline a logged-in human hits.

An agent authenticates like a scoped user, not like a background script
holding a shared secret.

## The CLI as the automation contract

The `c2f` binary (30+ commands, covering resources, database, releases,
Docker, and CI) is the same surface a human runs by hand at a terminal and
an agent dispatches remotely through the Conn2Flow AI Workspace **MCP Hub**:

- `c2f_run_command` — executes a native CLI command (`resources:sync`,
  `db:test`, `docker:status`, and the rest of the catalog);
- `dispatch_task` — queues work for an agent running in IDE-supervised or
  headless mode;
- `report_completion` — records and correlates the evidence of a finished
  batch back to the request that triggered it.

One contract, two operators: whichever one is driving, the commands, their
guardrails, and their output are identical.

## The IDE as the console

The **Conn2Flow Dev Tools** VS Code extension exposes the same operations
(Docker, Manager, Projects, Releases, AI Hub) as one-click actions in a
sidebar, so a human can drive the exact same automation surface an agent
uses — without ever leaving the editor.
