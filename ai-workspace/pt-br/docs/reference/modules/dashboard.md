---
title: "Módulo dashboard"
description: "Página inicial, cartões e barra de edição visual do site."
section: reference
module: dashboard
sources:
  - gestor/modulos/dashboard/dashboard.php
  - gestor/modulos/dashboard/dashboard.js
  - gestor/modulos/dashboard/dashboard.toolbar.js
  - gestor/modulos/dashboard/dashboard.iframe-toolbar.js
  - gestor/modulos/dashboard/dashboard.json
  - gestor/modulos/dashboard/resources
  - gestor/db/migrations/20250723165526_create_layouts_table.php
  - gestor/db/migrations/20250723165530_create_paginas_table.php
  - gestor/bibliotecas/html-editor.php
verified_at: 914c7b10
---

# Módulo dashboard

Reúne a página inicial do Gestor, cartões de módulos permitidos, avisos de atualização, dashboard 3D e a barra de edição sobre páginas do site. A barra roda em iframe, enquanto dashboard.toolbar.js atua na página hospedeira e permite editar conteúdo, layout, widgets e metadados sem abrir o CRUD.

## Como usar

Abra dashboard/ para os cartões e avisos; dashboard-3d/ é a apresentação alternativa. Ao navegar numa página do site com a barra disponível, use dashboard-site-toolbar/ para abrir menus e editar a página, adicionar widgets, consultar backups, restaurar uma versão ou abrir o editor avançado. A rota dashboard-testes/ consta no JSON com opção listar, mas não possui ramo de tratamento no switch principal. As quatro rotas constam em pt-br e en.

## Referência técnica

O switch de página atende inicio, dashboard-3d e dashboard-site-toolbar. AJAX atende os grupos site-toolbar-render/save, widget-types/widgets-list/widget-render, backups/backup-get/backup-restore, templates-load, ia-init/prompt/mode/request/prompt-new/prompt-edit/prompt-del e page-config/page-config-save. O JS da página hospedeira reconstrói marcadores de variáveis/widgets antes do salvamento; o script do iframe coordena menus e mensagens. A configuração site_toolbar.widgets_por_pagina vale 10 por padrão.

Não há tabela própria do dashboard. Cartões consultam módulos/permissões; a barra consulta e grava paginas e layouts no idioma corrente. O save atualiza conteúdo alterado, versão, user_modified, backup/histórico e sitemap; salvar layout preserva o head e exige o marcador de corpo. A configuração de página grava OG, meta descrição, keywords e imagem destacada. O módulo dispara a ação hook_do_action('dashboard','start') na página inicial e aplica o filtro site-toolbar.permissao-pagina sobre o registro da página. O JSON não declara widget público, template ou hooks.api próprios; a barra lista/renderiza widgets de outros módulos.

## Limitações confirmadas

> [!WARNING]
> O filtro de permissão por página retorna true por padrão quando não há handler; projetos precisam registrar a restrição adicional quando usuários só podem editar páginas próprias. Os handlers AJAX exigem a operação editar de admin-paginas, e a barra oculta controles quando o filtro nega a página. A permissão visual da barra não substitui as checagens dos handlers.

> [!CAUTION]
> O save do layout altera um recurso compartilhado por todas as páginas que o usam. dashboard-testes/ é uma rota sem ação própria no controlador. A página inicial também remove registros da página de instalação-sucesso quando encontrada, portanto não é apenas uma tela de leitura.

## Veja também

- [Páginas](admin-paginas.md)
- [Layouts](admin-layouts.md)
- [Publicações](publisher-pages.md)

## Dashboard V3.4: módulos e widgets

A aba **Módulos** reúne os cartões autorizados. P, M e G alteram a densidade: P favorece ícones compactos; M e G usam a capa quando disponível, com ícone como alternativa. A ordem e a densidade são persistidas por usuário. Os cartões permitem abrir o módulo e acessar edição/documentação conforme as ações disponíveis.

Na aba **Widgets e Métricas**, use Opções para editar a grade: escolha o tipo e o registro, arraste pela alça, troque o conteúdo ou remova a instância. A malha tem 12 colunas; largura usa colunas e altura varia de 120 a 960 px em passos de 20 px. O canto inferior direito redimensiona o cartão. O conteúdo ocupa a altura restante depois do cabeçalho; cada instância conserva seus parâmetros e registro.

### Documento isolado e precedência CSS

dashboard_ajax_widget_render valida registro ativo e idioma, lê somente colunas existentes e resolve o template pelo target. Ele entrega HTML, head, CSS, scripts, contrato de tema e URLs dos assets tailwindcss-browser e Lucide. O JS cria iframe srcdoc com sandbox="allow-scripts", origem opaca e permissão de tela cheia.

A ordem no head é: CSS pré-compilado do layout, contrato de tema, folhas parciais/template/registro e CSS de autoria, depois o compilador Tailwind do navegador. A folha completa gerada pelo compilador fecha a camada utilities. Colocar a folha parcial depois faria grid-cols-1 vencer regras responsivas do slide. CSS de autoria sem camada mantém sua precedência. Scripts e ícones inicializam dentro do documento isolado; o estado temporário do widget no servidor é restaurado em finally.

Cada iframe carrega seu próprio compilador: o custo aumenta com a quantidade de widgets. O iframe continua isolado mesmo com tela cheia habilitada. Veja [CSS e Tailwind](../../concepts/css-and-tailwind.md).
