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

---

## 🔁 Validação que prova o que afirma

- **Teste que não pode falhar não é evidência.** Depois de escrever uma verificação, confirme que ela falha no estado antigo: um teste que compara só o título passa com o corpo desatualizado.
- **Exercite o caminho, não o atalho.** Se o log diz `SKIP`, a regra não rodou. Force a execução antes de dizer que ela funciona.
- **Antes e depois.** Mudança que toca dados de um ambiente é validada com uma fotografia das tabelas antes e outra depois.
- **Nomeie o que não foi conferido.** Texto herdado que você manteve sem verificar no código entra no relatório como "mantido, não conferido".
- **Falha de ambiente é dita com o nome.** "1 falha, de fim de linha CRLF no ambiente, registrada no lote X" em vez de "suíte verde".

## Respostas de IA e fixtures realistas (BL-028)

Inspecione o pedido enviado e leia a resposta real, não apenas HTTP 200 e contagem de checks. Imprima/registre contexto mascarado e resposta numa evidência de teste restrita, sem contaminar telemetria permanente com texto bruto. Critérios de conteúdo devem ser sustentados pela documentação e pela ação pedida; não exija palavra arbitrária nem proíba frase que esconderia resposta parcial válida.

Fixtures incluem menu fora de main, username com formato de e-mail, rota desconhecida que devolve 200 e editor montado depois do hook. Confira privacidade no payload e na tela, permissões, modos editáveis, conteúdo hostil, ausência de provedor/créditos e timeout. Dublê de banco reproduz chaves de expressões calculadas e separação por vírgula do core.

Crie dados pelo caminho da interface exercitado; limpe no finally, confira que sumiram e registre o resultado, inclusive quando o teste falhar. Imprima o motivo de cada skip (cookie/permissão opcional pode mudar contagens). Abra e examine screenshots. Sem infraestrutura de IA disponível, registre dublês e limitações; não chame esse teste de resposta real homologada.

Relatório inclui: Defeitos achados e corrigidos antes da entrega; O que não foi exercitado; Limites conhecidos.
