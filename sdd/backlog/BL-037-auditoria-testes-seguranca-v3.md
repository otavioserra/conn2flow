# BL-037 — Auditoria, threat modeling e gates de segurança da v3

- **Tipo:** Security/Quality/Observability/Supply Chain
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** evidências contínuas de que os controles da v3 não possuem bypass

## Regra de governança

Este backlog define processo e gates futuros. Não autoriza scanner externo, pentest, publicação de relatório ou mudança operacional sem requisito explícito.

## Objetivo

Segurança não deve depender de lembrar de chamar uma função em cada módulo. A v3 precisa gerar evidências automáticas de mediação completa, autorização negativa, isolamento de arquivos, integridade de dependências e rastreabilidade de ações críticas.

## Baseline a preservar

A req-107 já possui testes unitários para CSPRNG, CSRF, compatibilidade do atualizador, Bearer, path traversal, checksum, escrita do `.env`, OAuth FIFO e shim do banco. Esses testes permanecem como regressão e devem ser ampliados para integração/HTTP real.

## Threat modeling por fronteira

Produzir modelos pequenos e versionados para:

1. painel e sessão web;
2. login/2FA/OAuth/recovery;
3. API project/system e app;
4. webhooks/gateways;
5. módulo central/distribuído;
6. upload e serving de conteúdo;
7. instalador/atualizador/plugins;
8. cron/CLI/migrations;
9. hooks e overlays privados;
10. bancos MySQL/PostgreSQL e backups.

Cada modelo identifica ativos, atores, fronteiras de confiança, abuso, controle, responsável e teste correspondente.

## Inventários gerados no CI

- rotas, métodos, autenticador, capability e handler;
- rotas públicas com justificativa;
- arquivos PHP sob diretório publicável;
- handlers/hooks com caminho fora da raiz permitida;
- capabilities cadastradas, órfãs e chamadas desconhecidas;
- usos de APIs legadas, SQL textual, `$_REQUEST` em serviços e mutações GET;
- dependências Composer/npm, licenças e hashes/lockfiles;
- scripts CDN/terceiros sem política SRI/self-host/CSP;
- secrets e arquivos sensíveis incluídos no artefato de release.

## Matriz de testes

### Roteamento/mediação

- acesso direto a módulo/controlador/config recebe `403/404`;
- rota/método/content type não declarado é negado;
- módulo não é incluído quando auth, CSRF ou policy falha;
- rota pública não herda handler privado;
- idiomas distintos resolvem a mesma policy.

### Autorização

- matriz perfil/capability positiva e negativa;
- operação inexistente nega;
- IDOR/BOLA cross-user, cross-host e cross-tenant;
- listagem e leitura individual têm o mesmo escopo;
- chamada AJAX/API/hook/cron não contorna policy;
- alteração de perfil invalida cache/sessão conforme contrato.

### Sessão/API

- fixation e rotação após login/2FA/step-up;
- idle/absolute/renewal timeout;
- cookie vencido em AJAX produz `401 AUTH_REQUIRED`;
- CSRF em todos os métodos mutáveis e ausência de mutação em GET;
- OAuth audience/client/scope/revocation;
- refresh token reuse e concorrência;
- webhook replay/timestamp/assinatura.

### Dados e arquivos

- prepared statements e allowlist de identificadores;
- escopo de tenant em toda query sensível;
- upload poliglota, MIME, extensão, tamanho, nome, traversal e conteúdo executável;
- arquivo privado não servido como público;
- extração ZIP contra Zip Slip, symlink e decompression bomb;
- migração/rollback e privilégio mínimo dos usuários de banco.
- dump isolado da tabela de sessões não permite autenticação nem reconstrução do bearer opaco;
- digest/HMAC copiado do banco e apresentado como cookie é rejeitado;
- rotação concorrente aceita somente a política explícita para bearer anterior/novo e não reativa sessão revogada;
- parser/formato RSA legado deixa de ser aceito ao fim da janela definida no BL-036.

## Auditoria de segurança

Registrar em formato estruturado:

- sucesso/falha de autenticação e 2FA;
- criação, rotação, revogação e expiração de sessão/token;
- negação de autorização/CSRF/origem/método;
- mudança de perfil, capability, usuário, segredo e configuração;
- update do sistema, instalação de plugin e migrations;
- importação/exportação, upload/exclusão e acesso a dado sensível;
- validação de webhook e detecção de replay.

Campos mínimos: timestamp UTC, correlation ID, ator/cliente pseudonimizado quando necessário, ação, tipo/ID do alvo, tenant/host, resultado, motivo codificado, canal e versão. Nunca registrar senha, bearer, refresh token, CSRF, segredo, conteúdo sensível ou dump.

Logs devem ter retenção, controle de acesso, proteção contra alteração e alerta proporcional. Negação comum não pode gerar ruído que esconda incidente real.

## Supply chain e navegador

- releases assinadas/verificadas, SHA-256 como integridade mínima e proveniência documentada;
- Composer/npm apenas por lockfile e auditoria automatizada;
- SBOM por release v3;
- plugins com manifesto, origem, hash/assinatura e compatibilidade declarada;
- eliminar `.git` e arquivos de desenvolvimento do artefato;
- preferir assets self-hosted; quando houver terceiro, fixar versão, SRI e CSP;
- mover CSP de `Report-Only` para enforcement por fases, com nonces/hashes e sem `unsafe-inline` como estado final;
- `frame-ancestors` por rota substitui dependência exclusiva de `X-Frame-Options`.

## Geração de release e ambientes

Os gates devem rodar nos perfis do BL-030:

- `L29` para hotfixes compatíveis;
- `C29-V3` para comportamento legado no runtime novo;
- `V3` MySQL;
- `V3-PG` PostgreSQL;
- versão PHP mínima aprovada e PHP 8.5.

Falha de teste negativo de autorização, rota pública inesperada, secret detectado ou dependência crítica bloqueia release.

## Critérios de aceite

- toda ameaça prioritária aponta para controle e teste automatizado;
- inventário de rotas/capabilities é completo e comparável entre commits;
- testes HTTP comprovam ausência de mutações GET e bypass direto;
- eventos críticos são auditáveis sem segredos;
- release contém SBOM/proveniência e passa auditoria de dependências;
- CSP possui plano mensurável de report-only para enforcement;
- overlays privados executam a mesma suíte contratual do core composto.

## Próxima ação

Promover um spike de baseline que gere inventários sem alterar runtime; em seguida, associar cada gap confirmado a uma requisição corretiva ou arquitetural específica.
