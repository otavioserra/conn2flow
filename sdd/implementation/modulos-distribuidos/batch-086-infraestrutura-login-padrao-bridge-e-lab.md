# BATCH-086 — Infraestrutura comum para módulos distribuídos

Estado `in-review`, 2026-10-02. REQ-092 autorizada em modo `autonomo_monitorado`; branch `feat/req-092`, base `017fe80f`. O lote usa worktree isolada e não incorpora o stage de outros executores.

O login distribuído usa o formulário padrão e o ciclo completo de `perfil-usuario`, com hook declarado `login.distribuido`. Códigos/tickets são de uso único; tokens OAuth2 nativos com escopo `distributed` são trocados em envelopes HMAC e mantidos em sessão no servidor. O endpoint legado de credenciais retorna 410. Contextos de iframe usam cookies separados e autorização por módulo, mantendo o código de negócio original.

O bootstrap ativa/finaliza a bridge de banco ao executar o módulo. A allowlist preserva identidade/permissões/recursos no Central e encaminha tabelas de negócio ao distribuído. SQL misto, schemas externos, comentários executáveis e queries empilhadas falham; o PDO dedicado desabilita múltiplas instruções. IDs e linhas afetadas acompanham a última query remota. Comparações HMAC são timing-safe; nonces e refresh têm consumo atômico e validade limitada.

O canal de leitura também aceita exclusivamente `SHOW COLUMNS FROM tabela`, exigido pelos helpers de CRUD, limitado à mesma allowlist. Metadados de tabelas protegidas e outras formas de SHOW são rejeitados; `banco_campos_nomes()` devolve array vazio quando não há resultado, sem warning de variável indefinida. A leitura real de colunas e a rejeição de `usuarios` foram verificadas pela API assinada.

Foi corrigido um HTTP 500 na página inicial: o helper de cookies distribuídos podia executar antes da inclusão da biblioteca quando não havia caminho de rota. A inclusão foi antecipada e `/` passou a ser asserção do E2E, respondendo 200. Também foram corrigidas a leitura da chave pública/payload completo na renovação e a resolução de assets `vendor/` no iframe. O canal SQL tem orçamento próprio configurável, sem aumentar o limite das outras APIs.

Validação final: **1414 testes PHPUnit, 11220 asserções, zero erros/falhas**, 4 skips, 4 deprecações PHP e 2 PHPUnit existentes. PHP 8.5.8, OpenSSL configurado no Windows. Testes adicionais cobrem replay, validade, SQL, erros sem vazamento e limites separados. O E2E real validou sign-in, troca, iframe, CRUD original de `coupons`, persistência remota e rotação dos dois tokens; 1280/390 px sem overflow global e sem erros de console/HTTP. O segundo fator mantém o mesmo ponto de hook, mas não recebeu E2E nesta rodada.

Artefatos detalhados de provisionamento, registro de tabelas, migrations do overlay e evidências sanitizadas pertencem ao repositório do site, em `sdd/implementation/modulos-distribuidos/batch-086-infraestrutura-login-padrao-bridge-e-lab.md` e `sdd/validation/modulos-distribuidos/evidence-req092/`. Somente `coupons` teve CRUD E2E; registrar os demais módulos não constitui validação de todos os seus fluxos.

- [x] Infraestrutura, auditoria e regressão do bootstrap corrigidas.
- [x] Build oficial dos recursos e PHPUnit.
- [x] Integração real e evidências no lote do site.
- [ ] Revisão técnica/humana e integração na branch principal.
