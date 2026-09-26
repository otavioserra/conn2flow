# BATCH-185: Documentação dos Módulos do Core (onda 3, em paralelo)

Execução da [req-180](../human-requests/req-180.md), pelo **segundo agente**. As regras de convivência estão na req-180, §4.

## Progresso

| Módulo | pt-br | en | Legado apagado | Commit | Achados do código |
|---|---|---|---|---|---|
| `publisher` | concluído | concluído | sim | `562ef169` | Legado inventava tabelas e aprovação; exclusividade de template só no seletor; renomear slug não propaga referências; schema sem validação estrutural completa e injetado em script sem JSON_HEX_TAG. |
| `publisher-index` | concluído | concluído | não havia | `562ef169` | Contagem manual inclui slugs inativos/ausentes; widget não verifica ACL/agendamento; cliente descarta busca durante request e não restaura página em erro; count vestigial. |
| `publisher-highlights` | concluído | concluído | não havia | `562ef169` | Ramo com item perde CSS compilado/head; replacement interpreta backreferences; arrobas residuais; LEFT JOIN inclui páginas sem publicação; sem ACL/agendamento no widget. |

## Checklist vivo

Segundo grupo (2026-09-25), commit `7bf08fe1`, enviado a `origin/main`:

| Módulo | pt-br/en | Legado | Achados |
|---|---|---|---|
| `admin-paginas` | concluído | módulo/manual removidos | Janela não hidratada na edição; limpeza exige parâmetros ausentes da tela; datas condicionadas ao acumulador de UPDATE; schema legado incorreto. |
| `publisher-pages` | concluído | módulo/manual removidos | Publicador sem escape em INSERT; valores falsy omitidos; JSON vazio inválido; escritas sem transação; mover preserva campos/template/HTML/CSS e aceita destino inativo. |
| `pages-index` | concluído | não havia | Filtro público sem janela de publicação; resumo decodificado sem escape final; JS herdado contém curadoria ignorada; cache/AbortController/URL/teclado; possível duplicidade de chamada ao logger. |

Terceiro grupo (2026-09-26), commit `b6839aa1`, enviado a `origin/main`:

| Módulo | pt-br/en | Legado | Achados |
|---|---|---|---|
| `forms` | concluído | `modulos/forms.md` removido | Widget herda CSS pré-compilado do template; JS só busca configuração no preview; processador valida honeypot, timestamp, CAPTCHA e limites; senha não é persistida no JSON do envio. |
| `forms-submissions` | concluído | não havia | Resposta com falha de e-mail ainda define `responded`; busca de definição do formulário concatena `form_id`/idioma sem escape; estado de atendimento separado de `status`. |
| `forms-search` | concluído | não havia | GET nativo para o índice, AJAX somente para sugestões; consulta sem janela de publicação e LIKE com curingas; logger de busca pode registrar duas vezes; tabela sem `css_precompiled` próprio. |
| `galleries` | concluído | não havia | HTML do template é copiado ao registro; links de página/publicador não verificam ACL/agendamento; CSS para item sem link é recuperado da cópia/template/`galleries-estados`. |

Quarto grupo (2026-09-26), commit `45812d2e`, enviado a `origin/main`:

| Módulo | pt-br/en | Legado | Achados |
|---|---|---|---|
| `perfil-usuario` | concluído | módulo, hooks e manual removidos | `signup.pos_banco` também é alcançável fora do POST e não recebe id; PAT permitido por `AUTH_API_ALLOWED_PROFILES` e exibido uma vez; sessões e PAT distintos; 2FA/OAuth/recuperação no mesmo controlador. |
| `usuarios` | concluído | módulo/manual removidos | Ramo que propagaria perfil para hosts está desativado por flag fixa; status/exclusão removem `usuarios_tokens`, não PAT; duplicidade de login/e-mail conferida no PHP. |
| `usuarios-perfis` | concluído | módulo/manual removidos | Criação limpa todos os perfis padrão mesmo sem marcar novo padrão e sem filtro de idioma; vínculos por slug, múltiplas consultas sem transação visível. |
| `modulos-operacoes` | concluído | módulo/manual removidos | `id` e `operacao` distintos; renomear `operacao` não propaga grants dos perfis; unicidade calculada no controlador, não declarada no SQL. |
| `modulos` | concluído | módulo/manual removidos | `copiar-variaveis` retorna por guarda desativada; `sincronizar-bancos` tem rota JSON sem caso no switch; `nao_menu_principal` é visual, não ACL. |

Quinto grupo (2026-09-26), commit `a9a226e0`, enviado a `origin/main`:

