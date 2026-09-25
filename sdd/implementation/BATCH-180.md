# BATCH-180: Renovação Silenciosa de Token CSRF (Silent Refresh & Retry) e Recuperação Graciosa de Sessão no Core e `global.js`

Execução da [req-175](../human-requests/req-175.md).

---

## Atividades e Checklist

### 1. [ ] Backend: Endpoint de Token CSRF e Código Padronizado de Resposta
- [ ] Em `gestor/gestor.php`:
  - [ ] Adicionar código de máquina `'code' => 'CSRF_INVALID_OR_EXPIRED'` em `gestor_csrf_resposta_invalida()` para respostas JSON de AJAX.
  - [ ] Implementar rota de sistema `_gestor-csrf-token` no switch do roteador `gestor_roteador()` retornando JSON com token ativo (`gestor_csrf_token()`) e status de autenticação.
- [ ] Em `gestor/bibliotecas/seguranca.php`:
  - [ ] Garantir que a rota `_gestor-csrf-token` seja isenta de validação prévia de CSRF em `seguranca_csrf_rota_isenta()`.

### 2. [ ] Frontend (`global.js`): Gerenciador de Silent Refresh, Fila de Retry e `visibilitychange`
- [ ] Em `gestor/assets/global/global.js`:
  - [ ] Implementar rotina de renovação de token CSRF (`renovarCsrf()`) com controle de concorrência e fila de requisições pendentes.
  - [ ] Integrar interceptação e retry transparente nos três fluxos:
    - [ ] `window.fetch`
    - [ ] `window.jQuery.ajax` / `ajaxError` / `ajaxPrefilter`
    - [ ] `XMLHttpRequest.prototype.send`
  - [ ] Ao obter token atualizado:
    - [ ] Atualizar `window.gestor.csrfToken`, tag `<meta name="csrf-token">` e `window.parent.gestor.csrfToken` (se em iframe).
    - [ ] Reexecutar requisições enfileiradas com o novo token.
  - [ ] Adicionar listener de `visibilitychange` no `document` com throttling para validação proativa de token ao retornar o foco à aba.
  - [ ] Implementar fallback gracioso caso a sessão tenha expirado definitivamente (redirecionamento ou aviso não destrutivo).

### 3. [ ] Versionamento de Assets e Recursos
- [ ] Incrementar a versão no metadado do asset `gestor/assets/global/global.json` (ou correspondente) para garantir cache-bust em runtime.

### 4. [ ] Validação e Testes Automatizados
- [ ] Criar testes unitários no PHPUnit (`tests/Unit/PHP/`):
  - [ ] Testar endpoint `_gestor-csrf-token` e resposta padronizada com código `CSRF_INVALID_OR_EXPIRED`.
- [ ] Criar testes no Vitest (`tests/Unit/JS/`):
  - [ ] Cobrir interceptação de erro 403 CSRF, fila de retry, silent refresh e evento `visibilitychange`.
- [ ] Executar suítes locais completas:
  - [ ] `composer test` (PHPUnit completo).
  - [ ] `npx vitest run` (Vitest completo).
  - [ ] `git diff --check`.
- [ ] Homologar no ambiente Lab / runtime.

---

## Critérios de Aceite e Validação

1. **Renovação Transparente**: Requisições mutáveis com token expirado são retentadas e concluídas automaticamente após renovação silenciosa.
2. **Concorrência Segura**: Múltiplas requisições simultâneas falhas por CSRF agrupam-se em uma única chamada de renovação.
3. **Detecção Proativa**: Ao reativar uma aba ociosa (`visibilitychange`), o frontend atualiza o token antes da interação do usuário.
4. **Isolamento de Erros de Permissão**: Respostas 403 legítimas (ACL/perfil) não causam loops de renovação de CSRF.
5. **Zero Regressões**: Suíte de testes 100% verde com exit 0.

---

## Estado

ready-for-intake
