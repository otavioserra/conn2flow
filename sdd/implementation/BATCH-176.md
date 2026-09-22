# BATCH-176: Suporte Nativo ao Cloudflare Turnstile no Core do Conn2Flow

Execução da [req-171](../human-requests/req-171.md).

---

## Atividades e Checklist

### 1. [x] Configuração de Ambiente e Retrocompatibilidade
- [x] Adicionar suporte a `CAPTCHA_PROVIDER` (`none` | `google-recaptcha` | `cloudflare-turnstile`) em `gestor/config.php`.
- [x] Adicionar variáveis `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` e `TURNSTILE_MODE` em `gestor/config.php`.
- [x] Garantir inferência retrocompatível: se `CAPTCHA_PROVIDER` não existir no `.env`, mas `USUARIO_RECAPTCHA_ACTIVE` for `true`, assumir `google-recaptcha`.
- [x] Atualizar template `.env` em `gestor/autenticacoes.exemplo/dominio/.env` com documentação e chaves de teste da Cloudflare.

### 2. [x] Abstração Centralizada de Validação Backend
- [x] Criar helper/função centralizada `gestor_captcha_validar(?string $token = null, array $opcoes = []): array|bool` (ex.: em `gestor/bibliotecas/seguranca.php` ou biblioteca apropriada).
- [x] Implementar chamada cURL para `https://challenges.cloudflare.com/turnstile/v0/siteverify` com tratamento de timeout (5s) e parse de retorno.
- [x] Refatorar validações de captcha em `gestor/bibliotecas/formulario.php` e `gestor/modulos/perfil-usuario/perfil-usuario.php` para utilizar a nova função centralizada.
- [x] Garantir que falha no Turnstile seja contabilizada como abuso no rate-limiting de acessos.

### 3. [x] Frontend, Componentes e Injeção de Assets
- [x] Injetar script oficial do Turnstile (`https://challenges.cloudflare.com/turnstile/v0/api.js`) dinamicamente quando o provedor for Turnstile.
- [x] Integrar renderização do widget nos formulários de autenticação (`perfil-usuario`: `acessar-sistema`, `cadastrar-se`, `esqueceu-a-senha`) nos temas Fomantic UI e Tailwind CSS.
- [x] Integrar widget no motor de formulários dinâmicos (`gestor/assets/interface/formulario.js` e `formulario.php`).
- [x] Garantir que o token `cf-turnstile-response` seja enviado em submissões AJAX e manipulado adequadamente em caso de erro/reset.

### 4. [x] Módulo Administrativo (admin-environment)
- [x] Atualizar UI de `admin-environment` (HTML e PHP) para permitir seleção de `CAPTCHA_PROVIDER` e inputs de chaves do Turnstile.
- [x] Implementar endpoint AJAX de teste das chaves do Turnstile no painel administrativo.

### 5. [x] Testes e Validação
- [x] Criar testes unitários PHPUnit cobrindo os diferentes cenários do validador (chaves de teste Cloudflare, mock de rede, timeout, erros).
- [x] Rodar `php -l` nos arquivos modificados.
- [x] Executar suíte de testes PHPUnit e Vitest sem regressões.

---

## Critérios de Aceite e Validação

1. **Seleção de Provedor**: Suporte transparente a `none`, `google-recaptcha` e `cloudflare-turnstile`.
2. **Retrocompatibilidade**: Instalações prévias continuam funcionando com Google reCAPTCHA sem qualquer quebra.
3. **Abstração Limpa**: Chamadas de validação de captcha no core unificadas na função centralizada.
4. **Proteção Efetiva**: Autenticação e formulários públicos validados com sucesso via Turnstile.
5. **Painel de Gestão**: `admin-environment` configura e testa as credenciais do Turnstile.
6. **Suíte Verde**: 100% dos testes da suíte passam com sucesso.

## Execução e evidências (2026-09-22)

