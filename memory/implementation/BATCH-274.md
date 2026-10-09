# BATCH-274 — Pagamentos, marca dos e-mails e confirmação de webhooks

- **Requisição:** [REQ-265](../human-requests/req-265.md)
- **Projeto:** `conn2flow`, `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`
- **Branch:** `feat/req-265`, criada da `main`; incorpora REQ-264 já publicada no Lab para preservar o ambiente.
- **Status:** `implemented-pending-homologation`
- **Data:** 2026-10-08
- **Coordenação:** Site REQ-122 / BATCH-116.

## Live Todo List

- [x] Auditar pagamentos, notificações, logs e receptor.
- [x] Serializar reconciliação com trava MySQL/MariaDB, transação SQL e rollback.
- [x] Normalizar referências `pi_`, `ch_`, `in_`, inclusive objetos expandidos e invoice payments.
- [x] Resolver marca e logo nos e-mails completos e nos layouts; incorporar arquivo absoluto por CID.
- [x] Devolver 401 para assinatura inválida e 503 para processamento incompleto, permitindo retentativa.
- [x] Compilar recursos, executar suítes completas e publicar pelo pipeline oficial no Lab.
- [x] Registrar melhoria arquitetural no backlog, revisar e preparar commit explícito.

## Implementação

`pagamentos_com_trava` protege a reconciliação financeira na mesma conexão, com `GET_LOCK`, transação, commit/rollback e liberação em `finally`. A extração de referências Stripe permite ao site reconhecer diferentes objetos da mesma cobrança. O cadastro e as tabelas de pagamentos existentes permanecem.

Os e-mails recebem fallback de marca configurada, remetente ou Conn2Flow. As imagens locais são resolvidas para arquivo absoluto e CID; imagens locais indisponíveis recebem URL absoluta quando há base válida. CIDs distintos preservam imagens já incorporadas. O receptor deixa de confirmar sucesso quando o consumidor informou erro ou assinatura ainda não localizada.

A solicitação humana posterior adiou a tabela durável dos gateways e a unificação dos e-commerces: [BL-031](../backlog/BL-031-eventos-gateways-ecommerce-unificado.md). Nenhuma tabela de eventos nova integra esta entrega.

## Validação

- `c2f resources:sync`: saída 0, 4.341 recursos na compilação após incorporar REQ-264. Aviso de DocumentRoot sem projeto resolvido pela publicação completa no Lab.
- `composer test`: 1.763 testes / 20.606 asserções; zero falhas e erros. Quatro deprecações, quatro avisos PHPUnit e cinco skips herdados. Windows requer `OPENSSL_CONF` do Git; regex de testes legados requer fonte Stripe LF. A rodada CRLF falhou em três testes; corrigido o formato do fonte e repetida a suíte completa.
- `npm.cmd run test`: 60 arquivos / 664 testes aprovados.
- Teste isolado do código real: aliases, falha de trava, liberação após exceção, marca escapada, URL absoluta, CID e respostas 401/503.
- [Resumo das suítes](../validation/req265/tests-summary.json).
- `project:update-all conn2flow-site-local --no-wait`: pipeline completo sequencial, saída 0; manutenção desligada.
- Lab: logo resolve para arquivo absoluto existente e CID; telas administrativa/cliente mostram somente R$ 999 e R$ 99, HTTP 200 e zero erros JS. Evidências coordenadas no site, `sdd/validation/req122/`.

## Limites

Validação de recibos e transporte isolada, sem criar nova cobrança nem enviar e-mail real. A assinatura existente foi reconciliada no site e seu histórico recuperado diretamente dos eventos sandbox do Stripe. Os logs não são fonte financeira. Não houve publicação em produção ou merge na main. Avisos de arquivos divergentes/legados do Lab estão registrados no BATCH-116.
