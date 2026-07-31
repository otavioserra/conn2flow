# BL-001 — Instalador: verificação de integridade e TLS no download do gestor

- **Tipo**: Architecture / Security
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: ALTA (cadeia de suprimentos)
- **Origem**: análise de segurança 2026-07-31 (código real)
- **Componentes**: `gestor-instalador/src/Installer.php`

## Contexto observado

`downloadFile()` baixa o release do gestor com `CURLOPT_SSL_VERIFYPEER = false` ([Installer.php:435](../../gestor-instalador/src/Installer.php)) e `extractZip()` extrai o ZIP **sem verificar checksum/assinatura** ([Installer.php:454-472](../../gestor-instalador/src/Installer.php)). Ou seja, durante a instalação não há garantia de que o código baixado é autêntico.

Isso contrasta com o **updater do core**, que já faz o certo: `atualizacoes-sistema.php` baixa o `.sha256` e chama `verifyZipSha256($zip,$shaFile)` antes de aplicar ([atualizacoes-sistema.php:703-713](../../gestor/controladores/atualizacoes/atualizacoes-sistema.php)). O instalador ficou para trás desse padrão.

## Risco

Um atacante em posição de rede (MITM, DNS spoof, proxy comprometido) pode injetar um `gestor.zip` malicioso durante a instalação — execução de código arbitrário no servidor no primeiro deploy, antes de qualquer hardening.

## Proposta de melhoria (a validar)

1. Remover `CURLOPT_SSL_VERIFYPEER = false`; manter verificação TLS ativa (com bundle de CAs atualizado).
2. Baixar também o `gestor.zip.sha256` do release e validar o hash antes de extrair — reutilizar a lógica `verifyZipSha256` do core.
3. Opcional: validar assinatura (GPG/minisign) do release, elevando de integridade para autenticidade.
4. Abortar a instalação com mensagem clara se a verificação falhar.

## Critérios de aceite (rascunho)

- Download com TLS verificado; instalação falha de forma segura em cadeia de certificados inválida.
- ZIP rejeitado quando o SHA256 não bate com o publicado no release.
- Paridade documentada com o fluxo de verificação do updater do core.

> Item de backlog — não autorizado para implementação até promoção para `sdd/human-requests/`.
