# BL-005 — Hardening da API pública: CORS wildcard e token via query string

- **Tipo**: Security
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: MÉDIA
- **Origem**: análise de segurança 2026-07-31 (código real)
- **Componentes**: `controladores/api/api.php`

## Contexto observado

1. **CORS liberado para qualquer origem**: `header('Access-Control-Allow-Origin: *')` fixo no topo do controlador ([api.php:14](../../../gestor/controladores/api/api.php)). Combinado com respostas autenticadas por Bearer, qualquer site pode consumir a API do navegador do usuário.
2. **Token aceito na query string**: `api_authenticate()` lê o token de `Authorization`, `X-API-Key` **e** `$_GET['token']` ([api.php:88](../../../gestor/controladores/api/api.php)). Tokens em URL vazam em logs de servidor, histórico de proxy, Referer e histórico do navegador.
3. **Rate limit em arquivo local** (100 req/h/IP) via cache em disco ([api.php:27-66](../../../gestor/controladores/api/api.php)) — frágil em múltiplos servidores/containers e sujeito a corrida de arquivo.

## Proposta de melhoria (a validar)

1. Restringir `Access-Control-Allow-Origin` a uma allow-list configurável por `.env` (ecoar origem só se permitida); manter `*` apenas para endpoints realmente públicos e sem credencial.
2. Remover o suporte a `?token=`; aceitar somente header `Authorization: Bearer` (e `X-API-Key` se necessário para M2M).
3. Migrar o rate limit para armazenamento compartilhado (MySQL/Redis) com verdadeira janela deslizante e chave por usuário+rota, não só IP.

## Critérios de aceite (rascunho)

- Origens não permitidas não recebem cabeçalho CORS que habilite leitura autenticada.
- Nenhum caminho de autenticação aceita token em query string.
- Rate limit consistente entre instâncias.

> Item de backlog — não autorizado para implementação até promoção para `sdd/human-requests/`.
