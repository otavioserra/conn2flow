# BL-027 — Documentação como código e mapa de conhecimento dos módulos

- **Tipo:** Architecture/Documentation/Developer Experience/AI Readiness
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** `ai-workspace/pt-br/docs`, `ai-workspace/en/docs` e documentação dos overlays privados
- **Relacionados:** BL-012, BL-021, BL-023, BL-025 e BL-028

## Objetivo

Transformar a documentação técnica bilíngue em uma fonte de contexto verificável para pessoas e agentes, com identidade estável, vínculo explícito ao código e indicação clara de versão, atualidade e ownership.

Os documentos atuais continuam sendo uma excelente fonte de entendimento histórico e funcional, mas um agente não deve presumir que todo exemplo ou contrato descrito ainda corresponde ao runtime sem verificar sua versão e os arquivos-fonte relacionados.

## Diagnóstico

Inventário de 2026-08-07:

| Árvore | Markdown | Tamanho aproximado | Subárvores |
| --- | ---: | ---: | --- |
| `ai-workspace/pt-br/docs` | 116 | 1,04 MB | `bibliotecas`, `manual`, `modulos` |
| `ai-workspace/en/docs` | 118 | 0,96 MB | `libraries`, `manual`, `modulos` |

Há boa cobertura paralela: cada idioma possui 26 documentos na pasta de módulos, 29 na biblioteca e 29 no manual. Entretanto:

- os READMEs ainda informam apenas 26/27 documentos e caminho genérico `ai-workspace/docs`;
- a comparação literal de paths encontra 56 arquivos exclusivos de `pt-br` e 58 de `en`, em grande parte porque filenames e diretórios foram traduzidos (`bibliotecas`/`libraries`), não porque sejam documentos diferentes;
- `BANCO-V2-DOCS.md`, `INTERFACE-V2-ARCHITECTURE.md` e `PHP85-INSTALL-GUIDE.md` não possuem par óbvio em `pt-br`;
- o título/conteúdo inicial de `en/BANCO-V2-DOCS.md` ainda aparece em português, mostrando que presença de arquivo não garante idioma ou qualidade;
- há ampla referência a banco/interface v1, Semantic/Fomantic, nomes de schema em português e APIs que mudarão na v3;
- o changelog histórico deve continuar histórico e não ser reescrito como se sempre tivesse usado a arquitetura v3.

## Decisão recomendada

Criar um catálogo de documentação com IDs independentes do filename. Não renomear toda a árvore primeiro, pois isso quebraria links e criaria um diff enorme. Para documentos novos, usar o mesmo slug técnico em inglês nos dois idiomas; para o legado, manter paths atuais e registrá-los no catálogo até migração controlada.

## Manifesto de documentação

Cada documento deve possuir front matter ou entrada equivalente no catálogo:

```yaml
doc_id: modules.admin_files
locale: pt-BR
title: Administração de arquivos
audience: developer
status: current
product_versions: ["3.x"]
translation_of: modules.admin_files
source_paths:
  - gestor/modulos/admin-arquivos
owners:
  - core
last_verified_commit: "<sha>"
```

Campos mínimos:

- `doc_id` estável e em inglês;
- locale BCP 47 normalizado;
- audiência: usuário, administrador, integrador, desenvolvedor ou agente;
- estado: `draft`, `current`, `legacy`, `historical` ou `deprecated`;
- versões do produto às quais se aplica;
- documento correspondente nos demais idiomas;
- módulos, bibliotecas, migrations, tabelas e contratos relacionados;
- owner core/overlay;
- commit ou release em que o conteúdo foi verificado.

## Mapa de conhecimento para módulos

Gerar um índice navegável e, preferencialmente, uma representação JSON para ferramentas/agentes:

```text
module_id
  -> documentação técnica pt-BR/en
  -> manual administrativo pt-BR/en
  -> PHP/JS/manifest/resources
  -> tabelas/repositórios/migrations
  -> rotas/operações/permissões/hooks
  -> dependências C2F
  -> testes e fixtures
  -> overlays que estendem o módulo
  -> status v2/v3 e último commit verificado
```

