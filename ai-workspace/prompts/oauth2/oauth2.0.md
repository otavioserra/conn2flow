# Projeto OAuth 2.0 para Conn2Flow Gestor

## Visão Geral

Este projeto visa implementar suporte ao protocolo OAuth 2.0 no sistema Conn2Flow Gestor, permitindo que sistemas externos acessem recursos protegidos do Conn2Flow de forma segura através de autenticação OAuth 2.0. O Conn2Flow atuará como servidor OAuth 2.0, gerenciando a emissão e validação de tokens de acesso para aplicações cliente externas.

## Objetivos

- Permitir que sistemas externos acessem recursos protegidos do Conn2Flow via OAuth 2.0
- Implementar operações básicas: geração de tokens de acesso, validação de tokens e autorização de requisições
- Integrar com a infraestrutura de segurança existente do sistema (JWT, criptografia RSA, controle de acessos)
- Manter compatibilidade com o framework e arquitetura do Conn2Flow

## Escopo

### Funcionalidades Principais

1. **Geração de Access Token**
   - Suporte ao fluxo Client Credentials (recomendado para aplicações servidor)
   - Suporte ao fluxo Authorization Code (para cenários interativos)
   - Validação e armazenamento seguro dos tokens

2. **Validação de Tokens**
   - Verificação de validade dos tokens de acesso
   - Renovação automática quando necessário
   - Controle de expiração e revogação

3. **Autorização de Requisições**
   - Middleware para validar tokens em endpoints protegidos
   - Controle de permissões baseado em escopos
   - Tratamento de erros de autenticação/autorização

4. **Integração com Sistema Existente**
   - Utilização da biblioteca `autenticacao.php` para operações criptográficas
   - Armazenamento de tokens na tabela `oauth2_tokens`
   - Controle de rate limiting via tabela `acessos`

### Fluxos OAuth 2.0 Suportados

- **Client Credentials**: Para comunicação máquina-a-máquina (primeiro acesso via navegador, retorno JSON para aplicações)
- **Authorization Code**: Para cenários interativos via navegador
  - GET: Exibe formulário de autenticação
  - POST: Processa credenciais e retorna token
  - Suporte a `url_redirect` para redirecionamento após autenticação

### Gerenciamento de Clientes

- **Simplificação**: Usar credenciais de usuário do sistema (usuario/senha) como client_id/client_secret
- **Validação**: Credenciais válidas na tabela `usuarios` com status ativo
- **Escopos**: Baseados em `usuarios_perfis` (perfis que definem módulos de acesso)

### Endpoints OAuth 2.0

