Gerar apenas a parte interna do <body> de um AVISO DE COOKIES. O HTML é um modelo com três peças: o aviso, o cartão de preferências e o botão que reabre o cartão. Um motor preenche textos e categorias; um script público guarda a decisão do visitante e libera o que ela permite.

ESTRUTURA OBRIGATÓRIA:
O contêiner externo tem `data-c2f-cookie-consent`, `id="cookie-consent"` e os atributos de configuração. Preserve-os.

```
<div id="cookie-consent" data-c2f-cookie-consent data-version="[[version]]" data-expiry-days="[[expiry_days]]" data-consent-mode="[[consent_mode]]" data-position="[[position]]" data-floating-position="[[floating_position]]" data-preview="[[preview]]">
    <section data-cc-banner hidden> ...aviso... </section>
    <div data-cc-panel hidden> ...cartão de preferências... </div>
    <!-- floating-button < --><button type="button" data-cc-open hidden>...</button><!-- floating-button > -->
</div>
```

As três peças nascem com o atributo `hidden`: o script mostra a que couber. Não use classe para esconder.

BOTÕES (o script reconhece pelos atributos):
- `data-cc-accept` — aceita todas as categorias
- `data-cc-reject` — recusa as opcionais
- `data-cc-customize` — abre o cartão de preferências
- `data-cc-save` — grava as categorias marcadas no cartão
- `data-cc-close` — fecha o cartão sem gravar
- `data-cc-open` — reabre o cartão

REGRA DE IGUALDADE: recusar tem de ser tão fácil quanto aceitar. Os botões `data-cc-accept` e `data-cc-reject` ficam lado a lado no aviso, com o mesmo tamanho e o mesmo destaque. Nenhuma categoria opcional vem marcada.

CATEGORIAS (bloco repetido uma vez por categoria, dentro do cartão):

```
<!-- category-item < -->
<li>
    <label for="c2f-cc-cat-[[category#id]]">[[category#name]]</label>
    <p>[[category#description]]</p>
    <!-- category-required < --><span>[[text#always_active]]</span><!-- category-required > -->
    <input type="checkbox" id="c2f-cc-cat-[[category#id]]" data-cc-category="[[category#id]]" [[category#checked]] [[category#disabled]]>
</li>
<!-- category-item > -->
```

A caixa `input[data-cc-category]` é obrigatória em cada categoria: é dela que o script lê a escolha. `[[category#checked]]` e `[[category#disabled]]` só são preenchidos na categoria obrigatória.

LINKS (blocos condicionais; saem quando o endereço não foi informado):

```
<!-- policy-link < --><a href="[[policy_url]]">[[text#policy_label]]</a><!-- policy-link > -->
<!-- terms-link < --><a href="[[terms_url]]">[[text#terms_label]]</a><!-- terms-link > -->
```

REGRA DE FORMATAÇÃO DE PLACEHOLDERS:
Use sempre `[[nome]]`, sem `@`. O pipeline converte para `@[[nome]]@` ao salvar. Não escreva texto fixo: todo texto visível vem de `[[text#chave]]`.

TEXTOS DISPONÍVEIS:
`[[text#title]]`, `[[text#message]]`, `[[text#accept_all]]`, `[[text#reject_all]]`, `[[text#customize]]`, `[[text#preferences_title]]`, `[[text#preferences_intro]]`, `[[text#save]]`, `[[text#always_active]]`, `[[text#policy_label]]`, `[[text#terms_label]]`, `[[text#floating_label]]`, `[[text#close]]`.

VARIÁVEIS DA CATEGORIA (só dentro de `category-item`):
`[[category#id]]`, `[[category#name]]`, `[[category#description]]`, `[[category#checked]]`, `[[category#disabled]]`.

ACESSIBILIDADE:
O cartão tem `role="dialog"`, `aria-modal="true"` e um título ligado por `aria-labelledby`. Todo botão só com ícone tem `aria-label`. O foco visível não pode ser removido.

ESTILO:
Escreva CSS próprio, com classes prefixadas (`c2f-cc-`) e cores em variáveis, para o aviso ter a mesma aparência em qualquer página. Não inclua `<script>`.
