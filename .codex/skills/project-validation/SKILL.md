---
name: project-validation
description: "LEIA ANTES de validar qualquer alteração de código ou fechar um lote SDD. Se não ler: testes incompletos deixam passar regressões graves e o lote é concluído sem evidências verificáveis."
user-invocable: false
---

# Validação do projeto

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Concluir implementações de código e preparar as evidências de testes técnicos, automatizados ou visuais para registrar em `VALIDATION-CHECKLIST.md`.
- **SKIP APENAS SE**: Tarefas de pura especificação/planejamento documental onde nenhum arquivo de código foi alterado.
- **CONSEQUÊNCIA DE IGNORAR**: Falso positivo de conclusão de lote, código com regressões em produção e falta de rastreabilidade de evidências.

---

Use esta skill quando a tarefa exigir validação do batch atual.

## Procedimento

1. Comece pela menor checagem capaz de falsificar o slice atual.
2. Prefira validação alinhada ao batch e ao checklist de validation antes de rodar suites maiores.
3. Registre evidência e pendências no artefato certo.
4. Se o repositório tiver comandos específicos de teste, lint, build ou Docker, ajuste esta skill para o projeto real.
