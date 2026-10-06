---
title: "Interface administrativa"
description: "Menus, CRUD, componentes Tailwind e visualização em iframe."
section: concepts
order: 100
sources:
  - gestor/gestor.php
  - gestor/bibliotecas/interface.php
  - gestor/resources/pt-br/layouts/layout-administrativo-tailwind/layout-administrativo-tailwind.html
  - gestor/modulos/admin-layouts/resources/pt-br/components/modal-layout/modal-layout.html
  - gestor/bibliotecas/controles.php
  - gestor/assets/interface/controles.js
  - gestor/assets/interface/controles.css
  - gestor/assets/interface/campo-moeda.js
  - gestor/bibliotecas/interface-listar-tailwind.php
  - gestor/bibliotecas/admin-topbar.php
verified_at: 914c7b10
---

# Interface administrativa

O painel administrativo usa Tailwind CSS v4, ícones Lucide e os controles compartilhados da biblioteca controles. O programa de migração iniciado na REQ-219 e consolidado nas REQ-236 a REQ-240 cobre os 51 módulos administrativos do Core, substituindo as telas Fomantic pelas telas Tailwind. Inclui as famílias de conteúdo, IA, formulários, galerias, arquivos, usuários, perfis, módulos e configuração. Recursos antigos podem continuar declarando Fomantic: a seleção efetiva considera a página e o layout.

## Componentes canônicos

| Classe | Uso |
| --- | --- |
| c2fc-campo | Agrupa rótulo, entrada, ajuda e erro |
| c2fc-campo-entrada | Input e textarea |
| c2fc-campo-selecao | Select nativo, ligado por data-c2f-select |
| c2fc-botao | Botão; variantes primario, perigo e fantasma |
| c2fc-tabela | Tabela; c2fc-tabela-caixa permite rolagem localizada |
| c2fc-cartao | Cartão branco com título e conteúdo |
| c2fc-abas | Abas; data-c2f-aba e data-c2f-painel identificam as seções |

Essas classes têm regras em controles.css e podem ser usadas no HTML produzido pelo PHP. Utilities Tailwind precisam constar das fontes de compilação declaradas do recurso. Textos e rótulos vêm das variáveis do sistema; os ajudantes escapam os valores.

## Seletores e mensagens

O select conserva o elemento nativo para envio do formulário. Oferece busca, seleção múltipla, teclado e consulta AJAX quando declarada. O painel flutuante evita corte por contêineres; observarSelects inicializa elementos inseridos depois da carga e refaz controles clonados. A seleção não propaga o clique para rótulos externos. Não clone uma instância esperando que seus eventos sejam copiados.

c2fControles.formatado aceita somente as tags b, strong, i, em, u, br e code, sem atributos para mensagens; alerta com a opção formatado usa esse caminho. Ele não é um renderizador de HTML arbitrário.

## Máscaras

data-c2f-mascara="moeda" ou c2fc-campo-moeda exibe dinheiro no formato pt-BR e envia decimal sem símbolo no evento formdata. data-c2f-moeda define a moeda (BRL por padrão); data-c2f-moeda-campo aponta um seletor de moeda. data-c2f-mascara="percentual" limita o valor a 0–100 e duas casas, mostra vírgula e transporta ponto. controles_incluir também carrega campo-moeda.js em telas sem formulário gerado pelo interface.

## Listagens e navegação

A listagem Tailwind tem busca, ordenação, paginação e quantidade de 10, 25, 50 ou 100 registros, com estado por usuário. Dados comuns são texto; HTML é reservado às colunas com formatador declarado pelo servidor. Status e exclusão passam pelo fluxo AJAX autorizado da interface.

A barra superior mostra o usuário, favoritos e largura do conteúdo (normal, expanded ou full). O catálogo de favoritos filtra permissões e caminhos seguros. A lateral destaca o módulo atual. No Dashboard, favoritos e cartões são preferências pessoais.

Veja [Dashboard](../reference/modules/dashboard.md), [Controles](../reference/libraries/controles.md) e [CSS e Tailwind](css-and-tailwind.md).
