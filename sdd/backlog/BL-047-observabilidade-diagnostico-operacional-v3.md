# BL-047 — Observabilidade e diagnóstico operacional da v3

- **Tipo:** Architecture/Observability/Reliability/Operations
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** logs, correlação, métricas, health checks, diagnóstico e suporte operacional
- **Relacionados:** BL-020, BL-029, BL-030, BL-037, BL-044, BL-045, BL-046

## Evidência e problema

Há logs úteis, porém fragmentados entre biblioteca global, cron, plugins, atualizador, redirects, API, `error_log()` e gravações diretas. Formato, contexto, retenção e redaction variam; correlacionar uma requisição AJAX com sessão de atualização, queries, jobs e erros exige investigação manual.

## Decisão proposta

Adotar uma fachada `C2FLogger` compatível com PSR-3 ou contrato equivalente, implementada em classes pequenas, e um contexto de correlação propagado por HTTP, CLI, cron e jobs.

## Contrato de eventos

Todo evento operacional estruturado deve poder incluir:

- timestamp UTC, nível, código estável e mensagem localizada apenas na apresentação;
- request/correlation id, installation/site id e execution/job id;
- módulo, ação e duração;
- usuário apenas por identificador mínimo permitido;
- causa/exceção normalizada;
- contexto allowlisted, nunca payload arbitrário.

SQL completo, parâmetros, cookies, tokens, senhas e conteúdo pessoal não entram por padrão.

## Pilares

### Logs

- formato estruturado e adapters para arquivo/stdout;
- rotação, retenção e permissões definidas;
- redaction central e testes de vazamento;
- compatibilidade temporária com funções legadas, medindo consumidores.

### Métricas

- latência, erro e volume por rota/caso de uso;
- duração/falha de migrations, atualizações, jobs, uploads e publicação;
- pool/conexões, queries lentas, filas pendentes e retries;
- métricas com cardinalidade controlada.

### Health e readiness

- liveness não depende de serviços externos;
- readiness verifica configuração, banco, schema e dependências realmente obrigatórias;
- diagnóstico detalhado exige autenticação/capability e aplica redaction;
- resposta pública não revela versões, caminhos ou credenciais.

## Operação e suporte

- página administrativa de saúde com ações somente leitura;
- pacote de diagnóstico exportável e sanitizado;
- catálogo de códigos de erro e runbooks bilíngues;
- alertas inicialmente baseados em objetivos simples, evitando ruído;
- integração futura com plataforma externa por adapter, sem acoplar o core a fornecedor.

## Testes mínimos

- propagação do correlation id em HTTP, AJAX, CLI e job;
- redaction de tokens, DSN, headers, payload e exceções;
- rotação/concorrência de arquivo e indisponibilidade do sink;
- liveness/readiness em falhas de banco, schema e configuração;
- cardinalidade e volume sob carga representativa;
- diagnóstico público versus autenticado.

## Critérios de aceite

- um incidente pode ser seguido de ponta a ponta por identificador;
- logs críticos têm schema estável, retenção e owner;
- nenhum sink de observabilidade é requisito para responder ao usuário;
- health checks distinguem processo vivo de aplicação pronta;
- segredos e dados pessoais não aparecem nas evidências;
- updater, migrations e jobs possuem duração e estado observáveis.

## Próxima ação

Promover ADR do schema de eventos e inventário dos loggers atuais. Usar atualizador e primeiro job da outbox como pilotos antes de migrar todos os módulos.
