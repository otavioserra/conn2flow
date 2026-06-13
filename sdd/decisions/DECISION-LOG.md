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

