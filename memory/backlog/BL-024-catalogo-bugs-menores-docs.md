# BL-024 — Catálogo de bugs menores apontados na reescrita das docs

- **Tipo**: Bug (vários) / Maintainability
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: BAIXA a MÉDIA, item a item
- **Origem**: avisos `[!WARNING]`/`[!CAUTION]` das docs reescritas (req-179, req-180, req-182 a req-186), 2026-09-25/26
- **Como usar**: cada linha aponta a doc (em `ai-workspace/pt-br/docs/`) que descreve o comportamento com mais detalhe. Ao corrigir um item, a doc correspondente perde o aviso.

## Bibliotecas

| Doc | Comportamento |
|---|---|
| `reference/libraries/log.md` | `log_disco()` lê e regrava o arquivo inteiro a cada chamada: lento com arquivos grandes, e duas requisições simultâneas perdem linhas. Pastas `0777`, arquivos `0666`. |
| `reference/libraries/banco.md` | `banco_delete_varios()` chama `count()` antes de checar se é array (`TypeError` no PHP 8) e não escapa os ids; sem chamadores. |
| `reference/libraries/banco.md` | `banco_identificador_unico()` apaga fisicamente o registro soft-deleted com o mesmo id. |
| `reference/libraries/banco.md` | `banco_retirar_acentos()` aplica `strtolower()` antes de trocar acentos: "MIGRAÇÕES" vira "migraCOes". |
| `reference/libraries/banco.md` | `'unico' => false` também devolve uma linha só (`isset`). Conexão em `utf8` (utf8mb3), sem emojis. |
| `reference/libraries/formato.md` | Os códigos do formato são trocados dentro de palavras (`'Data: D'` → `'26ata: 26'`); várias funções não validam a entrada. |
| `reference/libraries/html.md` | `html_adicionar_classe()` copia o `class` do primeiro elemento para todos os que casam. |
| `reference/libraries/host.md` | Nenhuma migração cria `hosts`, `hosts_variaveis`, `hosts_arquivos`; biblioteca inutilizável. |
| `reference/libraries/widgets.md` | Widget ausente ou vazio deixa o mockup e os comentários na página publicada. |
| `reference/libraries/sitemap.md` | `Disallow` absolutos não casam em instalação em subpasta nem nas versões `/en/`. |

## Módulos

| Doc | Comportamento |
|---|---|
| `reference/modules/admin-arquivos.md` | Upload com `dir` recusado cai na raiz (`?: ''`); o JSON declara `tabela: arquivos`, que não é mais usada. |
| `reference/modules/admin-environment.md` | Gravação do `.env` informa sucesso sem conferir os bytes escritos. |
| `reference/modules/admin-ia.md` | `salvar_modelos_globais` apaga e reinsere sem transação; o JS de edição lê o checkbox `padrao` sem guarda. |
| `reference/modules/admin-modos-ia.md` | Marcar um modo como padrão limpa `padrao` em `prompts_ia`, não em `modos_ia`. |
| `reference/modules/admin-prompts-ia.md` | Marcar padrão limpa todos os prompts do alvo, sem filtrar idioma nem usuário. |
| `reference/modules/admin-paginas.md` | Inputs de agendamento não mostram as datas salvas; datas isoladas não são gravadas sem outro campo alterado. |
| `reference/modules/admin-templates.md` | O POST aceita `target` embora a tela o desabilite; unicidade de slug ignora `target`. |
| `reference/modules/admin-categorias.md` | Edição muda `id_modulos` sem percorrer descendentes; breadcrumb sem detecção de ciclo. |
| `reference/modules/modulos.md` | `copiar-variaveis` retorna logo (`$ativar = false`); `sincronizar-bancos` não tem `case`. |
| `reference/modules/modulos-grupos.md` | Renomear o grupo muda o slug sem atualizar `modulos.modulo_grupo_id`. |
| `reference/modules/modulos-operacoes.md` | Renomear a operação não propaga para `usuarios_perfis_modulos_operacoes`. |
| `reference/modules/forms-search.md` e `pages-index.md` | `%`/`_` continuam curingas; a janela de publicação não é considerada; busca registrada duas vezes. |
| `reference/modules/pages-index.md` | "Carregar mais" não é recriado pelo JS; erro de rede pula resultados. |
| `reference/modules/forms-submissions.md` | `reply` marca `responded` mesmo quando o e-mail falha. |
| `reference/modules/publisher-highlights.md` | Um ramo do widget perde `css_compiled` e `html_extra_head`. |
| `reference/modules/galleries.md` | Links de página não testam `sem_permissao` nem a janela de publicação. |

## Próxima ação

O Humano escolhe os itens; os de uma linha podem ser agrupados numa requisição de "correções pequenas", como a req-187.
