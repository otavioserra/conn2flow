# BL-020 — E-mail: só SMTPS (porta 465) funciona; `EMAIL_SECURE` é ignorado

- **Tipo**: Bug
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: MÉDIA (instalações com provedor só em 587/STARTTLS não enviam e-mail)
- **Origem**: reescrita de `reference/libraries/comunicacao.md` (req-179), 2026-09-25
- **Componentes**: `gestor/bibliotecas/comunicacao.php` (`comunicacao_email()`)

## Contexto observado

1. O código testa se a chave `secure` **existe** na configuração do servidor, e ela sempre existe.
2. Resultado: `EMAIL_SECURE=false` não desliga a criptografia, e a porta 587 com STARTTLS falha na conexão.
3. Em falha fora do modo debug, os anexos temporários não são apagados (o log com a senha é o item A9 da req-181).

## Proposta

1. Ler o valor de `secure`: `ssl`/`true` → SMTPS; `tls` → STARTTLS; `false`/vazio → sem criptografia (só para desenvolvimento).
2. Apagar os anexos temporários também no `catch`.
3. Teste com PHPMailer simulado para os três modos.

## Critérios de aceite (rascunho)

- Envio funciona com 465/SMTPS e com 587/STARTTLS no Lab.
- A doc `comunicacao.md` perde o aviso de "só SMTPS" e a `installation.md` a observação correspondente.
