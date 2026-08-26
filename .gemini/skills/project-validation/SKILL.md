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

## Procedimento de Validação

1. Comece pela menor checagem capaz de falsificar o slice atual.
2. Prefira validação alinhada ao batch e ao checklist de validation antes de rodar suites maiores.
3. Registre evidência e pendências no artefato certo (`sdd/validation/VALIDATION-CHECKLIST.md`).
4. Se o repositório tiver comandos específicos de teste, lint, build ou Docker, utilize-os para coletar evidências objetivas.

---

## 🚫 Regra Anti-Hábito de "Pendente do Operador"

- O agente **DEVE OBRIGATORIAMENTE** executar as ferramentas autônomas de inspeção (`c2f page:inspect`, `c2f auth:cookie`), testes unitários (`c2f db:test`) ou suites de teste antes de dar um item como validado.
- É **estritamente proibido** marcar itens como "pendente de validação visual do operador" por comodidade.
- A única exceção aceitável é quando o recurso depender de infraestrutura externa inacessível no ambiente local de testes (ex: webhook de produção ou gateway bancário sem sandbox/mock). Nesses casos raros, o agente deve registrar no `VALIDATION-CHECKLIST.md` a razão técnica detalhada e o teste parcial executado.
