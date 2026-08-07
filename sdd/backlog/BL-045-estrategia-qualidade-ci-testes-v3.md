# BL-045 — Estratégia de qualidade, CI e testes da v3

- **Tipo:** Quality/Testing/Release Engineering/Developer Experience
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** pirâmide de testes, gates por batch, CI contínuo, homologação e composição core + overlays privados
- **Relacionados:** BL-020, BL-021, BL-029, BL-030, BL-033, BL-037, BL-039

## Evidência e problema

O repositório já possui PHPUnit, Vitest e Playwright, mas a proteção ainda é estreita:

- os workflows atuais são acionados na criação de tags de release, não em todo push/PR;
- o instalador é empacotado sem uma suíte bloqueante equivalente à do Gestor;
- há poucos testes de integração e E2E para o tamanho e o risco do sistema;
- a cobertura PHP está limitada a partes das bibliotecas e de dois publishers, sem orçamento mínimo;
- o produto final é a composição do core com overlays privados, enquanto o teste isolado do core não prova essa compatibilidade;
- atualizador, sessão expirada, migrations e rollback já produziram incidentes que precisam virar regressões automatizadas.

Fazer somente um smoke manual ao fim de dezenas de batches acumularia defeitos de integração e tornaria a causa difícil de localizar.

## Decisão proposta

Adotar qualidade incremental e em camadas. Cada batch termina verde e entrega seus próprios testes; a validação humana completa continua concentrada no RC, mas existem smokes curtos nos marcos arquiteturais.

```text
batch: lint/static + unit + contrato/integracao direcionados
merge: matriz de runtime + testes de integração + jornadas críticas
marco: smoke humano curto do conjunto integrado
RC: regressão completa + UAT/smoke humano + upgrade/restore realista
```

## Pirâmide e responsabilidades

### Testes unitários

- domínio, políticas, validação, parsers, serializers e adapters sem I/O;
- casos positivos, negativos, limites e propriedades de segurança;
- rápidos e obrigatórios em toda mudança.

### Contrato e integração

- HTTP/JSON, banco MySQL/PostgreSQL, migrations, arquivos, i18n e eventos;
- contratos compartilhados entre core, instalador, atualizador e overlays;
- banco real em container nas rotas em que mocks esconderiam incompatibilidades.

### Browser/E2E

- poucas jornadas críticas e estáveis: instalação, login/logout/expiração, autorização, CRUD piloto, upload, publicação e atualização;
- não duplicar em E2E todas as combinações já cobertas abaixo da interface;
- guardar trace, screenshot, console e respostas de rede quando falhar.

### Segurança e robustez

- testes negativos de rota direta, CSRF, IDOR, capabilities, upload hostil, segredo ausente e replay;
- fault injection em atualização, migration, outbox, lock e retomada;
- análise estática e de dependências como gate, com exceções versionadas e prazo.

## Lanes de CI

1. **Fast lane por push/PR:** validação SDD, lint/format check, PHPStan/Psalm escolhido, PHPUnit unit, Vitest e guardrails arquiteturais.
2. **Integration lane por merge:** MySQL/PostgreSQL, migrations, contratos HTTP, Playwright crítico e composição de ao menos um overlay.
3. **Compatibility lane programada:** matriz `L29`, `C29-V3`, `V3` e `V3-PG`, instalação limpa, upgrade e datasets maiores.
4. **Release lane:** repete gates bloqueantes antes de empacotar; o artefato testado deve ser exatamente o publicado.

O workflow do instalador deve testar instalação limpa, falha segura, retomada e resíduos. Nenhum release deve descobrir pela primeira vez um problema que poderia aparecer no PR.

## Estratégia de smoke humano

O usuário não precisa testar manualmente cada batch. Recomenda-se um roteiro de 15–30 minutos nos marcos:

1. fundações/runtime e instalação;
2. identidade, sessão e roteamento;
3. banco v2 e migrations;
4. interface/CRUD/piloto de páginas;
5. core + overlays privados;
6. beta/RC completo.

No fim, executar homologação integral com cópia sanitizada representativa, navegadores suportados e fluxos privados. O smoke final confirma experiência e composição; não substitui os gates incrementais.

## Contrato obrigatório de cada batch

- critérios de aceite transformados em testes verificáveis;
- testes novos/alterados e comandos executados;
- matriz de ambientes aplicável;
- impacto de migration, rollback, segurança, performance e documentação;
- evidências anexáveis e débitos explicitamente registrados;
- branch verde antes do handoff.

Teste flakey não pode ser simplesmente ignorado em jornada crítica. Deve ser corrigido ou isolado com responsável, prazo e cobertura alternativa.

## Métricas úteis

- tempo de feedback das lanes e taxa de falhas intermitentes;
- defeitos escapados por batch/marco;
- cobertura de requisitos e contratos, não apenas porcentagem de linhas;
- mutações sobreviventes em componentes críticos selecionados;
- jornadas críticas, migrations e combinações de overlay efetivamente cobertas.

## Critérios de aceite

- CI roda antes de merge/tag e possui fast lane abaixo de dez minutos como meta inicial;
- Gestor e Instalador possuem gates proporcionais ao risco;
- todo batch v3 declara e executa sua estratégia de teste;
- incidentes conhecidos do atualizador e sessão têm regressão automatizada;
- core + overlay é uma unidade explícita de compatibilidade;
- milestones possuem smoke curto e o RC possui plano humano completo;
- artefatos, logs e fixtures não contêm segredos ou dados pessoais reais.

## Próxima ação

Promover primeiro um batch de baseline que mova os testes existentes para CI de push/PR, catalogue jornadas críticas e defina o contrato de evidência. Só então aumentar cobertura por risco junto dos batches funcionais.
