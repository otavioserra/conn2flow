# BL-018 — Segurança: achados da reescrita da documentação (req-181 e complementos)

- **Tipo**: Security
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: CRÍTICA (A1) a BAIXA; ver cada item
- **Origem**: leitura do código para a reescrita das docs (FEAT-014: req-179, req-182 a req-186), 2026-09-25/26
- **Proposta já escrita**: [req-181.md](../human-requests/req-181.md) (`proposed`, sem código alterado)

## Resumo

A req-181 descreve onze achados verificados no código, com onde, o quê, impacto e correção proposta:

| Item | Gravidade | Resumo |
|---|---|---|
| A1 | CRÍTICO | SQL injection na busca e na ordenação de `interface_listar_ajax()` |
| A2 | ALTO | Exclusão e troca de status por GET, sem CSRF |
| A3 | MÉDIO | Nome de coluna controlável em `verificar-campo`; valor escapado duas vezes |
| A4 | MÉDIO | Funções do núcleo sem escape (`gestor_variaveis_alterar`, `gestor_layout`, `variaveis.php`) |
| A5 | ALTO | Segundo fator sem limite de tentativas nem proteção de replay |
| A6 | MÉDIO | Upload de HTML/SVG servido inline no domínio do site |
| A7 | MÉDIO | Proteção contra sequestro de sessão contornável |
| A8 | BAIXO | Login social vincula conta pelo e-mail sem `email_verified` |
| A9 | MÉDIO | Senha SMTP gravada em log |
| A10 | BAIXO/MÉDIO | Prompts de IA alteráveis por qualquer usuário do editor |
| A11 | MÉDIO | Canal do módulo distribuído aceita reenvio (sem `timestamp`/`nonce`) |

## Complementos encontrados depois da req-181 (ondas 2 e 3 das docs)

Documentados nas páginas de referência com `[!CAUTION]`/`[!WARNING]`:

1. **JWT** (`jwt.md`): `jwt_validate_token()` não confere `exp`, `nbf`, `iss` nem `aud`; a rotação por `AUTH_JWT_ROTATION_DAYS` não existe (só manual).
2. **OAuth2** (`oauth2.md`): o `scope` é gravado mas nenhum endpoint o confere; `iss` vem do `Host` da requisição.
3. **SQL concatenado em módulos**: `admin-ia` (ids numéricos em editar/testar/excluir/histórico), `admin-templates` (`admin_templates_alvo_ia()`), `forms-submissions` (`reply`: `form_id` e idioma).
4. **`html_finalizar()`** (`html.md`): `html_entity_decode()` no documento inteiro transforma texto escapado em HTML.
5. **Stripe modular** (`stripe.md`): `/_gateways/<modulo>/stripe/webhook` não valida a assinatura; depende de o módulo chamar `stripe_validar_webhook()`.
6. **IP atrás de CDN** (`ip.md`): `CF-Connecting-IP` não é lido; os limites por IP passam a valer para a borda da CDN inteira.
7. **Host desconhecido** (`global-variables.md`): uma requisição com `Host` fora da lista usa a configuração de outro domínio da instalação.

## Próxima ação

O Humano revisa item a item e decide quais promover (a req-181 pode ser aprovada inteira ou dividida).
