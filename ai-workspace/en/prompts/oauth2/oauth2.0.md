````markdown
# OAuth 2.0 Project for Conn2Flow Manager

## Overview

This project aims to implement support for the OAuth 2.0 protocol in the Conn2Flow Manager system, allowing external systems to access protected Conn2Flow resources securely through OAuth 2.0 authentication. Conn2Flow will act as an OAuth 2.0 server, managing the issuance and validation of access tokens for external client applications.

## Objectives

- Allow external systems to access protected Conn2Flow resources via OAuth 2.0
- Implement basic operations: access token generation, token validation, and request authorization
- Integrate with the system's existing security infrastructure (JWT, RSA encryption, access control)
- Maintain compatibility with the Conn2Flow framework and architecture

## Scope

### Main Features

1. **Access Token Generation**
   - Support for Client Credentials flow (recommended for server applications)
   - Support for Authorization Code flow (for interactive scenarios)
   - Validation and secure storage of tokens

2. **Token Validation**
   - Verification of access token validity
   - Automatic renewal when necessary
   - Expiration and revocation control

3. **Request Authorization**
   - Middleware to validate tokens on protected endpoints
   - Permission control based on scopes
   - Handling of authentication/authorization errors

4. **Integration with Existing System**
   - Use of `autenticacao.php` library for cryptographic operations
   - Token storage in `oauth2_tokens` table
   - Rate limiting control via `acessos` table

### Supported OAuth 2.0 Flows

- **Client Credentials**: For machine-to-machine communication (first access via browser, JSON return for applications)
- **Authorization Code**: For interactive scenarios via browser
  - GET: Displays authentication form
  - POST: Processes credentials and returns token
  - Support for `url_redirect` for redirection after authentication

### Client Management

- **Simplification**: Use system user credentials (username/password) as client_id/client_secret
- **Validation**: Valid credentials in `usuarios` table with active status
- **Scopes**: Based on `usuarios_perfis` (profiles that define access modules)

### OAuth 2.0 Endpoints

