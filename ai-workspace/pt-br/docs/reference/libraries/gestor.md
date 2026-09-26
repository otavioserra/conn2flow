---
title: "Biblioteca gestor.php"
label: "Núcleo do Gestor"
description: "O núcleo de apoio do Gestor: componentes e layouts, textos por módulo, recursos de página, SEO e OpenGraph, acesso restrito, redirecionamento, sessão em banco e higienização do HTML servido."
section: reference
order: 11
sources:
  - gestor/bibliotecas/gestor.php
  - gestor/config.php
verified_at: a6e51e29
---

# Biblioteca `gestor.php`

`bibliotecas/gestor.php` é a biblioteca de apoio usada por todo o Gestor e sempre carregada. Não confunda com `gestor/gestor.php`, o **roteador**, que monta a página a cada requisição e chama estas funções. Ao carregar, ela inclui também `recursos.php`, se ainda não estiver presente, porque todas as tags `<script>`/`<link>` resolvem a URL por `recursos_url()`.

Muitas funções são **puras**, sem estado global: foram extraídas do roteador (que termina em `exit` e não pode ser incluído num teste) justamente para serem testáveis.

## Utilitários básicos

- `existe($dado)` é o "tem conteúdo?" usado em todo o core: array com elementos, string com pelo menos um caractere, ou qualquer valor verdadeiro. Atenção: `existe('0')` é `true`, mas `existe(0)` é `false`.
- `gestor_asset_version($owner = null, $fallback = null)` devolve o token de cache de um diretório de assets. Procura em `$_GESTOR['asset-versions']['owners'][$owner]`; depois em `$_GESTOR['asset-version']`, no `$fallback` e na versão do sistema.
- `gestor_modulo_asset_version($modulo)` devolve o token de cache do JS/CSS de um módulo: `asset_version` do JSON do módulo, senão `versao`, senão o token global.
- `gestor_modulos_dados($modulo_id)` lê e decodifica `gestor/modulos/<id>/<id>.json`. Devolve `null` quando o arquivo não existe, sem emitir *warning*.
- `gestor_js_variavel_incluir($variavel, $valor)` publica um valor para o JavaScript da página em `$_GESTOR['javascript-vars']`, que o roteador serializa como objeto global. Se a variável já existe e os dois valores são arrays, faz `array_merge_recursive`; senão, sobrescreve.

## Bibliotecas

`gestor_incluir_bibliotecas()` e `gestor_incluir_biblioteca($nome)` carregam bibliotecas pelo nome lógico. Veja [Bibliotecas do Gestor](index.md).

## Framework CSS de uma página

Uma página final é layout + página, e **os dois** têm `framework_css`.

- `gestor_framework_css_resolver($layout, $pagina)` decide o que carregar:
  - **Fomantic** entra se um dos dois o declarar, ou se nenhum declarar nada (legado);
  - **Tailwind** entra se um dos dois o declarar.
  - Devolve `['fomantic' => bool, 'tailwind' => bool, 'modo' => 'fomantic-ui'|'tailwindcss'|'hibrido']`.
- `gestor_framework_css_atual()` aplica a regra a `$_GESTOR['layout#framework_css']` e `$_GESTOR['pagina#framework_css']`.

## Esquema do banco sem erro 500

Código novo pode chegar a um banco antigo (migração que falhou, atualização só de arquivos, `--skip-migrate`, a janela do deploy). Para a funcionalidade nova **sumir** em vez de derrubar a tela:

- `gestor_schema_tabela_existe($tabela)` faz um `SHOW TABLES` por requisição (memorizado) e responde `false` também quando o banco não pôde ser consultado;
- `gestor_schema_campo_existe($campo, $tabela)` verifica a tabela primeiro e memoriza por `tabela.campo`, sem lançar exceção.

## Componentes e layouts

### `gestor_componente($params)`

Busca componentes na tabela `componentes`, no idioma atual (ou em `linguagem`).

