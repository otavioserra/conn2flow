# BL-017 — Widget de formulários vaza os blocos-fragmento do template no HTML renderizado

- **Tipo**: Bug / UI / Formulários
- **Status**: PROMOTED (promovido para [req-173.md](../human-requests/req-173.md), BATCH-178)
- **Severidade sugerida**: BAIXA (não quebra o formulário, mas deixa marcador cru na página pública)
- **Origem**: Achado do BATCH-047 do `conn2flow-site` (REQ-054), 2026-09-22
- **Componentes**: `gestor/modulos/forms/forms.widget.php` (`forms_render`, `forms_widget_render_inline`),
  templates com `target=forms` em `gestor/modulos/forms/resources/*/templates/`

## Contexto observado

1. Um template de formulário termina com blocos-fragmento que o widget consulta por
   `modelo_tag_val()`: `<!-- option-choice < -->…<!-- option-choice > -->`,
   `<!-- option-select < -->…` e `<!-- password-toggle < -->…`.
2. O widget lê esses blocos da STRING do template, mas nunca os remove do HTML que devolve. O que
   está entre os marcadores é markup normal (`<label>`, `<div>`, `<option>`), então o navegador o
   desenha no fim do formulário.
3. Efeito visível na página pública: marcadores crus como `@[[option#type]]@`, `@[[option#value]]@`
   e `@[[password#input]]@` aparecem no HTML renderizado do checkout.
4. Os templates que acompanham o core (`forms-registro-usuario`, `forms-contato-basico` e os demais)
   têm a mesma estrutura, então o problema não é de um template específico.

## Contorno aplicado no conn2flow-site

O template do checkout (`conn2flow-checkout-assinatura`) embrulha os fragmentos em
`<template data-forms-fragments>…</template>`: o widget continua lendo (a leitura é na string) e o
navegador não desenha o conteúdo de um `<template>`.

## Proposta

1. `forms_render()` remove os blocos-fragmento conhecidos do HTML final (`modelo_tag_del()` para
   `option-choice`, `option-select` e `password-toggle`) depois de usá-los.
2. Como rede de segurança, limpar do HTML devolvido qualquer marcador `@[[…]]@` remanescente.
3. Teste de contrato: renderizar um formulário com select, checkbox e senha e conferir que o HTML
   não contém `<!-- option-` nem `@[[`.

## Critérios de aceite (rascunho)

- Formulário renderizado por qualquer template `target=forms` não exibe fragmento nem marcador cru.
- Selects, rádios, checkboxes e o botão de exibir senha continuam funcionando.
