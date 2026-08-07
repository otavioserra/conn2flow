# BL-023 — Padronização técnica em inglês para código e APIs

- **Tipo:** Architecture/Maintainability/Governance
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** `gestor/`, `gestor-instalador/`, contratos públicos e regras aplicáveis aos overlays privados
- **Relacionados:** BL-011, BL-012, BL-013, BL-021 e BL-024

## Decisão recomendada

O inglês técnico deve ser definido no começo da v3, mas o legado não deve sofrer uma tradução massiva antes das demais migrações. A estratégia recomendada é:

1. congelar um glossário e convenções antes de criar as novas APIs;
2. exigir inglês em todo código novo da branch `3.0.x`;
3. renomear código existente quando o respectivo arquivo, serviço ou módulo for migrado para banco/interface v2;
4. manter fachadas e aliases temporários para consumidores 2.9.x e overlays ainda não migrados;
5. remover os nomes em português apenas quando a busca na árvore final core + overlays chegar a zero.

Uma tradução geral antecipada teria alto custo de conflito, prejudicaria o histórico do Git e misturaria alterações semânticas com alterações mecânicas. Adiar toda a padronização também seria ruim, pois as novas classes v3 nasceriam com o débito antigo. O meio-termo progressivo evita ambos os problemas.

## Diagnóstico inicial

Uma varredura heurística do core, excluindo `vendor/`, encontrou:

- 267 arquivos PHP e 2.164 declarações de função;
- aproximadamente 494 funções com tokens técnicos em português;
- 244 classes/interfaces/traits/enums, com ao menos 21 nomes candidatos a tradução;
- comentários e docblocks predominantemente em português nas bibliotecas procedurais;
- APIs públicas muito usadas, como `gestor_variaveis()`, `banco_select_name()` e famílias de interface, que não podem ser simplesmente apagadas enquanto existirem módulos privados.

Os números são indicadores para dimensionamento, não uma lista de renomeação automática. Termos de domínio, marcas, siglas e falsos positivos exigem revisão humana.

O Gestor Instalador já usa classes com nomes em inglês, mas ainda mistura comentários, logs e helpers; ele deve consumir a mesma convenção sem depender do bootstrap completo do Gestor.

## Convenção alvo a registrar em ADR

### PHP

- componentes e contratos públicos próprios do Conn2Flow usam o prefixo canônico `C2F` — nunca apenas `C2` — como em `C2FI18n`, `C2FDataGrid`, `C2FUpload` e `C2FDialog`;
- namespaces completos usam `Conn2Flow` como raiz; o prefixo curto `C2F` identifica APIs públicas, assets e integrações em que o namespace completo não aparece;
- namespaces, classes, interfaces, traits e enums em inglês e `PascalCase`;
- métodos, funções e variáveis em inglês e `camelCase` no código novo orientado a objetos;
- constantes em inglês e `UPPER_SNAKE_CASE`;
- PSR-4 para autoload e PSR-12 para estilo;
- nomes procedurais legados permanecem apenas em uma fachada de compatibilidade marcada como deprecated;
- exceções, códigos técnicos, eventos e objetos de resultado em inglês.

### JavaScript, HTTP e artefatos

- JavaScript/TypeScript em inglês, com `camelCase` e classes `PascalCase`;
- propriedades JSON e parâmetros de API em inglês e convenção única documentada;
- rotas públicas não devem ser traduzidas silenciosamente; mudanças de URL precisam de redirect/versionamento;
- filenames, módulos, eventos, data attributes e seletores novos em inglês;
- mensagens destinadas ao usuário não pertencem ao identificador técnico nem ao código: usam recursos do BL-025/BL-026.

### Comentários e documentação

- comentários, docblocks, ADRs técnicos e documentação de extensão novos em inglês;
- comentários legados são traduzidos quando o arquivo recebe alteração substancial;
- uma varredura final trata o restante, evitando commits iniciais apenas de tradução que causem conflitos e destruam `git blame` útil;
- conteúdo editorial e traduções para usuários continuam no idioma correspondente, fora desta regra.

## Glossário canônico

Criar um catálogo versionado com, no mínimo:

- termo legado, termo canônico em inglês e contexto;
- nome PHP, nome de API, nome de evento e nome de recurso;
- nome lógico e nome físico de banco durante a transição;
- palavras que não devem ser traduzidas por serem marca, protocolo ou termo de domínio;
- pluralização e abreviações permitidas;
- aliases temporários, consumidores conhecidos e versão prevista de remoção.

Exemplos apenas para iniciar a discussão: `pagina/page`, `usuario/user`, `arquivo/file`, `categoria/category`, `componente/component`, `variavel/variable`, `modulo/module`, `operacao/operation`. A forma física de tabelas e chaves será fechada pelo BL-024, não por substituição textual.

## Compatibilidade e depreciação

1. criar classes/fachadas inglesas como API canônica;
2. fazer funções portuguesas encaminharem para a implementação nova sem duplicar regra de negócio;
3. emitir depreciação agregada por call-site somente em ambiente de desenvolvimento/teste;
4. publicar matriz de substituições para módulos privados;
5. impedir novos consumidores dos aliases por regra de CI baseada em baseline;
6. remover alias apenas depois de testar a árvore composta de cada produto.

Não usar `class_alias` ou wrappers indiscriminadamente para contratos cujo formato também mudou. Nesses casos, um adapter explícito deve converter entrada, saída e erro.

## Plano de execução

### Fase 1 — Norma e baseline

- aprovar ADR e glossário;
- gerar inventário por símbolo/arquivo e marcar API pública versus interna;
- definir allowlist de termos legítimos;
- adicionar lint que bloqueie novas violações em relação ao baseline, sem exigir limpeza total imediata;
- documentar política de depreciação e versionamento.

### Fase 2 — Fundação v3

- aplicar inglês às classes modulares do BL-012, banco v2, interface v2, `C2FI18n`, `C2FDataGrid` e `C2FUpload`;
- expor aliases procedurais somente na camada `Legacy`;
- criar testes de contrato que executem API nova e adapter legado sobre a mesma implementação.

### Fase 3 — Migração por módulo

- renomear símbolos internos junto com cada batch de migração;
- atualizar manifestos, hooks, chamadas AJAX, testes e documentação do módulo no mesmo batch;
- não misturar módulos não relacionados;
- registrar aliases ainda consumidos por overlays.

### Fase 4 — Encerramento

- executar varredura da composição core + projetos privados;
- traduzir comentários legados restantes com revisão;
- remover aliases e baselines somente após contador zero;
- publicar guia de extensão v3 inteiramente em inglês técnico.

## Estimativa relativa

- ADR, glossário, inventário e baseline: complexidade média;
- fundação/classes novas: acréscimo pequeno se feito desde o início;
- renomeação de módulos: acréscimo estimado de 10–20% por batch quando junto da migração funcional;
- tradução massiva isolada antes da arquitetura: complexidade e risco altos, não recomendada.

## Critérios de aceite

- convenção e glossário versionados e usados por core e overlays;
- nenhum símbolo técnico novo em português na v3 sem exceção documentada;
- comentários/docblocks de código novo em inglês;
- APIs públicas legadas possuem substituição e plano de remoção;
- CI compara contra baseline e impede regressão;
- texto voltado ao usuário é resolvido pelo mecanismo multilíngue, não convertido em literal inglês.

## Próxima ação

Promover primeiro o ADR/glossário e o gerador de baseline como batch pequeno da Fase 1 da v3. A renomeação do legado entra nos batches dos módulos correspondentes.
