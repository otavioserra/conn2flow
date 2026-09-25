# BATCH-180: Renovação Silenciosa de Token CSRF (Silent Refresh & Retry) e Recuperação Graciosa de Sessão no Core e `global.js`

Execução da [req-175](../human-requests/req-175.md).

---

## Atividades e Checklist

### 1. [x] Backend: Endpoint de Token CSRF e Código Padronizado de Resposta
- [x] Em `gestor/gestor.php`:
  - [x] Adicionar código de máquina `'code' => 'CSRF_INVALID_OR_EXPIRED'` em `gestor_csrf_resposta_invalida()` para respostas JSON de AJAX.
  - [x] Implementar rota de sistema `_gestor-csrf-token` no switch do roteador `gestor_roteador()` retornando JSON com token ativo (`gestor_csrf_token()`) e status de autenticação.
- [x] Em `gestor/bibliotecas/seguranca.php`:
  - [x] Garantir que a rota `_gestor-csrf-token` seja isenta de validação prévia de CSRF em `seguranca_csrf_rota_isenta()`.

### 2. [x] Frontend (`global.js`): Gerenciador de Silent Refresh, Fila de Retry e `visibilitychange`
- [x] Em `gestor/assets/global/global.js`:
  - [x] Implementar rotina de renovação de token CSRF (`renovarCsrf()`) com controle de concorrência e fila de requisições pendentes.
  - [x] Integrar interceptação e retry transparente nos três fluxos:
    - [x] `window.fetch`
    - [x] `window.jQuery.ajax` / `ajaxError` / `ajaxPrefilter` (coberto pelo envelope do XHR — ver nota 3)
    - [x] `XMLHttpRequest.prototype.send`
  - [x] Ao obter token atualizado:
    - [x] Atualizar `window.gestor.csrfToken`, tag `<meta name="csrf-token">` e `window.parent.gestor.csrfToken` (se em iframe).
    - [x] Reexecutar requisições enfileiradas com o novo token.
  - [x] Adicionar listener de `visibilitychange` no `document` com throttling para validação proativa de token ao retornar o foco à aba.
  - [x] Implementar fallback gracioso caso a sessão tenha expirado definitivamente (redirecionamento ou aviso não destrutivo).

### 3. [x] Versionamento de Assets e Recursos
- [x] Cache-bust em runtime. Não existe `global/global.json`: o token do asset é o owner `global` de `gestor/assets/asset-versions.json`, que foi regenerado (ver nota 4).

### 4. [x] Validação e Testes Automatizados
- [x] Criar testes unitários no PHPUnit (`tests/Unit/PHP/`):
  - [x] Testar endpoint `_gestor-csrf-token` e resposta padronizada com código `CSRF_INVALID_OR_EXPIRED`.
- [x] Criar testes no Vitest (`tests/Unit/JS/`):
  - [x] Cobrir interceptação de erro 403 CSRF, fila de retry, silent refresh e evento `visibilitychange`.
- [x] Executar suítes locais completas:
  - [x] `composer test` (PHPUnit completo).
  - [x] `npx vitest run` (Vitest completo).
  - [x] `git diff --check`.
- [ ] Homologar no ambiente Lab / runtime. **Pendente** (operador).

---

## Critérios de Aceite e Validação

1. **Renovação Transparente**: Requisições mutáveis com token expirado são retentadas e concluídas automaticamente após renovação silenciosa.
2. **Concorrência Segura**: Múltiplas requisições simultâneas falhas por CSRF agrupam-se em uma única chamada de renovação.
3. **Detecção Proativa**: Ao reativar uma aba ociosa (`visibilitychange`), o frontend atualiza o token antes da interação do usuário.
4. **Isolamento de Erros de Permissão**: Respostas 403 legítimas (ACL/perfil) não causam loops de renovação de CSRF.
5. **Zero Regressões**: Suíte de testes 100% verde com exit 0.

---

## Notas de Implementação

1. **Contrato do endpoint `_gestor-csrf-token/`** (decisão pura em `seguranca_csrf_token_resposta()`):
   - Sem cookie de autenticação (visitante): `200 {status:"success", token, authenticated:false}`.
   - Cookie presente e JWT válido: `200 {status:"success", token, authenticated:true}`.
   - Cookie presente e JWT recusado (login expirado): `401 {status:"error", code:"AUTH_EXPIRED", authenticated:false, redirect:"signin/"}` com `X-Gestor-Auth-Redirect` e **sem token**. O parâmetro `retorno` (caminho relativo, normalizado por `seguranca_csrf_retorno_normalizar()`) vira `redirecionar-local` na sessão, para o login devolver o usuário à mesma tela.
   - Sempre `Cache-Control: no-store`. A rota é atendida antes do porteiro do site restrito (req-163): ela só devolve o token da própria sessão, o mesmo já presente na `<meta>`.
