---
title: "Módulo publisher"
description: "Definições de publicação: templates, prefixos de caminho e esquema dos campos preenchidos nas publicações."
section: reference
module: publisher
sources:
  - gestor/modulos/publisher/publisher.php
  - gestor/modulos/publisher/publisher.js
  - gestor/modulos/publisher/publisher.json
  - gestor/modulos/publisher/resources
  - gestor/db/migrations/20260106180000_create_publisher_table.php
  - gestor/db/migrations/20260106180001_add_path_prefix_to_publisher_table.php
  - gestor/db/data/ModulosData.json
  - gestor/db/data/ModulosOperacoesData.json
verified_at: 33ce53d9
---

# Módulo `publisher`

`publisher` define tipos de publicação: nome, prefixo de caminho, template e campos personalizados. Essas definições são utilizadas pelo módulo `publisher-pages` para preencher publicações. O módulo aparece como **Publicador Definições**, no grupo de administração do Gestor.

## Como usar

1. Abra `publisher/` e escolha adicionar (`publisher/adicionar/`). As rotas declaradas são iguais nos recursos pt-br e en, relativas à raiz do idioma.
2. Informe `name`, revise `path_prefix` e selecione um template ativo do idioma atual com `target=publisher`. O navegador formata o prefixo em minúsculas, sem acentos, com hífens e barra final; editar o nome também pode recalcular esse prefixo.
3. Carregue os campos do template. Adicione todos de uma vez ou crie campos individualmente, defina rótulo, descrição, tipo e obrigatoriedade. Vincule cada campo à variável correspondente do template; o vínculo adota seu identificador e tipo. Use as setas para ordenar.
4. Salve. A edição abre em `publisher/editar/?id=<slug>`. `publisher/clonar/?id=<slug>` reaproveita prefixo e esquema, mas requer nome e seleção de template para o novo registro.
5. A listagem oferece editar, clonar, ativar/desativar e excluir. Para criar o conteúdo de uma publicação, use `publisher-pages`.

Templates já usados por publicadores não excluídos aparecem desabilitados no seletor; na edição, o template do próprio registro continua selecionável. Para reutilizar o desenho, clone o template no módulo `admin-templates`.

## Referência técnica

### Despacho e operações

`publisher_start()` inclui as bibliotecas declaradas (`interface`, `html`). No fluxo normal, configura a listagem em `publisher_interfaces_padroes()`, inicia a interface e despacha `adicionar`, `editar` ou `clonar`; a interface compartilhada finaliza a operação. A listagem configura as ações `status` e `excluir` para essa interface.

O único caso AJAX próprio é `template-load`, entre `interface_ajax_iniciar()` e `interface_ajax_finalizar()`. Recebe `params.template_id` e `params.fields_schema`, consulta template ativo do idioma atual e alvo `publisher`, e devolve `status=Ok`, `modelo`, `campos` e `publisherFields`. Template inexistente produz `status=Erro`. A extração reconhece `@[[publisher#tipo#id]]@` e elimina duplicatas pelo par tipo/id, preservando a primeira ocorrência.

O JSON não declara `hooks` nem `hooks.api`; não há widget público próprio neste diretório. `ModulosOperacoesData.json` não semeia operação específica para `publisher`. A disponibilidade administrativa é a do módulo e da interface comum; os botões do seletor não constituem uma permissão adicional.

### Tabela `publisher`

| Colunas | Contrato das migrations |
|---|---|
| `id_publisher` | Chave numérica gerada |
| `id`, `language` | Slug até 100 caracteres, idioma até 10 (padrão `pt-br`); índice único conjunto |
| `name` | String obrigatória, até 255 |
| `path_prefix`, `template_id` | Strings opcionais, até 255 |
| `fields_schema` | JSON opcional |
| `id_usuarios`, `plugin` | Autor (inteiro, padrão 1, aceita nulo) e origem de plugin (string até 255) |
| `status`, `versao` | Caractere, padrão `A`; inteiro, padrão 1 |
| `data_criacao`, `data_modificacao` | Datas; a segunda tem atualização automática |
| `user_modified`, `system_updated` | Indicadores inteiros, padrão 0 |

A criação gera slug a partir do nome no idioma atual, status `A` e versão 1. A edição compara os valores anteriores, registra histórico e backups de prefixo/template quando aplicáveis, incrementa versão e marca `user_modified=1`. Alterar o nome pode alterar o slug, salvo presença de `_gestor-nao-alterar-id`.

### `fields_schema` e templates

O navegador salva duas listas, na ordem dos campos da tela:

```json
{
  "fields": [
    {"id":"titulo","label":"Título","description":"Título principal","type":"text","mandatory":true}
  ],
  "template_map": [
    {"id":"titulo","variable":"[[publisher#text#titulo]]","found_template":true,"linked_template":true}
  ]
}
```

`fields` contém campos com id e rótulo preenchidos. Os tipos disponíveis na tela são `text`, `textarea`, `html` e `image`. `template_map` também inclui variáveis do template ainda não vinculadas. O vínculo é recuperado de `linked_template`, usando o mesmo id; `template_field_id` é estado da interface, não uma propriedade persistida por esse serializador.

Ao gravar, o PHP converte marcadores `[[...]]` em `@[[...]]@`; ao editar/clonar, faz o inverso. `publisher_normalize_array()` ordena recursivamente as chaves para comparar JSON na edição, mas mantém a ordem efetiva dos elementos das listas.

Os templates fornecidos são `noticias-simples` e `noticias-imagem-destaque`, ambos Tailwind e alvo `publisher`. O primeiro usa texto, descrição e HTML; o segundo acrescenta imagem e texto alternativo. Os ids das variáveis diferem entre os idiomas (`titulo`/`title`, `conteudo`/`content`, por exemplo). O modo de IA `publisher` orienta a geração do corpo HTML e dos marcadores a partir dos campos.

## Limitações e divergências do legado

> [!WARNING]
> A exclusividade de template é aplicada ao seletor, sem índice único em `template_id` nem verificação equivalente no INSERT/UPDATE deste controlador. Ela não é uma garantia de integridade contra requisições construídas fora da tela.

> [!WARNING]
> Renomear o slug de uma definição não atualiza referências em outras tabelas neste controlador. Preserve o identificador quando existirem publicações ou widgets vinculados.

> [!CAUTION]
> O esquema recebido não passa por validação estrutural completa. `template-load` pressupõe `fields` como lista; JSON válido com formato inesperado pode provocar avisos ou erro de tipo. O esquema é inserido em um `<script>` por `json_encode()` sem `JSON_HEX_TAG`; não trate esse editor como entrada segura para autores não confiáveis.

A documentação antiga em português inventava tabelas `publicador_tipos`/`publicador_taxonomias` e workflows de aprovação. O controlador usa `publisher`; não implementa esses workflows nem o dashboard de rascunhos descrito no manual antigo. Os templates embarcados são os dois modelos de notícias acima.

## Veja também

- [Menus](menus.md)
- [Biblioteca banco](../libraries/banco.md)
- [Contrato de documentação](../../guides/documentation.md)
