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

### Pipeline Mandatório para Projetos (8 etapas; ver Regra #8):
```bash
./c2f project:update-all <projectID>
```
**Sequência resumida** (a lista completa de 8 etapas está na Regra #8):
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
| 🗃️ Projects - Update All → ID | `c2f project:update-all <id>` | Pipeline de 8 etapas: Core → DB → Resources → Files → DB → CSS Rebuild → JS → dist |
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

---

## 🗑️ Regra #6: Expurgo de Registros Órfãos no Deploy (Lista `deletar`)

O pipeline de banco de dados opera em modo **upsert** e **não exclui** linhas que foram omitidas nas fontes JSON. Para remover páginas, blocos ou metadados obsoletos no deploy, registre explicitamente as chaves naturais no arquivo `project_tables_config.json`:

```json
{
  "tabelas": {
    "paginas": {
      "deletar": ["language", "modulo", "id"]
    },
    "publisher_pages": {
      "deletar": ["language", "page_id"]
    }
  }
}
```

### 6.1 Páginas Semente (`without_permission`)

Para páginas de template que geram instâncias filhas dinâmicas (ex: semente de produto `store/<id>/`), **remova** a propriedade `without_permission` da semente para que:
- A semente em si não conste no sitemap (não é uma página pública real).
- Apenas as páginas filhas públicas geradas a partir da semente constem no sitemap.

---

## 🧠 Regra #7: Diagnóstico de Estouro de Memória no Deploy via API

O endpoint `/_api/project/update` executa a sincronização de banco com `SELECT *` e `fetchAll`. Em projetos com centenas de páginas, pode ocorrer estouro do `memory_limit` padrão de 128 MB, gerando HTTP 500 sem corpo descritivo.

### Diagnóstico Obrigatório

Diante de HTTP 500 no deploy via API:

1. **Inspecionar imediatamente** o arquivo `conn2flow-gestor/logs/php-error.log` no servidor de destino.
2. Buscar por `Allowed memory size of ... bytes exhausted`.
3. O endpoint do Core foi ajustado para invocar `api_memoria_minima('1024M')`.

```bash
# Via SSH — verificar últimas linhas do log de erro
ssh usuario@host "tail -50 /path/to/conn2flow-gestor/logs/php-error.log"
```

> [!TIP]
> Se o estouro persistir, considere aumentar o `memory_limit` no `.htaccess` ou `php.ini` do servidor, ou otimizar as queries de sincronização para usar cursores em vez de `fetchAll`.

---

## 🧭 Regra #8: O pipeline de projeto tem 8 etapas (correção da contagem)

A Regra #1 descreve seis etapas; o `c2f project:update-all <id>` executa **oito**. Use esta lista ao ler o log:

1. Sincronizando Core
2. Atualizando Banco de Dados (dados do core)
3. Sincronizando Recursos (compilação do projeto: Tailwind por recurso e `*Data.json`)
4. Sincronizando Arquivos
5. Validação Final do Banco (dados do projeto)
6. Regenerando CSS derivado
7. Minificando JavaScript de autoria
8. Publicando assets estáticos em `dist/`

Para rodar só a compilação do projeto: `c2f project:sync-resources <id>`.

---

## 🔎 Regra #9: Saída 0 não prova que o conteúdo chegou

Caso real (2026-10-01): pipeline com saída 0, `publisher_pages` com 76 atualizações e `paginas` com zero. A gravação do `PaginasData.json` tinha falhado em silêncio (arquivo bloqueado numa pasta sincronizada). O core passou a interromper a compilação nesse caso, mas a conferência continua obrigatória.

Depois de publicar uma mudança de conteúdo, confira **três pontos**, nesta ordem:

1. **O dado compilado**: `grep` do texto novo em `gestor/db/data/PaginasData.json` do projeto. O arquivo de recurso (`resources/.../pagina.html`) estar certo não basta.
2. **O log do banco**: a linha `SYNC_FIM tabela=<tabela> +i ~u =s` da etapa 5. `~0` numa tabela que você alterou é sinal de problema.
3. **A página no ar**: o texto novo na resposta HTTP. Teste que compara só o título não percebe um corpo antigo.

`SKIP_NO_CHECKSUM_CHANGE tabela=<t>` significa que a tabela nem foi comparada. Para exercitar uma regra de sincronização que mudou:

```bash
bash ai-workspace/en/scripts/dev-environment/updates-manager-database.sh --project <id> --tables <tabela> --force-all
```

---

## 🚦 Regra #10: Um deploy por vez no mesmo ambiente

O pipeline por SSH não tem trava. Dois agentes publicando no mesmo ambiente geram respostas 500/503 passageiras e validações falsas.

- Antes de publicar e antes de validar, confira se o ambiente está ocioso: data de modificação do log `logs/atualizacoes-bd-<data>.log` e da pasta `resources/` no destino. Menos de dois minutos: espere.
- Falha 500/503 intermitente numa rota que respondia 200 é, antes de tudo, sinal de deploy concorrente. Repita com o ambiente ocioso antes de investigar o código.
- Árvore compartilhada com lote de outro agente em andamento não é origem de pipeline: o trabalho inacabado dele vai junto. Use uma worktree limpa (`scripts/git/create-agent-worktree`), com `dev-environment/data/environment.json` copiado.

---

## 🧱 Regra #11: Regras de dados do sincronizador

- `insert_only` vale para as estratégias `pk` e `natural_key`. Ao mudar a estratégia de uma tabela no contrato, confira que as proteções existem no ramo novo de `sincronizarTabela()`.
- **Retirada por dono**: o que o dono deixa de entregar recebe `status='D'` (ou é apagado, se a tabela não tem `status`). O que volta a ser entregue é reativado pelo manifesto de retirados. Uma entrega com fonte incompleta desativa registros de verdade: nunca publique a partir de fonte pela metade.
- **Compilação de projeto usa as sementes do projeto.** Tabela declarada pelo core sem semente no projeto é pulada (`DYNAMIC_SKIP_PROJETO_SEM_SEMENTE`).
- O pipeline de projeto roda **sem backup** das tabelas. Antes de validar regra de dados num ambiente, fotografe as tabelas envolvidas (uma consulta de leitura salva em arquivo) e compare depois.
