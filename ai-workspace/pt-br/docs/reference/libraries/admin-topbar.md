---
title: "Biblioteca admin-topbar.php"
description: "Comportamento, persistência e permissões de admin-topbar."
section: reference
order: 100
sources:
  - gestor/bibliotecas/admin-topbar.php
verified_at: 914c7b10
---

# Biblioteca admin-topbar.php


O cabeçalho usa o usuário autenticado no servidor. Favoritos ficam em usuarios_topbar_favoritos; a largura fica em usuarios_preferencias, chave admin_content_width. Sem colunas necessárias, a leitura cai no padrão e a escrita retorna indisponível.

- admin_topbar_escape escapa texto/atributos em UTF-8, substituindo caracteres inválidos.
- admin_topbar_catalogo reúne páginas ativas no idioma atual, filtra permissão de módulo/operação e caminhos seguros; não oferece gatilhos de alteração nem registros dinâmicos.
- admin_topbar_caminho_seguro recusa caminhos vazios, relativos acima da raiz, URLs, controles e marcadores.
- admin_topbar_favoritos lê somente os favoritos do usuário atual e conserva apenas itens presentes no catálogo autorizado.
- admin_topbar_largura_conteudo aceita normal, expanded e full; padrão normal.
- admin_topbar_renderizar devolve vazio sem usuário, preenche o componente, injeta estado JSON escapado e enfileira admin-topbar.js.
- admin_topbar_ajax inicia a segurança da interface, exige sessão e POST e atende adicionar/remover/ordenar favoritos ou salvar largura. Ordenação precisa conter exatamente os favoritos atuais, sem duplicatas. O usuário não vem do cliente; 400/401/403/405/503 e 500 distinguem entradas, sessão, permissão, método, schema e falha de escrita.

Veja [Interface administrativa](../../concepts/admin-interface.md).

## Funções extraídas

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/admin-topbar.php` por `c2f docs:extract` — 7 funções. Não edite dentro deste bloco.

- `admin_topbar_escape($valor)` — [linha 4](../../../../../gestor/bibliotecas/admin-topbar.php#L4)
  REQ-228: cabeçalho administrativo. Nenhum identificador de usuário vem do cliente.
- `admin_topbar_catalogo()` — [linha 8](../../../../../gestor/bibliotecas/admin-topbar.php#L8)
- `admin_topbar_caminho_seguro($caminho)` — [linha 38](../../../../../gestor/bibliotecas/admin-topbar.php#L38)
- `admin_topbar_favoritos($catalogo)` — [linha 43](../../../../../gestor/bibliotecas/admin-topbar.php#L43)
- `admin_topbar_largura_conteudo($usuarioId)` — [linha 55](../../../../../gestor/bibliotecas/admin-topbar.php#L55)
- `admin_topbar_renderizar()` — [linha 64](../../../../../gestor/bibliotecas/admin-topbar.php#L64)
- `admin_topbar_ajax()` — [linha 92](../../../../../gestor/bibliotecas/admin-topbar.php#L92)

<!-- c2f:extract:end -->
