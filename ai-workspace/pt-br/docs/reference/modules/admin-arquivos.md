---
title: "Módulo admin-arquivos"
description: "Navegação, upload e organização dos arquivos físicos do site."
section: reference
module: admin-arquivos
sources:
  - gestor/modulos/admin-arquivos/admin-arquivos.php
  - gestor/modulos/admin-arquivos/admin-arquivos.js
  - gestor/modulos/admin-arquivos/admin-arquivos.json
  - gestor/modulos/admin-arquivos/resources
  - gestor/bibliotecas/arquivo.php
  - gestor/db/migrations/20250723165436_create_arquivos_table.php
  - gestor/db/migrations/20250723165437_create_arquivos_categorias_table.php
  - gestor/db/migrations/20260717120000_create_arquivos_disco_categorias_table.php
verified_at: 45812d2e
---

# Módulo `admin-arquivos`

Gerencia a árvore física de arquivos sob `contents-path`: navega pastas, envia arquivos, cria miniaturas, renomeia, exclui e associa categorias. O cadastro individual em `arquivos` é legado e não alimenta a listagem atual.

## Como usar

Abra `admin-arquivos/` para navegar, filtrar por data/categoria e alternar visualização. Crie ou renomeie pastas, selecione arquivos, copie seus caminhos ou exclua itens. Em `admin-arquivos/adicionar/`, escolha a pasta de destino e envie arquivos por seletor ou arrastar e soltar; categorias podem ser associadas durante o upload ou depois. O painel oferece galeria de prévia e seleção para outros editores. As rotas são iguais nos dois idiomas. `admin-arquivos/emissao-teste/` consta no JSON sem opção de controlador.

## Referência técnica

O controlador despacha `listar-arquivos` e `upload`. AJAX administrativo: `navegar`, `uploadFile`, `miniaturas`, `excluir`, `pasta-criar`, `renomear`, `categorias-arquivo`. Não há widget, template, hooks ou `hooks.api` no JSON. O JS usa jQuery File Upload, pede páginas e miniaturas em lotes e mantém a seleção do seletor de mídia.

`admin_arquivos_ler_pasta()` varre o disco, ignora arquivos ocultos e a pasta `mini`, põe pastas antes dos arquivos, filtra por data/categoria e pagina apenas arquivos. Padrões do JSON: 24 arquivos por página, lotes de cinco miniaturas, 20 MB por upload, largura de miniatura 200. Miniaturas usam GD apenas quando a função de leitura/gravação do formato está disponível. A biblioteca `arquivo` valida caminho relativo, bloqueia extensão perigosa, higieniza o nome e resolve colisões. Excluir uma pasta não vazia requer `recursivo=true`; renomear move miniatura e associações de categoria.

A relação corrente é `arquivos_disco_categorias`: `id_arquivos_disco_categorias`, `caminho` até 1024, `caminho_hash` MD5, `id_categorias`, `data_criacao`, com unicidade `(caminho_hash,id_categorias)`. A tabela antiga `arquivos` contém `id_arquivos`, nome, tipo, caminho, miniatura, permissão e estado; `arquivos_categorias` associa seus IDs, mas o fluxo atual não cria registros individuais nela. O filtro de categorias cruza hashes dos caminhos listados com a tabela nova.

## Limitações confirmadas

> [!WARNING]
> A requisição de upload cujo `dir` não é aceito por `arquivo_caminho_relativo_seguro()` cai na raiz porque usa `?: ''`; confira o destino exibido antes de enviar. A exclusão recursiva apaga arquivos físicos; seu resultado é informado item a item e a rotina usa operações de filesystem com erros suprimidos.

> [!CAUTION]
> O JSON ainda declara `tabela: arquivos`, embora upload/listagem atuais usem o disco e `arquivos_disco_categorias`. Relatórios ou integrações que consultem só `arquivos` não refletem o acervo atual. A miniatura é tentativa de melhor esforço e pode faltar sem invalidar o upload.

## Veja também

- [Categorias](admin-categorias.md)
- [Galerias](galleries.md)
