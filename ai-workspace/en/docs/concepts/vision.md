---
title: "Conn2Flow vision"
description: "Content as resources, CLI automation, and product direction."
section: concepts
order: 130
sources:
  - gestor/controladores/api/api.php
  - cli/c2f.php
  - gestor/gestor.php
verified_at: 3b099ff0
---

# Conn2Flow vision

Conn2Flow treats pages, modules, and resources as data that can be compiled, inspected, and deployed. The panel is one interface to that data. The `c2f` CLI offers commands for resources, databases, projects, and releases; the API uses token authentication for its own operations. The public router still serves pages from the database. This division supports automation without making panel HTML the source of truth.

The repository tracks requests, decisions, batches, and validation under `sdd/`. This process organizes work by agents and people, but it is project governance rather than a guarantee enforced by the PHP runtime. See [architecture](architecture.md) and the [documentation guide](../guides/documentation.md) for implemented contracts.

Complementary integrations and applications can consume core interfaces. An AI gateway, coordination across repositories, and mobile clients are product directions; they do not imply endpoints, permissions, or availability that this repository's code does not implement.
