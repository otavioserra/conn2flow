# Visão: Do CMS ao Backend-para-Agentes

O Conn2Flow começou como um Sistema de Gestão de Conteúdo (CMS). Ele está
evoluindo para um **framework de backend projetado para ser operado de
dentro do IDE** — VS Code, Antigravity, Cursor, Claude Code, Codex — com
acesso total e controlado a tudo que o CMS gerencia: páginas, widgets,
variáveis, mídia, usuários.

Isso não é sobre agentes autônomos como um fim em si mesmos. É sobre
**operações de conteúdo — criar, editar, revisar, publicar, implantar —
se tornarem uma superfície compartilhada entre humanos e agentes de IA**,
governada com a mesma disciplina que um CMS já aplica a editores humanos:
identidade, escopo, trilha de auditoria.

## Páginas

1. [Conteúdo como Superfície de API](01-conteudo-como-api.md) — Tokens de
   Acesso Pessoal, a camada `_api/` e o CLI `c2f` como o único contrato de
   automação compartilhado por humanos e agentes.
2. [Governança em Tríade](02-governanca-triade.md) — o modelo
   Arquiteto / Executor / Revisor, `sdd/` como fonte única da verdade e o
   espectro de autonomia que delimita o que um agente pode decidir sozinho.
3. [Uma Só Frota de Governança, Muitos Repositórios](03-frota-multi-repo.md)
   — como o mesmo catálogo de skills e a mesma topologia de agentes se
   propagam, com memória local independente, pelo core e por todo projeto
   construído sobre ele.
4. [Gateway de IA & Prova em Produção](04-gateway-ia-e-producao.md) — o
   gateway de IA emergente e independente de fornecedor, a arquitetura de
   agente mobile e a evidência de que este modelo já roda em produção.

---
**Status:** documento vivo, sujeito a revisões conforme o modelo amadurece.