- **GET/POST /oauth-authenticate/**: Main endpoint
  - GET: Displays authentication form (similar to signin)
  - POST: Processes credentials with `_gestor-autenticate`
  - Return: JSON with tokens or redirection if `url_redirect` defined

## Architecture

### File Structure

- `gestor/bibliotecas/oauth2.php`: Main library with all OAuth 2.0 functions
- `gestor/db/migrations/20251104113023_create_oauth2_tokens_table.php`: Migration for oauth2_tokens table
- `gestor/modulos/perfil-usuario/perfil-usuario.php`: OAuth Controller (function perfil_usuario_oauth_authenticate)
- `gestor/modulos/perfil-usuario/resources/pt-br/pages/oauth-authenticate/oauth-authenticate.html`: View for OAuth authentication
- `ai-workspace/prompts/oauth2/oauth2.0.md`: Project documentation (this file)

### Dependencies

- `autenticacao.php` library: For JWT and cryptographic operations
- Database tables: `acessos`, `oauth2_tokens`, `usuarios`
- PHP with OpenSSL and cURL extensions enabled

## Detailed Implementation

### 1. MVC Architecture

The OAuth 2.0 system will be implemented following Conn2Flow's existing MVC architecture, based on the `perfil-usuario` module:

#### Model
- **oauth2_tokens table**: OAuth 2.0 token storage
- Fields: id_oauth2_tokens, id_usuarios, pubID, pubIDValidation, expiration, ip, user_agent, origem, data_criacao, senha_incorreta_tentativas

#### View
- **oauth-authenticate.html**: Page for OAuth authentication (based on acessar-sistema.html)
- Form similar to login with fields: usuario, senha, url_redirect (optional)
- Support for GET (displays form) and POST (processes authentication)

#### Controller
- **perfil_usuario_oauth_authenticate()**: Main function in perfil-usuario module
- Based on `perfil_usuario_signin()` function
- Processes OAuth 2.0 requests:
  - GET: Displays form
  - POST with `_gestor-autenticate`: Validates credentials and returns tokens
- Return: JSON with access_token, refresh_token, etc., or redirection

### 2. oauth2.php Library Structure

```php
<?php
/**
 * OAuth 2.0 Library
 *
 * Implements OAuth 2.0 server for integration with external applications.
 *
 * @package Conn2Flow
 * @subpackage Libraries
 * @version 1.0.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-oauth2'] = Array(
    'versao' => '1.0.0',
);

// Main functions:
// - oauth2_gerar_token_client_credentials()
// - oauth2_validar_token()
// - oauth2_autorizar_requisicao()
// - oauth2_revogar_token()
// - oauth2_armazenar_token()
// - oauth2_recuperar_token()
?>
```

### 3. Main Functions

#### oauth2_gerar_token_client_credentials($params)
- Parameters: usuario, senha, grant_type, scope (optional), url_redirect (optional)
- Validates credentials in `usuarios` table (username/password as client_id/secret)
- Generates access_token and refresh_token using JWT
- Return: JSON with tokens and metadata or redirection
- Secure storage in oauth2_tokens table

#### oauth2_validar_token($params)
- Parameters: access_token
- Verifies validity, expiration, and integrity via JWT
- Return: user/client data or false if invalid

#### oauth2_autorizar_requisicao($params)
- Middleware for protected endpoints
- Validates Authorization: Bearer header
- Verifies scopes based on usuarios_perfis
- Return: authorized user data

#### oauth2_revogar_token($params)
- Removes tokens from oauth2_tokens table
- Support for revocation of access_token and refresh_token

#### oauth2_armazenar_token($params)
- Stores encrypted tokens in oauth2_tokens table
- Uses RSA encryption from autenticacao library

#### oauth2_recuperar_token($params)
- Recovers stored tokens and decrypts
- Validates expiration

### 4. Integration with Existing System

#### Use of autenticacao.php library
- `autenticacao_encriptar_chave_privada()` to store tokens securely
- `autenticacao_decriptar_chave_privada()` to recover tokens
- `autenticacao_gerar_jwt_chave_privada()` to generate JWT tokens

#### Integration with autenticacao.php
- Function `autenticacao_validar_jwt_chave_publica()` modified to accept optional parameter `retornarPayloadCompleto`
- Allows returning full JWT payload or just the pubID as needed
- Maintains compatibility with existing code that expects only pubID

#### Integration with API
The Conn2Flow API has been modified to use OAuth 2.0 authentication:

**Authentication Endpoint:**
- `/_api/oauth/`: Redirects to the OAuth authentication endpoint

**OAuth 2.0 Endpoints:**
- `/_api/oauth/refresh/`: Token renewal using refresh token (POST)
- Returns new access_token and refresh_token

**Authentication on Protected Endpoints:**
- Supported headers: `Authorization: Bearer <token>`, `X-API-Key: <token>`
- Full OAuth 2.0 validation with RSA signature verification
- Integrated rate limiting with access control
- Function `api_authenticate()` corrected to correctly validate OAuth tokens

**Available Endpoints:**
- `/_api/status/`: API Status (public)
- `/_api/health/`: Health check (public)
- `/_api/project-update/`: Project update (requires OAuth 2.0 authentication)
- `/_api/ia/*`: AI Endpoints (requires OAuth 2.0 authentication)

#### Use of Tables
- **oauth2_tokens**: Main storage for OAuth tokens
- **usuarios**: To validate client credentials (if applicable)
- **acessos**: Rate limiting control for OAuth requests

### 5. Error Handling and Security

- Rigorous validation of all parameters
- Standard OAuth 2.0 HTTP error handling (400, 401, 403)
- Detailed logging of OAuth operations
- Protection against brute force attacks
- Rate limiting based on client_id and IP
- Automatic token expiration

### 6. OAuth 2.0 Endpoints

- **POST /oauth/token**: Endpoint for obtaining tokens
- **POST /oauth/revoke**: Endpoint for token revocation
- **GET/POST /oauth/authorize**: Endpoint for authorization (authorization code flow)

### 7. Configuration

Add to config.php:
```php
$_CONFIG['oauth2'] = Array(
    'enabled' => true,
    'token_expiration' => 3600, // 1 hour
    'refresh_token_expiration' => 2592000, // 30 days
    'allowed_grant_types' => ['client_credentials', 'authorization_code'],
    'default_scope' => 'read',
    'max_attempts' => 5,
);
```

## Development Plan

## 🔐 Security Features

### Token Validation
- **Generation**: Uses RSA private key for JWT signature
- **Validation**: Uses RSA public key for verification (no password needed)
- **Double Validation**: JWT + HMAC Hash (pubIDValidation) for extra security

### Token Management
- **Automatic Cleanup**: Expired tokens are automatically removed when generating new tokens
- **Renewal**: Refresh tokens allow renewing access tokens without re-authentication
- **Revocation**: Tokens can be revoked individually
- **Custom Payload**: Support for custom JWT payloads with specific OAuth claims (scope, token_type, etc.)
- **Active Token Limit**: Maximum active tokens per user (configurable, default: 5)

### Renewal Flow
1. Access token expires
2. Client uses refresh token to obtain new tokens
3. System validates refresh token
4. Generates new access token + new refresh token
5. Invalidates previous refresh token

### Custom JWT Payload

The implementation supports custom JWT payloads to include specific OAuth 2.0 claims:

**Access Token Payload:**
```php
$access_payload = Array(
    'iss' => $_SERVER['HTTP_HOST'],           // Issuer
    'sub' => $id_usuarios,                    // Subject (User ID)
    'exp' => $access_token_expiration,        // Expiration
    'iat' => time(),                          // Issued at
    'token_type' => 'Bearer',                 // Token type
    'scope' => 'read write',                  // Allowed scopes
    'client_id' => $id_usuarios               // Client ID
);
```

**Refresh Token Payload:**
```php
$refresh_payload = Array(
    'iss' => $_SERVER['HTTP_HOST'],           // Issuer
    'sub' => $id_usuarios,                    // Subject (User ID)
    'exp' => $refresh_token_expiration,       // Expiration
    'iat' => time(),                          // Issued at
    'token_type' => 'refresh'                 // Token type
);
```

**Integration with autenticacao.php:**
- Function `autenticacao_gerar_jwt_chave_privada()` modified to accept optional `payload` parameter
- Custom payload replaces default payload when provided
- Maintains compatibility with existing calls

### Recent Fixes and Improvements

#### 1. Enhanced JWT Validation
- **Function `autenticacao_validar_jwt_chave_publica()`**: Added optional parameter `retornarPayloadCompleto`
- Allows returning full JWT payload for OAuth validations needing custom claims
- Maintains backward compatibility with existing code expecting only pubID

#### 2. Complete OAuth 2.0 API
- **Endpoint `/oauth/refresh/`**: Implemented for token renewal
- **Correction `api_authenticate()`**: Correct validation of OAuth tokens (removed incorrect 'valid' key check)
- **Endpoint `project-update`**: Tested and functional for integration validation

#### 3. Integration Tests
- ✅ OAuth 2.0 token generation and validation
- ✅ Token renewal via refresh token
- ✅ Authentication on protected API endpoints
- ✅ Active token limitation per user
- ✅ Custom JWT payload with specific OAuth claims

### Phase 1: Basic Implementation ✅ COMPLETE
- [x] Create oauth2.php library structure
- [x] Implement oauth2_gerar_token_client_credentials() function
- [x] Implement oauth2_validar_token() function
- [x] Create /oauth-authenticate/ endpoint in controller
- [x] Create oauth-authenticate.html view
- [x] Basic token generation and validation tests
- [x] **Custom JWT Payload**: Support for custom payloads in OAuth tokens (scope, token_type, etc.)

### Phase 2: Advanced Features ✅ COMPLETE
- [x] Implement active token limit per user (maximum 5 by default)
- [x] Modify API to use OAuth 2.0 authentication
- [x] Add /oauth/ endpoint for redirection
- [x] Integrate OAuth 2.0 validation in API
- [x] Integration tests with token limit

### Phase 3: Integration and Tests ✅ COMPLETE
- [x] Integrate with autenticacao.php library (parameter `retornarPayloadCompleto`)
- [x] Use oauth2_tokens table
- [x] Complete integration tests (project-update endpoint, refresh token)
- [x] Correction of `api_authenticate()` function for correct OAuth validation
- [x] Implementation of `/_api/oauth/refresh/` endpoint for token renewal
- [x] Update of environment.json with valid tokens
- [ ] Usage documentation for external developers

### Phase 4: Production
- [ ] Complete security validation
- [ ] Performance testing
- [ ] Deploy and monitoring
- [ ] OAuth 2.0 API documentation

## Technical Considerations

### Security
- All tokens stored encrypted with RSA
- Mandatory HTTPS usage
- SSL certificate validation
- Protection against token leakage and replay attacks
- Client secrets stored with secure hash

### Performance
- Valid token cache
- Optimized indices on oauth2_tokens table
- Database connection pooling
- Configurable timeout for operations

### Compatibility
- Compatible with OAuth 2.0 RFC 6749
- Support for PKCE (Proof Key for Code Exchange)
- Extensible for future OpenID Connect

## Risks and Mitigations

1. **Exposure of client_secret**: Mitigation - secure hash, periodic rotation
2. **Insufficient rate limiting**: Mitigation - robust implementation via acessos table
3. **RSA encryption dependency**: Mitigation - fallbacks to other algorithms
4. **Token expiration**: Mitigation - transparent renewal via refresh tokens

## Success Metrics

- Ability to generate valid tokens for external applications
- Correct token validation on protected endpoints
- Error rate < 1% in OAuth operations
- Response time < 200ms for token validation
- Support for at least 100 simultaneous clients

## Next Steps

**Phases 1, 2 and 3 completed successfully!** ✅

The complete OAuth 2.0 server implementation is functional and integrated:

- ✅ `oauth2.php` library with main functions
- ✅ Controller `perfil_usuario_oauth_authenticate()`
- ✅ View `oauth-authenticate.html`
- ✅ Migration `oauth2_tokens` table
- ✅ Automatic cleanup of expired tokens
- ✅ Token renewal via refresh token
- ✅ Individual token revocation
- ✅ Functional `/oauth-authenticate/` endpoint
- ✅ Support for Client Credentials flow
- ✅ JSON return and redirection
- ✅ **Custom JWT payload implemented**
- ✅ **Active token limit per user**
- ✅ **Complete API integration**
- ✅ **OAuth 2.0 authorization middleware**
- ✅ **Enhanced JWT validation with `retornarPayloadCompleto`**
- ✅ **Endpoint `/oauth/refresh/` for token renewal**
- ✅ **Correction of `api_authenticate()` function**
- ✅ **Successful integration tests**

**To continue:** Phase 4 (Production) can be started when necessary, including performance tests, complete API documentation, and production monitoring.

## 📚 Usage Guide for Developers

### Obtaining OAuth 2.0 Tokens

**1. Authentication via Web Interface:**
```
GET/POST http://localhost/instalador/oauth-authenticate/
```

POST Parameters:
- `usuario`: Username
- `senha`: User password
- `grant_type`: `client_credentials`
- `scope`: `read write` (optional)
- `_gestor-autenticate`: `1`

**2. Token Renewal:**
```bash
curl -X POST "http://localhost/instalador/_api/oauth/refresh/" \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "your_refresh_token_here"}'
```

### Using the API with OAuth 2.0

**Example of Authenticated Request:**
```bash
curl -X POST "http://localhost/instalador/_api/project-update/" \
  -H "Authorization: Bearer your_access_token_here" \
  -H "Content-Type: application/json" \
  -d '{"project_id": "123", "status": "updated"}'
```

**Supported Headers:**
- `Authorization: Bearer <token>`
- `X-API-Key: <token>`

### Available Endpoints

- `GET /_api/status/` - API Status (public)
- `POST /_api/oauth/refresh/` - Renew tokens
- `POST /_api/project-update/` - Update projects (authenticated)
- `POST /_api/ia/*` - AI Endpoints (authenticated)

### Response Structure

**Success:**
```json
{
  "status": "success",
  "message": "Operation performed successfully",
  "timestamp": "2025-11-04T16:15:17-03:00",
  "data": { ... }
}
```

**Error:**
```json
{
  "status": "error", 
  "message": "Error description",
  "timestamp": "2025-11-04T16:15:17-03:00"
}
```
````