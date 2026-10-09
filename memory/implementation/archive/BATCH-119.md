# BATCH-119 — Layout administrativo Tailwind, menu interativo e painel de perfil com sessões

Origem: [req-118.md](../../human-requests/archive/req-118.md)
Validação: [VALIDATION-CHECKLIST.md#batch-119](../../validation/VALIDATION-CHECKLIST.md)
Status: `implemented-pending-homologation` (implementado em 2026-08-18)

O intake foi ampliado pelo Chefe em 2026-08-18: além do painel de perfil, o lote passou a exigir um
layout administrativo nativo em Tailwind e o menu reconstruído. A decisão de **não** carregar o
Fomantic no layout novo foi tomada pelo Chefe na mesma sessão, com a instrução de adaptar
`interface.php` para resolver por `framework_css` (a linha 3.0 será 100% Tailwind).

---

## O bloqueio que definiu o desenho

`interface.js` chama `$.fn.modal` do Fomantic em **16 pontos, sem nenhuma guarda** — e entre eles
está o modal de Área Restrita, que é a trava de credenciais do perfil. Além disso,
`gestor_pagina_css()` e `gestor_pagina_extra_head_e_javascript()` usavam a **mesma** condição para o
CSS e o JS do Fomantic: um layout `tailwindcss` perde os dois de uma vez.

Sem tratar isso, mover a página do perfil para um layout Tailwind entregaria uma tela onde o
formulário não valida e a trava de segurança fica invisível.

Duas medições sustentaram o resto:

1. **O contrato de validação é portável.** `interface_formulario_validacao()` emite regras no formato
   do validador do Fomantic, e o inventário inteiro do core usa só 6 tipos (`notEmpty`,
   `minLength[n]`, `maxLength[n]`, `email`, `match[campo]`, `regExp[/…/]`). O runtime novo interpreta
   esse mesmo dicionário — **nenhuma função PHP de validação mudou**.
2. **Utilities perdem do Fomantic no modo híbrido.** `.ui.form input[type="text"]` tem
   especificidade (0,3,1) contra (0,1,0) de `.px-3`; ordem na cascata não resolve. Medido com o CLI
   4.3.0: o modificador `!` do v4 (sufixo) emite `!important` e vence. Ele ficou nos controles de
   formulário porque o resolver admite o modo `hibrido` (página Tailwind sob layout Fomantic), onde
   ainda é o que salva os campos.

---

## M1 — Resolução de framework por layout **+** página

`gestor_framework_css_resolver()` (pura, em `bibliotecas/gestor.php`) devolve `fomantic`, `tailwind`
e `modo` (`fomantic-ui` | `tailwindcss` | `hibrido`). Uma página final é layout + página, e as duas
carregam a coluna `framework_css`: decidir por um lado só deixaria a tela sem CSS ou sem runtime.

As regras são exatamente as que `gestor_pagina_css()` já aplicava — agora em um lugar só, e o bloco
duplicado nos dois pontos de `gestor.php` foi eliminado.

## M2 — Variantes de componente e runtime da interface

- `interface_componente_variante()` / `interface_componente_canonico()`: cada componente ganha um
  irmão `<id>-tailwind`, escolhido em runtime. Em modo `hibrido` a variante **não** é aplicada — com
  Fomantic na página, o componente legado é o único com estilo garantido.
- Os `switch` que decidem quais variáveis cada componente recebe continuam escritos sobre o id
  canônico: a variante muda o HTML, nunca o contrato de dados.
- `interface_assets_incluir()` centraliza a inclusão do runtime (as 9 cópias do mesmo par de linhas
  em `interface.php` viraram uma chamada) e escolhe `interface-tailwind.js` em Tailwind puro.
- Novos componentes (pt-br e en): `interface-formulario-edicao-tailwind`,
  `interface-carregando-modal-tailwind`, `interface-alerta-modal-tailwind`,
  `interface-delecao-modal-tailwind`, `interface-formulario-autorizacao-provisoria-tailwind`.
- **Nenhum componente legado foi alterado** — é isso que mantém os módulos não migrados intactos.

`interface-tailwind.js` (vanilla) cobre: loader com contador (duas chamadas AJAX simultâneas não
apagam o loader na primeira resposta), alerta, deleção, validação sobre o dicionário do Fomantic, a
query string do momento do envio, e a Área Restrita — que abre sozinha e **não** fecha por Esc nem
por clique no fundo, porque fechar sem escolher equivaleria a burlar a trava.

## M3 — Layout `layout-administrativo-tailwind` e menu

- Layout novo (pt-br e en) com HTML + CSS autoral. O legado `layout-administrativo-do-gestor` não
  foi tocado.
- **Decisão registrada**: o layout carrega apenas `components/icon.min.css` do Fomantic — a folha de
  ÍCONES, não o framework. A coluna `modulos.icone` guarda nomes de ícone Fomantic para todos os
  módulos instalados; trocar essa fonte exigiria reescrever o cadastro e quebraria plugins de
  terceiros.
- `gestor_pagina_menu()` escolhe `menu-principal-sistema-tailwind` e injeta `admin-tailwind.js`. A
  árvore de permissões, os grupos e as células são as **mesmas** — nenhuma regra de acesso foi
  duplicada. Só o estado ativo mudou de vocabulário (`.active` é classe do Fomantic).
- `admin-tailwind.js` (vanilla): abrir/fechar com comportamento próprio em mobile (overlay, sem
  empurrar o conteúdo — empurrar poria a página em rolagem horizontal), redimensionamento por
  arraste com limites 220–450px e persistência, duplo clique restaurando o padrão, filtro tolerante
  a acento e caixa, e a navegação por teclado do BATCH-105 (sem ciclo no fim, retorno ao campo).

## M4 — Painel do perfil e sessões

- Página em abas (Dados / Segurança & 2FA / Sessões & Dispositivos), 100% utilities.
- Aba resolvida por querystring → hash → `localStorage`. A querystring vence: quem clicou em
  "Alterar e-mail" tem de cair no formulário, mesmo vindo de um link ancorado em outra aba.
- Medidor de força de senha com o piso do backend (12 caracteres): aprovar 8 seria mentir para o
  usuário, que só descobriria no POST.
- **A trava de Área Restrita foi preservada**: os botões de alteração continuam links com
  `?mudar-X=sim` e `usuario_autorizacao_provisoria()` continua interceptando antes da gravação.
- A aba Segurança agora existe sempre, mas o QR Code e a chave manual continuam materializados só
  sob `?configurar-seguranca=sim` — exibir o segredo TOTP em toda carga do perfil ampliaria a
  superfície aprovada no req-030 sem que ninguém tivesse pedido. A biblioteca do QR também deixou de
  ser carregada nas cargas que não desenham QR.
- Core (`bibliotecas/usuario.php`): `usuario_user_agent_analisar()` e `usuario_sessao_formatar()`
  (puras) + `usuario_sessoes_listar()`, `usuario_sessao_revogar()` e
  `usuario_sessoes_revogar_outras()`. As duas revogações **exigem `id_usuarios` no WHERE** — o
  `pubID` chega do cliente e sozinho permitiria derrubar a sessão de outro usuário; e revogar as
  outras exige o token atual, senão vira logout global disfarçado.
- A sessão atual não tem botão de revogar: derrubaria o usuário no meio da operação.
- Endpoints `sessoes-revogar` e `sessoes-revogar-outras`.

## M5 — Pré-compilação

- Página `perfil-usuario` declara `framework_css`, `tailwind_bundle` e `tailwind_dependencies` (o
  layout entra automaticamente; os 6 componentes são montados em runtime pelo PHP e não têm sidecar
  na cascata). Também declara `tailwind_sources` apontando para `perfil-usuario.php`, porque as abas
  de Segurança e Sessões são montadas em PHP.
- O bundle não foi escolha estética: **sem ele o compilador acusou o finding F3 do review de
  2026-08-15** — o layout emite `display` sob variante responsiva (`lg:block`, `lg:hidden`,
  `sm:inline`) e a concatenação de sidecars pode inverter desktop/mobile. Com o bundle o aviso
  desaparece e a ordem global das utilities fica preservada.

---

## Limites de escopo (declarados)

- `interface.php` foi adaptada apenas nos caminhos que este lote exercita (edição, componentes de
  modal, runtime e autorização provisória), conforme instrução do Chefe. Listagem, inclusão,
  visualização e configuração seguem no componente legado — elas continuam servidas pelo layout
  legado, então nada regride.
- O bloco jQuery de QR Code e alternância de método 2FA permanece: os mesmos ids são emitidos pelas
  telas de login 2FA (`perfil-usuario.php:1552` e `:2193`), que são do req-120.
- Módulos ainda não migrados continuam no `layout-administrativo-do-gestor`, intocado.

## Validação

- `php -l` OK (gestor.php, bibliotecas/gestor.php, bibliotecas/interface.php, bibliotecas/usuario.php,
  perfil-usuario.php e os dois testes novos); `node --check` OK nos 3 arquivos JS.
- Compilador de recursos: **191 encontrados, 16 compilados, 0 erros**; os 2 avisos de F3 sumiram com
  o bundle e sobraram os 4 pré-existentes do inventário calibrado no BATCH-117.
- `composer test` → **353/353** (novos `PerfilUsuarioSessoesTest` 24/24 e `FrameworkCssResolverTest`
  14/14).
- `npx vitest run` → **309/309** (novos `perfil-usuario.painel.test.js` 25/25,
  `admin-tailwind.test.js` 24/24 e `interface-tailwind.test.js` 32/32).
- Cache-bust: tokens determinísticos regenerados (`global`, `interface`, `system` e o
  `asset_version` do módulo).
- **Pendente**: deploy `Update => Core` (layout, componentes e página vêm do banco) e homologação
  runtime com o operador.
