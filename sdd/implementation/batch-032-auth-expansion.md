# BATCH-032 - Login sem Senha por E-mail e Auxílio de Configuração OAuth

## Escopo do Lote
Este lote expande as opções de login e configurações de segurança introduzidas no BATCH-030. Adiciona uma quarta opção global de autenticação ("Habilitar login sem senha via Código por E-mail") no módulo `admin-environment`, que permite a autenticação de usuários enviando um código OTP temporário ao e-mail cadastrado, dispensando o uso de senhas. Adicionalmente, melhora a UX do painel administrativo fornecendo links de auxílio direto e instruções passo a passo para a configuração das credenciais do Google e Facebook/Meta OAuth.

---

## Progresso por Slice

O lote foi quebrado em slices pequenos com alvo de validação explícito:

| Slice | Escopo | Status | Validação |
| --- | --- | --- | --- |
| 1 | `admin-environment` (toggle `auth_method_email_active` + links/guias OAuth) | implementado | `php -l` + `node --check` OK |
| 2 | `perfil-usuario` UI (toggles/abas na tela de login `acessar-sistema.html`) | implementado | `node --check` + JSON OK |
| 3 | `perfil-usuario` Backend (fluxo de autenticação sem senha e redirecionamento 2FA) | implementado | `php -l` OK; testes manuais pendentes |

---

## Checklist de Implementação

### 1. Painel de Configurações (`admin-environment`)
- [x] Adicionar checkbox `auth_method_email_active` na aba "Configurações de Usuário" em `admin-environment.php` e `admin-environment.html` (pt-br e en).
- [x] Mapear a leitura/escrita no `.env` para a variável `AUTH_METHOD_EMAIL_ACTIVE` no backend PHP.
- [x] Adicionar links e mensagens explicativas de auxílio ("How-To") para a criação de credenciais OAuth nos campos do Google e Meta no HTML e JSON (pt-br e en).
- [x] Garantir que os links do Google (`https://console.cloud.google.com/`) e Meta (`https://developers.facebook.com/`) abram em abas novas (`target="_blank"`).
- [x] Adicionar as variáveis criadas no BATCH-030 e BATCH-032 com seus valores default no arquivo de template `gestor/autenticacoes.exemplo/dominio/.env`.

### 2. Interface da Tela de Login (`perfil-usuario` / acessar-sistema)
- [x] Injetar verificação de `AUTH_METHOD_EMAIL_ACTIVE` na renderização de `acessar-sistema.html`.
- [x] Adicionar blocos de controle no HTML para alternar entre "Entrar com Senha" e "Entrar com Código por E-mail" quando ambos estiverem ativos globalmente.
- [x] No Javascript `perfil-usuario.js`, capturar cliques de alternância e aplicar classes do Fomantic UI (`active` em menu) ocultando/mostrando o input de senha (`senha`) e alterando os rótulos de botão.
- [x] Se apenas o login por e-mail estiver ativo globalmente, ocultar a senha permanentemente e carregar a interface diretamente no modo de e-mail.

### 3. Backend do Login por E-mail (`perfil-usuario.php`)
- [x] No handler POST do formulário de login (em `perfil_usuario_signin()`), verificar se o usuário está submetendo via método "sem senha por e-mail".
- [x] Buscar o usuário correspondente ao e-mail/username fornecido.
- [x] Se ativo, chamar `two_factor_email_send_code()` para gerar e enviar o código temporário.
- [x] Definir as variáveis de sessão do gestor (`gestor_sessao_variavel`):
  - `pending_2fa_user` = ID do usuário.
  - `pending_2fa_mode` = `'verify'`.
  - `pending_2fa_type` = `'email'`.
- [x] Redirecionar para `signin-2fa/` para que o usuário insira o código e finalize o login.

---

## Validação Esperada
- Testes manuais cobrindo o painel administrativo e a tela de login ainda pendentes com o operador.
- Linting estático limpo nos arquivos alterados: `php -l` (`admin-environment.php`, `perfil-usuario.php`), `node --check` (`admin-environment.js`, `perfil-usuario.js`), `JSON.parse` (`admin-environment.json`, `perfil-usuario.json`, `PaginasData.json`) e `git diff --check`.
