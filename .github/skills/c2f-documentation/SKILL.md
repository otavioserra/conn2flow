---
name: c2f-documentation
description: "LEIA ANTES de escrever, corrigir, migrar ou publicar documentação do Conn2Flow (\"faça a documentação de X\", \"rode a rotina de docs\"). Se não ler: a doc nasce do texto antigo e não do código, fora do contrato de frontmatter, sem par pt-br/en, e não chega ao site /docs/."
user-invocable: true
---

# Documentação do Conn2Flow: código → Markdown → site (`c2f-documentation`)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Pedido para documentar, corrigir ou migrar documentação (biblioteca, módulo, API, CLI, hook, guia); rodar a rotina periódica de docs; publicar as docs no site `/docs/`; criar ou alterar `docs.config.json` de um projeto.
- **SKIP APENAS SE**: Edição de artefatos SDD (`sdd/`), README de repositório ou docblocks de código sem relação com `ai-workspace/<idioma>/docs/`.
- **CONSEQUÊNCIA DE IGNORAR**: Documentação defasada ou alucinada (escrita a partir do texto antigo), docs sem `verified_at` invisíveis ao ranking, tradução divergente, e páginas editadas no painel sobrescritas no próximo build.

Complementa a `c2f-documentation-governance` (princípio da autoridade do código). Esta skill é o **procedimento**.

---

## 1. Onde a documentação mora (fonte única)

- **Fonte:** `conn2flow/ai-workspace/<idioma>/docs/` no repositório do Core. Idiomas: `pt-br` e `en`.
- **Árvore:** `guides/`, `concepts/`, `reference/{libraries,modules,api,cli,hooks}/`, `whats-new/` e o `index.md` da raiz.
- **Espelhamento:** pastas e nomes de arquivo são **iguais nos dois idiomas**. O caminho relativo define o par de tradução e a URL pública (`/docs/<caminho>/`).
- **Legado:** arquivos em MAIÚSCULAS na raiz de `docs/` (e subpastas antigas como `bibliotecas/`, `modulos/`, `manual/`). Ao migrar um assunto, a doc nova nasce na árvore e o legado correspondente é removido na mesma passagem.
- **Contrato completo:** `ai-workspace/pt-br/docs/guides/documentation.md`, que é ele mesmo uma doc do sistema.

## 2. Frontmatter obrigatório

```yaml
---
title: "Biblioteca modelo.php"
description: "Uma frase — índices, compartilhamento e llms.txt."
section: reference          # guides | concepts | reference | whats-new (= pasta); home só no index.md raiz
order: 20                   # opcional (padrão 100)
visibility: public          # public (padrão) | restricted (reservado: o build não publica)
module: menus               # opcional
sources:                    # obrigatório em reference/
  - gestor/bibliotecas/modelo.php
verified_at: 5b4348ab       # git rev-parse --short HEAD do Core no momento da conferência
---
```

## 3. Procedimento por passagem (a rotina)

1. **Ranking:** `php cli/c2f.php docs:audit` (no Core). Pegue os N itens do topo. `missing:` significa que a doc não existe.
2. **Esqueleto** (só para biblioteca nova): `php cli/c2f.php docs:extract <lib> --create`. Isso cria pt-br e en com o bloco de funções.
3. **Leitura profunda do código, ANTES de qualquer texto antigo:**
   - **Biblioteca:** o arquivo inteiro, os chamadores (`grep -rn "funcao(" gestor`), os testes em `tests/`.
   - **Módulo:** `<id>.php` (switch de opções e AJAX), `<id>.json` (tabela, resources, hooks), JS, `<id>.widget.php`/`.widget.js`, `resources/<idioma>/` (pages, templates, `ai_modes`), migrations da tabela, `hooks`/`api`.
   - **Sistema/CLI/API:** o controlador ou comando e o fluxo de chamada real.
4. **Escrever o comportamento real:**
   - caixa de letras;
   - primeira ocorrência;
   - padrões e *defaults*;
   - o que acontece quando falta algo;
   - riscos de segurança.
   Se o código contradiz o próprio docblock, a doc segue o código e aponta a divergência. **Marque o legado** (função sem chamadores, chamada a função inexistente). Use exemplos tirados de usos reais do core.
5. **Bilíngue na mesma passagem:** escreva pt-br e en juntos. Nada de "traduzir depois".
6. **Fechar a doc:**
   - preencha `sources` e `verified_at`;
   - rode `php cli/c2f.php docs:extract --all`;
   - rode de novo `php cli/c2f.php docs:audit` até o item sair do ranking (score 0).
7. **Publicar (local):**
   - `php cli/c2f.php docs:build --project=conn2flow-site-local`, depois `php cli/c2f.php project:update-all conn2flow-site-local`;
   - confira as páginas (HTTP 200, `c2f page:inspect ... --screenshot`).
   - O deploy de **produção é do operador**.
8. Registre no SDD do lote e atualize a memória de execução.

## 4. Escrita

- Callouts: `> [!NOTE]`, `> [!TIP]`, `> [!IMPORTANT]`, `> [!WARNING]`, `> [!CAUTION]` (primeira linha sozinha).
- Links entre docs: relativos para o `.md` (`../modules/menus.md`). Link quebrado **aborta** o build.
- Links para o código: relativos à doc (`../../../../../gestor/bibliotecas/x.php#L10`). O build converte para o GitHub.
- Marcadores do Gestor (`@[[grupo#var]]@`, `<!-- widgets#... -->`, `<!-- item < -->`) podem ser mostrados em código ou texto. O build neutraliza a arroba; não "corrija" isso na doc.
- Não edite dentro de `<!-- c2f:extract:start -->` … `<!-- c2f:extract:end -->`. O `docs:audit` avisa quando uma função do código não é citada fora do bloco.

## 5. Publicação num projeto (`docs:build`)

- **Configuração:** `<gestor do projeto>/docs.config.json` define:
  - `languages`, `base_path`, `layout`;
  - `article_template` (template `target=publisher`);
  - `menu` (id + template `target=menus`);
  - `publishers` (seções → publisher, `index_path` e `index_widget` da página de entrada com `publisher-index`);
  - `repository`/`branch`, `site_url` e `labels` por idioma.
- **Requisito:** `resources/project_tables_config.json` com `sync_resources` para `publisher`, `publisher_pages`, `publisher_index` e `menus`.
- **O que é gerado** (ids `docs` e `docs-*`, **gerenciados pelo build**, que nunca devem ser editados à mão nem no painel):
  - `pages/`;
  - `publisher_pages/`;
  - `menus/<menu>/`;
  - as entradas em `pages.json`, `publisher-pages.json` e `menus.json`;
  - `assets/docs/llms*.txt`.
- O build é idempotente. Rodar duas vezes sem mudar o Markdown não altera arquivo.
- Referência de implementação: `conn2flow-site` (REQ-057 / BATCH-050) e Core `cli/src/Support/Docs/`.

## ⛔ Proibições

- Documentar a partir de memória, do texto legado ou de outra doc sem abrir o código.
- Publicar só um idioma, ou deixar `verified_at` vazio.
- Editar páginas `docs*` pelo painel ou direto em `resources/` do projeto (o próximo build sobrescreve).
- Publicar informação sensível (hosts, IPs, credenciais, caminhos de VPS, tokens). Na dúvida, `visibility: restricted`.
- Deploy de produção pelo agente.