- **GET/POST /oauth-authenticate/**: Endpoint principal
  - GET: Exibe formulário de autenticação (similar ao signin)
  - POST: Processa credenciais com `_gestor-autenticate`
  - Retorno: JSON com tokens ou redirecionamento se `url_redirect` definido

## Arquitetura

### Estrutura de Arquivos

- `gestor/bibliotecas/oauth2.php`: Biblioteca principal com todas as funções OAuth 2.0
- `gestor/db/migrations/20251104113023_create_oauth2_tokens_table.php`: Migração para tabela oauth2_tokens
- `gestor/modulos/perfil-usuario/perfil-usuario.php`: Controller OAuth (função perfil_usuario_oauth_authenticate)
- `gestor/modulos/perfil-usuario/resources/pt-br/pages/oauth-authenticate/oauth-authenticate.html`: View para autenticação OAuth
- `ai-workspace/prompts/oauth2/oauth2.0.md`: Documentação do projeto (este arquivo)

### Dependências

- Biblioteca `autenticacao.php`: Para operações JWT e criptográficas
- Tabelas de banco: `acessos`, `oauth2_tokens`, `usuarios`
- PHP com extensões OpenSSL e cURL habilitadas

## Implementação Detalhada

### 1. Arquitetura MVC

O sistema OAuth 2.0 será implementado seguindo a arquitetura MVC existente do Conn2Flow, baseado no módulo `perfil-usuario`:

#### Model
- **Tabela oauth2_tokens**: Armazenamento de tokens OAuth 2.0
- Campos: id_oauth2_tokens, id_usuarios, pubID, pubIDValidation, expiration, ip, user_agent, origem, data_criacao, senha_incorreta_tentativas

#### View
- **oauth-authenticate.html**: Página para autenticação OAuth (baseado em acessar-sistema.html)
- Formulário similar ao login com campos: usuario, senha, url_redirect (opcional)
- Suporte a GET (exibe formulário) e POST (processa autenticação)

#### Controller
- **perfil_usuario_oauth_authenticate()**: Função principal no módulo perfil-usuario
- Baseado na função `perfil_usuario_signin()`
- Processa requests OAuth 2.0:
  - GET: Exibe formulário
  - POST com `_gestor-autenticate`: Valida credenciais e retorna tokens
- Retorno: JSON com access_token, refresh_token, etc., ou redirecionamento

### 2. Estrutura da Biblioteca oauth2.php

```php
<?php
/**
 * Biblioteca OAuth 2.0
 *
 * Implementa servidor OAuth 2.0 para integração com aplicações externas.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-oauth2'] = Array(
    'versao' => '1.0.0',
);

// Funções principais:
// - oauth2_gerar_token_client_credentials()
// - oauth2_validar_token()
// - oauth2_autorizar_requisicao()
// - oauth2_revogar_token()
// - oauth2_armazenar_token()
// - oauth2_recuperar_token()
?>
```

### 3. Funções Principais

#### oauth2_gerar_token_client_credentials($params)
- Parâmetros: usuario, senha, grant_type, scope (opcional), url_redirect (opcional)
- Valida credenciais na tabela `usuarios` (usuario/senha como client_id/secret)
- Gera access_token e refresh_token usando JWT
- Retorno: JSON com tokens e metadados ou redirecionamento
- Armazenamento seguro na tabela oauth2_tokens

#### oauth2_validar_token($params)
- Parâmetros: access_token
- Verifica validade, expiração e integridade via JWT
- Retorno: dados do usuário/cliente ou false se inválido

#### oauth2_autorizar_requisicao($params)
- Middleware para endpoints protegidos
- Valida Authorization: Bearer header
- Verifica escopos baseado em usuarios_perfis
- Retorno: dados do usuário autorizado

#### oauth2_revogar_token($params)
- Remove tokens da tabela oauth2_tokens
- Suporte para revogação de access_token e refresh_token

#### oauth2_armazenar_token($params)
- Armazena tokens criptografados na tabela oauth2_tokens
- Utiliza criptografia RSA da biblioteca autenticacao

#### oauth2_recuperar_token($params)
- Recupera tokens armazenados e descriptografa
- Valida expiração

### 4. Integração com Sistema Existente

#### Utilização da biblioteca autenticacao.php
- `autenticacao_encriptar_chave_privada()` para armazenar tokens de forma segura
- `autenticacao_decriptar_chave_privada()` para recuperar tokens
- `autenticacao_gerar_jwt_chave_privada()` para gerar tokens JWT

#### Integração com autenticacao.php
- Função `autenticacao_validar_jwt_chave_publica()` modificada para aceitar parâmetro opcional `retornarPayloadCompleto`
- Permite retornar payload completo do JWT ou apenas o pubID conforme necessário
- Mantém compatibilidade com código existente que espera apenas o pubID

#### Integração com API
A API do Conn2Flow foi modificada para usar autenticação OAuth 2.0:

**Endpoint de Autenticação:**
- `/_api/oauth/`: Redireciona para o endpoint OAuth de autenticação

**Endpoints OAuth 2.0:**
- `/_api/oauth/refresh/`: Renovação de tokens usando refresh token (POST)
- Retorna novos access_token e refresh_token

**Autenticação em Endpoints Protegidos:**
- Headers suportados: `Authorization: Bearer <token>`, `X-API-Key: <token>`
- Validação OAuth 2.0 completa com verificação de assinatura RSA
- Rate limiting integrado com controle de acessos
- Função `api_authenticate()` corrigida para validar corretamente tokens OAuth

**Endpoints Disponíveis:**
- `/_api/status/`: Status da API (público)
- `/_api/health/`: Health check (público)
- `/_api/project-update/`: Atualização de projetos (requer autenticação OAuth 2.0)
- `/_api/ia/*`: Endpoints de IA (requer autenticação OAuth 2.0)

#### Utilização das Tabelas
- **oauth2_tokens**: Armazenamento principal de tokens OAuth
- **usuarios**: Para validar credenciais de clientes (se aplicável)
- **acessos**: Controle de rate limiting para requests OAuth

### 5. Tratamento de Erros e Segurança

- Validação rigorosa de todos os parâmetros
- Tratamento de erros HTTP padrão OAuth 2.0 (400, 401, 403)
- Logging detalhado de operações OAuth
- Proteção contra ataques de força bruta
- Rate limiting baseado em client_id e IP
- Expiração automática de tokens

### 6. Endpoints OAuth 2.0

- **POST /oauth/token**: Endpoint para obtenção de tokens
- **POST /oauth/revoke**: Endpoint para revogação de tokens
- **GET/POST /oauth/authorize**: Endpoint para autorização (fluxo authorization code)

### 7. Configuração

Adicionar ao config.php:
```php
$_CONFIG['oauth2'] = Array(
    'enabled' => true,
    'token_expiration' => 3600, // 1 hora
    'refresh_token_expiration' => 2592000, // 30 dias
    'allowed_grant_types' => ['client_credentials', 'authorization_code'],
    'default_scope' => 'read',
    'max_attempts' => 5,
);
```

## Plano de Desenvolvimento

## 🔐 Funcionalidades de Segurança

### Validação de Tokens
- **Geração**: Usa chave privada RSA para assinatura JWT
- **Validação**: Usa chave pública RSA para verificação (sem necessidade de senha)
- **Validação**: Usa chave pública RSA para verificação (sem necessidade de senha)
- **Validação Dupla**: JWT + Hash HMAC (pubIDValidation) para segurança extra

### Gerenciamento de Tokens
- **Limpeza Automática**: Tokens expirados são removidos automaticamente na geração de novos tokens
- **Renovação**: Refresh tokens permitem renovar access tokens sem reautenticação
- **Revogação**: Tokens podem ser revogados individualmente
- **Payload Customizado**: Suporte a payloads JWT customizados com claims OAuth específicos (scope, token_type, etc.)
- **Limite de Tokens Ativos**: Máximo de tokens ativos por usuário (configurável, padrão: 5)
- **Payload Customizado**: Suporte a payloads JWT customizados com claims OAuth específicos (scope, token_type, etc.)

### Fluxo de Renovação
1. Access token expira
2. Cliente usa refresh token para obter novos tokens
3. Sistema valida refresh token
4. Gera novo access token + novo refresh token
5. Invalida refresh token anterior

### Payload Customizado JWT

A implementação suporta payloads JWT customizados para incluir claims específicos do OAuth 2.0:

**Access Token Payload:**
```php
$access_payload = Array(
    'iss' => $_SERVER['HTTP_HOST'],           // Emissor
    'sub' => $id_usuarios,                    // Assunto (ID do usuário)
    'exp' => $access_token_expiration,        // Expiração
    'iat' => time(),                          // Emitido em
    'token_type' => 'Bearer',                 // Tipo do token
    'scope' => 'read write',                  // Escopos permitidos
    'client_id' => $id_usuarios               // ID do cliente
);
```

**Refresh Token Payload:**
```php
$refresh_payload = Array(
    'iss' => $_SERVER['HTTP_HOST'],           // Emissor
    'sub' => $id_usuarios,                    // Assunto (ID do usuário)
    'exp' => $refresh_token_expiration,       // Expiração
    'iat' => time(),                          // Emitido em
    'token_type' => 'refresh'                 // Tipo do token
);
```

**Integração com autenticacao.php:**
- Função `autenticacao_gerar_jwt_chave_privada()` modificada para aceitar parâmetro `payload` opcional
- Payload customizado substitui o payload padrão quando fornecido
- Mantém compatibilidade com chamadas existentes

### Correções e Melhorias Recentes

#### 1. Validação JWT Aprimorada
- **Função `autenticacao_validar_jwt_chave_publica()`**: Adicionado parâmetro opcional `retornarPayloadCompleto`
- Permite retornar payload completo do JWT para validações OAuth que precisam de claims customizados
- Mantém compatibilidade backward com código existente que espera apenas pubID

#### 2. API OAuth 2.0 Completa
- **Endpoint `/oauth/refresh/`**: Implementado para renovação de tokens
- **Correção `api_authenticate()`**: Validação correta de tokens OAuth (removida verificação incorreta de chave 'valid')
- **Endpoint `project-update`**: Testado e funcional para validação de integração

#### 3. Testes de Integração
- ✅ Geração e validação de tokens OAuth 2.0
- ✅ Renovação de tokens via refresh token
- ✅ Autenticação em endpoints protegidos da API
- ✅ Limitação de tokens ativos por usuário
- ✅ Payload customizado JWT com claims OAuth específicos

### Fase 1: Implementação Básica ✅ COMPLETA
- [x] Criar estrutura da biblioteca oauth2.php
- [x] Implementar função oauth2_gerar_token_client_credentials()
- [x] Implementar função oauth2_validar_token()
- [x] Criar endpoint /oauth-authenticate/ no controller
- [x] Criar view oauth-authenticate.html
- [x] Testes básicos de geração e validação de tokens
- [x] **Payload Customizado JWT**: Suporte a payloads customizados nos tokens OAuth (scope, token_type, etc.)

### Fase 2: Funcionalidades Avançadas ✅ COMPLETA
- [x] Implementar limite de tokens ativos por usuário (máximo 5 por padrão)
- [x] Modificar API para usar autenticação OAuth 2.0
- [x] Adicionar endpoint /oauth/ para redirecionamento
- [x] Integrar validação OAuth 2.0 na API
- [x] Testes de integração com limite de tokens

### Fase 3: Integração e Testes ✅ COMPLETA
- [x] Integrar com biblioteca autenticacao.php (parâmetro `retornarPayloadCompleto`)
- [x] Utilizar tabela oauth2_tokens
- [x] Testes de integração completos (endpoint project-update, refresh token)
- [x] Correção da função `api_authenticate()` para validação OAuth correta
- [x] Implementação do endpoint `/_api/oauth/refresh/` para renovação de tokens
- [x] Atualização do environment.json com tokens válidos
- [ ] Documentação de uso para desenvolvedores externos

### Fase 4: Produção
- [ ] Validação de segurança completa
- [ ] Performance testing
- [ ] Deploy e monitoramento
- [ ] Documentação da API OAuth 2.0

## Considerações Técnicas

### Segurança
- Todos os tokens armazenados criptografados com RSA
- Utilização de HTTPS obrigatório
- Validação de certificados SSL
- Proteção contra token leakage e replay attacks
- Client secrets armazenados com hash seguro

### Performance
- Cache de tokens válidos
- Índices otimizados na tabela oauth2_tokens
- Connection pooling para banco de dados
- Timeout configurável para operações

### Compatibilidade
- Compatível com OAuth 2.0 RFC 6749
- Suporte a PKCE (Proof Key for Code Exchange)
- Extensível para OpenID Connect futuro

## Riscos e Mitigações

1. **Exposição de client_secret**: Mitigação - hash seguro, rotação periódica
2. **Rate limiting insuficiente**: Mitigação - implementação robusta via tabela acessos
3. **Dependência de criptografia RSA**: Mitigação - fallbacks para outros algoritmos
4. **Expiracão de tokens**: Mitigação - renovação transparente via refresh tokens

## Métricas de Sucesso

- Capacidade de gerar tokens válidos para aplicações externas
- Validação correta de tokens em endpoints protegidos
- Taxa de erro < 1% em operações OAuth
- Tempo de resposta < 200ms para validação de tokens
- Suporte a pelo menos 100 clients simultâneos

## Próximos Passos

**Fases 1, 2 e 3 concluídas com sucesso!** ✅

A implementação completa do servidor OAuth 2.0 está funcional e integrada:

- ✅ Biblioteca `oauth2.php` com funções principais
- ✅ Controller `perfil_usuario_oauth_authenticate()`
- ✅ View `oauth-authenticate.html`
- ✅ Migração `oauth2_tokens` table
- ✅ Limpeza automática de tokens expirados
- ✅ Renovação de tokens via refresh token
- ✅ Revogação de tokens individuais
- ✅ Endpoint `/oauth-authenticate/` funcional
- ✅ Suporte a Client Credentials flow
- ✅ Retorno JSON e redirecionamento
- ✅ **Payload customizado JWT implementado**
- ✅ **Limite de tokens ativos por usuário**
- ✅ **Integração completa com API**
- ✅ **Middleware de autorização OAuth 2.0**
- ✅ **Validação JWT aprimorada com `retornarPayloadCompleto`**
- ✅ **Endpoint `/oauth/refresh/` para renovação de tokens**
- ✅ **Correção da função `api_authenticate()`**
- ✅ **Testes de integração bem-sucedidos**

**Para continuar:** A Fase 4 (Produção) pode ser iniciada quando necessário, incluindo testes de performance, documentação completa da API e monitoramento em produção.

## 📚 Guia de Uso para Desenvolvedores

### Obtendo Tokens OAuth 2.0

**1. Autenticação via Interface Web:**
```
GET/POST http://localhost/instalador/oauth-authenticate/
```

Parâmetros POST:
- `usuario`: Nome do usuário
- `senha`: Senha do usuário  
- `grant_type`: `client_credentials`
- `scope`: `read write` (opcional)
- `_gestor-autenticate`: `1`

**2. Renovação de Tokens:**
```bash
curl -X POST "http://localhost/instalador/_api/oauth/refresh/" \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "your_refresh_token_here"}'
```

### Usando a API com OAuth 2.0

**Exemplo de Requisição Autenticada:**
```bash
curl -X POST "http://localhost/instalador/_api/project-update/" \
  -H "Authorization: Bearer your_access_token_here" \
  -H "Content-Type: application/json" \
  -d '{"project_id": "123", "status": "updated"}'
```

**Headers Suportados:**
- `Authorization: Bearer <token>`
- `X-API-Key: <token>`

### Endpoints Disponíveis

- `GET /_api/status/` - Status da API (público)
- `POST /_api/oauth/refresh/` - Renovar tokens
- `POST /_api/project-update/` - Atualizar projetos (autenticado)
- `POST /_api/ia/*` - Endpoints de IA (autenticado)

### Estrutura de Resposta

**Sucesso:**
```json
{
  "status": "success",
  "message": "Operação realizada com sucesso",
  "timestamp": "2025-11-04T16:15:17-03:00",
  "data": { ... }
}
```

**Erro:**
```json
{
  "status": "error", 
  "message": "Descrição do erro",
  "timestamp": "2025-11-04T16:15:17-03:00"
}
```