2. **Marcação do 403 de CSRF**: além do `code` no JSON, `gestor_csrf_resposta_invalida()` emite `X-Gestor-Csrf-Error: CSRF_INVALID_OR_EXPIRED` nos DOIS ramos (JSON e HTML). `fetch`/XHR que não pedem JSON recebem a página HTML, e só o cabeçalho torna a detecção determinística nesse caso. Um 403 sem a marca (ACL, perfil, site restrito) nunca entra em retry.
3. **`$.ajax` coberto pelo XHR**: o jqXHR delega ao `XMLHttpRequest` nativo, então o retry acontece no envelope do prototype. Ouvintes de **captura** no próprio XHR disparam antes do `onload` do jQuery (ordem at-target do DOM) e seguram o 403. O mesmo objeto é reaberto e reenviado com cabeçalhos preservados e o token novo, e o jQuery só enxerga a resposta da repetição. O reenvio roda numa macrotask (`setTimeout 0`), para não reabrir o objeto entre o `readystatechange` e o `load` originais. Se a renovação falhar, os eventos seguros são redespachados e quem chamou recebe o 403 original. XHR síncrono fica fora.
4. **Cache-bust**: `php cli/c2f.php assets:minify` regenerou `global.min.js` (40,1 KB → 16,4 KB) e `minify-manifest.json`; `atualizacao-versoes-assets.php` (a mesma função que o `resources:sync` chama) atualizou o owner `global` `133f33a986ce3395` → `d71d5de76902860f` e o token `system`.
5. **Garantias do retry**: uma única repetição por requisição; só métodos mutáveis de mesma origem; corpo em `ReadableStream` fica fora; `_csrf_token` em `FormData`/`URLSearchParams`/string urlencoded é atualizado. Uma falha que chega depois de uma renovação já concluída reaproveita o token atual sem nova consulta.
6. **`visibilitychange`**: consulta só após ≥ 30 s oculta e ≥ 30 s desde a última renovação. Dentro de iframe de mesma origem, a página hospedeira faz a checagem. A checagem proativa **nunca redireciona**: com a sessão morta, quem leva ao login é a próxima ação do usuário, e um formulário meio preenchido não se perde só porque a aba ganhou foco.
7. **Expiração real do token (para a homologação)**: o CSRF não tem TTL próprio, é variável de sessão. O cookie de sessão expira `SESSION_LIFETIME` (padrão 10800 s) após a CRIAÇÃO e não é renovado. A linha em `sessoes` é varrida após o mesmo tempo sem acesso, de forma probabilística (1/51). Para reproduzir rápido: `SESSION_LIFETIME=120` no `.env` local, ou apagar o cookie de sessão no DevTools e disparar uma ação AJAX.

---

## Evidências (2026-09-25)

| Verificação | Resultado |
|---|---|
| `php -l` em `gestor/gestor.php` e `gestor/bibliotecas/seguranca.php` | sem erros |
| `node --check` em `global.js` e `global.min.js` | OK |
| PHPUnit focado `CsrfSilentRefreshReq175Test` | **15/15**, 62 asserções |
| Vitest focado `global-csrf-refresh.test.js` | **23/23** |
| Vitest regressão `global-csrf.test.js` + `global-auth-redirect.test.js` | **20/20** |
| `composer test` (PHPUnit completo, `OPENSSL_CONF` do PHP 8.5 explicitado) | **1.220/1.220**, 7.970 asserções, 4 skipped, exit 0 |
| `npx vitest run` (Vitest completo) | **31 arquivos, 449/449**, exit 0 |
| `git diff --check` (inclusive arquivos novos) | exit 0 |

Sem `OPENSSL_CONF`, o PHPUnit do Windows acusa só o `CoreHelpersTest::testCriptografiaBasicaComChavesRsa` (`openssl.cnf` ausente, pré-existente e independente do lote).

### Arquivos tocados

- `gestor/gestor.php`: marca da recusa CSRF, `gestor_roteador_csrf_token()` e `case SEGURANCA_CSRF_ROTA_TOKEN`.
- `gestor/bibliotecas/seguranca.php`: constantes, isenção, `seguranca_csrf_resposta_invalida_corpo()`, `seguranca_csrf_token_resposta()`, `seguranca_csrf_retorno_normalizar()`.
- `gestor/assets/global/global.js`, `global.min.js`, `gestor/assets/minify-manifest.json`, `gestor/assets/asset-versions.json`.
- `tests/Unit/PHP/CsrfSilentRefreshReq175Test.php` (novo), `tests/Unit/JS/global-csrf-refresh.test.js` (novo).

### Memória de execução

`c2f ai:prune-memories` acusou o teto obrigatório (28.476 B, 351 linhas). Poda com a skill `sdd-memory-gardening`: nenhuma tarefa removida (16 anteriores + BATCH-180 = 17), as entradas BATCH-155 a 161 e 164 foram condensadas e a regra do `OPENSSL_CONF` no Windows foi destilada para "Skills Core destiladas". Resultado: **18.669 B, 243 linhas**, faixa de alerta preventivo e abaixo do teto. `git diff --check` limpo. A memória de Chefia não foi tocada.

### Pendente

- Homologação runtime no Lab/local (renovação transparente em tela real após expirar a sessão). Nada foi commitado.

---

## Estado

implemented-pending-homologation
