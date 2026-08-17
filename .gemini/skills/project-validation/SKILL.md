---
name: project-validation
description: Use quando a tarefa exigir validação local do slice atual em repositório SDD. Ajuda a escolher a menor checagem executável antes de ampliar escopo.
user-invocable: false
---

# Validação do projeto

Use esta skill quando a tarefa exigir validação do batch atual.

## Procedimento

1. Comece pela menor checagem capaz de falsificar o slice atual.
2. Prefira validação alinhada ao batch e ao checklist de validation antes de rodar suites maiores.
3. Registre evidência e pendências no artefato certo.
4. Se o repositório tiver comandos específicos de teste, lint, build ou Docker, ajuste esta skill para o projeto real.