| Módulo | pt-br/en | Legado | Achados |
|---|---|---|---|
| `modulos-grupos` | concluído | módulo/manual removidos | Renomear slug não propaga `modulos.modulo_grupo_id`; rótulo/ordem do menu e host não são ACL. |
| `admin-arquivos` | concluído | módulo/manual removidos | Acervo em árvore física; `arquivos` é tabela legada; associação por hash de caminho; `dir` inválido no upload cai na raiz; exclusão recursiva física e miniaturas best effort. |
| `admin-categorias` | concluído | módulo/manual removidos | Árvore por id numérico, sem idioma; herda módulo ao criar filho, mas edição não propaga; breadcrumb recursivo sem guarda de ciclo. |
| `admin-componentes` | concluído | módulo/manual removidos | CSS/head incluídos por `gestor_componente`; slug renomeado não atualiza chamadas; CRUD carimba CSS sem compilar, runtime de desenvolvimento pode ler arquivo físico. |
| `admin-layouts` | concluído | módulo/manual removidos | `gestor_layout` tem fallback mínimo; mudança de slug não propaga páginas; pré-compilado tem papel de cascata próprio; CRUD não compila CSS. |

Sexto grupo (2026-09-26), commit `c4e05805`, enviado a `origin/main`:

| Módulo | pt-br/en | Legado | Achados |
|---|---|---|---|
| `admin-templates` | concluído | módulo/manual removidos | POST de edição aceita target apesar de campo desabilitado na tela; índice único `(id, language)` ignora target; helper de IA concatena id na SQL; CRUD não compila CSS. |
| `variables` | concluído | módulo/manual removidos | Tela edita `variaveis` mas tabela principal do JSON é `modulos`; remoção de card apaga variável; botões herdados de status/exclusão foram retirados por afetarem o módulo inteiro; consultas concatenam ids/idioma. |
| `admin-environment` | concluído | módulo/manual removidos | Grava .env sem conferir bytes escritos; modo debug de e-mail sai do envelope AJAX; restrição de acesso inclui guarda de autobloqueio; testes externos e rotação JWT têm efeitos reais. |
| `contatos` | concluído | módulo/manual removidos | Formulário público usa `forms`/`forms_submissions`, sem tabela própria; rota de sucesso é recurso estático, sem caso PHP; componentes de e-mail diferem entre idiomas. |

Sétimo grupo (2026-09-26), commit `98ac881d`, enviado a `origin/main`:

| Módulo | pt-br/en | Legado | Achados |
|---|---|---|---|
| `admin-atualizacoes` | concluído | módulo/manual removidos | Detalhe de plano usa `$dir` indefinido; JSON anuncia metadados de tabela diferentes da migration; wrapper sobrescreve `$_GET`/`$_REQUEST`; atualização é real sem dry_run. |
| `admin-cron` | concluído | não havia | Sincronização preserva estado operacional `user_modified`; tarefa de módulo só tem autoria no arquivo; cartão do agendador infere atividade, não lê crontab; background com setsid pode perder isolamento. |
| `admin-plugins` | concluído | módulo/manual removidos | Instalação roda código e altera arquivos/banco; checksum só quando SHA-256 disponível; índice de `plugins.id` não é único nas migrations; também aceita execução via CLI. |
| `interface` | concluído | não havia | Módulo só contém catálogo de variáveis pt-br/en; não tem controlador, rota nem tabela próprios; rótulos são usados pela biblioteca de interface compartilhada. |

Oitavo grupo (2026-09-26), commit `a8f58483`, enviado a `origin/main`:

| Módulo | pt-br/en | Legado | Achados |
|---|---|---|---|
| `admin-ia` | concluído | módulo/manual removidos | Busca por id numérico concatena entrada na SQL em vários ramos; somente Gemini tem teste; modelos globais gravados em `ia_user_models` sem migration core encontrada e sem transação; JS de edição acessa checkbox opcional sem guarda. |
| `admin-modos-ia` | concluído | módulo/manual removidos | Marcar modo como padrão limpa `prompts_ia`, não `modos_ia`; verificação AJAX também consulta prompts; UPDATE não filtra idioma. |
| `admin-prompts-ia` | concluído | módulo/manual removidos | Padrão por alvo é limpo sem filtro de idioma/usuário, salvo se hook restringir; checagem AJAX também é global por alvo; criação grava proprietário mas edição/listagem não aplicam escopo próprio. |
| `dashboard` | concluído | módulo/manual removidos | `dashboard-testes/` tem rota sem switch; filtro de permissão por página aceita por padrão sem handler; toolbar grava página/layout, backups/histórico e sitemap; layout é compartilhado; dashboard inicial remove página instalação-sucesso. |

