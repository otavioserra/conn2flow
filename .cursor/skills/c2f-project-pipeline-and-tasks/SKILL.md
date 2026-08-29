---
name: c2f-project-pipeline-and-tasks
description: "LEIA ANTES de sincronizar, compilar, testar ou fazer deploy de projetos e sites locais ou remotos. Se não ler: sincronização por cópia manual (cp) em vez do pipeline, banco desincronizado, *Data.json não recompilados, migrações Phinx não aplicadas e deploy acidental em produção."
user-invocable: false
---

# Pipeline de Projetos, Autoridade Declarativa e Tasks do Conn2Flow

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Sincronizar alterações do Core para projetos, compilar recursos, testar em ambiente local, ou fazer deploy de projetos/sites.
- **SKIP APENAS SE**: Edição isolada de arquivos do Core sem necessidade de propagação para projetos.
- **CONSEQUÊNCIA DE IGNORAR**: Arquivos divergentes entre Core e projetos, `*Data.json` não recompilados, migrações Phinx não aplicadas, banco desincronizado e risco de deploy acidental em ambiente de produção.

---

## ⛔ Regra #1: Pipeline ≠ Cópia de Arquivo

> [!CAUTION]
> É **estritamente proibido** sincronizar alterações entre o Core e projetos usando cópia manual de arquivos (`cp`, `copy`, `Copy-Item`, `xcopy`). Isso resulta em:
> - `*Data.json` não recompilados (recursos desatualizados no banco)
> - Migrações Phinx não aplicadas (schema divergente)
> - Banco SQL desincronizado com os arquivos em disco
> - Arquivos de espelho (`dev-environment/data/sites/...`) inconsistentes

### Pipeline Mandatório para o Sistema (Core):
```bash
./c2f manager:update-all
```
**Sequência**: Core → Resources (`resources:sync`) → Files → Database (Upsert + Migrations)

### Pipeline Mandatório para Projetos:
```bash
./c2f project:update-all <projectID>
```
**Sequência**: Core → DB → Resources → Files → DB (Upsert + Migrations)

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
| 🗃️ Projects - Update All → ID | `c2f project:update-all <id>` | Pipeline completo: Core → DB → Resources → Files → DB |
| 🚀 Projects - Deploy Project → ID | `c2f project:deploy <id>` | Deploy do projeto para o servidor de destino |
| 📦 Manager - Update All | `c2f manager:update-all` | Pipeline completo do sistema: Core → Resources → Files → DB |
| 🎨 Tailwind - Sync Resources | `c2f resources:sync` | Compila recursos e gera `*Data.json` |
| 🧪 Manager - Run Tests | `c2f test:run` | Executa suite de testes automatizados |
| 🗄️ DB - Run Migrations | `c2f db:migrate` | Aplica migrações Phinx pendentes |
| 🗄️ DB - Create Migration | `c2f db:create-migration <name>` | Cria nova migração Phinx |

> [!TIP]
> Ao documentar procedimentos ou instruções para agentes, sempre referencie o **comando CLI** (`c2f ...`) em vez do nome da task do VS Code. O CLI é universal e funciona em qualquer terminal, IDE ou pipeline CI/CD.
