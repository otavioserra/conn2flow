# BATCH-119 — Painel de Perfil em Tailwind, abas e gestão de sessões ativas

Origem: [req-118.md](../human-requests/req-118.md)
Validação: [VALIDATION-CHECKLIST.md#batch-119](../validation/VALIDATION-CHECKLIST.md)
Status: `in-progress` (aberto em 2026-08-17)

---

## Levantamento antes de editar (o que decidiu o desenho)

Três fatos medidos no repositório mudaram o desenho em relação ao que o intake sugere:

1. **A página do perfil não é uma página autônoma — ela é o `#form-page#` do componente de core
   `interface-formulario-edicao`.** `interface_editar_finalizar()` (`bibliotecas/interface.php:4166`)
   envolve o HTML do módulo num `<form class="ui form interfaceFormPadrao">` e acrescenta o botão de
   salvar, os metadados e o histórico. Esse componente é COMPARTILHADO por todos os módulos
   administrativos: reescrevê-lo em Tailwind está fora do escopo e violaria a regra de ouro do
   baseline. Logo, o alvo do lote é o conteúdo da página, e o `<form>`/botão de salvar do core
   permanecem — registrado como limite explícito abaixo.

2. **Utilities do Tailwind perdem do Fomantic dentro do `.ui.form`.** `.ui.form input[type="text"]`
   tem especificidade (0,3,1) contra (0,1,0) de `.px-3` — ordem na cascata não resolve. O CSS
   pré-compilado da página entra DEPOIS do CDN do Fomantic (`gestor_pagina_css()`), e mesmo assim
   perde. Medido com o CLI 4.3.0: o modificador `!` do v4 (sufixo, `px-3!`) emite `!important` e
   vence. Ele é usado **apenas nos controles de formulário**, que é onde o conflito existe.

3. **Página com `framework_css = tailwindcss` sob layout Fomantic é segura.** Duas verificações:
   (a) `gestor_pagina_css()` inclui o Fomantic quando o LAYOUT o declara — e
   `layout-administrativo-do-gestor` declara `fomantic-ui` —, então nada é removido do painel;
   (b) recurso que não é layout nem bundle compila com `@reference` + `utilities` (`source(none)`),
   ou seja, **sem Preflight**, e o v4.3 emite `var(--spacing,.25rem)` **com fallback inline** — o
   CSS funciona sem `@theme` no layout. Verificado compilando um probe real e conferindo o
   `.precompiled.css` já existente de `dashboard-site-toolbar-menu-item`.

Essa é a primeira página administrativa do core em Tailwind; é a dívida "decisão de arquitetura
sobre Tailwind no painel administrativo" registrada no review de 2026-08-15.

---

## M1 — Painel em abas (Tailwind)

- `resources/{pt-br,en}/pages/perfil-usuario/perfil-usuario.html` reescritos: container
  `#perfil-usuario-painel`, navegação `role="tablist"` com três abas e três painéis.
- Blocos de template preservados (contrato do PHP): `botoes`, `nome-campos`, `email-campos`,
  `usuario-campos`, `senha-campos`, `seguranca-campos`. Bloco novo: `sessoes-campos`.
- **A trava de Área Restrita é preservada**: os botões de alteração continuam sendo links com
  `?mudar-X=sim`, e `usuario_autorizacao_provisoria()` continua interceptando antes da gravação.
- Aba ativa resolvida por: querystring (`?mudar-*` → Dados, `?configurar-seguranca` → Segurança,
  `?sessoes` → Sessões) → hash da URL → `localStorage`. Sem recarregamento entre abas.
- Medidor de força de senha em tempo real dentro do bloco `senha-campos`.

## M2 — Sessões ativas e dispositivos

Biblioteca core `bibliotecas/usuario.php` — duas funções **puras** sustentam as três de contrato,
porque é a parte que decide o que o usuário lê e é a única testável sem banco:

- `usuario_user_agent_analisar(string $userAgent): array` — navegador, sistema e dispositivo.
- `usuario_sessao_formatar(array $registro, ?string $tokenAtual): array` — normaliza a linha de
  `usuarios_tokens` e marca `atual`.
- `usuario_sessoes_listar(int $id_usuario, ?string $token_atual_pubID = null): array`
- `usuario_sessao_revogar(string $pubID, int $id_usuario): bool`
- `usuario_sessoes_revogar_outras(string $token_atual_pubID, int $id_usuario): bool`

As duas revogações **exigem `id_usuarios` no WHERE**: o `pubID` chega do cliente e sozinho
permitiria derrubar a sessão de outro usuário.

Endpoints AJAX novos no módulo: `sessoes-revogar` e `sessoes-revogar-outras`.

## M3 — Contrato de recursos e pré-compilação

- `framework_css: "tailwindcss"` na página `perfil-usuario` (pt-br e en), versão bumpada e
  checksums esvaziados para o pipeline recalcular.
- `.precompiled.css` gerado pelo compilador de recursos nos dois idiomas.

---

## Limites de escopo (declarados)

- O `<form class="ui form">`, o botão de salvar, os metadados e o histórico vêm do componente de
  core `interface-formulario-edicao` e permanecem em Fomantic. O critério de aceite 1 é atendido no
  arquivo da página (`perfil-usuario.html`), que fica 100% em utilities Tailwind.
- O bloco jQuery de QR Code e de alternância de método 2FA **não** é removido: os mesmos ids
  (`#seg-2fa-qr`, `#seg-2fa-metodo`) são emitidos pelas telas de login 2FA
  (`perfil-usuario.php:1552` e `:2193`), que não fazem parte deste lote.
- As telas de autenticação/cadastro/recuperação são o req-120 (BATCH-121) e ficam de fora.
