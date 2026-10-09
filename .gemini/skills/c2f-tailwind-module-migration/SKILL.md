---
name: c2f-tailwind-module-migration
description: "LEIA ANTES de migrar um módulo do painel do Fomantic-UI para Tailwind (programa da req-219). Se não ler: o JS legado quebra só na página Tailwind, sem erro no PHP, e as responsivas se invertem."
user-invocable: false
---

# Migração de módulo do painel para Tailwind (req-219, DEC-132)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: trocar páginas de um módulo do `layout-administrativo-do-gestor` para o `layout-administrativo-tailwind`, criar variante `-tailwind` de componente ou mexer na biblioteca de controles.
- **SKIP APENAS SE**: o módulo já está todo em Tailwind e a mudança não toca HTML, JS de tela nem componentes.
- **CONSEQUÊNCIA DE IGNORAR**: botão sem ação, aba que não troca, modal aberto no rodapé, `grid-cols-1` vencendo `md:grid-cols-2`, salvar com 403.

## Referência viva

- Implementação completa: `admin-paginas` (commits `7cf44d27` e `435fd83c`, relatório `sdd/implementation/BATCH-227.md`).
- Módulo Tailwind nativo: `perfil-usuario`.
- Biblioteca: cabeçalhos de `gestor/assets/interface/controles.js` e `gestor/bibliotecas/controles.php`.
- Contrato testado: `tests/Unit/PHP/HtmlEditorTailwindReq219Test.php`. Roteiro de navegador modelo: `conn2flow-site/sdd/validation/core/req219-controles-e2e.cjs`.

## Passo a passo por módulo

1. **Metadados** (`<modulo>.json`, pt-br e en), em cada página migrada: `layout: layout-administrativo-tailwind`, `framework_css: tailwindcss`, `tailwind_bundle: true`, `tailwind_dependencies` (os globais do painel — `menu-principal-sistema-tailwind`, `interface-formulario-edicao-tailwind` ou `-inclusao-tailwind`, `interface-carregando/alerta/delecao-modal-tailwind`, `interface-formulario-autorizacao-provisoria-tailwind` — mais toda variante montada em runtime) e `tailwind_sources` para PHP/JS que escrevem utilities. Suba `version` da página e `versao` do módulo.
2. **PHP da opção**: `$_GESTOR['tailwind-page-bundle'] = true;` no começo da função da opção. Sem isso os sidecars dos componentes entram depois do bundle e as responsivas se invertem.
3. **HTML da página**: campos no padrão `c2fc-campo` / `c2fc-campo-rotulo` / `c2fc-campo-entrada`, grade com `grid grid-cols-1 gap-4 md:grid-cols-2`, chave com `c2fc-chave` (checkbox nativo + `c2fc-chave-trilho`), aviso em caixa `rounded-lg border … bg-sky-50`. Mantenha `name`, `id`, placeholders `#…#`, variáveis `@[[…]]@` e marcadores `<!-- x < -->` exatamente. `data-checked="#x#"` vira o atributo direto `#x#` (o PHP troca por `checked`). O selo de status dos metadados sai por `interface_status_selo($status)` (já aplicado em quase todos os módulos); outras peças escritas no PHP do módulo também mudam.
4. **Componentes do módulo** montados em runtime: crie `<id>-tailwind` e escolha com `interface_componente_variante('<id>')` (guarde com `function_exists` em biblioteca que roda sem o `interface`). Registre no JSON do módulo (ou em `resources/<lang>/components.json` se for global) com `framework_css: tailwindcss`.
5. **JS do módulo**: continua funcionando pela ponte (`dropdown`, `checkbox`, `tab`, `modal`, `dimmer`, `popup`, `form`, `transition`). Troque só o que depende de classe visual: seletores `.ui.form`, `.x.button`, `.closest('.field')`, `$.formSubmitNormal` (use `form.requestSubmit()`), `alert/confirm/prompt` (use `c2fControles.dialogo`). Código novo é JavaScript puro.
6. **Modais na variante** nascem com `hidden`, guardam `header/content/actions` e os ganchos `approve`/`cancel`/`deny`; a ponte os leva ao `body`. Cobertura de carregamento: `ui dimmer c2fc-cobertura` (o JS legado liga `.active`).
7. **Listagem** (página raiz): a listagem Tailwind da req-220 já está em `main`. Troque layout/bundle da raiz, ponha `interface-listar-tailwind` nas dependências e `$_GESTOR['tailwind-page-bundle'] = true;` no `case 'listar'`. Filtro próprio no topo vai em `tabela.cabecalho` como variante `-tailwind` (exemplo: `lista-pagina-ou-sistema-tailwind` do `admin-paginas`). O componente tem `id="_gestor-interface-listar"`, então o JS do módulo que liga filtros continua achando a lista.

## Regras da casa

- Utility do Tailwind só no HTML de recurso (é o que o compilador vê). Elemento criado por JS/PHP genérico usa classe `c2fc-*` em `controles.css`.
- Ícones: Lucide (`<i data-lucide="nome" class="size-4">`); dentro de `<template>` não renderiza.
- Tooltips: `data-c2f-dica` (o `data-tooltip` é CSS do Fomantic).
- Não mexa em `controles.js`/`controles.css`/`controles.php`, `interface.php` nem `html-editor.php` fora do seu escopo: peça no relatório ao dono da req-219.
- Comentário em recurso nunca cita placeholder (`#x#`, `@[[x]]@`): a troca pega a primeira ocorrência, dentro do comentário.
- Pipeline: desde a req-219 a minificação é a etapa 1; uma rodada de `project:update-all` basta.
- Validação mínima: teste PHP de contrato (variante x original, metadados, HTML sem `class="ui `), Vitest se mexer em JS, roteiro Playwright no Lab (sem asset do Fomantic no documento principal — `request.frame() === page.mainFrame()` —, salvar sem 403, 390 px sem rolagem, nenhum `.ui.modal` visível solto) e o mesmo módulo Fomantic de controle sem mudança.
- Lab compartilhado: trave com `mkdir C:/Users/otavi/OneDrive/Documentos/GIT/.c2f-lab-lock` (falhou = outro agente usando; espere), faça `git merge origin/main` na sua branch antes do `project:update-all conn2flow-site-local` (rode 2 vezes quando mudar JS) e do roteiro, e libere com `rmdir` ao terminar.
- Pendência humana vai para `conn2flow-site/sdd/PENDENCIAS-HUMANAS.md`, item do roteiro único da req-219.

## Escopo main e inicialização tardia por hooks (BL-028)

Leitura de campos/rótulos da tela fica restrita a main, pois o menu de busca também usa label. Não varra todos os labels do document para formar contexto de IA. Se não houver main, aguarde a montagem ou reporte ausência de contexto; não caia para o documento inteiro.

Script incluído por hook pode executar antes de o editor/componentes serem criados. Faça tentativa inicial após DOM pronto e observe main com MutationObserver (childList/subtree); use debounce/intervalo limitado para novas tentativas, sem loop apertado. Inicialização deve ser idempotente, impedir handlers/botões duplicados e desconectar observador/timer quando não forem mais necessários ou ao desmontar tela. Valide com editor criado depois do hook e abertura repetida.

A orientação histórica de duas publicações para JS não se aplica ao pipeline atual: com lock, atualize timestamps dos arquivos versionados alterados e rode project:update-all uma vez por projeto, sequencialmente. Dois projetos com a mesma origem precisam de uma rodada cada.
