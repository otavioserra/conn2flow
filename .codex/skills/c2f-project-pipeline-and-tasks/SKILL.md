---
name: c2f-project-pipeline-and-tasks
description: "LEIA ANTES de sincronizar, compilar, testar ou fazer deploy de projetos e sites locais ou remotos. Se não ler: sincronização por cópia manual (cp) em vez do pipeline, banco desincronizado, *Data.json não recompilados, estado híbrido pós-deploy por ausência de css:rebuild e paralelismo concorrente travando o PHP."
user-invocable: false
---

# Pipeline de Projetos, Autoridade Declarativa e Tasks do Conn2Flow

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Sincronizar alterações do Core para projetos, compilar recursos, testar em ambiente local, ou fazer deploy de projetos/sites.
- **SKIP APENAS SE**: Edição isolada de arquivos do Core sem necessidade de propagação para projetos.
- **CONSEQUÊNCIA DE IGNORAR**: Arquivos divergentes entre Core e projetos, `*Data.json` não recompilados, migrações Phinx não aplicadas, estado híbrido pós-deploy por falta de `css:rebuild`, banco desincronizado e supressão de warnings PHP por paralelismo indevido.

---

## ⛔ Regra #1: Pipeline ≠ Cópia de Arquivo

> [!CAUTION]
> É **estritamente proibido** sincronizar alterações entre o Core e projetos usando cópia manual de arquivos (`cp`, `copy`, `Copy-Item`, `xcopy`). Isso resulta em:
> - `*Data.json` não recompilados (recursos desatualizados no banco)
> - Migrações Phinx não aplicadas (schema divergente)
> - Banco SQL desincronizado com os arquivos em disco
> - Arquivos de espelho (`dev-environment/data/sites/...`) inconsistentes
> - **Estado híbrido pós-deploy** (HTML novo servido com CSS desatualizado em cache)

### Pipeline Mandatório para o Sistema (Core / 4 etapas):
```bash
./c2f manager:update-all
```
**Sequência Canônica de 4 Etapas**:
1. **Core**: Sincronização do núcleo do sistema.
2. **Resources**: Compilação de recursos e metadados (`c2f resources:sync`).
3. **Files**: Atualização de arquivos físicos e permissões.
4. **Database & CSS Rebuild**: Upsert no Banco SQL + Migrações Phinx + **Reconstrução Final de CSS (`c2f css:rebuild`)**.

### Pipeline Mandatório para Projetos (6 etapas):
```bash
./c2f project:update-all <projectID>
```
**Sequência Canônica de 6 Etapas**:
1. **Core**: Atualização dos componentes base do Core.
2. **Database (Pré)**: Validação e preparação do estado inicial do banco.
3. **Resources**: Compilação de recursos (`c2f resources:sync`).
4. **Files**: Sincronização do espelho de arquivos do projeto.
5. **Database (Pós)**: Upsert no Banco SQL + Migrações Phinx pendentes.
6. **CSS Rebuild**: Reconstrução final do CSS derivado a partir do HTML real no banco (`c2f css:rebuild`).

> [!IMPORTANT]
> **Prevenção do Estado Híbrido Pós-Deploy**:
> O `css:rebuild` no encerramento de ambos os pipelines é a etapa mandatória que recalcula o CSS derivado (`css_precompiled` e `css_compiled`) a partir do HTML real no banco de dados. Isso **impede o estado híbrido** (CSS antigo/stale em cache vs novo HTML entregue) de retornar após cada deploy.

---

## 🏷️ Regra #2: Autoridade Declarativa de `devProjects.<id>.local`

Antes de interagir com QUALQUER projeto, o agente DEVE inspecionar o campo `local` do projeto em `dev-environment/data/environment.json`:

```json
{
  "devProjects": {
    "transformamp": {
      "local": false
    },
    "transformamp-local": {
      "local": true
    }
  }
}
```

| Valor de `local` | Significado | Permissões do Agente |
|---|---|---|
| `true` | **Ambiente de teste local** | ✅ Liberdade total: alterar, testar, quebrar, fazer deploy, manipular banco |
| `false` | **Ambiente de produção real** | 🔒 Exige autorização explícita e confirmação do operador humano |

