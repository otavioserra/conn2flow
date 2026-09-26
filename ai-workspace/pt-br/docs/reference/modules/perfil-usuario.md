---
title: "Módulo perfil-usuario"
description: "Autenticação, cadastro, perfil, 2FA, sessões e chaves pessoais de API."
section: reference
module: perfil-usuario
sources:
  - gestor/modulos/perfil-usuario/perfil-usuario.php
  - gestor/modulos/perfil-usuario/perfil-usuario.js
  - gestor/modulos/perfil-usuario/perfil-usuario.json
  - gestor/modulos/perfil-usuario/resources
  - gestor/db/migrations/20250723165538_create_usuarios_table.php
  - gestor/db/migrations/20250723165548_create_usuarios_tokens_table.php
  - gestor/db/migrations/20260706100000_add_two_factor_to_usuarios_table.php
  - gestor/db/migrations/20260706100010_create_usuarios_provedores_table.php
  - gestor/db/migrations/20260818100000_create_usuarios_api_tokens_table.php
verified_at: b6839aa1
---

# Módulo `perfil-usuario`

Concentra os fluxos de identidade do usuário: entrar e sair, cadastro, confirmação de e-mail, recuperação de senha, login social, OAuth, segundo fator e edição da própria conta. Também permite gerenciar sessões e chaves pessoais de API quando a política autoriza.

## Como usar

Use `signin/` para entrar, `signin-2fa/` para completar o segundo fator e `signout/` para encerrar a sessão. `signup/` cria conta e `email-confirmation/` confirma o endereço. Para recuperar acesso, siga `forgot-password/`, `forgot-password-confirmation/`, `redefine-password/` e `redefine-password-confirmation/`. A tela `perfil-usuario/` edita a própria conta e expõe abas de segurança, sessões e, quando permitido, chaves de API. Login social passa por `social-login/` e `oauth-callback/`; `oauth-authenticate/` e `oauth-authenticate-2fa/` atendem a autenticação da API. Há ainda `restrict-area/` e `validate-user/`. As rotas são idênticas em pt-br e en.

## Referência técnica

O `switch($_GESTOR['opcao'])` despacha as rotas acima como `signin`, `signin-2fa`, `signup`, `editar`, `signout`, `area-restrita`, `validar-usuario`, `confirmacao-email`, `forgot-password*`, `redefine-password*`, `social-login`, `oauth-callback` e `oauth-authenticate*`. A edição força o id do usuário autenticado pela interface. O `switch($_GESTOR['ajax-opcao'])` atende `seguranca-2fa-email-enviar`, `seguranca-2fa-ativar`, `seguranca-2fa-desativar`, `seguranca-social-vincular`, `seguranca-social-desvincular`, `sessoes-revogar`, `sessoes-revogar-outras`, `api-token-gerar` e `api-token-revogar`.

Os dados principais ficam em `usuarios` (`id_usuarios`, id, nome, e-mail, login, hash da senha, perfil, status e confirmação). Migrações adicionam `two_factor_secret`, `two_factor_enabled`, `two_factor_type`, código/expiração de e-mail e `two_factor_recovery_codes`. Vínculos sociais ficam em `usuarios_provedores`; sessões em `usuarios_tokens`. `usuarios_api_tokens` guarda dono, nome, prefixo, hash único do token, escopos, expiração, último uso, estado e datas. O segredo da chave pessoal é mostrado uma vez, na resposta de criação; a listagem usa apenas o prefixo. A aba e os endpoints de emissão exigem perfil permitido por `AUTH_API_ALLOWED_PROFILES` e a tabela disponível; o padrão dessa lista é `1`.

Signup valida nome, e-mail, senha de no mínimo 12 caracteres, acesso e CAPTCHA; cria o usuário, token de confirmação e, conforme o filtro, envia e-mail. O código emite ações `signup.start`, `signup.banco`, `signup.pos_banco`, `signup.end` e filtros `signup.email` e `signup.redirect`. `signup.banco` recebe id numérico e dados de nome/e-mail/id/plano/domain após o INSERT. `signup.email` pode suprimir o e-mail automático; `signup.redirect` troca o destino após cadastro. `signup.pos_banco` e `signup.end` ficam fora do ramo exclusivo de gravação e também podem ser alcançados ao montar a tela. A execução de callbacks depende do registro no sistema de hooks. O módulo não fornece widget nem template público de widget; fornece páginas e componentes de interface.

O login comum pode exigir TOTP ou código de e-mail; o fluxo OAuth tem interceptação 2FA própria. A área de segurança pode ativar/desativar 2FA, gerar códigos de recuperação e vincular provedores. A área de sessões revoga outras sessões do usuário; a sessão corrente não tem botão de revogação. `signout` remove o token da sessão corrente, limpa cookies de autenticação e redireciona para `signin/`.

## Limitações confirmadas

> [!CAUTION]
> A autorização das chaves pessoais de API usa `AUTH_API_ALLOWED_PROFILES`, não uma permissão específica no CRUD de módulos. O token em claro só aparece na criação; copie-o nesse momento. Revogar uma sessão em `usuarios_tokens` e revogar uma chave em `usuarios_api_tokens` são operações distintas.

> [!WARNING]
> `signup.pos_banco` não recebe o id do usuário como argumento e sua posição no fluxo não garante que uma gravação tenha ocorrido. Integrações que dependam da conta recém-criada devem usar `signup.banco` e tratar falhas de etapas posteriores separadamente.

## Veja também

- [Usuários](usuarios.md)
- [Perfis de usuários](usuarios-perfis.md)