- `CAPTCHA_PROVIDER` aceita `none`, `google-recaptcha` e `cloudflare-turnstile`. Valor ausente ou vazio preserva a inferência por `USUARIO_RECAPTCHA_ACTIVE`.
- `gestor_captcha_validar()` concentra Siteverify, valida token e resposta, limita cURL a 5 segundos e aceita transporte injetado nos testes. Os fluxos de perfil-usuario, formulários públicos e testes de reCAPTCHA do painel usam o helper. Falhas de CAPTCHA seguem como abuso no controle de acessos; o signup ganhou contagem explícita.
- Widget Turnstile com renderização explícita em autenticação e formulários públicos. O campo `cf-turnstile-response` entra na submissão nativa ou no `FormData` AJAX. O motor de formulários reinicia o widget após erro. O modo do widget é definido pela Site Key na Cloudflare; `TURNSTILE_MODE` registra essa escolha no ambiente.
- Admin Environment oferece seletor, credenciais e teste AJAX do Turnstile, com textos em variáveis de módulo para `en` e `pt-br`.
- `php -l` em 6 arquivos: sem erros; `node --check` em 3 arquivos: sem erros; PHPUnit: **1.187 testes, 7.843 asserções, 4 pulados, exit 0**; Vitest: **423/423, exit 0**; `assets:minify --verificar`: 0 derivados desatualizados; `resources:sync`: exit 0, 2.882 recursos e nenhum problema detectado; `git diff --check`: exit 0.
- A primeira execução do PHPUnit falhou no teste RSA porque `OPENSSL_CONF` apontava para um caminho ausente no host. Com o arquivo `extras/ssl/openssl.cnf` da instalação PHP 8.5, a suíte completa passou.
- O ambiente de teste foi identificado em `dev-environment/data/environment.json`: `conn2flow-site-local` usa SSH/HestiaCP em `lab` e `https://conn2flow.local/`. `php cli/c2f.php project:update-all conn2flow-site-local` concluiu os 8 estágios no Lab (core, banco, recursos, arquivos, CSS, JS e assets). Foi preciso priorizar `C:\Program Files\Git\bin` no `PATH` para que `bash` resolvesse para Git Bash em vez do atalho WSL do Windows. A publicação opcional em `dist/` permaneceu sem `PUBLIC_PATH`; o pipeline publicou o que era aplicável ao projeto.
- MariaDB `admin_conn2flow`: `paginas` contém `admin-environment` em `en` e `pt-br`, com HTML de Turnstile sincronizado (comprimentos 33.252 e 37.704 bytes, posição do texto 15.379 e 19.205). O banco respondeu sem impedimento; não havia necessidade de Docker.
- Com as chaves oficiais de teste da Cloudflare temporariamente configuradas no Lab, Playwright verificou `/signin/`, `/signup/`, `/forgot-password/` e `/contact/`: HTTP 200, contêiner `.cf-turnstile`, campo `cf-turnstile-response`, widget visualmente renderizado e nenhum erro de console. Capturas em `temp/batch-176-*-turnstile.png` (artefatos locais ignorados pelo Git). O inspector padrão aguardou `networkidle` indefinidamente por causa do script da Cloudflare; a checagem usou `domcontentloaded` e espera curta.
- Em `/admin-environment/`, a aba Usuário mostrou o provedor selecionado e o bloco Turnstile. O botão de teste gerou token e a rota AJAX retornou **“Chaves e token do Turnstile válidos.”**; o salvamento pela interface retornou HTTP 200 e `status=success`, e as quatro variáveis apareceram no `.env` remoto. O `.env` original do Lab foi restaurado após cada teste; o checkout do projeto também foi restaurado após a geração automática de `schema-metadata.json`.
- Correção de diagnóstico: a primeira conclusão de que a homologação dependia de Docker foi incorreta. A skill `c2f-agent-visual-inspection` descreve um ciclo antigo centrado em Docker, mas `c2f-project-pipeline-and-tasks`, `c2f-projects-system` e `c2f-shell-and-windows-traps`, em conjunto com `environment.json`, apontavam para o fluxo SSH/HestiaCP. Eu não cruzei essas fontes antes de encerrar a primeira validação. O SQL é a fonte do HTML servido e foi consultado no MariaDB do Lab; o entrave prático adicional foi `auth:cookie --project` montar aspas incompatíveis com SSH remoto quando iniciado no Windows. A sessão de teste foi gerada com o gerador existente diretamente no Lab.

## Estado

complete