> [!WARNING]
> Projetos existem em pares (ex: `transformamp` / `transformamp-local`, `snapphoton` / `snapphoton-local`). Os comandos de leitura parecem idênticos, mas o **destino do deploy** e o **banco de dados** são diferentes. Sempre verifique `local` antes de qualquer operação destrutiva.

---

## 📊 Regra #3: Fonte da Verdade em Runtime

O runtime do Conn2Flow serve HTML e CSS exclusivamente do **BANCO DE DADOS** (`gestor.php:2782`). O diretório `resources/` é lido diretamente apenas sob `DEVELOPMENT_ENV=true`.

**Ao investigar o comportamento de uma página publicada:**
1. **Primeiro**: Inspecione o banco de dados (tabelas `paginas`, `layouts`, `componentes`).
2. **Depois**: Compare com os arquivos em `resources/` para identificar divergências.
3. **Nunca**: Assuma que o conteúdo em disco é o que está sendo servido ao visitante.

---

## 🔄 Regra #4: Tabela de Equivalência VS Code Tasks ↔ CLI `c2f`

As tarefas definidas em `.vscode/tasks.json` são atalhos visuais para os comandos nativos do Core CLI:

| VS Code Task | Comando CLI Equivalente | Descrição |
|---|---|---|
| 🗃️ Projects - Sync Core → ID | `c2f project:sync-core <id>` | Sincroniza o Core para o espelho do projeto |
| 🗃️ Projects - Update All → ID | `c2f project:update-all <id>` | Pipeline de 6 etapas: Core → DB → Resources → Files → DB → CSS Rebuild |
| 🚀 Projects - Deploy Project → ID | `c2f project:deploy <id>` | Deploy do projeto para o servidor de destino |
| 📦 Manager - Update All | `c2f manager:update-all` | Pipeline de 4 etapas: Core → Resources → Files → DB + CSS Rebuild |
| 🎨 Tailwind - Sync Resources | `c2f resources:sync` | Compila recursos e gera `*Data.json` |
| 🧪 Manager - Run Tests | `c2f test:run` | Executa suite de testes automatizados |
| 🗄️ DB - Run Migrations | `c2f db:migrate` | Aplica migrações Phinx pendentes |
| 🗄️ DB - Create Migration | `c2f db:create-migration <name>` | Cria nova migração Phinx |

> [!TIP]
> Ao documentar procedimentos ou instruções para agentes, sempre referencie o **comando CLI** (`c2f ...`) em vez do nome da task do VS Code. O CLI é universal e funciona em qualquer terminal, IDE ou pipeline CI/CD.

---

## ⚡ Regra #5: Execução Sequencial Exclusiva & Proibição de Paralelismo em Lote

> [!CAUTION]
> **Proibição Estrita de Comandos de Compilação em Paralelo**:
> Comandos pesados de compilação, banco de dados ou sincronização em lote (`css:rebuild`, `resources:sync`, `project:update-all`, `manager:update-all`, `db:migrate`) DEVEM ser executados **estritamente de forma sequencial (um por vez)** no mesmo ambiente/container.

**Por que o paralelismo é destrutivo:**
1. **Travamento de Processos PHP**: Múltiplos processos simultâneos disputando banco e I/O travam o container Docker ou deixam conexões pendentes.
2. **Supressão Silenciosa de Warnings/Notices**: Rodar comandos pesados em background ou com redirecionamentos que suprimam `stderr` oculta erros de runtime vitais.
   - *Caso Real Documentado*: O erro `$fontesExtras` sem parâmetro na assinatura do método permaneceu invisível por horas no Core porque o comando rodava em background com buffer suprimido — as `tailwind_sources` nunca eram aplicadas e nada no terminal acusava o warning.

**Diretrizes Mandatórias:**
* **Foreground Obrigatório**: Sempre execute comandos de compilação em foreground direto.
* **Saída Desbufferizada**: Nunca redirecione saídas para descartar `stderr` (`2>&1 > /dev/null`). Permita que notices, warnings e stack traces do PHP sejam visíveis no terminal imediatamente para correções a quente.
* **Ordem Estrita**: Espere um comando terminar com código de saída 0 antes de iniciar o próximo.
