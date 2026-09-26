---
title: "Sistema de recursos"
description: "Como layouts, páginas, componentes, templates, variáveis e demais recursos saem de arquivos em resources/, viram *Data.json e chegam ao banco, e como o deploy preserva o que o usuário editou."
section: concepts
order: 30
sources:
  - gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php
  - gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php
  - gestor/resources/resources.map.php
  - gestor/resources/tables_config.json
  - cli/src/Commands/ResourcesSyncCommand.php
  - cli/src/Commands/CssRebuildCommand.php
  - gestor/controladores/agents/arquitetura/atualizacao-versoes-assets.php
  - gestor/bibliotecas/gestor.php
verified_at: ceb259cb
---

# Sistema de recursos

Um **recurso** é qualquer registro do banco que nasce de arquivos versionados: um layout, uma página, um componente, uma variável de texto, um prompt de IA, uma tarefa agendada, um menu. Você edita arquivos; o pipeline os compila em `*Data.json`; o atualizador grava no banco.

> [!IMPORTANT]
> Em produção, o site é servido **do banco**. Editar um arquivo em `resources/` não muda nada no ar até o pipeline rodar. Só com `DEVELOPMENT_ENV=true` o runtime lê HTML e CSS direto dos arquivos (layouts, páginas e componentes), e mesmo assim os metadados (caminho, layout, permissão) continuam vindo do banco.

## O caminho de um recurso

```
resources/<idioma>/<tipo>/<id>/<id>.html (+ .css, .precompiled.css, .md)
resources/<idioma>/<tipo>.json            metadados (nome, caminho, layout, versão, checksum)
        │  c2f resources:sync   (atualizacao-dados-recursos.php)
        ▼
gestor/db/data/<Tabela>Data.json   +   gestor/db/data/schema-metadata.json
        │  atualizador (atualizacoes-banco-de-dados.php), no deploy
        ▼
tabelas paginas, layouts, componentes, templates, variaveis, …
        │  c2f css:rebuild
        ▼
css_precompiled regenerado a partir do HTML do banco, com assinatura de procedência
```

Para o sistema, o pipeline completo é `c2f manager:update-all`. Para um projeto, `c2f project:update-all <id>`. Os dois terminam com `css:rebuild`.

## Onde os recursos vivem

| Origem | Pasta | Tipos |
|---|---|---|
| Globais do core ou do projeto | `gestor/resources/<idioma>/` com os arquivos listados em `resources.map.php` | `layouts`, `pages`, `components`, `templates`, `variables` |
| Módulos | `gestor/modulos/<id>/resources/<idioma>/`, declarados no bloco `resources.<idioma>` do `<id>.json` | os mesmos e mais `ai_prompts`, `ai_prompts_targets`, `ai_modes`, `widgets` (só registro, sem arquivos) e `forms` |
| Tarefas agendadas | chave `cron` na **raiz** do `<id>.json` do módulo | uma por `id`, sem idioma (biblioteca `cron.php`) |
| Tabelas declarativas | `tables_config.json` (core), `project_tables_config.json` (projeto) ou o bloco `tabela` do módulo, com `sync_resources: true` | qualquer tabela: `menus`, `publisher_pages`, `pages_index`… |

Os idiomas vêm de `resources.map.php` (`pt-br` e `en` no core). Um recurso que só existe em um idioma simplesmente não existe no outro.

### Anatomia

```
gestor/resources/pt-br/pages.json
gestor/resources/pt-br/pages/404-pagina-nao-encontrada/404-pagina-nao-encontrada.html
```

```json
{
    "name": "404 - Página Não Encontrada",
    "id": "404-pagina-nao-encontrada",
    "layout": "layout-pagina-sem-permissao",
    "path": "404/",
    "type": "page",
    "without_permission": true,
    "version": "1.4",
    "checksum": { "html": "ee70…", "css": "", "combined": "ee70…" }
}
```

Os metadados usam nomes em inglês que o compilador traduz para as colunas: `name` → `nome`, `layout` → `layout_id`, `path` → `caminho`, `type` → `tipo` (`page` → `pagina`, `system` → `sistema`), `module` → `modulo`, `option` → `opcao`, `root` → `raiz`, `without_permission` → `sem_permissao`. As formas em português também são aceitas. Página sem `path` ganha `<id>/`.

