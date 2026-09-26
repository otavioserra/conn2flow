---
title: "Módulo admin-environment"
description: "Painel das configurações de ambiente do domínio."
section: reference
module: admin-environment
sources:
  - gestor/modulos/admin-environment/admin-environment.php
  - gestor/modulos/admin-environment/admin-environment.js
  - gestor/modulos/admin-environment/admin-environment.json
  - gestor/modulos/admin-environment/resources
  - gestor/config.php
verified_at: a9a226e0
---

# Módulo admin-environment

É a tela administrativa das configurações carregadas do arquivo de ambiente do domínio. Reúne nome do site, sanitização, acesso restrito, CAPTCHA, SMTP, idiomas, PayPal, login, OAuth, 2FA, JWT e perfis autorizados a chaves de API.

## Como usar

Abra admin-environment/ (mesmo caminho nos dois idiomas), altere as seções desejadas e salve. Os botões de teste conferem CAPTCHA, e-mail e PayPal com os valores do formulário; a rotação de JWT é uma ação separada. O seletor de perfis lê usuarios_perfis ativos no idioma corrente. Após salvar, recarregue a aplicação para que config.php volte a ler os novos valores do .env.

## Referência técnica

O caso de página é raiz. O switch AJAX oferece opcao (payload vazio), salvar, buscar-perfis, testar-recaptcha, testar-turnstile, testar-recaptcha-v2, testar-email, testar-paypal e rotacionar-jwt. O JS envia o formulário e aciona esses testes. admin_environment_env_read() usa os valores já carregados em $_ENV; env_write() modifica ou acrescenta chaves no arquivo em AUTH_PATH_SERVER/.env. O JSON não possui tabela própria, widget, template, hook nem hooks.api. A consulta a usuarios_perfis sustenta os seletores de perfis.

O salvamento aceita campos presentes na requisição. HTML_SANITIZE, HTML_SANITIZE_JS, CAPTCHA_PROVIDER, TURNSTILE_MODE e SITE_RESTRICTED_ACCESS têm listas de valores válidos; tokens de crawler e ids de perfis restritos são normalizados. Uma guarda impede ligar acesso restrito com lista de perfis que exclua o perfil do operador atual. Os testes de CAPTCHA e PayPal fazem chamadas aos serviços; o teste de e-mail usa a biblioteca de e-mail. A ação JWT chama jwt_rotate_keys().

## Limitações confirmadas

> [!WARNING]
> A gravação escreve diretamente no .env, e o controlador informa sucesso após chamar file_put_contents() sem verificar a quantidade de bytes escrita. O formulário renderiza chaves sensíveis já carregadas; controle de acesso ao módulo e transporte seguro são essenciais. Não coloque valores reais de segredos em documentação ou repositório.

> [!CAUTION]
> O modo de debug do teste de e-mail emite texto diretamente e termina com exit, fora do envelope AJAX comum. Os testes externos e a rotação JWT têm efeitos reais, distintos da simples edição do formulário.

## Veja também

- [Usuários e perfis](usuarios-perfis.md)
- [Perfil do usuário](perfil-usuario.md)