| Parâmetro | Efeito |
|---|---|
| `id` | Id textual, ou **array de ids** (devolve `[id => ['html' => …]]`) |
| `id_componentes` | Id numérico (alternativa) |
| `modulo` | Restringe ao módulo |
| `return_css` | Devolve também `css`, `css_precompiled`, `css_compiled` e `html_extra_head`, sem incluí-los na página |
| `modulosExtra` | Módulos cujas variáveis também serão resolvidas na página |

- Sem `return_css`, o CSS e o `html_extra_head` do componente entram no `<head>` via `gestor_pagina_recursos_incluir()`, sem duplicar, e só o HTML volta.
- Com `$_GESTOR['development-env']` ligado, o HTML e o CSS vêm **dos arquivos** em `resources/` (do core ou do módulo), e não do banco.
- Não encontrado: `''` (ou `[]` com array de ids). A consulta **não filtra `status`**.
- `gestor_componente_ids_condicao($ids)` monta o `(id='a' OR id='b')` escapado usado na busca múltipla.

Para anexar componentes ao fim da página: marque com `gestor_componentes_incluir(['id' => …])` ou `(['componentes' => [...]])`; o roteador chama `gestor_componentes_incluir_pagina()`, que renderiza cada marcado e concatena em `$_GESTOR['pagina']`.

### `gestor_layout($params)`

Mesmo modelo, na tabela `layouts` (`id`, `id_layouts`, `return_css`, `modulosExtra`). Diferenças:
- grava o `framework_css` do layout em `$_GESTOR['layout#framework_css']`;
- inclui o CSS pré-compilado com o papel `layout-precompiled`, para o layout vir primeiro na cascata;
- em desenvolvimento, lê também layouts de **plugins** (`plugins/<plugin>/modules/<modulo>/resources/…`);
- se o layout não existe, devolve um HTML mínimo (`<!-- pagina#titulo -->`, `<!-- pagina#css -->`, `<!-- pagina#js -->` e `@[[pagina#corpo]]@`).

> [!WARNING]
> `gestor_layout()` põe `id`/`id_layouts` no SQL **sem escapar** e **sem filtrar `status`**. Passe só valores vindos do banco ou do código, nunca da requisição.

## Recursos de página (CSS e `<head>`)

`gestor_pagina_recursos_incluir(['css' => …, 'css_precompiled' => …, 'css_precompiled_role' => …, 'css_compiled' => …, 'html_extra_head' => …])` é o **único** caminho certo para widgets e componentes levarem CSS ao `<head>`. Cada trecho entra uma vez só, com deduplicação por MD5 em `$_GESTOR['recursos-incluidos-hashes']`, e é marcado no DOM:
- `css` vira `<style data-c2f-css-role="authored">`;
- `css_compiled` vira `data-c2f-css-role="compiled"`;
- `css_precompiled` vira `<style data-tailwind-role="…">`, com papel `layout-precompiled`, `dependency-precompiled`, `page-precompiled` ou `resource-precompiled` (o padrão).

`gestor_css_precompiled_ordenar($styles)` ordena os `<style>` pré-compilados por papel: layout, dependências, página, recursos e o resto. As *cascade layers* do Tailwind v4 são definidas na primeira aparição, e o layout precisa declarar `theme, base, components, utilities` antes de tudo. Com `$_GESTOR['tailwind-page-bundle']` (bundle canônico por página), só entram o da página e os sem papel. Os `resource-precompiled` descartados geram um único `log_disco` na categoria `tailwind`.

### Auditoria e procedência do CSS

Usadas pelos comandos `css:audit` e `css:rebuild` e pelos CRUDs que gravam recursos:

