---
title: "Módulo cookie-consent"
description: "Aviso de cookies, cartão de preferências por categoria e liberação de scripts conforme a decisão."
section: reference
module: cookie-consent
sources:
  - gestor/modulos/cookie-consent/cookie-consent.php
  - gestor/modulos/cookie-consent/cookie-consent.js
  - gestor/modulos/cookie-consent/cookie-consent.widget.php
  - gestor/modulos/cookie-consent/cookie-consent.widget.js
  - gestor/modulos/cookie-consent/cookie-consent.json
  - gestor/modulos/cookie-consent/resources
  - gestor/db/migrations/20261002110000_create_presentations_and_cookie_consent_tables.php
verified_at: 268f7e75
---

# Módulo `cookie-consent`

Mostra o aviso de cookies, guarda a decisão do visitante por categoria e só libera o que ela permite. Um registro define categorias, textos, endereços da política e dos termos; o modelo desenha o aviso, o cartão de preferências e o botão que reabre o cartão.

## Como usar

Abra `cookie-consent/adicionar/`, dê um nome, escolha o modelo (`cookie-consent-card` ou `cookie-consent-card-light`) e ajuste as três abas: geral, categorias e textos. Insira o widget no layout do site, uma vez:

```html
<!-- widgets#cookie-consent->render({"grupo_slug":"site"}) < -->
<!-- widgets#cookie-consent->render({"grupo_slug":"site"}) > -->
```

Para um script só rodar com permissão, troque o tipo e diga a categoria:

```html
<script type="text/plain" data-cookie-category="analytics" data-src="https://exemplo.com/medicao.js"></script>
<script type="text/plain" data-cookie-category="marketing">/* código em linha */</script>
<iframe data-cookie-category="marketing" data-src="https://exemplo.com/video"></iframe>
```

Para reabrir o cartão de qualquer lugar (rodapé, política), use um link para `#cookie-consent` ou o atributo `data-cookie-consent-open`.

### Opções

| Opção | Padrão | O que faz |
|---|---|---|
| `version` | `1` | Trocar a versão faz o aviso voltar para quem já tinha decidido |
| `expiry_days` | `180` | Validade da decisão, de 1 a 395 dias |
| `position` | `bottom-left` | `bottom-left`, `bottom-right`, `bottom` ou `center` |
| `show_floating_button`, `floating_position` | `true`, `left` | Botão que reabre o cartão e o lado dele |
| `policy_url`, `terms_url` | vazio | Caminho do site ou endereço `http(s)`. Vazio, o link não aparece |
| `consent_mode` | `true` | Envia os sinais do Google Consent Mode v2 ao `dataLayer` |
| `texts` | variáveis do módulo | Treze textos; campo vazio usa o padrão do idioma |
| `categories` | quatro de fábrica | Lista de `id`, `name`, `description` e `required` |

## Referência técnica

O controlador despacha `listar`, `adicionar`, `editar` e `clonar`. AJAX administrativo: `template-load` e `widget-preview`. Na pré-visualização o aviso aparece sempre e nada é gravado no navegador.

A tabela `cookie_consent` tem a estrutura de `galleries`, com `id_cookie_consent` e a coluna `project`.

O widget repete `category-item` por categoria (`[[category#id]]`, `[[category#name]]`, `[[category#description]]`, `[[category#checked]]`, `[[category#disabled]]`), com `category-required` e `category-optional` dentro dele; mantém `policy-link`, `terms-link` e `floating-button` conforme as opções; e resolve `[[text#chave]]` e as globais `[[version]]`, `[[expiry_days]]`, `[[position]]`, `[[floating_position]]`, `[[consent_mode]]`, `[[policy_url]]`, `[[terms_url]]` e `[[preview]]`. Tudo o que vem do painel sai escapado.

A decisão fica no cookie `c2f_consent` do próprio site: `{ v, t, c }`, com a versão, o instante e um booleano por categoria. Sem decisão válida para a versão atual, só as categorias obrigatórias valem.

O controlador público expõe `window.c2fConsent` (`get`, `has`, `open`, `reset`, `onChange`), dispara `c2f:consent` no `document` e marca `<html data-c2f-consent="pending|decided">`. Com `consent_mode`, envia `consent default` com tudo negado na carga e `consent update` com a decisão: `analytics` controla `analytics_storage`; `marketing` controla `ad_storage`, `ad_user_data` e `ad_personalization`; `preferences` controla `functionality_storage` e `personalization_storage`.

Os modelos trazem CSS próprio, com classes `c2f-cc-` e cores em variáveis: o aviso tem a mesma aparência em qualquer página, com ou sem framework CSS.

## Limitações confirmadas

> [!WARNING]
> O widget só bloqueia o que foi marcado com `type="text/plain"` e `data-cookie-category`. Script de terceiro escrito como script comum roda antes de qualquer decisão. O sinal `consent default` sai quando o controlador carrega, no fim da página: uma tag de medição colocada no `<head>` como script comum dispara antes dele.

> [!CAUTION]
> Retirar uma categoria já concedida recarrega a página, porque script que já rodou não se desfaz. Cookies que o terceiro gravou antes não são apagados pelo widget. A decisão fica só no navegador: o módulo não mantém registro de consentimento no servidor.

## Veja também

- [Menus](menus.md)
- [Modelos](admin-templates.md)