O mapa orienta a leitura inicial, mas não substitui inspeção do código. Quando documentação e código divergirem, o processo deve registrar a divergência e corrigir o documento ou o runtime conforme a especificação autorizada.

## Estrutura alvo

- `architecture/`: decisões e contratos transversais atuais;
- `modules/`: referência técnica por módulo;
- `libraries/`: APIs públicas, adapters legados e exemplos;
- `manual/`: procedimentos para administradores/usuários;
- `migration/`: guias 2.9.x → 3.x e compatibilidade;
- `historical/`: changelogs e decisões encerradas, preservados sem fingir atualidade;
- catálogo/índice na raiz para pareamento, ownership e roteamento de agentes.

A mudança física das pastas existentes deve ser incremental; o catálogo pode representar essa estrutura lógica antes de mover arquivos.

## Automação e CI

- validar front matter/catálogo e unicidade de `doc_id` por locale;
- garantir paridade dos idiomas obrigatórios ou exceção documentada;
- validar links, anchors, imagens e paths de código referenciados;
- detectar documento `current` cujo `source_path` não existe;
- sinalizar `last_verified_commit` antigo após mudança nos arquivos relacionados;
- bloquear segredos, tokens, dados pessoais, URLs internas e exemplos perigosos;
- testar snippets executáveis quando viável;
- gerar índices e matriz de cobertura, sem gerar automaticamente explicações arquiteturais;
- distinguir seções geradas de seções manuais para não sobrescrever decisões humanas.

## Uso por agentes

O guia de entrada deve instruir agentes a:

1. consultar o catálogo pelo módulo/contrato em escopo;
2. preferir documento `current` aplicável à versão/branch ativa;
3. ler documentação nos dois idiomas apenas quando necessário para resolver lacuna, não assumir que uma é mais nova pelo idioma;
4. verificar `source_paths`, testes e commit relacionado antes de propor alteração;
5. atualizar documentação e catálogo no mesmo batch quando houver impacto;
6. não usar documentos `historical`/`legacy` como especificação atual;
7. registrar divergências encontradas em vez de perpetuá-las.

## Segurança e qualidade

- exemplos usam dados fictícios e endpoints locais/documentados;
- nenhuma credencial real, dump de produção ou informação pessoal;
- comandos destrutivos são marcados, escopados e acompanhados de pré-condições;
- exemplos de SQL usam banco v2/prepared statements na v3;
- exemplos de UI usam `C2FI18n`, Tailwind e contratos C2F atuais;
- documentos em inglês têm texto em inglês; documentos em português preservam identificadores técnicos ingleses.

## Plano

### Fase 1 — Catálogo e baseline

- atribuir `doc_id` sem mover arquivos;
- mapear pares `pt-br`/`en`, owners, audiência e versões;
- corrigir os READMEs/totais;
- marcar histórico, legado, atual e lacunas;
- gerar índice de módulos e relatório inicial.

### Fase 2 — Integração ao SDD

- adicionar `Documentation impact` obrigatório às requisições/batches;
- ligar backlog/requisito/batch aos documentos afetados;
- integrar validações de documentação ao CI da v3;
- criar template técnico e template de manual por módulo.

### Fase 3 — Integração com agentes

- criar roteiro de leitura por módulo/atividade;
- permitir busca pelo `doc_id`, source path, tabela, hook e API;
- testar tarefas de compreensão contra o catálogo e corrigir rotas ambíguas;
- medir documentos consultados, divergências e tempo para localizar o contexto.

## Critérios de aceite

- 100% dos documentos possuem identidade, locale, versão, status e owner;
- cada módulo suportado possui rota de conhecimento para código, banco, recursos, testes e manuais;
- pares bilíngues são conhecidos semanticamente, independentemente do filename atual;
- documentos históricos/legados não são apresentados como arquitetura atual;
- links, paths e catálogo são validados no CI;
- agentes recebem instrução explícita para verificar documentação contra código/versionamento;
- nenhum batch que muda contrato público termina sem avaliar impacto documental.

## Próxima ação

Promover um spike somente de inventário/catálogo, sem reescrever os 234 documentos. O resultado deverá classificar lacunas e escolher um módulo piloto para o fluxo completo.