- `gestor_css_classes_usadas($html)` lista as classes do markup, ignorando marcadores de template (`[`, `{`, `@`). Isso inclui variantes arbitrárias do Tailwind como `bg-[rgb(…)]`.
- `gestor_css_classes_definidas($css)` lista as classes definidas numa folha, desescapando `md\:flex`.
- `gestor_css_classes_descobertas($html, $css)` lista as usadas e não definidas, exceto os marcadores `group` e `peer`.
- `gestor_css_classes_em_codigo($codigo)` acha classes montadas em PHP/JS (`class="…"`, `classList.add/remove/toggle`, `className =`): é a dívida que obriga a declarar `tailwind_sources`.
- `gestor_css_procedencia_assinatura(['html','css','baseline','compilador'])` gera `v2:<sha1>`: o carimbo de **com que entrada** o CSS derivado foi gerado. O `baseline` é o CSS do layout, e o `compilador` é a versão do Tailwind.
- `gestor_css_compilador_versao()` lê essa versão do registro de `assets-externos.php` (`tailwindcss-browser`).
- `gestor_css_procedencia_para_recurso($html, $css, $layout_id, $tabela)` devolve a assinatura pronta para gravar em `css_source_hash`, ou `''` se a coluna ainda não existe.
- `gestor_css_procedencia_valida($gravada, $params)` compara. Assinatura ausente conta como **inválida**, de propósito.

## Variáveis de texto (i18n)

Os textos de interface ficam na tabela `variaveis`, por `language` e `modulo`.

- `gestor_variaveis(['modulo' => …, 'id' => …])` devolve o texto no idioma atual. Sem `modulo`, usa `_global_` (`modulo IS NULL`). Carrega **o módulo inteiro** na primeira chamada e guarda em `$_GESTOR['variaveis'][$modulo]`. Ausente: `''`. Com `conjunto => true`, devolve todas as variáveis do módulo; `padrao` filtra os ids por uma substring, sem diferenciar maiúsculas de minúsculas, e `reset` relê do banco.
- `gestor_variaveis_globais(['id' => …])` faz a busca pontual por id e devolve `null` se não achar.
- `gestor_variaveis_alterar(['modulo', 'id', 'tipo', 'valor', 'linguagem'])` atualiza o valor. Com `tipo = 'bool'`, grava `1`/`NULL`.

> [!WARNING]
> `gestor_variaveis_globais()` **não filtra por módulo**: devolve a primeira variável com aquele id em qualquer módulo e a guarda como global. `gestor_variaveis_alterar()` põe o `valor` no SQL **sem escapar**, e um apóstrofo quebra a consulta. `gestor_variaveis()` também não escapa `modulo`. Nenhuma das três atualiza o cache de outra.

`gestor_pagina_variaveis_globais(['html' => …])` resolve os marcadores `@[[…]]@` de um HTML, nesta ordem:
1. variáveis do módulo atual (`$_GESTOR['modulo-id']`);
2. variáveis dos módulos extras (`$_GESTOR['paginas-variaveis']`);
3. os marcadores de sistema `pagina#url-raiz`, `pagina#url-full-http`, `pagina#titulo`, `pagina#contato-url` e `pagina#url-caminho`.

O que não for resolvido permanece no HTML.

## Rotas, redirecionamento e query string

- `gestor_redirecionar($local = false, $queryString = '', $externo = false)` envia `Location` e **encerra com `exit`**.
  - Um `$local` interno recebe `$_GESTOR['url-raiz']` na frente.
  - Sem `$local`, usa a variável de sessão `redirecionar-local` (e a apaga) ou a raiz.
  - O alerta em `$_GESTOR['pagina-alerta']` é guardado na sessão para a próxima página.
