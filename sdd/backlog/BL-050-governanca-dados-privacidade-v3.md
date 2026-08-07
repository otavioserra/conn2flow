# BL-050 — Governança de dados, privacidade e ciclo de vida na v3

- **Tipo:** Architecture/Data Governance/Privacy/Security
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** inventário, classificação, retenção, exportação, anonimização e eliminação de dados
- **Relacionados:** BL-011, BL-024, BL-032, BL-033, BL-036, BL-037, BL-045, BL-046, BL-047

## Problema

O sistema armazena usuários, acessos, formulários, uploads, integrações, pagamentos e logs. A migração de schema e a criação do AMS ampliarão fluxos de dados. Sem catálogo de finalidade, owner e retenção, fica difícil responder onde um dado pessoal existe, por quanto tempo permanece, como é exportado ou removido e o que chega a logs, backups e provedores externos.

Este item prepara governança e LGPD; não representa, sozinho, certificação ou parecer jurídico.

## Decisão proposta

Criar um inventário versionado de dados ligado ao dicionário do BL-024. Cada entidade/campo sensível declara:

- categoria e sensibilidade;
- finalidade e base/política definida pelo responsável do produto;
- origem e destinos/integrações;
- owner e módulos consumidores;
- retenção, arquivamento e eliminação;
- presença em logs, cache, busca, backup e analytics;
- regras de exportação, correção, anonimização e exclusão.

## Princípios técnicos

- minimização por padrão e coleta explícita;
- dados pessoais fora de URLs, logs e mensagens de erro;
- criptografia/transporte e controle de acesso proporcionais à sensibilidade;
- separação entre exclusão operacional, anonimização e retenção obrigatória;
- propagação idempotente de exclusão/correção a projeções e integrações;
- fixtures, dumps e ambientes de teste sempre sintéticos ou sanitizados;
- identificação por pseudônimo em observabilidade sempre que possível.

## Capacidades planejadas

- exportação estruturada por pessoa/conta/site com autorização forte;
- workflow auditável de solicitação, revisão, execução e confirmação;
- políticas automáticas de retenção via BL-048;
- legal hold/exceção somente com motivo e acesso restrito;
- relatório de localização e dependências do dado;
- registro de consentimento/preferência quando o produto realmente o exigir;
- contrato para provedores externos e deleção propagada.

## Relação com migrations e backups

Renames e cutovers preservam classificação e lineage. Exclusão em banco ativo não promete remoção instantânea de backup imutável: o restore deve reaplicar tombstones/políticas e respeitar a janela documentada do BL-051.

## Testes mínimos

- exportação completa sem dados de terceiros;
- autorização/step-up para operações sensíveis;
- anonimização/exclusão com referências e projeções;
- retenção vencida, hold e retries;
- restauração de backup seguida de reaplicação de exclusões;
- scanners para segredo/dado pessoal em logs, fixtures e artefatos;
- isolamento entre sites/tenants.

## Critérios de aceite

- entidades sensíveis têm classificação, owner e retenção;
- novos schemas não são aprovados sem impacto de dados;
- exportação e eliminação são verificáveis e auditáveis;
- ambientes não produtivos não recebem dump pessoal bruto;
- logs, jobs e caches obedecem às mesmas políticas;
- documentação distingue garantia técnica de decisão jurídica/organizacional.

## Próxima ação

Promover inventário inicial de usuários, acessos, formulários, arquivos, pagamentos e tokens antes das migrations físicas do BL-024. Validar as políticas resultantes com responsável jurídico/negócio quando necessário.
