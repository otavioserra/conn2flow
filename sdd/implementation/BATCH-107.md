# BATCH-107 — Hardening de Segurança, Mitigação de Vulnerabilidades e Saneamento do Core

## Origem

- Intake: `sdd/human-requests/req-107.md`
- Decisão: DEC-102
- Status: `complete`
- Data de fechamento: 2026-08-06

## Entrega

- **Instalador**: SSL/TLS verificado, checksum SHA-256 obrigatorio, trava de execução (`install.lock`), bloqueio de reinstalação, escrita literal no `.env` e remoção/destruição de resíduos pós-instalação (incluindo `installer.log`).
- **Servidor de Estáticos**: Proteção estrita contra Path Traversal textual (`..`, `\0`) e física via `realpath()` + `str_starts_with()` contra as raízes autorizadas (`assets/`, `contents/`, `modulos/`).
- **Criptografia & Sessões**: Centralização de gerador CSPRNG em `seguranca_token_aleatorio()` usando `random_bytes()` / `random_int` (no mínimo 128 bits de entropia) para IDs de sessão, cookies de verificação e `pubID` do OAuth2.
- **Proteção CSRF**: Ativação obrigatória de `gestor_csrf_validar()` nos formulários e requisições AJAX autenticados por cookie de sessão do painel, com injeção de tokens em HTML/meta e headers jQuery/fetch.
- **Cabeçalhos HTTP**: Emissão de `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: SAMEORIGIN` e `Strict-Transport-Security` em HTTPS, além de suporte a CSP `Report-Only`.
- **API Pública**: Restrição de CORS por allow-list configurável, obrigatoriedade de `Authorization: Bearer` (remoção de `?token=`) e rate limit persistido por usuário/rota na nova tabela `api_rate_limits`.
- **OAuth2**: Validação do HMAC `pubIDValidation` nos access tokens e rotação FIFO (revogação do token mais antigo ao atingir o limite de sessões).
- **Acesso a Dados & Saneamento**: Banco v1 legado marcado como obsoleto para novas implementações, mas preservado como padrão das áreas existentes; remoção do fallback inseguro `addslashes()` em `banco_escape_field()`, remoção de algoritmos/comentários mortos, preservação de APIs públicas legadas como shims deprecated e parametrização de Argon2id para `password_hash()`.

## Evidência automatizada

- `php -l`: 17 arquivos validados com sucesso.
- `node --check`: 2 scripts validados com sucesso.
- `git diff --check`: sem problemas de espaçamento/formatação.
- PHPUnit inicial: **181 testes, 732 asserções** com 100% de aprovação (incluindo a nova suíte `HardeningReq107Test.php`).
- Vitest inicial: **118 testes** com 100% de aprovação.

## Rodadas de homologação pós-entrega — 2026-08-06

### 1. Transição CSRF no autoatualizador

O deploy do Gestor substitui seus próprios arquivos antes de executar o banco. Em instalações que ainda não possuíam o cliente CSRF, a página permanecia aberta com o JavaScript antigo enquanto o backend passava imediatamente à versão nova. Foram reproduzidas três manifestações do mesmo problema:

- `finalize` retornando `403 Token CSRF inválido ou ausente`;
- polling infinito de `status` com o mesmo 403, mantendo a interface em 95%;
- `db` falhando logo depois de `deploy_files_done` na instância `photon` (SID `a94064ba170414e5`).

A primeira compatibilidade baseada apenas em `version_compare($_GESTOR['versao'], '2.9.25', '<=')` mostrou-se insuficiente: o `config.php` já continha a versão nova no primeiro request posterior ao deploy. A solução definitiva ficou vinculada à sessão real do atualizador:

- SID estritamente hexadecimal de 16 caracteres;
- arquivo `temp/atualizacoes/sessions/<sid>.json` existente, pequeno, com SID correspondente e `finished=false`;
- criação há no máximo seis horas, com tolerância defensiva de relógio;
- ação limitada a `deploy`, `db`, `finalize` ou `cancel`, conferida contra `progress.bootstrap`, `progress.deploy_files` e `progress.database`;
- ausência total de token recebido — token presente e inválido continua sendo rejeitado;
- sessões criadas pelo cliente novo recebem `opts.csrf-capable=1` e nunca usam a isenção;
- `status` legado permanece isento por ser consulta de leitura;
- reset global de sessões do banco adiado para depois do POST de `finalize`.

