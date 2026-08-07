# BL-025 — Arquitetura C2FI18n sobre os recursos multilíngues existentes

- **Tipo:** Architecture/I18n/Maintainability/Security
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** runtime PHP/JS, recursos globais e de módulos, tooling, instalador, APIs e overlays privados
- **Relacionados:** BL-012, BL-013, BL-016, BL-017, BL-021, BL-023 e BL-026

## Diretriz

O sistema não precisa de um quarto mecanismo de tradução. Deve criar uma API única e modular, provisoriamente chamada `C2FI18n`, que use e fortaleça a infraestrutura de recursos já existente.

## Diagnóstico

O core já possui uma base relevante:

- `gestor/resources/pt-br` e `gestor/resources/en`, com 160 arquivos em cada idioma;
- 33 manifestos de módulo com blocos `resources` para `pt-br` e `en`;
- aproximadamente 1.397 chamadas a `gestor_variaveis()`/renderização de variáveis;
- pipeline que consolida manifestos/recursos em `VariaveisData.json`, demais `*Data.json` e `schema-metadata.json`;
- sincronização que preserva customizações por `user_modified`;
- dicionários JSON separados para ferramentas CLI/atualização via `bibliotecas/lang.php`;
- tradutor próprio do Gestor Instalador, necessário antes de o banco/core estarem disponíveis.

Hoje esses caminhos têm contratos distintos, globals, fallbacks e formatos de placeholder diferentes. Há ainda textos literais como fallback em PHP/JS. A proposta é unificar a API e o schema, não obrigar todos os contextos a usar o mesmo meio físico de armazenamento.

## Arquitetura alvo

Dividir em classes pequenas, seguindo o BL-012:

- `Locale` e `LocaleResolver` — validação/negociação do idioma;
- `Translator` — resolução de chave, parâmetros e pluralização;
- `Catalog`/`CatalogLoader` — contrato de leitura;
- `DatabaseCatalogLoader` — recursos publicados no banco;
- `JsonCatalogLoader` — instalador, CLI, build e fallback de filesystem;
- `CompositeCatalog` — precedência entre global, módulo, plugin/projeto e fallback;
- `MissingTranslationReporter` — diagnóstico sem quebrar produção;
- `FrontendCatalogBuilder` — payload mínimo e versionado para JavaScript;
- `ResourceValidator` — schema, paridade e placeholders no CI.

O instalador recebe as mesmas classes/contratos em seu artefato autônomo, com `JsonCatalogLoader`; não depende do banco nem do autoload do Gestor ainda não instalado.

## Namespaces e ownership

- chaves técnicas estáveis em inglês;
- namespace global apenas para conteúdo realmente compartilhado;
- cada módulo possui seu namespace e seu próprio catálogo;
- plugins/projetos podem sobrescrever somente chaves explicitamente extensíveis;
- evitar chaves genéricas como `title`, `error` ou `save` sem contexto;
- padrão proposto: `module.context.message`, por exemplo `admin_files.upload.file_too_large`;
- IDs antigos com hífen podem receber aliases gerados durante a migração, com telemetria e prazo de remoção.

## Contrato de tradução

1. locale solicitado → locale padrão configurado → inglês de referência → chave com diagnóstico;
2. placeholders nomeados, com igualdade de nomes e tipos em todos os idiomas;
3. pluralização e seleção por quantidade definidas pelo contrato, sem concatenação de sufixos no call-site;
4. datas, números e moedas formatados por locale em serviço próprio;
5. valores são texto simples e escapados por padrão;
6. tradução com HTML é tipo explícito, sanitizado/revisado e não aceita parâmetros sem escape;
7. logs técnicos, nomes de exceção e códigos de erro ficam em inglês e não são traduzidos;
8. mensagens ao usuário são resolvidas na fronteira adequada.

## APIs e AJAX

Endpoints não devem retornar uma frase localizada como único identificador do erro. O contrato deve fornecer:

