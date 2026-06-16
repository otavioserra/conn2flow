# Decision Log

### Índice de Decisões Arquivadas

Para manter o arquivo corrente leve, as decisões `DEC-001` a `DEC-030` foram movidas para **[decisions-001-030.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/decisions/archive/decisions-001-030.md)** e `DEC-031` a `DEC-033` para **[decisions-031-040.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/decisions/archive/decisions-031-040.md)**.

| ID | Data | Status | Título Resumido |
| --- | --- | --- | --- |
| DEC-001 | 2026-05-25 | accepted | Adotar SDD repo-wide no `conn2flow` |
| DEC-002 | 2026-05-25 | accepted | Suportar Claude Code e GitHub Copilot |
| DEC-003 | 2026-05-25 | accepted | 00-baseline-architecture.md como referência primária |
| DEC-004 | 2026-05-25 | accepted | sdd/human-requests/ como intake não normativo |
| DEC-005 | 2026-05-25 | accepted | Definir Plano 1 de sincronização de projetos |
| DEC-006 | 2026-05-25 | accepted | Adotar fallbacks estruturados no sync de projetos |
| DEC-007 | 2026-05-25 | accepted | Arquitetura baseada em schema contract JSON |
| DEC-008 | 2026-05-25 | accepted | Padronizar idioma para coluna 'language' |
| DEC-009 | 2026-05-25 | accepted | Loteamento dinâmico para max_allowed_packet |
| DEC-010 | 2026-05-25 | accepted | Usar data-hooks.php para pipeline local/global |
| DEC-011 | 2026-05-25 | accepted | Deleção controlada via chave no descritor de módulo |
| DEC-012 | 2026-05-25 | accepted | Padronizar logs com log_disco() em log.php |
| DEC-013 | 2026-05-25 | accepted | Widgets Envelopados com marcação de comentário HTML |
| DEC-014 | 2026-05-25 | accepted | Seleção manual baseada em IDs textuais/slugs |
| DEC-015 | 2026-05-25 | accepted | Persistência de templates HTML/CSS apenas no banco |
| DEC-016 | 2026-05-25 | accepted | publisher-highlights estruturado análogo ao publisher |
| DEC-017 | 2026-05-26 | accepted | Extensão de order_by no fields_schema do Highlights |
| DEC-018 | 2026-05-26 | accepted | Curadoria manual de destaques via Fomantic Dropdown |
| DEC-019 | 2026-05-28 | accepted | Componentização no PublisherHighlightsCustomDropdown |
| DEC-020 | 2026-05-28 | accepted | Ciclo de vida isolado do dropdown manual |
| DEC-021 | 2026-06-04 | accepted | Substituir dropdown por Autocomplete + Sortable.js |
| DEC-022 | 2026-06-04 | accepted | Módulo Menus desacoplado e templates próprios |
| DEC-023 | 2026-06-04 | accepted | Hierarquia multi-nível de menus e DnD bidimensional |
| DEC-024 | 2026-06-05 | accepted | Aba de Variáveis no Menus, simulação e hover CSS |
| DEC-025 | 2026-06-05 | accepted | Tipo de Item Publicador no menus e correções |
| DEC-026 | 2026-06-05 | accepted | Criação do Módulo de Galerias de Imagens |
| DEC-027 | 2026-06-05 | accepted | Contrato de Target em Link-Custom e [[item#target]] |
| DEC-028 | 2026-06-05 | accepted | Bloco item-separator em templates de Menus |
| DEC-029 | 2026-06-05 | accepted | Controles de exibição e resolução de imagem em Galerias |
| DEC-030 | 2026-06-05 | accepted | Comportamento dinâmico em JS público dos widgets |
| DEC-031 | 2026-06-05 | accepted | Alvo/Variáveis Globais/Modos de IA do módulo Galerias → [archive](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/decisions/archive/decisions-031-040.md) |
| DEC-032 | 2026-06-08 | accepted | Compilação Tailwind CSS CLI no core e pipeline de release → [archive](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/decisions/archive/decisions-031-040.md) |
| DEC-033 | 2026-06-08 | accepted | Estrutura de testes Unit/Integration/E2E na raiz → [archive](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/decisions/archive/decisions-031-040.md) |
| DEC-034 | 2026-06-08 | accepted | Correção HTML (Quill), automação de campos e v2.8.0 → [archive](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/decisions/archive/decisions-031-040.md) |

---

## DEC-035 - 2026-06-09 - accepted

Unificação do Pré-visualizador de HTML Externo nos Módulos (req-022 / BATCH-022). Decisões desta rodada:
1. **Unificação do Visualizador**: Substituir a geração de iframe/srcdoc duplicada e hardcoded no sucesso do AJAX `opcao: 'widget-preview'` nos arquivos de Javascript de destaques (`publisher-highlights.js`), menus (`menus.js`) e galerias (`galleries.js`).
2. **Biblioteca de Editor HTML**: Consumir a nova função de biblioteca unificada `window.previewExternalHtmlConteudo({ htmlDoUsuario, cssDoUsuario, framework })` exposta globalmente por `html-editor-interface.js`.
3. **Mapeamento de Parâmetros**:
   - `htmlDoUsuario`: Conteúdo retornado pela resposta AJAX (`dados.html`).
   - `cssDoUsuario`: CSS personalizado do editor, obtido a partir da chamada para `window.html_editor_get_css()` (passado via variável `css` local do escopo).
   - `framework`: Identificar qual framework CSS está ativo por meio da variável global `gestor.html_editor.framework_css`.
   - **Garantia de Contingência**: Implementar fallback seguro mantendo a estrutura CDN do TailwindCSS hardcoded antiga se `window.previewExternalHtmlConteudo` não for detectada no escopo.

## DEC-036 - 2026-06-09 - accepted

Otimização do CSS Automático com Filtragem de Redundâncias do Tailwind (req-023 / BATCH-023). Decisões desta rodada:
1. **Remoção de Redundâncias**: Filtrar o CSS compilado dinamicamente gerado pelo Tailwind CDN no editor antes de ser inserido no campo `CodeMirrorCssCompiled` e gravado no banco de dados.
2. **API Nativa do Navegador**: Usar a API `document.styleSheets` no JavaScript do painel administrativo para ler os seletores textuais do `system-output.css` (e `output.css`) carregado no iframe do previewer e armazená-los em um `Set` de controle.
3. **Extração e PURGE Dinâmicos**: Acessar as regras de estilo de `tailwindStyleElement.sheet.cssRules`. Filtrar as regras mantendo apenas os seletores que não existem no `Set` de seletores globais. Para media queries (`@media`), filtrar individualmente as suas sub-regras. 
4. **Compatibilidade v3/v4**: A extração via `sheet.cssRules` garante suporte tanto ao Tailwind v3 (que injeta estilos no DOM) quanto ao Tailwind v4 (que insere regras dinamicamente via `insertRule`).

## DEC-037 - 2026-06-10 - accepted

Links Individuais, Controles de Exibição de Galerias e Correções Visuais (req-024 / BATCH-024). Decisões desta rodada:
1. **Estrutura de Links**: Cada item de imagem em `selected_items` no módulo `galleries` suportará metadados de link: `link_type` (`nenhum`, `pagina`, `link-custom`, `link-css-classes`, `publicador`), `link_page_id`, `link_url`, `link_target`, `link_css_classes`, `link_publisher_id`, `link_order_by`. A serialização é feita na curadoria do painel e salva no `fields_schema`.
2. **Resolução de Links no Widget**: Em `galleries.widget.php`, carregar slugs de páginas e buscar publicações de forma consolidada e eficiente, retornando `link-url`, `link-target` e `link-css-classes` por item. Imagens sem link resolvem para `javascript:void(0);`.
3. **Parâmetros Dinâmicos de Altura/Margem**: Inclusão de `height` (default 300) e `margin_lateral` (default 0) nos controles globais de exibição de galeria. Injetá-los dinamicamente nas tags raiz/imagem dos templates.
4. **Correções de Layout**: Ajuste fino de padding/margem nos submenus recursivos de `menus-horizontal-navbar`, legenda do `galleries-masonry` e tags horizontais coladas no `publisher-highlights.js`.

## DEC-038 - 2026-06-10 - accepted

Autocomplete de Páginas em Galerias, Ajuste de Menu e Preparação de Release (req-025 / BATCH-025). Decisões desta rodada:
1. **Buscador de Páginas Isolado**: Substituir o dropdown estático de páginas nas galerias pelo buscador AJAX autocomplete multilíngue clonado do Menus. Para evitar colisões em múltiplas linhas de curadoria simultâneas, os inputs e elementos de sugestões do autocomplete serão identificados utilizando o ID do item curado (ex: `manual_search_type_${it.id}`).
2. **Exclusão de Link Visual**: Configurar a classe `pointer-events-none cursor-default` nos links do widget se o tipo de link for `'nenhum'`, impedindo a interatividade e a indicação de ponteiro de link (hand cursor).
3. **Alinhamento do Submenu**: Ajustar o estilo CSS do submenu navbar horizontal para `display: flex !important; justify-content: space-between; align-items: center;` para manter a setinha do submenu alinhada horizontalmente no mesmo bloco.
4. **Miniaturas Ampliadas**: Ampliar a proporção de exibição da imagem curada no painel administrativo de `64x48px` para `200x140px` para melhor visualização.
5. **Workflow de Release**: Atualizar a data oficial da versão `2.8.0` para `2026-06-10` nos changelogs e incluir o descritivo de otimização de CSS (BATCH-023) e novas correções de links/layouts (BATCH-024/025) no workflow `.github/workflows/release-gestor.yml`.

## DEC-039 - 2026-06-10 - accepted

Ajuste do Modo de IA de Destaques e Preservação de Template Modificado (req-026 / BATCH-026). Decisões desta rodada:
1. **Modo de IA de Destaques e Galerias**: Atualização dos prompts do modo de IA de destaques (`publisher-highlights.md` em `pt-br` e `en`) para incluir regras claras de uso do bloco condicional opcional `<!-- no-item < -->` e variáveis adicionais, e de galerias (`galleries.md` em `pt-br` e `en`) para incluir as variáveis de link individual (`[[item#link-url]]`, `[[item#link-target]]`, `[[item#link-css-classes]]`) e a regra obrigatória de envelopar cada imagem curada em uma tag de âncora `<a>`.
2. **Identificador Suffix do Dropdown de Modelos**: No dropdown de seleção de modelo (`template_id`), se o registro atual em edição ou clonagem possuir código HTML ou CSS customizado no banco de dados, gerar e selecionar uma opção sufixada `[template_id]-modificado` (ex: `menus-horizontal-navbar-modificado`), com o rótulo `[Nome do Modelo] - (Modificado)`.
3. **Preservação de Código Customizado**: No carregamento inicial das telas de edição/clonagem, se o modelo selecionado for a versão `-modificado`, o script JavaScript do módulo correspondente (`menus.js`, `galleries.js`, `publisher-highlights.js`) não disparará a chamada AJAX de carregamento de modelo padrão (`loadTemplate()`), mantendo intacto o HTML/CSS carregado do banco.
4. **Alternância entre Modelo Padrão e Modificado e Extração de Variáveis**: O JavaScript armazenará em cache o HTML/CSS inicial recuperado do DOM no carregamento. Caso o usuário mude a seleção para a versão limpa do modelo, o template original será carregado via AJAX (`loadTemplate`). Se retornar para a versão `-modificado`, o HTML/CSS original em cache do registro será restaurado no editor. Em `publisher-highlights.js`, ao carregar/re-selecionar a variante `-modificado`, as variáveis `[[item#X]]` serão extraídas localmente do HTML do banco via expressão regular no cliente para manter o painel de mapeamento de variáveis populado.
5. **Consistência do Banco de Dados**: Antes do envio do formulário (submit) ou serialização (`fields_schema`), o sufixo `-modificado` será removido de qualquer referência a `template_id` no JavaScript para garantir que o identificador de modelo gravado no banco de dados permaneça limpo e compatível com as consultas existentes.
6. **Resolução de Framework CSS do Template**: Para evitar que o pré-visualizador (`live widget-preview`) falhe ao abrir um modelo `-modificado` devido à falta da variável de estilo global, o PHP selecionará o `framework_css` dos templates e o disponibilizará no dropdown como um atributo de dados `data-framework` nos elementos `<option>`. O JavaScript lerá este atributo no page load e em eventos `change` para inicializar a variável `gestor.html_editor.framework_css` de forma síncrona.

## DEC-040 - 2026-06-10 - accepted

Resolução de Framework CSS e Variáveis de Destaques de Modelo Modificado (req-027 / BATCH-027). Decisões corretivas desta rodada:
1. **Atributo `data-framework` nos Três Módulos**: Injetar síncronamente o `framework_css` (ex: `tailwindcss`) a partir da tabela de templates em todos os `<option>` (incluindo a opção `-modificado` gerada) de templates no PHP para `menus.php`, `galleries.php` e `publisher-highlights.php`.
2. **Sincronização de Runtime no JS**: No `ready` e no listener `change` do dropdown `#template_id` dos arquivos `menus.js`, `galleries.js` e `publisher-highlights.js`, ler o `data-framework` da opção ativa e atualizar a variável global `gestor.html_editor.framework_css`.
3. **Extração Client-side no Highlights**: Implementar a rotina `extractVariablesFromHtml` no `publisher-highlights.js` que processa o HTML com regex `/\[\[item#([a-zA-Z0-9_\-]+)\]\]/g` no ready e na re-seleção da opção `-modificado`, populando `availableItemVars` e disparando `renderItemVars()` / `syncEditorVariables()` síncronamente no cliente.

## DEC-041 - 2026-06-11 - accepted

Centralização de Injeção de Recursos de Widgets e Arquitetura do Publicador Índice (req-028 / BATCH-028). Decisões desta rodada:
1. **Helper de Injeção Centralizada**: Criar a função `gestor_pagina_recursos_incluir()` na biblioteca comum `gestor/bibliotecas/gestor.php`. A função lida com `css`, `css_compiled` e `html_extra_head`, aplicando formatação e incluindo nos respectivos arrays globais (`$_GESTOR`).
2. **Prevenção de Duplicidades via MD5**: A helper calculará o hash MD5 dos conteúdos incluídos para evitar a injeção repetida das mesmas regras de estilo ou tags de cabeçalho na página se múltiplos blocos do mesmo widget forem inseridos.
3. **Páginas sem CSS/Head inline**: Refatorar `gestor_componente()` e os widgets públicos (`menus.widget.php`, `galleries.widget.php`, `publisher-highlights.widget.php`) para chamar a helper em vez de concatenar o CSS/Head inline no HTML do widget.
4. **Módulo `publisher-index`**: Criar o novo módulo "Publicador Índice" baseado no publicador de destaques, implementando a tabela de banco correspondente e controles adicionais no `fields_schema` JSON para `items_per_page`, `show_search_input`, `show_sorting_select`, `show_load_more_btn`.
5. **Roteamento AJAX e Comportamento Interativo**: No widget renderer, tratar requisições AJAX públicas direcionando para `publisher_index_render_ajax`, que consulta, filtra (busca textual via LIKE) e ordena as publicações de acordo com o estado do frontend. Criar o script público `publisher-index.widget.js` para escutar inputs, atualizar o estado e anexar dinamicamente novos itens (carregamento sob demanda) sem recarga completa de página.

## DEC-042 - 2026-06-12 - accepted

Reestruturação e Otimização de Dados, Sincronização e Visibilidade de Logs (req-029 / BATCH-029). Decisões desta rodada:
1. **Divergência aprovada do req §1 (escopo da migração de idioma)**: O req-029 §1 listava 7 tabelas (`menus`, `galleries`, `publisher_highlights`, `publisher_index`, `prompts_ia`, `alvos_ia`, `modos_ia`) para migrar `linguagem_codigo`→`language`, porém todas já usam `language` em suas migrações de criação (premissa do intake desatualizada). A **única** tabela que ainda usava `linguagem_codigo` era a `variaveis` (não listada no req). Sob aprovação explícita do Engenheiro Chefe Humano, o escopo real do Slice de migração foi redirecionado para a tabela `variaveis`, cumprindo o princípio de abertura do req ("todas as tabelas que utilizam `linguagem_codigo`") e o DEC-008. Inclui migração de criação ajustada, índice composto atualizado e nova migração corretiva idempotente (`renameColumn` com guards `hasColumn`), além das referências em `configuracao.php`/`gestor.php`/`plugins-installer.php`/gerador/atualizadores e regeneração do `VariaveisData.json`.
2. **Metadados de sincronização declarativos (`tabela.config`)**: As regras antes hardcoded no atualizador (`$tabelasChaveNatural`, `$tabelasInsertOnly`, `$preserveMap` e a chave natural por tabela) passam a ser declaradas no bloco `"tabela"` de cada módulo, sob a sub-chave **`config`** (`strategy`, `natural_key_columns`, `preserve_on_user_modified`, `insert_only`) — nome de chave definido pelo Engenheiro Chefe Humano — mais um bloco `"deletar"`. Tabelas sem módulo dono (`variaveis`, `usuarios_perfis_modulos`, `usuarios_perfis_modulos_operacoes`, `alvos_ia`) ficam no arquivo global `gestor/resources/tables_config.json` com o mesmo schema.
3. **Contrato consolidado (`schema-metadata.json`) via Registry Pattern**: O gerador `atualizacao-dados-recursos.php` ganha um motor de varredura genérico que consolida os blocos locais + o global em `gestor/db/data/schema-metadata.json` (17 tabelas), preservando a geração específica dos `*Data.json` existente (decisão de baixo risco: adicionar o motor sem reescrever a coleta de recursos). Inclui carregamento/execução sequencial de `data-hooks.php` (globais e por módulo) pós-geração e substituição dos `@` cegos por `ensureDir()` com log.
4. **Atualizador dinâmico**: `atualizacoes-banco-de-dados.php` lê o contrato (`schemaMetadata()`), remove os arrays hardcoded, usa chave natural genérica (`naturalKeyGenerica`, lowercase + alias `language`/`linguagem_codigo`) e WHERE genérico null-safe (`<=>`), loteador threshold-based (`maxAllowedPacket()` a 70%, fallback 16MB) com `inserirEmLote` (multi-row agrupado por assinatura de colunas + fallback individual para duplicatas), deleção imperativa (`executarDelecoes`) e transações PDO (`beginTransaction`/`commit`/`rollBack`) envolvendo sincronização + deleção.
5. **Unificação e visibilidade de logs / remoção de CLI exec**: `log_unificado()` (em `atualizacoes-banco-de-dados.php` e `atualizacao-plugin-banco-de-dados.php`) escreve em disco, no `EXTERNAL_LOGGER` por referência e no stdout CLI (parte implementada por outro agente que tocou o 029 sem querer e foi incorporada, sem reverter). `api.php` (`api_executar_atualizacao_banco`) captura os logs e retorna `db_logs` (resumido ou completo conforme `full_log`); `atualizacoes-sistema.php` (`executarAtualizacaoBanco`) passa a rodar **estritamente inline** (remoção do `exec()` de banco), capturando e prefixando os logs com `[BANCO]`. O `passthru()` remanescente em `atualizacoes-sistema.php` é o auto-bootstrap do próprio script de deploy (não roda atualização de banco) e foi preservado.

## DEC-043 - 2026-06-13 - accepted

Fundação de Autenticação, 2FA e JWT (req-030 / BATCH-030, Slices 1–2). Decisões de design da fundação (banco + bibliotecas puras):
1. **`usuarios_provedores` sem chave estrangeira física**: O req-030 §1 pedia FK `usuario_id → usuarios.id_usuario ON DELETE CASCADE`, mas (a) a PK real é `id_usuarios` e (b) nenhuma migração do legado usa `addForeignKey` (relacionamentos são por coluna integer + índice, ex.: `usuarios.id_hosts`). Decisão: criar `usuario_id` integer + índice, índice único composto `(provider_name, provider_uid)`, e tratar o cascateamento ON DELETE em código no fluxo de exclusão de usuário. Migração com guard `hasTable()`; a de 2FA em `usuarios` com guards `hasColumn()`/up/down idempotentes. Timestamps `20260706*` (> maior existente `20260705100000`) para garantir ordem.
2. **JWT HS256 com `kid` e chaves versionadas no banco**: A biblioteca `jwt.php` assina em HMAC-SHA256 (chaves simétricas, auto-suficiente, sem dependência de RSA/par de chaves) e inclui `kid` no header para selecionar a chave na validação. O conjunto de chaves fica em `variaveis` (`modulo='sistema'`, `id='jwt_keys'`, coluna `valor` em JSON) — a tabela `variaveis` não tem coluna `chave`, ao contrário do que o req §3.2 sugeria.
3. **Grace period medido por `expired_at`**: Divergência justificada do req §3.2 (que sugeria `created_at`). O período de carência é contado a partir de quando a chave foi rotacionada para `expired` (campo `expired_at`, fallback `created_at`). Medir por `created_at` invalidaria o grace em produção, pois a chave ativa vive `AUTH_JWT_ROTATION_DAYS` (30) dias antes de expirar, e `created_at + 24h` já teria passado.
4. **2FA TOTP próprio (RFC 6238/4226) sem libs externas**: `2fa.php` implementa Base32, HOTP e TOTP (drift ±1 ciclo) em funções byte-wise puras (sem `mb_*`), validado contra os vetores oficiais dos RFCs. O envio por e-mail usa a função real `comunicacao_email()` (o req cita `gestor_email_enviar()` como placeholder).
5. **Cobertura de testes da fundação**: Funções puras com testes PHPUnit permanentes (`tests/Unit/PHP/TwoFactorTest.php`, `JwtTest.php`); o ciclo JWT dependente de banco foi validado por teste standalone com stubs em memória (registrado na evidência), pois `banco.php` implementa apenas `mysqli` (não PDO/SQLite), deixando a integração end-to-end para o operador (MySQL real).

## DEC-044 - 2026-06-13 - accepted

Integração de Autenticação, 2FA, Login Social e Endurecimento (req-030 / BATCH-030, Slices 3–6). Decisões de integração:
1. **admin-environment como painel global de auth**: As chaves `AUTH_METHOD_*`, `OAUTH_*`, `AUTH_2FA_*` e `AUTH_JWT_*` são lidas/gravadas no `.env` pela infraestrutura existente (`admin_environment_env_read/write`); credenciais OAuth aparecem condicionalmente e as URIs de callback são exibidas em readonly. O botão "Rotacionar Chaves JWT" usa a AJAX `rotacionar-jwt` → `jwt_rotate_keys()`.
2. **Rota de Segurança embutida no CRUD de perfil**: A seção 2FA + contas sociais é renderizada no bloco `<!-- seguranca-campos -->` da página de perfil quando `?configurar-seguranca=sim` (helper `modelo_tag_in`), com HTML gerado em PHP e i18n via variáveis do módulo. O QR Code é renderizado **client-side** (qrcodejs CDN) a partir da URI `otpauth://`, mantendo o secret fora de serviços de terceiros. O secret TOTP é persistido com `two_factor_enabled=0` até a confirmação do primeiro código (evita estado de sessão frágil). Desativar 2FA exige senha (`password_verify`) + código atual.
3. **Interceptador 2FA fail-safe no login**: Inserido no `perfil_usuario_signin` após a validação de credenciais; só intercepta quando o usuário tem 2FA habilitado **ou** `AUTH_2FA_REQUIRED` é true — caso contrário o login segue idêntico (não trava instalações sem 2FA). Tela `signin-2fa` unificada serve verificação e configuração obrigatória. A finalização do login foi extraída para `perfil_usuario_finalizar_login()` (reuso entre login direto, 2FA e social). Todo o estado de fluxo (2FA pendente e OAuth `state`/`provider`/`action`) usa o store de sessão do sistema (`gestor_sessao_variavel`, persistido em `sessoes_variaveis` e iniciado automaticamente por `gestor_sessao_iniciar`), não `$_SESSION` nativo — por orientação do Engenheiro Chefe.
4. **Callback OAuth via rota do módulo**: Divergência justificada do req §2 — `oauth_redirect_uri()` aponta para `{url}/oauth-callback/?provider=X` (rota do módulo perfil-usuario) em vez de `/_api/auth/callback/X`, evitando alterar o roteador de API genérico. Novas páginas/opções `social-login`, `oauth-callback` e `signin-2fa` registradas no manifest. Login social casa por vínculo (`usuarios_provedores`) ou, na ausência, por e-mail de usuário ativo (cria o vínculo); aplica o mesmo interceptador 2FA.
5. **Endurecimento via `bibliotecas/seguranca.php`**: Session Hijacking valida User-Agent + bloco de IP (3 octetos) dentro de `gestor_permissao_token()` de forma **fail-safe** (não bloqueia sessões sem marcadores registrados; registra no login efetivo, não na renovação de token — para não anular a checagem). CSRF: helpers `gestor_csrf_token()`/`gestor_csrf_validar()` prontos; o rollout estrito em 100% dos controllers é **incremental** (validação runtime), pois a aplicação global cega quebraria os AJAX legados que ainda não enviam o token. Logs de eventos de segurança via `log.php` ficam para um slice corretivo.

## DEC-045 - 2026-06-13 - accepted

Login sem Senha por E-mail e Auxílio de Configuração OAuth (req-032 / BATCH-032). Decisões de design:
1. **Configuração e Auxílio na UI**: Adicionar toggle `AUTH_METHOD_EMAIL_ACTIVE` nas configurações globais do `admin-environment` (salvo no `.env`). Adicionar na interface de configuração links diretos externos com `target="_blank"` para os consoles do Google e Meta acompanhados de textos de instrução ("How-To") passo a passo de como gerar e vincular os tokens client ID / secret usando as URIs de callback exibidas.
2. **Login Sem Senha via E-mail (Passwordless)**: Em `acessar-sistema.html`, se habilitado o login por e-mail, ocultar o campo de senha caso o login convencional com senha esteja inativo. Se ambos estiverem ativos, apresentar abas ou botões alternadores (toggles) Fomantic UI para escolher o método de autenticação. Ao selecionar o método por e-mail, o formulário oculta o campo de senha.
3. **Reuso da Infraestrutura 2FA**: Ao submeter o e-mail no login sem senha, o backend valida a existência e status do usuário. Em caso de sucesso, gera o código de uso único, dispara o e-mail pelo helper `two_factor_email_send_code` e salva as variáveis na sessão do gestor (`pending_2fa_user = ID`, `pending_2fa_mode = 'verify'`, `pending_2fa_type = 'email'`), redirecionando-o à rota `signin-2fa/`. Desta forma, a tela de verificação existente resolve a autenticação final e gera o token de autorização sem necessidade de novas páginas.

## DEC-046 - 2026-06-13 - accepted

Segurança no Acesso e Geração de Chaves de API (req-033 / BATCH-033). Decisões de design:
1. **Nova aba API no admin-environment**: Criar aba "API" no módulo para expor controles e persistir no `.env`: `AUTH_API_ALLOWED_PROFILES` (lista separada por vírgula de perfis autorizados), e toggles de login (senha/e-mail) e 2FA (obrigatório, métodos app/e-mail) para a rota de API.
2. **Interface Adaptativa de Login da API**: Em `oauth-authenticate`, reaproveitar a interface reativa de login (Senha vs Código por E-mail) dependendo do estado das variáveis de controle ativas no `.env`.
3. **Interceptador 2FA na API e Holding de Tokens**: Em `perfil_usuario_oauth_authenticate()`, se o usuário logado pertencer a um perfil permitido e se autenticar com sucesso, e se `AUTH_API_2FA_REQUIRED` estiver ativo (ou se o usuário possuir 2FA ativo em sua conta), o backend gera as chaves de resposta OAuth e as armazena temporariamente na sessão (`pending_oauth_tokens`), redirecionando-o para a nova rota `oauth-authenticate-2fa/`.
4. **Finalização via nova rota `oauth-authenticate-2fa/`**: Essa nova página recebe o código de 6 dígitos. Após validação, recupera `pending_oauth_tokens`, remove as variáveis temporárias da sessão, e conclui emitindo os tokens originais da API (imprimindo JSON ou redirecionando via OAuth).

## DEC-047 - 2026-06-13 - accepted

Aprimoramento do Editor HTML Visual (req-034 / BATCH-034). Decisões de design:
1. **Lógica de Identificação Permissiva**: Modificar o `HtmlEditor` para que qualquer tag no DOM seja editável (exceto se declarada na lista `ignoredTags` do editor). O tipo de edição resolve para `'image'` para tags `<img>`, `'text'` para tags que contêm texto puro editável diretamente (conforme `isDirectlyTextEditable`), e `'code'` (outerHTML) como fallback para todas as demais tags estruturais (ex: `div`, `section`, `table`, etc.).
2. **Dual-Overlay com Barra Flutuante**: Substituir o comportamento de clique de edição imediata por um fluxo de seleção persistente. O editor visual passa a gerenciar um overlay de hover transitório (`#html-editor-hover-overlay`) e um overlay de seleção persistente (`#html-editor-selection-overlay`). Este último sustenta acoplada logo acima a barra de ferramentas flutuante (`#html-editor-floating-toolbar`).
3. **Barra de Ferramentas de Seleção**: A barra flutuante possui botões para:
   - Duplicar: clona o elemento via `cloneNode(true)` e insere-o como irmão adjacente inferior do elemento ativo.
   - Deletar: remove o elemento do DOM após confirmação do usuário usando o `confirm()` nativo do JavaScript.
   - Editar: abre o Fomantic UI modal no pai para edição de propriedades ou código.
   - Arrastar/Mover: aciona o modo de arraste de elementos (DnD).
4. **Lightweight Drag and Drop (DnD)**: O arraste do elemento selecionado exibe uma linha tracejada horizontal de placeholder (`.conn2flow-dnd-placeholder`). O reposicionamento do placeholder é calculado comparando as coordenadas do cursor com os elementos sob o mouse. O drop altera a ordem física do nó no DOM e re-sincroniza o CodeMirror no editor principal.
5. **Inclusão de Novos Elementos e Widgets (Carregar e Soltar)**: Adicionar um botão de inclusão "+" no cabeçalho do visual-modal. O clique abre um popup categorizado (Elementos HTML x Widgets do Sistema). A lista de Widgets e seus slugs é carregada dinamicamente via AJAX no endpoint `html-editor-widgets-list` em `html-editor.php`. Selecionar um elemento ou widget com slug entra em modo de inserção, exibindo o placeholder de drop no iframe e inserindo o novo elemento/bloco de widget (incluindo imagens via ImagePicker) na posição clicada.
6. **Histórico Undo/Redo Parametrizado**: Gerenciamento de estado visual mantido na pilha `undoStack`/`redoStack` da instância do editor no iframe. A profundidade máxima do histórico é controlada por `config.undoLimit` (padrão `30`), parametrizada para fácil ajuste. O gatilho de restauração é feito por setas no modal (pai) e atalhos globais de teclado (`Ctrl + Z` / `Ctrl + Y`).
7. **Breadcrumb DOM e Quick Tailwind Styler**: Trilha breadcrumb em linha na base do overlay de seleção permite navegabilidade vertical rápida na hierarquia do DOM. Ao lado, o quick styler lê classes Tailwind vigentes no elemento em tags visuais descartáveis e possui um campo de input com sugestões para injeção rápida de novas classes Tailwind no nó selecionado.
8. **Handles de Resize e Wrappers Virtuais**: Contêiner do iframe preview equipado com alças de arraste laterais livres que mostram a largura pixelada em tempo real. Comentários de widgets (highlights, menus, galleries, index) convertidos em divs de wrapper atômicas com borda tracejada amarela para bloquear cliques acidentais no seu conteúdo interno na edição visual, restauradas para comentários no salvamento.

## DEC-048 - 2026-06-13 - accepted

Decisões de **execução** do BATCH-034 (complementam o design da DEC-047, sem alterar requisito):
1. **Modularização em `html-editor-visual-controls.js`**: A pedido do Engenheiro Chefe Humano, toda a lógica nova da JANELA PAI (painel do botão "+", AJAX `html-editor-widgets-list`, atalhos de teclado, alças de redimensionamento e sincronização de largura) ficou num arquivo novo `gestor/assets/interface/html-editor-visual-controls.js` (incluído via `html-editor.php`), evitando inflar o já extenso `html-editor-interface.js` (3236 linhas). O `html-editor-interface.js` sofreu apenas 2 edições cirúrgicas: remoção do handler antigo `.screenPagina` (que encolhia o modal inteiro com `tiny`/`longer`) e uso de `iframe.contentWindow.htmlEditorGetCleanHtml()` no save.
2. **Protocolo `postMessage` `c2f-he:*`**: Comunicação pai↔iframe namespaced (`c2f-he:undo/redo/insert-element/insert-widget/cancel-insert/history`), preservando intactos os nomes legados do ImagePicker (`html-editor-imagepick-open/selected`).
3. **Largura controlada no frame interno, não no modal**: Os botões desktop/tablet/mobile (agora com `data-width` 100%/768px/375px) e as alças ajustam a largura de um wrapper `.iframe-preview-frame` centralizado, mantendo o modal sempre `fullscreen` (a troca de classes `tiny`/`longer` do modal foi removida). O dimmer/loader foi mantido como irmão do `#iframe-preview` para não quebrar `iframe.parent().find('.ui.dimmer')`.
4. **Modal de edição vive no iframe**: Diferente da redação da DEC-047 (item 3.3, "modal no pai"), o modal de edição (`#html-editor-modal`, modos text/image/code) é o que o helper já clona para dentro do `srcdoc` — a ação "Editar" reusa `openEditModal` no iframe. Os overlays/toolbar/breadcrumb/styler são ocultados enquanto esse modal está aberto (evita conflito de z-index).
5. **Edição de widget por `prompt()`**: Como a modal CRUD completa de cada widget está fora do escopo do editor visual, o botão "Editar" de um `.conn2flow-widget-wrapper` usa `prompt()` nativo para trocar o `grupo_slug` (coerente com o `confirm()` nativo do delete), atualizando `data-widget-slug`/`data-widget-signature` e o rótulo.
6. **i18n parcial**: Apenas os 3 tooltips do visual-modal (`html-editor-add-element-tooltip`/`-undo-tooltip`/`-redo-tooltip`) foram adicionados em `resources/{pt-br,en}/variables.json`. Os rótulos do painel de inclusão e os títulos da toolbar interna do iframe são strings pt-br embutidas no JS (paridade en pode ser endereçada em slice corretivo). O `VariaveisData.json` **não** foi editado — é compilado a partir do `variables.json` pelo pipeline.

## DEC-049 - 2026-06-13 - accepted

Refinamentos no Editor HTML Visual (req-035 / BATCH-035). Decisões de design/layout:
1. **Toolbar à direita**: Ancorar o `#html-editor-floating-toolbar` no lado direito do contêiner selecionado (calculando a coordenada `left` com base na borda direita do elemento ativo menos a largura do próprio toolbar) para evitar sobreposição lateral com o breadcrumb e com o editor de classes Tailwind CSS que ficam do lado esquerdo.
2. **Seletor de Filhos (Children Breadcrumb)**: Criar o contêiner `#html-editor-selection-children` posicionado verticalmente abaixo do contêiner de ancestrais e acima do editor de classes Tailwind.
3. **Labels de Identificação**: Inserir os labels "Ancestrais:" e "Filhos:" nas barras de navegação do DOM correspondentes.
4. **Visual e Interação**:
   - Usar um fundo mais claro para o contêiner de filhos em relação ao de ancestrais, criando diferenciação visual.
   - Utilizar a barra `|` como separador na lista de filhos (atualização do Engenheiro Chefe Humano em 2026-06-13; o intake original previa `/`).
   - Implementar listeners de hover e clique para destacar/selecionar os filhos diretos editáveis.
5. **Destaque de Hover nos Breadcrumbs**: Hover sobre um link/item nos breadcrumbs (ancestrais ou filhos) deve desenhar uma caixa tracejada roxa (mesma cor/tom do selection overlay) sobre o elemento físico correspondente para melhorar a visualização e orientação do operador no DOM.

**Notas de execução (BATCH-035)**: implementado integralmente no editor do iframe (`gestor/assets/interface/html-editor.js`); o orquestrador da janela pai não precisou de lógica nova (só o `sistemaSel` do save ganhou os 2 novos ids). O hover roxo usa um overlay dedicado `#html-editor-breadcrumb-hover-overlay` (z-index 999991, acima da seleção), e `onHoverMove()` ganhou uma guarda para não desenhar o hover azul quando o cursor está sobre o próprio chrome do editor (toolbar/breadcrumbs/styler), evitando conflito visual entre o azul direto e o roxo dos breadcrumbs. O empilhamento abaixo do elemento é cumulativo por `offsetHeight` (ancestrais → filhos → styler). Widgets (`.conn2flow-widget-wrapper`) seguem atômicos: o seletor de filhos é suprimido para eles.

## DEC-050 - 2026-06-13 - accepted

Novas Operações Estruturais no Editor HTML Visual (req-036 / BATCH-036). Decisões de design:
1. **Copiar e Colar (Copy/Paste)**:
   - Adicionar os botões "Copiar" (`.he-tb-copy`) e "Colar" (`.he-tb-paste`) na toolbar flutuante.
   - Guardar o nó clonado em `this.clipboardElement` via `cloneNode(true)`.
   - Colar insere a cópia como irmão adjacente inferior do elemento atualmente selecionado (alvo), tanto por botão quanto por atalho `Ctrl + V`.
   - Adicionar atalhos globais de teclado `Ctrl + C` e `Ctrl + V` sincronizados no iframe e repassados pelo pai.
2. **Embrulhar Elemento (Wrap Element)**:
   - Adicionar o botão "Embrulhar" (`.he-tb-wrap`) na toolbar flutuante.
   - O clique exibe um menu popup com tags estruturais básicas (`div`, `section`, `a`, `p`, `article`, `aside`).
   - Ao selecionar a tag, o editor cria o novo elemento pai, substitui o selecionado atual por ele, coloca o selecionado dentro dele como filho, e mantém o foco da seleção visual no elemento original (filho), forçando o recálculo dos breadcrumbs e styler.

**Notas de execução (BATCH-036)**: o botão Colar nasce oculto e só aparece quando `clipboardElement` tem um clone (`updatePasteButton()`); como esse botão muda a largura da toolbar, `copySelected()` chama `updateSelectionUI()` para reancorá-la à direita. Para não quebrar a cópia nativa de texto, o `Ctrl+C` só captura o elemento quando **não há seleção de texto ativa** (`getSelection().isCollapsed`) e o foco não está em input/textarea — tanto no iframe quanto na janela pai (`html-editor-visual-controls.js`, que repassa `c2f-he:copy`/`c2f-he:paste`). O ícone do botão Duplicar mudou de `copy` para `clone` para diferenciá-lo do novo Copiar. O popup `#html-editor-wrap-menu` foi registrado em `isEditorOwned`/`extractUserHtml`/`sistemaSel` e fecha ao clicar fora, ao trocar de seleção, em `hideChrome` e no Esc.

## DEC-051 - 2026-06-13 - accepted

Painel Auxiliar de Formatação Visual (req-037 / BATCH-037). Decisões de design:
1. **Estrutura de Duas Colunas**: Dividir `#html-editor-tailwind-styler` em duas colunas: a da esquerda mantendo a lista de tags e input autocomplete existentes; a da direita contendo o Visual Helper com botões interativos.
2. **Propriedades Mapeadas**: O Visual Helper deve gerenciar alinhamento de texto (Esquerda, Centro, Direita, Justificado), espaçamento/padding (Nenhum, Pequeno, Médio, Grande), arredondamento de bordas (Reto, Leve, Médio, Redondo) e paletas de cores rápidas em formato circular para Texto e Fundo.
3. **Sincronização e Limpeza de Conflitos**:
   - Cada grupo de controles de formatação visual deve ser mutuamente exclusivo (ex: aplicar `bg-blue-500` limpa outras classes com prefixo `bg-`).
   - Ao carregar ou atualizar o selecionado, ler as classes vigentes e adicionar a classe `.active` aos botões visuais correspondentes.
   - Qualquer clique nos controles visuais recalcula as classes aplicadas, atualiza a lista de tags na esquerda e chama `afterDomMutation()`.
4. **Empilhamento Responsivo**: Se a largura do elemento selecionado for inferior a 400px, o painel do styler muda para o layout vertical `.he-styler-stacked` (`flex-direction: column`) para manter a usabilidade em botões e blocos pequenos.

**Notas de execução (BATCH-037)**: a limpeza de conflitos NÃO usa prefixo cego (`bg-`/`text-`), e sim **lista fechada do grupo + regex específica de cor** — `text-(<cor>)-<shade>|text-white|text-black` e `bg-(<cor>)-<shade>|bg-white|bg-black|bg-transparent`. Isso evita dois falsos positivos: (a) trocar a cor do texto NÃO remove o alinhamento `text-left/center/...` nem o tamanho `text-lg/...`; (b) trocar a cor de fundo NÃO remove utilitários de imagem como `bg-cover`/`bg-center`/`bg-no-repeat`. Os grupos de alinhamento/padding/bordas usam lista fechada (+ regex `^p-\d+$` e `^rounded(-.+)?$` para variantes digitadas pelo operador). O painel (`buildHelperPanelHtml()`) é estático (gerado uma vez de `tailwindHelperConfig()`); `renderStyler()` só alterna `.active` via `syncHelperButtons()`. O botão "Nenhum" do padding mapeia para `p-0` (classe real). Defaults destacados quando nada do grupo está presente: alinhamento→`text-left`, bordas→`rounded-none`, fundo→`bg-transparent`.

## DEC-052 - 2026-06-13 - accepted

Inversão e Expansão do Painel Auxiliar de Formatação Visual (req-038 / BATCH-038). Decisões de design:
1. **Inversão de Colunas**: Inverter a exibição das colunas do styler `#html-editor-tailwind-styler`. A Coluna Esquerda passa a exibir os controles visuais interativos (Visual Helper), enquanto a Coluna Direita exibe as tags de classes aplicadas e o input autocomplete.
2. **Propriedades Estendidas**: Adicionar 7 novos grupos ao Visual Helper:
   - Tamanho do Texto (`text-sm/base/lg/xl`).
   - Espessura do Texto (`font-normal/medium/bold`).
   - Margem Externa (`m-0/2/4/8`).
   - Espessura da Borda (`border-0/border/border-2/border-4`).
   - Cor de Borda (Paleta circular de 8 cores).
   - Opacidade (`opacity-100/75/50/25`).
   - Layout / Display (`block/inline-block/flex/grid`).
3. **Limpeza e Sincronização Estendidas**:
   - Ampliar `applyHelperClass()` para remover classes conflitantes do mesmo grupo usando regras específicas (ex: regex para margem `^m-\d+$` ou `^m-[a-z]+$`, cor de borda `^border-(<cor>)-<shade>|border-white|border-black`, e display/font-size/font-weight/opacity por lista fechada).
   - Estender `syncHelperButtons()` para detectar e destacar o estado ativo para todos os novos grupos.
4. **Deslocamento da Toolbar na Borda Inferior**: Se a barra flutuante for desenhada na borda inferior do elemento (quando não há espaço no topo), a posição inicial de empilhamento vertical do breadcrumb (`stackTop`) é acrescida da altura da toolbar (`toolbar.offsetHeight + 12`), empurrando os elementos de navegação e formatação para baixo da barra e evitando sobreposições.

**Notas de execução (BATCH-038)**: sob autorização do Engenheiro Chefe Humano para "ser criativo e criar mais ainda", o painel foi expandido para **20 grupos organizados em 4 seções** (`.he-helper-section`): Texto (Alinhamento, Tamanho, Peso, Caixa de texto, Decoração, Cor), Layout (Exibição, Direção flex, Justificar, Alinhar itens, Gap), Caixa (Largura, Padding, Margem), Aparência (Fundo, Cantos, Borda, Cor da borda, Sombra, Opacidade) — os 7 pedidos + 8 extras. O styler ganhou `max-height:72vh` + scroll. A limpeza de conflitos combina três mecanismos em `applyHelperClass()`: lista fechada do grupo (derivada dos botões), `cleanList` (variantes de palavra isolada: displays/sombras/transform/decoração/flex-dir/justify/items/width) e `cleanRe` (cores, `^p-\d+$`, `^m[xytblr]?-\d+$` que preserva `mx-auto`/`min-h-*`, `^gap(-[xy])?-\d+$`, `^opacity-\d+$`, `^border-\d+$`). As cores de borda são renderizadas como **anel** (`.he-helper-bordercolor`, fundo branco + borda 3px) para distingui-las das cores de fundo. As classes de largura com barra (`w-1/2`) funcionam normalmente em `classList`/seletores de atributo. **Ajuste pós-feedback**: como 20 grupos deixaram o painel alto, as seções viraram um **accordion** (cabeçalho `.he-helper-section` clicável + `.he-helper-section-body` colapsável; uma seção aberta por vez, "Texto" por padrão), reduzindo a altura visual; o estado persiste entre seleções.

## DEC-053 - 2026-06-14 - accepted

Melhorias Visuais e Funcionais do Editor HTML Visual (req-039 / BATCH-039). Decisões de design:
1. **Nova Seção "Fundo" (Background) no Styler**: Criar seção "Fundo" reposicionando a cor de fundo (`bgColor`) de "Aparência" para ela. Adicionar controles para imagem de fundo: botão de ImagePicker (enviando `html-editor-imagepick-open` para o pai), tratamento de retorno `html-editor-imagepick-selected` (aplicando estilo inline `background-image` e gerenciando preview local via flag `imagePickerTarget = 'background'`), e grupos de botões Tailwind para repetição (`bg-repeat`/`bg-no-repeat` etc.), tamanho (`bg-auto`/`bg-cover` etc.) e posição (`bg-center`/`bg-top` etc.).
2. **Deseleção e Toggle de Seleção**: Inserir botão de deselecionar na toolbar flutuante (`.he-tb-deselect`, ícone `times circle` ou `ban`) com estilo destacado. Implementar toggle de seleção no clique global: se o elemento clicado for exatamente o selecionado ativo (`el === this.selectedElement`), deselecioná-lo via `clearSelection()`. Preservar tecla `Esc`.
3. **Preservação de Rolagem no Histórico (Scroll Sync)**: Armazenar a posição `scrollTop` do iframe do preview em cada snapshot da `undoStack`/`redoStack` (formato `{ html, scrollTop }`). Ao realizar `Undo` (Ctrl+Z) ou `Redo` (Ctrl+Y), restaurar a rolagem vertical (`window.scrollTo`) correspondente.
4. **Alinhamento e Wrapping dos Breadcrumbs**: Ajustar o CSS de `#html-editor-selection-breadcrumb` e `#html-editor-selection-children` para `display: flex; flex-wrap: wrap; white-space: normal;` para permitir quebra de linha. No método `updateSelectionUI()`, aplicar um clamp horizontal se `left + offsetWidth` ultrapassar os limites do viewport do iframe.
5. **Elemento Fantasma (Ghost Insert Cursor)**: No modo de inserção, desenhar e mover o elemento `insertGhost` com opacidade `0.6` e borda tracejada roxa/cinza seguindo o mouse. Destruir ao sair do modo de inserção.
6. **Highlight de Append Completo**: Ao realizar arraste ou inserção, se não houver posição em linha (antes/depois) mapeada, mas o cursor estiver sobre um contêiner pai, destacar o contêiner de destino com uma borda amarela tracejada de 4 lados (`#html-editor-parent-highlight-overlay`).
7. **Renderização de Widgets com Esqueleto Real (AJAX)**: Criar o endpoint AJAX `html-editor-widget-render` no backend que aceita `signature` do widget, e chama `widgets_get(['id' => signature])` com `$_GESTOR['ajax'] = false` para retornar o HTML real do widget. No visual editor, chamar este endpoint ao adicionar ou atualizar widgets e injetar o resultado no `.conn2flow-widget-inner` do wrapper virtual.

**Notas de execução (BATCH-039)**: (a) O iframe (srcdoc) não faz o AJAX direto — pede ao pai por `postMessage` (`c2f-he:widget-render`), o `html-editor-visual-controls.js` consulta `html-editor-widget-render` e devolve `c2f-he:widget-rendered`; cada wrapper recebe um `data-widget-id` para casar a resposta. (b) **O preview renderizado NÃO vaza no save**: o mockup original é preservado em `data-widget-mockup` e é ele (não o `.conn2flow-widget-inner` com o preview) que volta entre os comentários `<!-- widgets#... -->` em `extractUserHtml`. O endpoint valida a assinatura por regex `^[\w\-]+->[\w\-]+\(.*\)$` antes de chamar `widgets_get`. (c) O `bgImage` é um grupo de `kind:'bgimage'` (sem classe Tailwind) que aplica `background-image` inline; a regex de limpeza de `bgColor` (`bgColorRe`) não atinge `bg-cover`/`bg-center`/`bg-repeat`, então cor de fundo e propriedades de imagem coexistem. (d) Highlight de contêiner e placeholder em linha são mutuamente exclusivos (`showDropIndicator`); a inserção real usa `insertAtTarget` (não depende mais do placeholder estar no DOM). (e) Após undo/redo, `rerenderVisibleWidgets()` repinta o esqueleto dos widgets sem mockup.

## DEC-054 - 2026-06-14 - accepted

Ajustes Finais no Pré-visualizador de Widgets e Elemento Fantasma do Cursor (req-040 / BATCH-040). Decisões de design:
1. **Renderização de Widgets no Pré-visualizador**: Injetar um script utilitário em `previewHtmlConteudo()` (em `html-editor-interface.js`) para processar e renderizar dinamicamente os widgets do preview no `#iframe-visualizacao-pagina` via AJAX para `html-editor-widget-render` (com os parâmetros de `window.parent.gestor`).
2. **Elemento Real no Cursor Fantasma**: Em `createInsertGhost(payload)` (em `html-editor.js`), renderizar o elemento ou wrapper de widget real usando `buildElement()` ou `buildWidgetWrapper()` (com renderização assíncrona) e anexá-lo como filho de `#html-editor-insert-ghost`.
3. **Estilo Limpo do Cursor Fantasma**: Ajustar a estilização de `#html-editor-insert-ghost` para remover restrições de layout estritas que impeçam ou quebrem a exibição e estruturação interna do elemento real renderizado.

**Notas de execução (BATCH-040)**: (a) O script do preview é uma função nomeada `widgetPreviewBootstrap()` injetada por `(${fn.toString()})()` — isso preserva as regex literais (evita o problema de `\s`→`s` que ocorreria escrevendo o script como template string) e mantém o código autocontido (usa só globals do iframe + `window.parent.gestor`). Injetado nos dois caminhos de `previewHtmlConteudo` (layout e padrão), antes de `</body>`. O contêiner usa `display:contents` para não introduzir caixa extra no fluxo. (b) O nó de widget dentro do ghost recebe `data-widget-id` e dispara `requestWidgetRender`, mas **não vaza no save/snapshot**: o ghost (`#html-editor-insert-ghost`) é `isEditorOwned`, então seu conteúdo não é filho direto do body nem entra em `getUserContentNodes`. (c) `pointer-events:none` foi reforçado também nos descendentes do ghost (`#html-editor-insert-ghost *`) para a caixa flutuante jamais interceptar o cursor.

## DEC-055 - 2026-06-15 - accepted

Correções e Melhorias no Módulo publisher-index (req-041 / BATCH-041). Decisões de design:
1. **Busca Tolerante a Acentuação**:
   - Implementar a função utilitária `publisher_index_widget_unicode_escape()` que converte caracteres não-ASCII (acentos) do termo pesquisado para suas representações de escape Unicode (`u00xx` e `\u00xx`).
   - Modificar a query SQL para realizar buscas combinadas com `OR` cobrindo o termo original e as versões Unicode escapadas, permitindo que a busca encontre registros mesmo com acentuação corrompida no banco de dados.
2. **Decodificação de Unicode Corrompido**:
   - Criar o helper `publisher_index_widget_corrigir_unicode()` que detecta padrões de escape Unicode (`\?u[0-9a-fA-F]{4}`) em strings e os re-decodifica para UTF-8 nativo usando a função `pack('N', hexdec($m[1]))` e `mb_convert_encoding()`.
   - Aplicar esta decodificação ao título e campos dinâmicos ao buscar publicações do banco, garantindo exibição textual correta no widget renderizado.
3. **Filtro Estrito de Publicações (INNER JOIN)**:
   - Alterar o `LEFT JOIN` da consulta de publicações para `INNER JOIN` entre `paginas` e `publisher_pages`.
   - Isso elimina do widget a renderização indesejada da própria página de índice que renderiza o widget (e qualquer outra página comum que compartilhe o mesmo `publisher_id` mas não seja uma publicação cadastrada na tabela `publisher_pages`).
4. **Resolução de Métricas de Paginação**:
   - Expor as variáveis globais de widget `[[page_count]]` e `[[page_total]]` no backend PHP.
   - Enviar a propriedade `total` com a contagem total de publicações filtradas no JSON do retorno AJAX.
   - No cliente JS (`publisher-index.widget.js`), contar fisicamente os filhos renderizados na listagem e preencher o texto de seletores `[data-page-count]` e `[data-page-total]` dinamicamente no término de cada chamada AJAX.
5. **Ajustes e Simplificação de Layout do CRUD**:
   - Remover o input de quantidade máxima de itens (`#count`) por ser redundante com a paginação do índice.
   - Expandir os campos de Regra de Alimentação (`rule`) e Ordenação (`order_by`) para 8 colunas cada.
   - Mover `#manual-items-wrapper` para dentro do bloco de Regra de Curadoria, e corrigir os typos de barra invertida em suas tags HTML (`div\ class` e `\div\`).
6. **Integrações com Editor HTML Visual**:
   - Declarar `window.publisher_index_update_target_variables` no interface JS do editor.
   - Atualizar `alvoUsaItemVars()` para suportar o alvo `'publisher-index'`.
   - Atualizar a simulação interna de itens no editor para ler de `#items_per_page` (fallback 10) em vez de `#count`.

**Notas de execução (BATCH-041)**: (a) `banco_select` mapeia a chave do array de retorno pela string literal de cada campo (via `explode(',', ...)`), então `COUNT(*) AS total` vira uma chave não-trivial — `publisher_index_widget_contar_publicacoes()` lê o valor com `reset($row)`. (b) A cláusula de busca foi extraída para `publisher_index_widget_clausula_busca()` (reusada na listagem e na contagem) gerando `p.nome LIKE` para o termo literal + variantes `u00xx`/`\u00xx`; o `banco_escape_field` escapa a `\` da variante com barra, casando o conteúdo corrompido no banco. (c) O bloco de métricas "Exibindo X de Y" foi adicionado aos templates físicos lista/grid (e aos novos) com `[data-page-count]`/`[data-page-total]` e os placeholders `@[[page_count]]@`/`@[[page_total]]@`; o JS conta os filhos da listagem ignorando `.publisher-index-empty`. (d) O branch de simulação do `publisher-highlights` foi unificado com `publisher-index` em `publisherVariablesOrSimulation` (parametrizando a origem do count: `#count` vs `#items_per_page`). (e) **Pedido adicional do Engenheiro Chefe Humano nesta rodada (fora do req-041)**: criados 2 novos modelos para `publisher-index` — `publisher-index-timeline` (Linha do Tempo, trilho vertical com marcadores) e `publisher-index-agenda` (cartões horizontais com data em destaque), em pt-br e en, usando exclusivamente variáveis garantidas + blocos do widget, registrados em `publisher-index.json` (`version` 1.0, checksums vazios para o pipeline). Versões dos recursos alterados incrementadas (módulo 1.0.0→1.1.0; templates/páginas 1.1→1.2); checksums recalculados pelo pipeline.

## DEC-056 - 2026-06-15 - accepted

Controle de Métricas no publisher-index e suíte de testes MySQL (req-042 / BATCH-042). Decisões desta rodada:
1. **Controle `show_metrics` como flag retrocompatível de schema**: O campo vive em `fields_schema`, com default `true` em adicionar/editar/clonar e no JavaScript administrativo. Registros antigos continuam exibindo métricas sem migração de dados.
2. **Bloco condicional próprio para métricas**: As métricas usam os delimitadores `<!-- metrics < --> ... <!-- metrics > -->`, processados pelo mesmo helper `publisher_index_widget_bloco_condicional()` dos controles de busca, ordenação e carregar mais.
3. **Variável global `show_metrics`**: O renderer passa a resolver `[[show_metrics]]`/`@[[show_metrics]]@` como `'true'` ou `'false'`, permitindo templates customizados que queiram refletir esse estado em atributos ou lógica visual.
4. **Teste integrado MySQL gated e banco dedicado**: O teste de busca real só roda com `CONN2FLOW_RUN_DB_TESTS=1` e exige `CONN2FLOW_DB_DATABASE=conn2flow_test`. O setup cria/dropa apenas tabelas mínimas nesse banco dedicado, evitando qualquer impacto no banco de desenvolvimento.
5. **Correção no `LIKE` para barra invertida literal**: A validação integrada revelou que `\` também é caractere de escape no padrão `LIKE` do MySQL. Para casar nomes gravados como `T\u00edtulo`, a variante com barra precisa ter a barra dobrada no padrão antes de `banco_escape_field()`.

**Notas de execução (BATCH-042)**: implementado em `publisher-index.php`, `publisher-index.js`, `publisher-index.widget.php`, seis views CRUD, oito templates físicos e `tests/Integration/PublisherIndexWidgetTest.php`. Validação: `php -l` nos PHPs alterados, `node --check` no JS administrativo, `composer test` OK e teste MySQL dedicado contra `conn2flow_test` OK.

## DEC-057 - 2026-06-15 - accepted

Curadoria Manual no publisher-index, Novos Templates com Imagem, Variável de Widget no CRUD e Suporte Inline no Editor (req-043 / BATCH-043). Decisões desta rodada:
1. **Regra de alimentação manual resolvida em PHP**: Quando `rule === 'manual'` no `fields_schema`, a busca dinâmica é substituída pela curadoria explícita. Uma nova função `publisher_index_widget_lista_manual()` consulta os itens com filtro restritivo `AND p.id IN (...)` (cada ID via `banco_escape_field`), **sem ORDER BY nem LIMIT no SQL**, mapeia os registros por `page_id` e **reordena em PHP para respeitar exatamente a ordem de `selected_items`**; a busca textual filtra em PHP (case-insensitive sobre título e campos custom, via `publisher_index_widget_item_casa_busca()`) e a paginação é aplicada por `array_slice(offset, limit)`. `buscar_publicacoes()` e `contar_publicacoes()` ganham um dispatch no topo: sem busca, a contagem é `count(selected_items)` literal; com busca, conta os itens curados que casam; `selected_items` vazio retorna `[]`/`0`. `render_inline` e `render_ajax` passam `rule`/`selected_items` adiante. A montagem de itens foi extraída para `publisher_index_widget_montar_itens()` (compartilhada com a busca dinâmica). Retrocompat: registros sem `rule` seguem em `latest` (dinâmico).
2. **Novos modelos com imagem destaque**: 4 templates físicos (`publisher-index-grid-imagem`, `publisher-index-lista-imagem`, em pt-br e en), clonados dos modelos grid/lista existentes acrescentando `<img src="@[[item#imagem]]@">` (campo custom mapeável) e um resumo `@[[item#resumo]]@`, preservando os blocos do widget (`search-input`/`sort-select`/`metrics`/`item`/`no-item`/`load-more`) e utilitários Tailwind padrão. Registrados em `publisher-index.json` com `version` 1.0 e checksums vazios (pipeline recalcula).
3. **Variável de Widget no CRUD dos 4 módulos**: A aba `data-tab="hep-widget"` ganhou, entre o bloco informativo e o editor CodeMirror, um campo "Variável do Widget" (input readonly `#hep-widget-val` + botão `#btn-copy-widget-val` `.teal` com ícone `copy`) e um header "Código HTML". O JS de cada módulo (`updateWidgetCodeTab`) passa a popular `#hep-widget-val` com a assinatura em formato de variável `[[widgets#MODULO->render({"grupo_slug":"SLUG"})]]` e registra (uma vez, via delegação) o clique de copiar usando `navigator.clipboard.writeText()` com fallback por elemento temporário e feedback visual i18n.
4. **Variáveis de widget inline no editor e pré-visualizador**: No `renderWidgets()` de `widgetPreviewBootstrap` (`html-editor-interface.js`) e em `convertWidgetCommentsToWrappers()` (`html-editor.js`), antes da varredura de comentários, ocorrências de `[[widgets#...]]`/`@[[widgets#...]]@` no `document.body.innerHTML` são convertidas em pares de comentários `<!-- widgets#$1 < --><!-- widgets#$1 > -->` (regex literal `/@?\[\[widgets#(.+?)\]\]@?/gi`, preservada pela injeção via `.toString()`), tornando-as renderizáveis (preview) e editáveis (wrappers).
5. **Correção de teste (req §5)**: O stub jQuery do Vitest (`tests/Unit/JS/helpers/jquery-stub.js`) não implementava `.children()`/`.not()`, deixando `publisher-index.widget.test.js` (do BATCH-041/042) vermelho no baseline. Adicionados os dois métodos (adição pura, sem alterar comportamento existente) — formalizado no req-043 §5. As versões dos módulos foram incrementadas (`publisher-index` 1.1.0→1.2.0; `menus` 1.0.2→1.0.3; `galleries` 1.0.1→1.0.2; `publisher-highlights` 1.2.0→1.2.1); as `version` de páginas/templates ficam para o pipeline (que detecta mudança por checksum dos `*Data.json`, não pela `version` do manifest).
6. **Prefixagem dinâmica de url-raiz em campos de imagem (req §6)**: Os campos custom em `publisher_pages.fields_values` guardam caminhos relativos, mas os novos templates de imagem destaque precisam do caminho a partir da raiz. Como `publisher_pages` não declara tipos, o tipo de cada campo é lido do `fields_schema` da tabela `publisher` (`fields[].{id,type}`) por uma helper com **cache estático** por idioma+publicador (`publisher_index_widget_tipos_campos_publicador` / `publisher_highlights_widget_tipos_campos_publicador`). Em `publisher-index`, `montar_itens($rows, $publisher_id = null)` ganhou o 2º parâmetro (passado por `buscar_publicacoes` e `lista_manual`); em `publisher-highlights`, a prefixagem entra no loop de `buscar_publicacoes`. Campos cujo tipo é `'image'` e valor não-vazio recebem `$_GESTOR['url-raiz']` via `*_widget_prefixar_url_raiz()`, que **preserva URLs já absolutas** (`http`/`https`/protocol-relative/`data:`) e **evita barra dupla** (divergência defensável do req, que pedia prefixo incondicional).

**Notas de execução (BATCH-043)**: (a) A reordenação manual usa um mapa `page_id => item` e itera `selected_items`, então IDs inexistentes, inativos ou sem correspondência em `publisher_pages` (INNER JOIN) são silenciosamente descartados — mas a contagem **sem busca** segue `count(selected_items)` literal por requisito (§1.2). (b) A inserção do campo "Variável do Widget" nos 24 HTMLs foi feita por script PowerShell (UTF-8 sem BOM, acentos via `[char]` para sobreviver ao PS 5.1) inserindo o bloco antes do `<div style="border:1px solid #ccc;">` comum aos 24 arquivos. (c) Os 4 JS administrativos compartilham o mesmo handler de cópia (delegado, registrado uma vez), diferindo só no `MODULO` da assinatura. (d) A prefixagem de imagem (§6) lê o tipo do `fields_schema` da tabela `publisher` (não de `publisher_pages`); o teste integrado passou a criar/popular também a tabela `publisher` no seed (`noticias` com `resumo`=text, `imagem`=image) — necessário porque `montar_itens` agora a consulta em todos os testes integrados. (e) Validação: `php -l`/`node --check`/JSON OK; PHPUnit **40/40 (132 asserts)** com MySQL `conn2flow_test` incluindo os testes da curadoria manual (ordem/slice/busca/contagem), `item_casa_busca`, `prefixar_url_raiz` (puro) e prefixagem de imagem (integrado, relativo→raiz / absoluto preservado / text intacto); Vitest 3/3 OK.

## DEC-058 - 2026-06-15 - accepted

Prefixagem Dinâmica de URL Raiz para Campos de Imagem nos Widgets (req-043 / BATCH-043). Decisões de design:
1. **Recuperação de Tipos de Campo via Schema**: A tabela `publisher` guarda o schema de campos no campo `fields_schema` (JSON). Para identificar quais campos customizados de uma publicação correspondem a imagens, o sistema deve recuperar este schema usando o `publisher_id` (natural key) e a linguagem corrente (`$_GESTOR['linguagem-codigo']`), mapeando os IDs de campos que possuem o tipo `"image"`.
2. **Otimização por Cache em Memória**: Para evitar que o sistema execute consultas repetitivas ao banco de dados ao processar múltiplos itens do mesmo publicador (no mesmo request/render), os schemas decodificados devem ser armazenados em cache estático por chave composta (`publisher_id` + `language`).
3. **Prefixagem Dinâmica nos Widgets**:
   - **No `publisher-index`**: A assinatura da função helper de montagem de itens (`publisher_index_widget_montar_itens()`) passa a aceitar `$publisher_id`. Seus chamadores (`buscar_publicacoes` e `lista_manual`) fornecem o ID. Durante a conversão de campos customizados, caso o tipo do campo no schema do publicador seja `'image'` e ele contenha valor, a URL raiz (`$_GESTOR['url-raiz']`) é concatenada automaticamente.
   - **No `publisher-highlights`**: Na rotina de busca de publicações (`publisher_highlights_widget_buscar_publicacoes()`), após decodificar o `fields_values`, realiza-se a mesma verificação baseada no schema do publicador para prependir a URL raiz aos campos do tipo `'image'`.

**Notas de execução (BATCH-043)**: A mudança visa garantir que os novos templates que utilizam `@[[item#imagem]]@` (ou campos mapeados equivalentes de imagem) recebam o caminho absoluto ou a partir da raiz do site configurada na variável global `$_GESTOR['url-raiz']` em tempo de execução.

## DEC-059 - 2026-06-16 - accepted

Correção de Caracteres Especiais nos Widgets, Suporte AJAX no Preview e Refatoração de Módulos (req-044 / BATCH-044). Decisões de design:
1. **Assinatura de widget fora do DOM (mapa em memória)**: A causa-raiz da corrupção (`menus-&gt;render`/`&amp;gt;`/`&quot;`) era persistir a assinatura crua (`->`, aspas, chaves) no atributo `data-widget-signature`, que o navegador re-escapa ao serializar/reparsear o `innerHTML`. O atributo foi **removido**; a assinatura passa a viver apenas em `this.widgetsMap` (objeto em memória do `HtmlEditor`), chaveado por um `data-widget-id` limpo (`widget-N`, contador `this.widgetCounter`). No DOM ficam só atributos alfanuméricos (`data-widget-id`/`data-widget-type`/`data-widget-slug`/`data-widget-variable`). `getWidgetSignature(wrapper)` resolve pelo mapa (fallback reconstruindo de type/slug).
2. **Diferenciação variável vs comentário na carga**: `convertWidgetCommentsToWrappers()` converte `[[widgets#...]]`/`@[[widgets#...]]@` em comentários temporários rotulados `widgets-var#` (distintos dos comentários reais `widgets#`), marcando `isVariable` no mapa. No save (`extractUserHtml`), `isVariable=true` reconstrói a variável `[[widgets#signature]]` e `isVariable=false` reconstrói o par de comentários com o mockup — corrigindo a perda do formato de variável reportada no req §1.1.
3. **Unescape de entidades (incl. duplo escape)**: helper `htmlUnescape` (no `html-editor.js`) e `unescapeEntities` (interno ao `widgetPreviewBootstrap`, injetado via `.toString()`) usam `<textarea>` (RCDATA) em laço idempotente (até 3 passes) para decodificar `&gt;`/`&quot;`/`&amp;` e o duplo escape `&amp;gt;`, aplicado antes de processar/rotear a assinatura.
4. **Save de variável sem re-escape (token)**: como `container.innerHTML` re-escaparia `>`/`&` de um text node, o save substitui o wrapper-variável por um token alfanumérico (`__C2F_WVAR_N__`) e troca o token pela string `[[widgets#signature]]` na string final — preservando `->`/aspas intactos.
5. **Edição/clonagem com novo id**: `editWidgetWrapper` gera um **novo** `data-widget-id` e copia os metadados anteriores ao atualizar o slug, evitando que clones na tela compartilhem a mesma entrada do mapa.
6. **Inclusão automática e desduplicada de `*.widget.js` no preview**: `previewHtmlConteudo` extrai as assinaturas presentes (`extrairAssinaturasWidgets`) e injeta no `<head>` do iframe os scripts controladores `{raiz}{modulo}/widget.js` (mapa fixo `galleries`/`publisher-index`/`menus`), uma única tag por módulo. URL no padrão já usado por `menus.js`/`galleries.js`/`publisher-index.js` (`gestor.raiz + modulo + '/widget.js'`).
7. **`widgetsToAjax` replicado no iframe**: como o `srcdoc` é gerado estaticamente em JS (o PHP não cria a variável nesse contexto), `montarWidgetAssetsHead` injeta `window.gestor = Object.assign({}, window.parent.gestor); window.gestor.widgetsToAjax = "SIG1<#;>SIG2…"` com as assinaturas únicas. Contrato confirmado em `widgets.php`: cada item de `widgetsToAjax` (divisor `<#;>`) é a assinatura completa repassada a `widgets_get` como `$id`.
8. **Refatoração para `html-editor-modules.js` (divergência defensável do req §5.2)**: as 26 simulações de `menus`/`galleries`/`publisher` (+ `MENUS_SIM_FALLBACK`/`GALLERIES_SIM_FALLBACK` + a var de estado `publisher_table_tr_skeleton`) foram extraídas para o novo arquivo, anexadas ao `window`, carregado **antes** do interface (`html-editor.php`). Além das 3 variáveis pedidas pelo req (`CodeMirrorHtml`/`CodeMirrorHtmlExtraHead`/`publisher_fields_schema`), a análise de dependências mostrou que as funções movidas também usam as auxiliares `frameworkCSS`/`previewHtml`/`regexVariaveisGlobal`/`alvoUsaItemVars` (que permanecem no interface) — todas igualmente expostas no `window` para que as referências por nome nu resolvam em runtime. Os itens intercalados não-listados (`getUpdatedHtmlWithValues`, `window.publisher_*_update_target_variables`, `addVariableSkeleton`) permaneceram no interface.

**Notas de execução (BATCH-044)**: (a) A extração do Slice 5 foi feita por script Node temporário (localização por nome no nível de indentação 4 + fim por linha `    }`/`    ];`, mais comentários contíguos acima), preservando o código verbatim e removendo as exposições obsoletas (`window.publisherValuesUpdate`/`window.publisherGetAllVariables`, agora feitas pelo modules.js). (b) `publisher_fields_schema` é compartilhado por referência de objeto (mutação de `.template_map` no interface é visível no modules.js); nunca é reatribuído. (c) Validação: `node --check` em 5 JS, `php -l` no `html-editor.php`, Vitest 3/3 e PHPUnit 40/40 (112 asserts, 4 skip de DB gated). Deploy/runtime pendentes com o operador.
