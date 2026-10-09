# BATCH-213: README (en e pt-br) e descrição do GitHub — plataforma PHP e AMS (req-205)

Execução da [req-205](../../human-requests/archive/req-205.md).

**Status**: `in-review` (escrito e conferido; revisão humana pendente).
**Worktree**: `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-readme`, branch `feat/req-205`.

## O que mudou

| Item | Antes | Depois |
|---|---|---|
| Título e abertura | "CMS, Backend Framework, and Content Automation Platform" | Plataforma PHP open source para construir e operar produtos digitais com IA, e Agent Management System |
| Estrutura | 255 linhas, com 14 versões listadas uma a uma | O que é, o que tem dentro (tabela por área), como pessoas e agentes trabalham, começar, documentação por objetivo, mapa do repositório, ecossistema, situação |
| Links para docs | 8 apontavam para arquivos removidos na reescrita das docs (`vision/README.md`, `CONN2FLOW-*.md`) | 39 links por arquivo, todos resolvendo |
| Site | Sem link | `conn2flow.com`, Plataforma e Pro, no topo e na seção Ecossistema |
| Histórico de versões | No corpo do README | Seis itens recentes, com link para Novidades e Changelog |
| Descrição do GitHub | "lightweight and flexible open-source CMS built using LAMP" | Plataforma PHP e AMS; site e tópicos preenchidos |

## Decisões de conteúdo

- "Agent Management System" é explicado como os controles de um CMS (identidade, permissão, escopo, validação, auditoria) estendidos ao trabalho assistido por IA. O texto diz que o processo é regra do projeto, não garantia do runtime, como a doc de visão.
- Números arredondados para baixo: "50+ comandos" (o `c2f help` lista 56), "30+ módulos" (33 documentados), "40+ bibliotecas" (41).
- Nexus aparece como direção, sem dependência. O aplicativo móvel saiu do README.
- `/docs/` e `/noticias/` do site não foram linkados: respondem 404 em produção nesta data. Quando forem publicados, vale trocar a tabela de documentação para apontar para o site.

## Validação

- Links relativos: script percorre os dois arquivos; 39 de 39 existem em cada um.
- Links do site: `conn2flow.com/`, `/plataforma/` e `/pro/` respondem 200.
- `gh repo view`: descrição, site e tópicos conferidos depois da alteração.
- Sem alteração de código; nenhuma suíte precisa rodar.

## Pendências

- Revisão humana do texto e mesclagem.
- Trocar os links de documentação para o site quando `/docs/` estiver em produção.
