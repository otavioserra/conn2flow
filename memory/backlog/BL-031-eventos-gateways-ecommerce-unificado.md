# BL-031: Eventos duráveis dos gateways e unificação dos e-commerces

- **Tipo**: Architecture / Reliability
- **Status**: ICEBOX
- **Criado em**: 2026-10-08
- **Origem**: Solicitação humana durante REQ-265 / REQ-122.
- **Repositórios**: core `conn2flow` e projeto `conn2flow-site`, em `C:\Users\otavi\OneDrive\Documentos\GIT\`.

O humano pediu que esta melhoria permaneça no backlog para implementação futura. BATCH-274 / BATCH-116 devem somente corrigir pagamentos, e-mails e exibição do histórico na estrutura atual.

## Situação confirmada no código e no Lab

O cadastro de gateways é `gateways_pagamentos`. No site, `subscriptions_transactions` e `subscriptions_transaction_refs` representam os pagamentos das assinaturas; `subscriptions_notifications` registra notificações e sua deduplicação. O histórico exibido pela assinatura lê `subscriptions.custom_fields.audit` e a chave legada `audit_trail`. Os arquivos em `conn2flow-gestor/logs/`, incluindo `gateways/stripe/`, são diagnósticos e podem ser rotacionados: não devem constituir a fonte durável dos eventos. O e-commerce de produtos utiliza `order_transactions`, com fluxo separado.

## Melhoria futura

- Projetar uma tabela de eventos vinculada ao cadastro de gateways, válida para Stripe e demais provedores. Nome proposto pelo humano: `gateways_pagamentos_eventos`; decidir o nome definitivo conforme a convenção de schema vigente.
- Registrar cada evento validado com identidade única por gateway/ambiente, tipo, datas, correlação com pedido/assinatura, estado de processamento, tentativas e erro. Definir retenção e proteção do payload.
- Separar recebimento durável de processamento financeiro e envio de notificações, permitindo retentativas e reprocessamento auditáveis sem duplicar pagamentos ou e-mails.
- Uniformizar contratos de transações, referências e notificações entre assinaturas e produtos; planejar migração e compatibilidade das telas existentes.
- Usar o banco como fonte da verdade e manter logs apenas para diagnóstico.

## Critérios para promoção

Nova requisição aprovada pelo humano, desenho de migração e rollback, idempotência concorrente, eventos fora de ordem, correlação tardia e cobertura dos dois e-commerces. Nenhuma nova tabela de eventos faz parte da entrega atual.
