---
title: "Ciclo de uma requisição"
description: "O caminho de uma requisição no Conn2Flow: front-controller, gestor.php, sessão e CSRF, roteamento da página, permissão, execução do módulo, montagem do HTML e resposta."
section: concepts
order: 10
sources:
  - gestor/gestor.php
  - gestor/config.php
  - gestor-instalador/public-access/index.php
  - gestor-instalador/public-access/.htaccess
  - gestor/bibliotecas/seguranca.php
verified_at: 82b0b6b8
---

# Ciclo de uma requisição

Toda requisição ao site passa pelo mesmo caminho. Entendê-lo explica quase todo o comportamento do Conn2Flow: por que uma página vem do banco, quando o módulo roda, em que momento as variáveis `@[[…]]@` são trocadas e por que um 404 vira redirecionamento.

## 1. Front-controller

A pasta pública tem só dois arquivos, gravados pelo instalador:
- **`.htaccess`** (Apache) força HTTPS (se escolhido) e manda tudo que não é arquivo ou pasta real para `index.php?_gestor-caminho=<caminho>`. No Nginx, o bloco de exemplo gerado na instalação faz o mesmo.
- **`index.php`** define `$_INDEX['sistemas-dir']` (a pasta do Gestor, fora da pasta pública) e inclui `gestor.php`.

## 2. `gestor.php` e `config.php`

`gestor.php` inclui `config.php`, que:
- lê o `.env` de `autenticacoes/<domínio>/`;
- preenche `$_GESTOR` (caminhos, URL raiz, idiomas, versões), `$_BANCO` e `$_CONFIG` (sessão, cookies, segurança, captcha, e-mail…);
- carrega as bibliotecas básicas (`banco`, `gestor`, `modelo`, `hooks`).

Depois chama `gestor_start()`, a sequência inteira:

```
gestor_start()
 ├─ gestor_cabecalhos_seguranca()   nosniff, Referrer-Policy, X-Frame-Options, HSTS (em HTTPS), CSP (se configurada)
 ├─ gestor_config()                 caminho, idioma, arquivos estáticos, _api, _gateways
 ├─ gestor_sessao_iniciar()         cookie de sessão
 ├─ seguranca_csrf_requisicao_validar()  → gestor_csrf_resposta_invalida() (403) se falhar
 └─ gestor_roteador()               página, permissão, módulo, montagem e saída
```

## 3. `gestor_config()`: caminho e desvios