- `gestor_redirecionar_montar_url($local, $queryString)` junta destino e query com `?` ou `&` conforme o destino (req-173).
- `gestor_redirecionar_raiz()` vai para a página marcada como `raiz` do módulo atual (ou para `/`).
- `gestor_reload_url()` recarrega o caminho atual.
- `gestor_querystring($remover = '')` devolve a query atual sem o parâmetro interno `_gestor-caminho` (e sem `$remover`).
- `gestor_querystring_variavel($qs, $nome)` lê um parâmetro.
- `gestor_querystring_remover_variavel($qs, $nome)` tira um parâmetro. Os valores voltam **decodificados**, sem `urlencode`.
- `gestor_querystring_before_submit($campo, $padrao)` lê a query que o formulário guardou num campo oculto (`_c2f_query_string_before_submit`), já escapada para SQL.
- `gestor_roteador_erro_terminal($codigo, $caminho)` diz se o 404 já está na rota `404/`, para não entrar em laço 404 → 404.
- `gestor_roteador_pagina_status_http($caminho)` devolve 404 para essa página.
- `gestor_pagina_301_registrar($id_paginas, $caminho)` grava em `paginas_301` que o caminho antigo pertence à página, deduplicando pelo par (caminho, página).
- `gestor_pagina_rota_sistema($caminho)` reconhece as rotas que não são conteúdo: `cookies-is-mandatory`, `_gestor-cookie-verify`, `404`, `403`, `500` e `503`. Nelas não entram scripts de rastreamento.

## Robôs, OpenGraph e SEO

- `gestor_crawler_detectar($userAgent, $tokensExtra = null)` compara o User-Agent, em minúsculas e por substring, com:
  - `gestor_crawler_tokens_padrao()`: WhatsApp, Meta, Googlebot, Bing, Lighthouse, Ahrefs, monitores de uptime…;
  - `gestor_crawler_tokens_extra()`: a lista de *Ambiente → Configurações do Site*, que só vale com `crawler-tokens-extra-ativo`.
- `gestor_crawler_tokens_normalizar($bruto)` transforma texto separado por vírgula, ponto e vírgula ou quebra de linha numa lista sem repetição.
- `gestor_cookie_verificacao_desfecho(['crawler','tem_cookie','exigir_sessao','caminho'])` decide entre `ignorar`, `emitir` e `redirecionar` na verificação de cookie. Numa rota de sistema **nunca redireciona**, o que evita o laço da página "cookies obrigatórios".
- `gestor_open_graph_tags([...])` monta `og:title`, `og:description`, `og:image`, `og:url`, `og:site_name` e `og:type`, mais `twitter:card`. Nunca emite tag vazia.
- `gestor_open_graph_existe($html)` detecta se o HTML já traz as suas; nesse caso o core não injeta as dele.
- `gestor_pagina_og_do_registro($pagina)` extrai `og_titulo`, `og_descricao`, `imagem_destaque`, `meta_descricao` e `meta_keywords` preenchidos do registro da página.
- `gestor_meta_seo_tags(['description','keywords'])` gera as metatags clássicas; `gestor_meta_seo_existe($html)` evita duplicá-las; `gestor_meta_keywords_normalizar($bruto)` limpa as palavras-chave (sem repetição, mantendo a caixa).
- `gestor_pdf_viewer_detectar($html)` vê se a página tem o leitor PDF.js (classe `conn2flow-pdfjs`), e `gestor_pdf_viewer_assets($urlRaiz, $versao)` devolve os `<script>` (PDF.js 3.11.174 do cdnjs e `interface/pdf-viewer.js`).

## Acesso restrito ao site (req-163)

Com `SITE_RESTRICTED_ACCESS=true` no `.env`, só usuários logados veem o site:
- `gestor_site_acesso_restrito_ativo()` lê a chave de `$_CONFIG` e cai para `$_ENV`/`getenv()`;
- `gestor_site_acesso_restrito_perfis()` devolve os `id_usuarios_perfis` permitidos em `SITE_RESTRICTED_PROFILES` (só inteiros positivos);
- `gestor_site_acesso_restrito_perfil_autorizado($perfil, $lista)` libera qualquer logado se a lista estiver vazia; anônimo nunca;
- `gestor_site_acesso_restrito_rota_isenta($caminho)` libera as rotas de identidade (login, cadastro, recuperação de senha, OAuth), `_api`, `api`, `_gateways` e as rotas de sistema.

## CSRF e ícones do menu