## Versão e checksum

- **Não edite `version` nem `checksum` à mão.** A etapa 2 do `resources:sync` recalcula o checksum de cada recurso e, se mudou, incrementa `version` e **reescreve o JSON de origem**. Por isso um `resources:sync` costuma deixar `pages.json`, `layouts.json` e os `<modulo>.json` alterados no Git: é esperado e deve ser commitado junto.
- O checksum é **MD5** (`html`, `css`, `css_precompiled` e `combined`); prompts usam o MD5 do `.md`. Não é uma verificação de segurança, só de mudança.
- A versão tem o formato `X.Y` e só o `Y` sobe (`1.27` → `1.28`). Um valor fora desse formato (`2.0.1`, `v2`) é **reiniciado para `1.0`**.
- No `*Data.json` há duas versões: `file_version` (a da origem) e `versao`, um inteiro que sobe quando o checksum difere do `*Data.json` anterior.

## Unicidade e órfãos

O compilador rejeita duplicados e os grava em `gestor/db/orphans/<Tipo>Data.json`, fora do deploy:

| Tipo | Único por |
|---|---|
| Layouts, componentes | idioma + módulo + id |
| Páginas | idioma + módulo + id **e** idioma + caminho (sem barras, minúsculo), mesmo entre módulos |
| Templates | idioma + `target` + id |
| Variáveis | idioma + módulo + id + grupo. Sem grupo, só uma |
| Prompts, modos, alvos de IA, widgets | idioma + id |
| Tarefas de cron | id (também são rejeitadas sem callback ou com frequência inválida) |

> [!WARNING]
> Um recurso órfão some do deploy **sem erro**: o `resources:sync` termina com sucesso. Depois de adicionar recursos, confira o relatório final ou a pasta `gestor/db/orphans/`. Dois módulos com uma página no mesmo caminho é o caso mais comum.

## O atualizador: o que é sobrescrito no deploy

O atualizador compara cada `*Data.json` com a tabela e só processa as tabelas cujo arquivo mudou desde a última execução. As regras de cada tabela vêm de `schema-metadata.json`, gerado pelo compilador a partir de `tables_config.json`, do bloco `tabela` dos módulos e do `project_tables_config.json`:

- **`strategy`**: `natural_key` (casa pelas colunas de `natural_key_columns`, como `language, modulo, id`) ou `pk`.
- **`insert_only`**: só insere, nunca atualiza (é o caso de `usuarios`).
- **`preserve_on_user_modified`**: campos protegidos quando o registro tem `user_modified=1`, ou seja, foi editado pelo painel. Em `paginas`: `nome, layout_id, caminho, framework_css, sem_permissao, html, css, css_compiled`.
- **`deletar`** e **`forcar_atualizacao`**: listas de registros a remover ou a sobrescrever ignorando as proteções (e voltando `user_modified` a 0).

Quando um campo protegido difere, o valor novo do sistema **não se perde**: vai para a coluna espelho (`html_updated`, `css_updated`; em variáveis, `value_updated`) e o registro recebe `system_updated=1`. Hoje nenhuma tela do painel lê essas colunas: a versão do sistema fica guardada, mas aplicá-la é manual (ou por `forcar_atualizacao`).

**Projetos.** No deploy de um projeto (`--project=<id>`), os registros tocados recebem `project=<id>`. Uma atualização posterior do **core** não sobrescreve registros de projeto, a não ser campos não protegidos de registros editados pelo usuário, e nunca troca o `css_precompiled` deles.

Opções do atualizador (`php gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`): `--dry-run`, `--log-diff`, `--debug`, `--force-all`, `--tables=paginas,variaveis`, `--skip-migrate`, `--backup`, `--reverse` (exporta o banco para `*Data.json`), `--orphans-mode=ignore|export|log`, `--hooks-only`, `--project=<id>`, `--env-dir=<dominio>`.

## CSS: autoria e derivado

Cada layout, página, componente e template tem quatro colunas de CSS:

| Coluna | Papel | Quem escreve |
|---|---|---|
| `css` | Autoria: o CSS escrito à mão (`<id>.css`) | você |
| `css_precompiled` | Derivado: o Tailwind compilado para aquele HTML (`<id>.precompiled.css`) | `resources:sync` (a partir dos arquivos) e `css:rebuild` (a partir do banco) |
| `css_compiled` | Derivado: o delta gerado pelo editor online | o editor visual |
| `css_source_hash` | Assinatura de procedência: de que HTML, CSS, layout e versão do Tailwind o derivado saiu | quem gera o derivado |

No HTML final, cada um entra num `<style>` marcado (`data-c2f-css-role="authored"`, `data-tailwind-role="page-precompiled"`, `data-c2f-css-role="compiled"`), deduplicado por hash.

Quando o deploy muda a autoria ou o derivado, o atualizador zera `css_source_hash`: o CSS atual continua servido, mas o recurso passa a contar como **desatualizado**, e o `c2f css:rebuild` o recompila contra o HTML que está no banco. É o que evita o estado híbrido "HTML novo com CSS antigo". Veja `c2f css:rebuild --help` para `--project`, `--tipo`, `--id`, `--todos` e `--dry-run`.

## Tabelas declarativas (`sync_resources`)

Qualquer tabela pode virar recurso sem código novo. No `project_tables_config.json` do projeto (ou no bloco `tabela.config` de um módulo):

```json
{
  "tabelas": {
    "menus": {
      "nome": "menus",
      "id": "id",
      "id_numerico": "id_menus",
      "config": {
        "scope": "global",
        "strategy": "natural_key",
        "natural_key_columns": ["language", "id"],
        "sync_resources": true,
        "metadata_file": "menus.json",
        "field_types": { "html": "file:html", "css": "file:css", "fields_schema": "json" }
      }
    }
  }
}
```

- `scope`: `global` (arquivos em `gestor/resources/`) ou `module` (arquivos em `gestor/modulos/<modulo>/resources/`, com `"modulo": "<id>"`; a coluna `modulo`/`module` é preenchida sozinha).
- `metadata_file`: JSON com a lista de registros, em `<base>/<idioma>/<resources_dir ou tabela>/`. Sem ele, os registros ficam inline em `resources.<idioma>.<tabela>`.
- `field_types`: `json` serializa o valor; `file:<ext>` injeta o conteúdo de `<id>/<id>.<ext>`.
- `id`: a coluna que identifica o registro (pode ser outra, como `page_id`).
- Os registros ganham `language` e `status='A'` quando ausentes.
- O compilador gera `<Tabela>Data.json` (`menus` → `MenusData.json`).

Quando existe `project_tables_config.json`, o compilador também grava `project-schema-metadata.json` na raiz do Gestor do projeto. Ele vai no deploy para que o servidor, que não tem `resources/`, saiba quais tabelas pertencem ao projeto ao responder `_api/project/recover` (o *pull* do banco).

**Data hooks.** Um `data-hooks.php` em `gestor/resources/`, em `gestor/db/` ou na pasta de um módulo é incluído no fim da compilação, para pós-processar os dados gerados.

## Detalhes e armadilhas

- `c2f resources:sync --force` aparece na ajuda, mas **não é repassado** ao compilador: não tem efeito. Da mesma forma, as opções do script (como `--no-origin-update`) só funcionam chamando `php gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php` direto.
- Depois de compilar, o `resources:sync` publica os assets em `public_html/dist/` (`assets:publish --opcional`); sem `PUBLIC_PATH`, só avisa.
- JavaScript, CSS e imagens **estáticos** (servidos por URL, fora de `resources/`) têm token de cache automático: o `resources:sync` calcula o SHA-256 dos arquivos de cada módulo e grava `asset_version` no `<modulo>.json`, e faz o mesmo para `gestor/assets/` em `assets/asset-versions.json`. Não é preciso subir a versão do módulo à mão; basta commitar esses arquivos alterados.
- O runtime em modo de desenvolvimento lê do disco layouts, páginas e componentes, mas **não** templates, variáveis nem tabelas declarativas.

## Veja também

- [Ciclo de uma requisição](request-lifecycle.md): onde o HTML do banco vira página.
- [Biblioteca gestor.php](../reference/libraries/gestor.md): `gestor_componente()`, `gestor_layout()`, `gestor_pagina_recursos_incluir()`.
- Skills `c2f-resources-system`, `c2f-project-pipeline-and-tasks` e `c2f-html-css-pages-and-components`.
