# BATCH-264 — Linha 3.1: mais modelos e modo IA nas "Páginas de Lousa"

- **Requisição:** [REQ-255](../../human-requests/archive/req-255.md)
- **Status:** `implemented-pending-homologation`
- **Linha:** `3.1` (branch `feat/req-255`, entregue na `3.1`). Não está na `main` nem na `3.0`.
- **Data:** 2026-10-07
- **Ambiente de teste:** `https://v3.1-conn2flow.local/` (projeto `conn2flow-v31-local`)

## Live Todo List

- [x] Dois modelos novos, com estrutura em volta da lousa (total de quatro)
- [x] Modo IA e alvo de IA do `dashboard-pages`, nos dois idiomas
- [x] Aba Modelos do editor conferida no navegador
- [x] Testes, publicação na instalação da 3.1 e roteiro de navegador
- [ ] Assistente IA exercitado com um servidor de IA
- [ ] Homologação humana

## O que mudou

| Modelo | O que traz em volta da lousa |
|---|---|
| Simples (já existia) | Título e lousa, centrados em até 1280 px |
| Largura total (já existia) | Título centrado e lousa na largura da janela |
| **Campanha** (novo) | Faixa de abertura com rótulo, título e frase de apoio; lousa; chamada final com botão para a página de contato |
| **Painel com navegação** (novo) | Barra com o nome e três links (âncoras e contato); lousa na largura da janela; rodapé de texto |

Os textos dos modelos novos são exemplos por idioma, editáveis no editor. Os links usam a variável de raiz do site.

**Modo IA** (`ai_modes/dashboard-pages`, padrão do alvo): diz à IA para gerar só a moldura, com `[[lousa#widget]]` uma vez, `[[lousa#titulo]]` dentro do bloco de título, sem script nem iframe, sem repetir cabeçalho e rodapé do site, e com classes próprias. **Alvo de IA** `dashboard-pages` registrado para os prompts.

## Limites

- **O Assistente IA não foi exercitado**: a instalação da 3.1 não tem servidor de IA configurado (a aba mostra "Nenhum servidor de IA disponível"). O modo e o alvo estão no banco; a geração em si não foi vista.
- Os modelos não têm miniatura (como os demais modelos de módulo; ver a requisição de miniaturas).
- Texto de exemplo dos modelos novos precisa ser trocado pelo autor.

## Arquivos

- `gestor/modulos/dashboard-pages/dashboard-pages.json`
- `gestor/modulos/dashboard-pages/resources/{pt-br,en}/templates/dashboard-pages-campanha/`, `dashboard-pages-painel/`
- `gestor/modulos/dashboard-pages/resources/{pt-br,en}/ai_modes/dashboard-pages/dashboard-pages.md`
- `tests/Unit/PHP/DashboardPagesReq253Test.php`, `sdd/validation/req253/req253-browser.cjs`

## Validação

| Checagem | Resultado |
|---|---|
| PHPUnit (suíte completa) | 1.710 testes, sem falha |
| Vitest (suíte completa) | 648 testes, sem falha |
| `resources:sync` e `project:update-all conn2flow-v31-local` | código de saída 0; quatro modelos, modo e alvo de IA conferidos no banco |
| Navegador, `req253-browser.cjs` | 42/42: a aba Modelos lista os quatro; os dois modelos novos geram página no ar com a estrutura, o título, a lousa e os links resolvidos; 390 px sem rolagem lateral |

Imagem do modelo "Campanha" conferida.

## Critérios de aceite

- [x] Quatro modelos, nos dois idiomas, cada um com o lugar da lousa e o do título.
- [x] Os modelos novos geram página no ar.
- [x] Modo IA e alvo de IA registrados nos dois idiomas.
- [x] A aba Modelos mostra os modelos do alvo. *O Assistente IA abre, mas sem servidor de IA não mostra o modo: não exercitado.*
- [x] PHPUnit e Vitest verdes; roteiro de navegador na 3.1.