A versão do módulo `admin-atualizacoes` foi elevada de `1.0.2` até `1.0.4` durante as rodadas, garantindo que documentos novos carreguem o JavaScript atualizado.

### 2. Expiração de login em operações AJAX longas

Foi reproduzido um `401 {"error":"401","info":"JSON unauthorized"}` quando o login expirou entre as etapas do atualizador. O comportamento anterior deixava a interface apenas em estado de falha; o operador experiente recarregava a página e encontrava o login, mas o usuário comum não recebia orientação.

O core passou a distinguir autenticação ausente de falta de permissão:

- `gestor_permissao()` salva `redirecionar-local` também no caminho AJAX e responde com `code=AUTH_REQUIRED`, `redirect=signin/` e `X-Gestor-Auth-Redirect`;
- `global.js` observa erros globais do jQuery e respostas de `fetch`, valida mesma origem e faz somente um redirecionamento;
- o 401 por falta de permissão no módulo não contém o marcador e mantém o comportamento existente;
- a operação mutável que falhou não é repetida automaticamente após o login.

Cobertura nova em `tests/Unit/JS/global-auth-redirect.test.js`: jQuery marcado redireciona, 401 comum não redireciona e `fetch` marcado redireciona.

### 3. Performance e saneamento dos releases

A lentidão anormal do deploy foi correlacionada ao volume desnecessário dentro de `vendor/`: instalações Composer obtidas de source mantinham repositórios `.git` aninhados. Foram encontrados e removidos 21 diretórios, totalizando aproximadamente 99,8 MB de metadados. O código de runtime de `vendor/` foi preservado e `autoload.php`, Phinx e Dotenv continuaram disponíveis.

O empacotamento automático e o manual passaram a:

- instalar dependências Composer com `--prefer-dist`;
- remover recursivamente diretórios `.git` do staging;
- excluir `*.git*` também na criação do ZIP;
- abortar se ainda existir `.git` aninhado antes da publicação;
- remover resíduos de PHPUnit/Composer, testes, resources de build, logs e `.env` sensível sem retirar templates de exemplo;
- evitar que `gestor.zip`, checksum e ZIP do instalador sejam adicionados ao commit;
- produzir `gestor.zip.sha256` e publicar o release manual pela CLI quando o GitHub Actions estiver indisponível.

As tarefas do VS Code e os scripts `ai-workspace/en/scripts/releases/release*.sh` foram alinhados aos modos `automatic` e `manual`. O workflow continua usando `--prefer-dist`; isto reduz checkouts de source, enquanto a limpeza defensiva garante que metadados preexistentes nunca entrem no artefato.

### 4. Evidência final consolidada

- Tag de consolidação: `gestor-v2.9.30` (`01503cbd`).
- PHPUnit antes do adendo de compatibilidade: **184 testes, 745 asserções**, 4 skips preexistentes, zero falhas.
- Vitest: **140 testes**, 13 arquivos de teste, zero falhas.
- Testes focados da transição CSRF: 4 casos / 16 asserções aprovados.
- Estado real do `photon`: SID `a94064ba170414e5` em `deploy_files_done` aceito somente para `db` pela nova regra.
- `php -l`, `node --check` e `git diff --check`: aprovados.

### 5. Correção de compatibilidade da biblioteca de banco — 2026-08-07

`banco_smartstripslashes()` foi inicialmente removida porque o algoritmo de stripslashes estava integralmente comentado e o core havia substituído suas chamadas internas por cast explícito. A remoção da função pública, porém, quebrou consumidores externos ainda suportados: foram localizadas 13 chamadas nos módulos `snapphoton-system`, `oauth-client` e `busca-clinica` do projeto `lumix`.

Decisão corrigida:

- restaurar `banco_smartstripslashes($str)` em `banco.php` como shim de compatibilidade;
- manter exatamente o comportamento efetivo anterior, `return (string)$str`;
- não reativar o algoritmo comentado de manipulação de barras;
- marcar a função como `@deprecated`, orientando novas implementações a converter tipos explicitamente;
- elevar `biblioteca-banco` de `1.2.0` para `1.2.1`;
- adicionar teste de contrato para string com barras, `null` e valor numérico.

Esta rodada corrige a interpretação de “código morto”: implementação interna inativa pode ser removida, mas um símbolo público documentado exige auditoria de consumidores externos e ciclo de depreciação antes da remoção.

Validação após o adendo: **185 testes, 749 asserções**, 4 skips preexistentes e zero falhas. A restauração é posterior à tag `gestor-v2.9.30` e deve integrar o próximo release do Gestor.
