# BL-049 — Engenharia de performance, cache e capacidade da v3

- **Tipo:** Performance/Architecture/Quality/Operations
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** baselines, orçamentos, profiling, queries, cache, assets e testes de carga
- **Relacionados:** BL-011, BL-018, BL-020, BL-022, BL-030, BL-039, BL-041, BL-045, BL-047

## Problema

A v3 troca banco, arquitetura, interface, grid, uploads, runtime e publicação. Sem baseline, uma regressão pode ser atribuída ao sistema inteiro somente no fim. O histórico recente do atualizador mais lento mostra que tempo, I/O, número de arquivos e queries precisam ser evidência, não percepção tardia.

## Decisão proposta

Definir orçamentos por jornada e medir antes/depois em datasets reproduzíveis. Otimização só ocorre após profiling; cache não pode esconder query ruim ou consistência indefinida.

## Baselines prioritários

- bootstrap/TTFB público e administrativo;
- login, autorização e primeira tela;
- CRUD simples e admin-paginas com grid pequeno/grande;
- número e duração de queries por jornada;
- publicação, sitemap e invalidação de cache;
- upload pequeno/grande e processamento associado;
- instalação/atualização completa, migrations e merge de arquivos;
- memória, CPU e tamanho dos assets JS/CSS.

Cada resultado registra commit, ambiente, dataset, warm/cold cache e percentis; média isolada não basta.

## Estratégia de cache

- owner e chave versionada por contexto;
- namespace por instalação/site/locale/permissão quando aplicável;
- TTL e invalidação definidos pelo domínio;
- cache de resposta privada nunca compartilhado com usuário indevido;
- stampede protection para recomputações caras;
- operação correta sem cache e ferramenta de purge segura;
- observar hit ratio, tamanho e tempo de recomposição.

## Banco e aplicação

- detectar N+1, scans e paginação sem índice;
- planos de execução aprovados para consultas críticas em MySQL/PostgreSQL;
- limites e cursor/paginação para volumes grandes;
- evitar carregar módulos/configuração não usados por request;
- OPcache/autoload otimizados em perfil production-like;
- processamento pesado delegado ao BL-048.

## Frontend

- budget de JS/CSS, imagens e fontes;
- code splitting/lazy loading para DataGrid, Uppy e recursos administrativos grandes;
- Core Web Vitals apenas onde fizer sentido público, além de métricas administrativas próprias;
- nenhuma dependência duplicada ou asset legado após cutover.

## Testes e gates

- microbenchmark somente para componentes isolados; decisão de produto usa jornada;
- teste de carga em instalação sanitizada e descartável;
- comparar p50/p95 e erro em cold/warm runs;
- regressão acima do orçamento bloqueia merge ou exige decisão registrada;
- perfil de CI rápido usa smoke de budget; suíte completa roda por marco/release.

## Critérios de aceite

- existe baseline 2.9.x antes das reescritas e baseline v3 por marco;
- jornadas críticas possuem orçamento aprovado e owner;
- contagem de queries e tamanho de assets são gates automáticos onde estáveis;
- caches têm contrato de consistência, isolamento e invalidação;
- atualização e migrations têm telemetria por etapa;
- regressão não é resolvida apenas aumentando timeout/memória.

## Próxima ação

Promover um batch de medição sem otimização sobre o ambiente `L29` e o primeiro `V3`. Escolher cinco jornadas críticas e datasets pequeno/grande antes de definir números bloqueantes.