- `gestor_csrf_rotas_identidade()` lista as telas com token de uso único (`signin`, `signin-2fa`, `signup`, `forgot-password`, `reset-password`, `validate-user`).
- `gestor_csrf_destino_recarregamento($caminho, $referer, $urlRaiz)` escolhe para qual dessas telas a página de erro de CSRF deve recarregar, pelo caminho atual e depois pelo referer.
- `gestor_pagina_menu_icone_lucide_valido($nome)` aceita só nomes kebab-case, os únicos que o Lucide resolve.
- `gestor_pagina_menu_icone_lucide_atributo($nome)` devolve `data-lucide="…"`, ou nada para nome inválido, o que evita o aviso `icon name was not found`.

## Sessão

A sessão do Gestor fica **no banco**: tabelas `sessoes` e `sessoes_variaveis`. Não usa `$_SESSION`.

- `gestor_sessao_iniciar()` cria o cookie `$_CONFIG['session-authname']` com 32 bytes aleatórios (`seguranca_token_aleatorio()`), `HttpOnly`, `SameSite=Lax`, domínio `SERVER_NAME` e validade `session-lifetime` (10800 s por padrão).
- `gestor_cookie_is_secure()` decide o `Secure`: sempre em HTTPS (inclusive atrás de proxy, por `X-Forwarded-Proto`) e sempre em produção; só fica sem `Secure` com HTTP **e** `development-env`.
- `gestor_sessao_id()` devolve o id numérico da sessão, criando a linha se preciso, e atualiza `acesso` uma vez por requisição. Em **1 de cada 51 requisições** apaga as sessões (e variáveis) sem acesso há mais de `session-lifetime`.
- `gestor_sessao_variavel($nome, $valor = null)` guarda (JSON) ou lê um valor. Ausente: `''`. Não há como gravar `null`: ele significa leitura.
- `gestor_sessao_variavel_del($nome)` apaga uma variável; `gestor_sessao_del()` apaga a sessão atual e expira o cookie.
- `gestor_sessao_del_all()` apaga **todas as sessões de todos os usuários**.

> [!NOTE]
> O cookie vence `session-lifetime` segundos depois de **criado** e não é renovado a cada acesso. Por isso o token CSRF (variável de sessão) pode expirar numa aba aberta; o `global.js` o renova sozinho (req-175).

## Higienização do HTML servido (req-132)

Em produção, o HTML entregue sai sem comentários e sem indentação.
- `gestor_pagina_higienizar_ativo()` lê `HTML_SANITIZE`:
  - `auto` (o padrão) liga em produção e desliga em desenvolvimento;
  - `on` liga sempre;
  - `off` desliga sempre;
  - valor desconhecido vira `auto`.
  - Com a barra de edição ao vivo (`gestor_dashboard_toolbar_ativo()`), nunca higieniza.
- `gestor_html_higienizar($html)` preserva `<pre>`, `<textarea>`, `<script>` e comentários condicionais; remove comentários HTML e comentários/indentação de CSS dentro de `<style>`; troca a indentação por uma quebra de linha, porque espaço entre elementos em linha é renderizado.
- `gestor_js_higienizar($js)` é um *scanner* (não uma regex) que respeita strings, *template literals* e regex literais, e mantém as quebras de linha por causa da ASI. `gestor_js_barra_inicia_regex($anterior)` decide se uma `/` abre regex ou é divisão.
- `gestor_pagina_higienizar_js_ativo()` é a chave própria `HTML_SANITIZE_JS` para desligar só a parte de JavaScript.
- `gestor_html_script_e_javascript($tag)` só aceita `<script>` inline de JavaScript. Ignora `src`, `application/json`, `text/template` e outros tipos usados como depósito de dados.

## Veja também

- [Bibliotecas do Gestor](index.md), [Biblioteca modelo.php](modelo.md), [Biblioteca banco.php](banco.md)