- `_gestor-caminho` é validado por `gestor_caminho_publico_valido()` (sem `..`, byte nulo ou `\`, mesmo depois de decodificado até 3 vezes). Caminho inválido encerra com **400**.
- **Idioma na URL:** se o primeiro segmento é um idioma de `LANGUAGES` (`/en/docs/…`), ele vira `linguagem-codigo`, sai do caminho e entra em `url-raiz`. Sem idioma na URL, vale o cookie de idioma; senão, `LANGUAGE_DEFAULT`.
- **Caminho com extensão** (`.js`, `.css`, `.png`, `.txt`…) vai para o controlador `arquivo-estatico`, que serve de `gestor/assets/`, dos módulos ou de `contents/` (arquivos enviados) e encerra.
- `_api/…` vai para `controladores/api/api.php` e `_gateways/…` para `controladores/plataforma-gateways/`. Os dois têm autenticação própria (token ou assinatura), não passam pelo roteador de páginas e ficam isentos de CSRF.

## 4. Sessão e CSRF

- `gestor_sessao_iniciar()` garante o cookie de sessão. A sessão fica no banco; veja [gestor.php → Sessão](../reference/libraries/gestor.md).
- O **CSRF** vale para POST/PUT/PATCH/DELETE de quem tem o cookie de autenticação. O token vai em `<meta name="csrf-token">` e em `gestor.csrfToken`, e o `global.js` o anexa a `fetch`, `$.ajax` e `XMLHttpRequest` e o renova sozinho quando expira (req-175). Requisições GET não são verificadas.

## 5. `gestor_roteador()`: da URL à página

1. Lê os parâmetros de controle: `ajax`, `ajaxOpcao` (→ `ajax-opcao`), `opcao`, `ajaxPagina`, `ajaxWidgets`, `paginaIframe` e `ajaxRegistroId`.
2. **Rotas de sistema:** `_gestor-cookie-verify/` (prova de cookie) e a rota que devolve um token CSRF novo (`gestor_roteador_csrf_token()`).
3. **Acesso restrito ao site** (`gestor_roteador_acesso_restrito()`): com `SITE_RESTRICTED_ACCESS` ligado, quem não está logado vai para `/signin/`, exceto nas rotas isentas.
4. **Busca a página** na tabela `paginas`: `caminho` igual, idioma atual, `tipo` `sistema` ou `pagina`, `status='A'`, dentro da janela `data_publicacao_inicio`/`fim`. Se não achar no idioma atual, **aceita a mesma rota em qualquer idioma**.
5. O filtro `hook_apply_filters('gestor', 'roteador.paginas', $paginas)` permite a um plugin ou projeto trocar o resultado. Com o Live Editor, um backup restaurado pode substituir o HTML (`gestor_site_toolbar_backup_aplicar()`).
6. **Página não encontrada:** `gestor_roteador_301_ou_404()` procura o caminho em `paginas_301`. Se ele pertenceu a uma página ativa, responde **301** para o caminho atual dela; senão, **redireciona para `/404/`**. A página `404/` em si é servida com status 404. Em AJAX, a resposta é JSON `{"error":"404"}`.

## 6. Permissão

Se a página não tem `sem_permissao`, o roteador chama `gestor_permissao()`:
- **`gestor_permissao_token()`** valida o login. O cookie de autenticação guarda um token cuja assinatura é o `header.payload` **cifrado com a chave RSA do site**; o servidor o confere decifrando com a chave privada (`gestor_permissao_validar_jwt()`). Depois exige que o `pubID` exista em `usuarios_tokens`, com o HMAC `USUARIO_HASH_PASSWORD`, dentro da validade. Renova o token a cada `COOKIE_RENEWTIME` e confere User-Agent e IP contra roubo de sessão. O resultado é memorizado por requisição.
- Sem login: a rota atual é guardada para voltar depois do login, e a resposta é **401 → `/signin/`** (em AJAX, JSON com `code: AUTH_REQUIRED` e o cabeçalho `X-Gestor-Auth-Redirect`).
- **`gestor_permissao_modulo()`** confere se o perfil do usuário tem o módulo da página (`usuarios_perfis_modulos`, ou os perfis de gestor de host). Sem permissão: 401 → `/dashboard/`.
- Robôs de redes sociais numa página protegida recebem só o `<head>` com OpenGraph (`gestor_roteador_crawler_pagina_protegida()`), para o link ter prévia sem vazar conteúdo.

Dentro do código, use:
- `gestor_usuario()` para o usuário atual (anônimo: `id` `_anonimo`, `id_usuarios` 0);
- `gestor_acesso('operacao', 'modulo')` para uma operação específica. Se a operação não estiver cadastrada em `modulos_operacoes`, basta ter o módulo.

> [!CAUTION]
> `gestor_usuario_perfil()` devolve o valor do cookie `COOKIE_AUTHPROFILE`, que **não é assinado**. Serve para decisões de apresentação (como a visibilidade de menus), nunca para autorização. Quem decide acesso é `gestor_permissao_token()` + `gestor_usuario()`.

## 7. Execução do módulo

A página pode apontar um `modulo` (e um `plugin`). O roteador então inclui `gestor/modulos/<modulo>/<modulo>.php` (ou o do plugin), que termina chamando `<modulo>_start()`.

- **Requisição AJAX:** o módulo responde preenchendo `$_GESTOR['ajax-json']`, que o roteador devolve como JSON. Sem resposta, o erro é 500 ("No response data set").
  - Numa página **sem permissão**, o arquivo incluído é `<modulo>.ajax.public.php`, o único jeito de ter AJAX público.
  - Com `ajaxPagina`, o HTML da página é carregado junto.
  - `ajaxWidgets` aciona o AJAX dos widgets (`gestor_pagina_widgets_ajax()`).
- **Requisição normal com `opcao`** (por exemplo, um POST para `?opcao=salvar`): o módulo roda e o roteador **redireciona para a página raiz do módulo**.
- **Requisição normal sem `opcao`:** o HTML da página (do banco, ou dos arquivos de `resources/` em desenvolvimento) vira `$_GESTOR['pagina']`, e então o módulo roda e pode alterá-la.
- Sem `modulo` mas com `opcao`, roda `gestor/modulos/global.php`.

## 8. Montagem do HTML

Depois do módulo, nesta ordem:

1. `gestor_componentes_incluir_pagina()`: componentes marcados.
2. **Layout:** o de `$_GESTOR['layout']` (definido pelo módulo), o `layout_id` da página (`layout-iframes` com `paginaIframe`) ou nenhum.
3. `gestor_pagina_recursos_incluir()` do layout e da página (CSS pré-compilado, compilado, autoral e `html_extra_head`).
4. Dados do seletor de idioma, se ativo.
5. `gestor_pagina_layout()`: a página entra no `@[[pagina#corpo]]@` do layout, e `<!-- pagina#titulo -->` vira `<title>`.
6. `gestor_pagina_widgets()`: cada `<!-- widgets#modulo->funcao({...}) < -->…<!-- … > -->` (e a forma antiga `@[[widgets#…]]@`) é trocado pelo HTML do widget.
7. Barra do Live Editor (só para editores logados), PDF.js e CSS do Quill, só nas páginas que os usam.
8. `gestor_pagina_css()` e `gestor_pagina_extra_head_e_javascript()` preenchem `<!-- pagina#css -->` e `<!-- pagina#js -->`, com o objeto global `gestor` (versões, raiz, idioma, módulo, CSRF, `javascript-vars`), OpenGraph, SEO e robots.
9. `gestor_pagina_variaveis()` troca os marcadores `@[[…]]@`, na ordem:
   - os de sistema: `pagina#url-raiz`, `pagina#titulo`, `pagina#menu`, `usuario#nome`, `gestor#versao`…;
   - as variáveis de texto (globais, do módulo, dos módulos extras).
10. `gestor_pagina_ultimas_operacoes()` aplica os marcadores finais (`pagina-marcadores-finais`), remove linhas vazias e, em produção, **higieniza** o HTML (sem comentários nem indentação).
11. Saída com `Content-Type: text/html` e `exit`.

> [!IMPORTANT]
> Qualquer `@[[x]]@` que sobrar no HTML é procurado como variável de texto em **qualquer módulo**. Para **mostrar** um marcador como exemplo numa página, escape a arroba (`&#64;[[x]]&#64;`). É o que o `c2f docs:build` faz nas docs.

## Detalhes e legado

- `?hotfix` em qualquer URL faz o roteador responder "Hotfix Done!" e encerrar (`gestor_hotfix()`), resto de um mecanismo antigo.
- `gestor_permissao_fingerprint()` existe, mas a verificação por *fingerprint* está comentada em `gestor_permissao()`.
- `gestor_pagina_css_incluir($css)` com um CSS explícito empilha o valor na fila de **JavaScript** (`javascript-fim`), não na de CSS. Só a chamada sem argumento (o `css.css` do módulo) funciona como o nome sugere.
- `gestor_pagina_menu()` e `gestor_pagina_menu_icone()` montam o menu lateral do painel administrativo (`@[[pagina#menu]]@`) a partir dos módulos e grupos permitidos ao perfil.

## Veja também

- [Biblioteca gestor.php](../reference/libraries/gestor.md): sessão, redirecionamento, recursos de página e higienização.
- [Biblioteca interface.php](../reference/libraries/interface.md): o que acontece dentro de um módulo de CRUD.
