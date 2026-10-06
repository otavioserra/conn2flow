---
title: "Administração do site em Tailwind"
description: "Produtos, cupons, assinaturas, fretes, apresentações e operações no painel do site."
section: guides
order: 96
verified_at: 914c7b10
---

# Administração do site em Tailwind

Os módulos do projeto Conn2Flow Site usam o mesmo painel Tailwind v4 do Core: campos, selects, abas, cartões, tabelas e dicas compartilham comportamento. Abra o módulo pela lateral ou pelo Dashboard. A disponibilidade depende das permissões do perfil; um guia não concede acesso às ações.

## Produtos e catálogo

Em Products, edite a descrição com Quill, escolha a imagem pelo seletor de arquivos e revise preço, promoção, período e fretes. Os valores monetários têm máscara na tela e são enviados como decimal. O botão de mídia abre o seletor administrativo; confira o arquivo selecionado antes de salvar. A página do produto tem edição visual e link público. As ações de desativar e excluir têm dicas próprias; exclusão pede confirmação.

Stripe Products organiza título e ações antes da caixa de sincronização. Produtos vinculados ao Stripe herdam os dados protegidos da origem; os campos bloqueados não devem ser usados para modificar preço de origem. Products Index mostra modelos em cartões compactos, prévia do widget e botão de copiar. Product Types usa os mesmos campos, seletores e checkboxes do painel. Product Reviews mantém selects e botões padronizados.

## Cupons e afiliados

Escolha o tipo de desconto antes de preencher o valor. Cupons mantém dois campos alternados: percentual de 0 a 100, com até duas casas, e valor fixo com máscara monetária na moeda selecionada. Somente o campo ativo envia discount_value; o outro fica oculto e desabilitado. Valor mínimo também usa máscara monetária. Onde Vale permite escolher produtos ou assinaturas com seleção múltipla; duração controla os campos específicos de recorrência.

Afiliados usam campos monetários e percentuais e os controles compartilhados. A máscara ajuda a digitar; limites e autorização continuam sendo verificados pelo servidor. Não interprete um valor formatado como confirmação de pagamento ou repasse.

## Fretes, pedidos e relatórios

Shipping Methods reúne a lista e o formulário de método: nome, tipo e configuração JSON. Tipos disponíveis na tela: fixed_table, free_over, pickup e melhor_envio. Revise o JSON exigido pelo método antes de salvar. Editar e excluir têm botões com dicas; operações de exclusão usam formulário e confirmação.

Orders usa selects compartilhados no formulário do comprador. Sales Reports usa seletor de tipo e filtros padronizados; revise os filtros antes de gerar o relatório. Analytics Manager usa o mesmo select com busca na página principal e no pipeline. A troca visual de controles não muda as regras de cálculo ou integração externa.

## Assinaturas e gateways

Subscriptions mantém seletores flutuantes, ações de edição com dicas e modal de logomarca. Planos e estágios de serviço usam campos padronizados e botões de copiar compactos. Gateways mostra a escolha padrão em destaque; configurar um gateway continua exigindo seus dados de integração. A migração visual não prova uma cobrança real: use o ambiente de testes do provedor para essa operação.

## Apresentações e Dashboard

Crie ou edite uma apresentação, use Editar Visual e revise os slides. Para exibi-la no Dashboard, abra Widgets e Métricas, escolha presentations e o registro ativo no idioma atual. Setas, pontos, contador e progresso dependem das opções da apresentação e da existência de mais de um slide. Tela cheia depende da opção correspondente.

O widget público carrega CSS autoral e head do template, depois os estilos do registro. A folha pré-compilada parcial do template não é acrescentada depois da folha da página: isso preserva o desenho responsivo dos slides. No Dashboard, o compilador Tailwind do iframe gera a folha completa por último. A apresentação incorporada ocupa a altura útil do cartão. Veja [Dashboard](../reference/modules/dashboard.md).

## Operações, conteúdo e documentação

Host Manager conserva a barra comum de navegação nas sete telas administrativas. O menu de ações fecha ao clicar fora; títulos e ícones seguem o painel. Social Connections, Social Apps, catálogo 3D e módulos distribuídos usam a estrutura Tailwind, com operações e permissões próprias. O Workspace Social do editor depende dos módulos do projeto que o fornecem.

O módulo Documentation mostra o estado da última geração. Os guias privados em documentation/<módulo>/ enumeram ações declaradas no manifesto administrativo e filtram páginas de edição, clonagem e visualização. O acervo público docs/ é gerado a partir do Markdown bilíngue; editar seus recursos gerados não é uma forma persistente de atualizar a documentação.

## Fontes verificadas

Conteúdo conferido nos controladores, scripts, widgets, manifestos e páginas de autoria dos módulos products, stripe-products, products-index, product-types, product-reviews, coupons, affiliates, shipping-methods, orders, sales-reports, analytics-manager, subscriptions, subscriptions-plans, subscriptions-service-stages, gateways-pagamentos, presentations, host-manager e documentation do Conn2Flow Site, consolidação a7192e75. Os contratos compartilhados estão em [Interface administrativa](../concepts/admin-interface.md) e [Controles](../reference/libraries/controles.md).
