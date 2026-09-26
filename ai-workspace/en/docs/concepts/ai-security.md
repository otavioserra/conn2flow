---
title: "AI and administrative security"
description: "AI editor access boundaries and known risks from the core review."
section: concepts
order: 120
sources:
  - gestor/bibliotecas/ia.php
  - gestor/bibliotecas/interface.php
  - gestor/bibliotecas/seguranca.php
  - gestor/controladores/api/api-module-distributed.php
verified_at: 3b099ff0
---

# AI and administrative security

The AI editor loads models and prompts through `ia.php`. Filters such as `ia.models.available` and `ia.prompts.load.where` can narrow what a user sees. This does not itself authorize writes: prompt editing and deletion have their own queries and must be assessed separately in multiuser projects.

The core security review recorded eleven findings still proposed for remediation (req-181). They concern administrative list queries and sorting, state changes over GET, column name validation, escaping in shared functions, second-factor limits, uploaded browser-active file types, session binding and lifetime, email verification in social login, SMTP log secrecy, prompt ownership, and replay on the distributed-module channel. A proposed fix is not an implemented fix.

> [!CAUTION]
> In multiuser installations, limit who can edit prompts and review administrative endpoints exposed to lower-privilege profiles. Protect logs and treat uploaded HTML/SVG as active content.

The [AI](../reference/libraries/ia.md), [interface](../reference/libraries/interface.md), and [security](../reference/libraries/seguranca.md) library references describe concrete functions. Remediation tracking belongs in the security request, rather than this public page.
