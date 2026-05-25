---
name: project-validation
description: Use quando validar mudanças em repositórios SDD com a menor checagem automatizada primeiro e, se necessário, validação de stack depois.
---

# Validação do projeto

Adapte esta skill aos comandos canônicos do projeto.

## Ordem recomendada

1. Leia a validation checklist e o batch atual.
2. Rode primeiro a menor validação automatizada coerente com o batch.
3. Amplie para a regressão local do projeto.
4. Só depois parta para validação de stack, containers, serviços externos ou smoke flow quando o batch realmente pedir isso.

## Ajuste este arquivo por projeto

- Troque os comandos de teste pelos gates reais do repositório.
- Adicione comandos de stack, healthcheck e smoke flow se o projeto tiver essa camada.
- Registre aqui a ordem canônica de validação para evitar pular direto para checagens caras.