- [x] Ler req-180, req-179, contrato, piloto e governança aplicável.
- [x] Executar auditoria inicial: 32 módulos ausentes; piloto menus com score 0 nos dois idiomas.
- [x] Migrar páginas/publicação (publisher, publisher-index, publisher-highlights, admin-paginas, publisher-pages e pages-index).
- [x] Migrar formulários e galerias.
- [x] Migrar usuários, perfis e permissões.
- [x] Migrar demais módulos.
- [x] Remover legados correspondentes e índices antigos autorizados.
- [x] Auditar todos os módulos e registrar commits/push por grupos de 3–5.

Escopo exclusivo: docs de módulos e este arquivo. Sem pipeline ou deploy, conforme req-180 §4.

## Validação

- 2026-09-25: `php cli/c2f.php docs:audit --json` após redação de `publisher`: pt-br/en com score 0 e nenhuma issue. Fontes conferidas em `33ce53d9`. Isso valida metadados/fontes/links; não substitui revisão semântica nem encerra o lote.
- Auditoria final do conjunto: `docs:audit --json` registrou 33 referências por idioma (32 módulos da req-180 e o piloto `menus`), zero `reference/modules/*` com score > 0 e zero `missing:reference/modules/*`. Os caminhos legados de §2 foram removidos, incluindo genéricos e READMEs. Sem build/deploy, conforme §4. Fontes do último grupo conferidas em `98ac881d`.
- Sexto grupo: oito docs pt-br/en com score 0, restavam oito ausências. Commit `c4e05805` enviado; pull pré-commit recusado pela árvore concorrente, fetch confirmou `0 0` em `HEAD...origin/main` e commit usou caminhos explícitos com `--only`.
- Sétimo grupo: oito docs pt-br/en com score 0, restavam quatro ausências. Commit `98ac881d` enviado; pull pré-commit recusado pela árvore concorrente, fetch confirmou `0 0` e commit usou caminhos explícitos com `--only`.
- Oitavo grupo: oito docs pt-br/en com score 0 e zero ausências; 30 legados finais (módulos, manuais genéricos e READMEs) removidos. `git diff --cached --check` passou; commit `a8f58483` enviado. Pull pré-commit recusado pela árvore concorrente, fetch confirmou `0 0` e commit usou os 39 caminhos próprios com `--only`.
- Segundo grupo: seis docs pt-br/en com score 0 e nenhuma issue em `docs:audit --json`; `git diff --check` sem erro. Fontes conferidas em `837c383f`. O manual genérico `manual/modulos/paginas.md` permanece para a limpeza final conjunta prevista no §2 da req-180. Commit `7bf08fe1` enviado. O pull pré-commit foi recusado pela árvore concorrente; fetch confirmou zero commits remotos ausentes antes da consolidação com caminhos explícitos e `--only`.
- Terceiro grupo: oito docs pt-br/en com score 0 e nenhuma issue em `docs:audit --json`; restam 22 módulos ausentes. Fontes conferidas em `7bf08fe1`. A auditoria geral ainda contém achados fora do escopo deste agente. Commit `b6839aa1` enviado; pull pré-commit recusado pela árvore concorrente, fetch confirmou zero commits remotos ausentes e o commit foi limitado aos caminhos próprios com `--only`.
- Quarto grupo: dez docs pt-br/en com score 0 e nenhuma issue em `docs:audit --json`; restam 17 módulos ausentes. Fontes conferidas em `b6839aa1`. Auditoria geral ainda contém achados fora deste escopo. Commit `45812d2e` enviado; pull pré-commit recusado pela árvore concorrente, fetch confirmou zero commits remotos ausentes e o commit foi limitado aos caminhos próprios com `--only`.
- Quinto grupo: dez docs pt-br/en com score 0 e nenhuma issue em `docs:audit --json`; restam 12 módulos ausentes. Fontes conferidas em `45812d2e`. Commit `a9a226e0` enviado; pull pré-commit recusado pela árvore concorrente, fetch confirmou zero commits remotos ausentes e o commit foi limitado aos caminhos próprios com `--only`.
- Primeiro grupo: `docs:audit --json` confirmou score 0 e nenhuma issue nas seis docs (três módulos, dois idiomas). `git diff --check` sem erros. Revisão estática de controladores, widgets, JS, metadados e migrations; sem execução runtime/deploy, conforme o escopo. Nenhum código alterado.
- Git: `562ef169` enviado a `origin/main`. O `pull --rebase` pré-commit recusou a árvore com alterações concorrentes; `fetch origin main` + `rev-list HEAD...origin/main` confirmou `0 0` antes do commit. Commit limitado aos 11 caminhos próprios com `--only`; alterações do outro agente preservadas.