```json
{
  "status": "error",
  "error": {
    "code": "session.expired",
    "message_key": "auth.session.expired",
    "params": {}
  }
}
```

O cliente traduz `message_key`; para compatibilidade, a fronteira pode incluir `message` já resolvida. Código, status HTTP e parâmetros permanecem estáveis para automação e testes.

## JavaScript e performance

- entregar apenas namespaces globais indispensáveis e os módulos da rota atual;
- gerar catálogo serializado/versionado no build ou no primeiro uso;
- cache key inclui locale, módulo, versão de recursos e overrides do projeto;
- invalidação acompanha o pipeline de atualização de recursos;
- não embutir `VariaveisData.json` inteiro em cada página;
- permitir pré-carregamento de namespaces necessários a dialog, toast, listagem e upload;
- medir tamanho comprimido, tempo de resolução e taxa de cache antes/depois.

## Compatibilidade com recursos e atualização

- manter `resources/{locale}` e o bloco `resources` dos módulos como fonte declarativa;
- normalizar schema sem perder `version`, checksum, `user_modified`, `deletar` e `forcar_atualizacao`;
- gerar os catálogos de runtime a partir da mesma fonte, evitando edições divergentes no banco e no JSON;
- ferramentas CLI podem manter arquivos locais, mas usam o mesmo contrato de chave/placeholder/fallback;
- o pipeline deve detectar colisão entre core e overlay e exigir regra de precedência explícita.

## Validação automatizada

O CI deve verificar:

- JSON/schema inválido e locale não suportado;
- chave ausente no idioma de referência ou em idiomas obrigatórios;
- chave duplicada, órfã ou não usada, com baseline inicial;
- placeholders divergentes entre idiomas;
- HTML não declarado ou conteúdo ativo perigoso;
- IDs/paths de recursos divergentes entre idiomas;
- payload frontend acima do orçamento;
- fallback literal hardcoded em código novo.

A varredura atual já encontrou pequenas assimetrias no core: três IDs presentes apenas em `pt-br` e um apenas em `en` entre páginas/componentes/templates dos manifestos. Isso confirma que o validador deve existir antes da migração em massa.

## Plano

### Fase 1 — Contrato e compatibilidade

- ADR de locale, namespaces, fallback, placeholders, plural e escaping;
- testes de caracterização para `gestor_variaveis()`, `__t()` e `Translator` do instalador;
- interfaces/classes pequenas e adapters para os mecanismos atuais;
- baseline de catálogo e faltas conhecidas.

### Fase 2 — Tooling e frontend

- schema único e validador;
- compilador de catálogo PHP/JS;
- cache/versionamento e integração com atualização;
- pacote autônomo do instalador;
- API de erro por código/chave.

### Fase 3 — Pilotos

- componentes globais Tailwind (dialog/toast/loading);
- `admin-paginas-v2`, `C2FDataGrid` e `C2FUpload`;
- instalador e atualizador, incluindo sessão expirada e falhas de deploy/banco;
- validação em `pt-br` e `en`.

### Fase 4 — Migração por ondas

- executar BL-026 no core e instalador;
- projetos privados migram seus namespaces sem copiar chaves globais;
- remover globals/fachadas antigas somente após contador zero.

## Critérios de aceite

- uma única API conceitual atende PHP, JS, CLI e instalador por loaders apropriados;
- recursos existentes e customizados continuam atualizáveis sem perda;
- chaves novas são inglesas, namespaced e validadas;
- fallback, placeholder, pluralização e escaping são previsíveis e testados;
- frontend recebe somente o catálogo necessário;
- respostas de API têm código estável independente do idioma;
- `gestor_variaveis()`, `__t()` e tradutores antigos ficam apenas como adapters temporários.

## Próxima ação

Promover o ADR e um PoC pequeno que resolva a mesma chave global/módulo em PHP, JavaScript e instalador, sem alterar ainda todos os call-sites.