## Funções (referência gerada)

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/gestor.php` por `c2f docs:extract` — 76 funções. Não edite dentro deste bloco.

- `existe(mixed $dado = false): bool` — [linha 43](../../../../../gestor/bibliotecas/gestor.php#L43)
- `gestor_asset_version($owner = null, $fallback = null)` — [linha 69](../../../../../gestor/bibliotecas/gestor.php#L69)
- `gestor_modulo_asset_version($modulo)` — [linha 80](../../../../../gestor/bibliotecas/gestor.php#L80)
- `gestor_framework_css_resolver(string|null $layoutFramework = null, string|null $paginaFramework = null): array{fomantic:bool,tailwind:bool,modo:string}` — [linha 112](../../../../../gestor/bibliotecas/gestor.php#L112)
- `gestor_framework_css_atual(): array{fomantic:bool,tailwind:bool,modo:string}` — [linha 139](../../../../../gestor/bibliotecas/gestor.php#L139)
- `gestor_schema_tabela_existe(string $tabela): bool` — [linha 175](../../../../../gestor/bibliotecas/gestor.php#L175)
- `gestor_schema_campo_existe(string $campo, string $tabela): bool` — [linha 211](../../../../../gestor/bibliotecas/gestor.php#L211)
- `gestor_css_precompiled_ordenar($styles)` — [linha 247](../../../../../gestor/bibliotecas/gestor.php#L247)
- `gestor_pagina_recursos_incluir(array $params = false)` — [linha 315](../../../../../gestor/bibliotecas/gestor.php#L315)
- `gestor_crawler_detectar(string|null $userAgent = null, $tokensExtra = null): bool` — [linha 399](../../../../../gestor/bibliotecas/gestor.php#L399)
- `gestor_crawler_tokens_padrao(): array` — [linha 430](../../../../../gestor/bibliotecas/gestor.php#L430)
- `gestor_crawler_tokens_extra(): array` — [linha 504](../../../../../gestor/bibliotecas/gestor.php#L504)
- `gestor_crawler_tokens_normalizar(string $bruto): array` — [linha 525](../../../../../gestor/bibliotecas/gestor.php#L525)
- `gestor_pagina_rota_sistema(string $caminho = ''): bool` — [linha 555](../../../../../gestor/bibliotecas/gestor.php#L555)
- `gestor_site_acesso_restrito_ativo(): bool` — [linha 586](../../../../../gestor/bibliotecas/gestor.php#L586)
- `gestor_site_acesso_restrito_perfis(string|array|null $bruto = null): array` — [linha 607](../../../../../gestor/bibliotecas/gestor.php#L607)
- `gestor_site_acesso_restrito_rota_isenta(string $caminho = ''): bool` — [linha 645](../../../../../gestor/bibliotecas/gestor.php#L645)
- `gestor_site_acesso_restrito_perfil_autorizado(string|int $perfilId, array $perfisAutorizados): bool` — [linha 698](../../../../../gestor/bibliotecas/gestor.php#L698)
- `gestor_roteador_erro_terminal(int|string $codigo, mixed $caminho = ''): bool` — [linha 716](../../../../../gestor/bibliotecas/gestor.php#L716)
- `gestor_roteador_pagina_status_http(mixed $caminho = ''): int|null` — [linha 728](../../../../../gestor/bibliotecas/gestor.php#L728)
- `gestor_pagina_301_registrar(int|string $id_paginas, string $caminho): bool` — [linha 754](../../../../../gestor/bibliotecas/gestor.php#L754)
- `gestor_open_graph_tags(array $params = false): array` — [linha 801](../../../../../gestor/bibliotecas/gestor.php#L801)
- `gestor_cookie_verificacao_desfecho(array $params = false): string` — [linha 860](../../../../../gestor/bibliotecas/gestor.php#L860)
- `gestor_pagina_og_do_registro(array $pagina = Array()): array` — [linha 885](../../../../../gestor/bibliotecas/gestor.php#L885)
- `gestor_meta_seo_tags(array $params = false): array` — [linha 927](../../../../../gestor/bibliotecas/gestor.php#L927)
- `gestor_meta_keywords_normalizar(string|array $bruto): string` — [linha 955](../../../../../gestor/bibliotecas/gestor.php#L955)
- `gestor_meta_seo_existe(string|array $html): bool` — [linha 988](../../../../../gestor/bibliotecas/gestor.php#L988)
- `gestor_open_graph_existe(string|array $html): bool` — [linha 1006](../../../../../gestor/bibliotecas/gestor.php#L1006)
- `gestor_pdf_viewer_detectar(string $html): bool` — [linha 1025](../../../../../gestor/bibliotecas/gestor.php#L1025)
- `gestor_pdf_viewer_assets(string $urlRaiz = '', string $versao = ''): array` — [linha 1054](../../../../../gestor/bibliotecas/gestor.php#L1054)
- `gestor_css_classes_usadas(string $html): array` — [linha 1073](../../../../../gestor/bibliotecas/gestor.php#L1073)
- `gestor_css_classes_definidas(string $css): array` — [linha 1103](../../../../../gestor/bibliotecas/gestor.php#L1103)
- `gestor_css_classes_descobertas(string $html, string $css): array` — [linha 1130](../../../../../gestor/bibliotecas/gestor.php#L1130)
- `gestor_css_classes_em_codigo(string $codigo): array` — [linha 1170](../../../../../gestor/bibliotecas/gestor.php#L1170)
- `gestor_css_procedencia_assinatura(array $params = false): string` — [linha 1249](../../../../../gestor/bibliotecas/gestor.php#L1249)
- `gestor_css_compilador_versao(): string` — [linha 1288](../../../../../gestor/bibliotecas/gestor.php#L1288)
- `gestor_css_procedencia_para_recurso(string $html, string $css, string $layout_id = '', string $tabela = 'paginas'): string` — [linha 1323](../../../../../gestor/bibliotecas/gestor.php#L1323)
- `gestor_css_procedencia_valida(string $assinaturaGravada, array $params = false): bool` — [linha 1370](../../../../../gestor/bibliotecas/gestor.php#L1370)
- `gestor_componente_ids_condicao($ids, $escape = null)` — [linha 1382](../../../../../gestor/bibliotecas/gestor.php#L1382)
- `gestor_componente(array|false $params = false): string|array|false` — [linha 1416](../../../../../gestor/bibliotecas/gestor.php#L1416)
- `gestor_layout(array|false $params = false): string|array|false` — [linha 1648](../../../../../gestor/bibliotecas/gestor.php#L1648)
- `gestor_incluir_bibliotecas(): void` — [linha 1910](../../../../../gestor/bibliotecas/gestor.php#L1910)
- `gestor_incluir_biblioteca(string $biblioteca): void` — [linha 1937](../../../../../gestor/bibliotecas/gestor.php#L1937)
- `gestor_variaveis(array|false $params = false): string|array` — [linha 1994](../../../../../gestor/bibliotecas/gestor.php#L1994)
- `gestor_variaveis_globais(array|false $params = false): string|null` — [linha 2083](../../../../../gestor/bibliotecas/gestor.php#L2083)
- `gestor_variaveis_alterar(array|false $params = false): void` — [linha 2142](../../../../../gestor/bibliotecas/gestor.php#L2142)
- `gestor_redirecionar_raiz(): void` — [linha 2191](../../../../../gestor/bibliotecas/gestor.php#L2191)
- `gestor_reload_url(): void` — [linha 2222](../../../../../gestor/bibliotecas/gestor.php#L2222)
- `gestor_csrf_rotas_identidade(): array<int,string>` — [linha 2243](../../../../../gestor/bibliotecas/gestor.php#L2243)
- `gestor_csrf_destino_recarregamento(string $caminhoTotal, string|null $referer, string $urlRaiz): string` — [linha 2271](../../../../../gestor/bibliotecas/gestor.php#L2271)
- `gestor_pagina_menu_icone_lucide_valido(string $nome): bool` — [linha 2330](../../../../../gestor/bibliotecas/gestor.php#L2330)
- `gestor_pagina_menu_icone_lucide_atributo(string $nome): string` — [linha 2350](../../../../../gestor/bibliotecas/gestor.php#L2350)
- `gestor_querystring_remover_variavel(string $queryString, string $removerVariavel = ''): string` — [linha 2368](../../../../../gestor/bibliotecas/gestor.php#L2368)
- `gestor_querystring_variavel(string $queryString, string $variavel = ''): string` — [linha 2397](../../../../../gestor/bibliotecas/gestor.php#L2397)
- `gestor_querystring_before_submit(string $fieldName = '_c2f_query_string_before_submit', string $default = ''): string` — [linha 2422](../../../../../gestor/bibliotecas/gestor.php#L2422)
- `gestor_querystring(string $removerVariavel = ''): string` — [linha 2445](../../../../../gestor/bibliotecas/gestor.php#L2445)
- `gestor_redirecionar(string|false $local = false, string $queryString = '', bool $externo = false): void` — [linha 2477](../../../../../gestor/bibliotecas/gestor.php#L2477)
- `gestor_redirecionar_montar_url(string $local, string $queryString = ''): string` — [linha 2517](../../../../../gestor/bibliotecas/gestor.php#L2517)
- `gestor_pagina_variaveis_globais(array|false $params = false): string` — [linha 2542](../../../../../gestor/bibliotecas/gestor.php#L2542)
- `gestor_js_variavel_incluir(string $variavel, mixed $valor): void` — [linha 2637](../../../../../gestor/bibliotecas/gestor.php#L2637)
- `gestor_componentes_incluir(array|false $params = false): void` — [linha 2667](../../../../../gestor/bibliotecas/gestor.php#L2667)
- `gestor_componentes_incluir_pagina(array|false $params = false): void` — [linha 2708](../../../../../gestor/bibliotecas/gestor.php#L2708)
- `gestor_cookie_is_secure(): bool` — [linha 2760](../../../../../gestor/bibliotecas/gestor.php#L2760)
- `gestor_sessao_iniciar()` — [linha 2782](../../../../../gestor/bibliotecas/gestor.php#L2782)
- `gestor_sessao_id(): int` — [linha 2816](../../../../../gestor/bibliotecas/gestor.php#L2816)
- `gestor_sessao_del(): void` — [linha 2892](../../../../../gestor/bibliotecas/gestor.php#L2892)
- `gestor_sessao_variavel(string $variavel, mixed $valor = NULL): mixed` — [linha 2943](../../../../../gestor/bibliotecas/gestor.php#L2943)
- `gestor_sessao_variavel_del(string $variavel): void` — [linha 3011](../../../../../gestor/bibliotecas/gestor.php#L3011)
- `gestor_sessao_del_all(): void` — [linha 3044](../../../../../gestor/bibliotecas/gestor.php#L3044)
- `gestor_modulos_dados(string $modulo_id = ''): array|null` — [linha 3067](../../../../../gestor/bibliotecas/gestor.php#L3067)
- `gestor_pagina_higienizar_ativo()` — [linha 3100](../../../../../gestor/bibliotecas/gestor.php#L3100)
- `gestor_html_higienizar($html)` — [linha 3139](../../../../../gestor/bibliotecas/gestor.php#L3139)
- `gestor_js_higienizar($js)` — [linha 3245](../../../../../gestor/bibliotecas/gestor.php#L3245)
- `gestor_js_barra_inicia_regex($anterior)` — [linha 3350](../../../../../gestor/bibliotecas/gestor.php#L3350)
- `gestor_pagina_higienizar_js_ativo()` — [linha 3368](../../../../../gestor/bibliotecas/gestor.php#L3368)
- `gestor_html_script_e_javascript($tagCompleta)` — [linha 3389](../../../../../gestor/bibliotecas/gestor.php#L3389)

<!-- c2f:extract:end -->
