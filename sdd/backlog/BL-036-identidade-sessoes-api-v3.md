# BL-036 — Identidade, sessões, tokens e APIs na v3

- **Tipo:** Architecture/Security/Authentication/API
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** login web, sessão auxiliar, OAuth, 2FA, CSRF, webhooks e módulos distribuídos

## Regra de governança

Este backlog não autoriza trocar tokens, chaves ou cookies. Toda mudança criptográfica exige plano de coexistência, revogação, rotação de chaves e recuperação antes do cutover.

## Decisão consolidada

Em 2026-08-07 foi aprovada definitivamente para a v3 a sessão web com token opaco server-side. O painel administrativo não usará JWT como cookie de sessão. JWT também não será o default de nenhum outro canal: só poderá ser adotado por ADR própria quando houver necessidade concreta de interoperabilidade/claims verificáveis fora do servidor.

Permanecem pendentes o contrato detalhado, a janela de coexistência, a rotação de segredos e a promoção para requisição humana. Esta decisão fecha a alternativa arquitetural, mas não autoriza implementação nem alteração da linha 2.9.x.

Terminologia para evitar ambiguidade:

- **CSPRNG** é o gerador criptograficamente seguro usado para produzir valores aleatórios imprevisíveis;
- **JWT** é um formato de token contendo claims, assinado e/ou criptografado;
- **token de sessão opaco** é um valor aleatório sem claims interpretáveis pelo cliente;
- **token CSRF** prova que uma mutação autenticada por cookie partiu de uma página/cliente autorizado a ler o token.

Eles não são substitutos diretos. Um token de sessão opaco e um token CSRF podem ambos ser gerados por CSPRNG, mas têm valores, armazenamento, finalidade e validação separados.

## Diagnóstico

A req-030 e a req-107 já entregaram peças importantes: múltiplos métodos de login, 2FA, OAuth, rotação, tokens CSPRNG, HMAC adicional, cookies `Secure/HttpOnly/SameSite`, fingerprint de sessão, Bearer na API e fallback AJAX de login expirado.

Para a v3, essas peças devem ser reunidas em contratos separados de identidade:

- sessão web de navegador;
- access/refresh token OAuth;
- assinatura de webhook de provedor;
- credencial máquina-a-máquina/distribuída;
- identidade CLI/job.

Não se deve tratar todos como variações do mesmo token nem isentá-los genericamente da mesma proteção.

## Token web: decisão por opacidade

O cookie web atual contém um formato chamado JWT, mas usa criptografia RSA customizada (public encrypt/private decrypt) e consulta obrigatoriamente `usuarios_tokens` em todo request. Como não há benefício stateless, a arquitetura aprovada para a v3 é:

1. gerar no mínimo 256 bits aleatórios por CSPRNG para cada sessão;
2. colocar somente o bearer aleatório, sem claims, no cookie;
3. calcular `HMAC-SHA-256(server_pepper, bearer)` e persistir/indexar somente o digest, nunca o bearer bruto;
4. guardar server-side usuário, expiração absoluta/idle, revogação, instante de rotação, `security_version`, contexto de risco e metadados mínimos;
5. recalcular o digest apresentado, localizar a sessão ativa e usar comparação constante onde houver comparação em memória;
6. rotacionar bearer e digest atomicamente após autenticação e mudanças de privilégio;
7. falhar fechado se o segredo/pepper estiver ausente, fraco ou inválido;
8. nunca registrar cookie, bearer ou digest completo em logs, erros, métricas ou histórico.

O digest armazenado não é uma credencial substituta: apresentá-lo como cookie será novamente submetido ao HMAC e não localizará a sessão. Um dump somente da tabela não deve permitir reconstruir ou reutilizar o bearer.

### JWT fora da sessão web

- não haverá camada RSA adicional no cookie administrativo;
- o formato customizado atual será removido após a janela de coexistência;
- API/OAuth pode usar access token opaco e introspecção/revogação server-side;
- somente um consumidor distribuído que prove necessidade de validação offline/interoperável poderá propor JWT;
- nesse caso excepcional, usar JOSE padrão e biblioteca mantida: assinatura com chave privada, verificação com pública, algoritmo fixo, `iss`, `aud`, `sub`, `iat`, `nbf`, `exp`, `jti`, key ID e rotação;
- o caso excepcional não reutiliza automaticamente o cookie web nem reintroduz criptografia caseira.

### Modelo de ameaça aceito

- cookie/bearer roubado continua sendo utilizável até revogação; TLS, `HttpOnly`, CSP, CSRF, rotação e step-up reduzem esse risco;
- dump somente do banco não contém o bearer e não deve criar sessão válida;
- acesso somente ao pepper, sem bearer e sem registro ativo, também não deve criar sessão válida;
- compromisso simultâneo da aplicação/segredo e capacidade de escrever no banco excede a proteção do token e exige resposta a incidente, rotação global e auditoria;
- fingerprint de IP/User-Agent é sinal de risco, não segundo fator criptográfico.

## Ciclo de vida da sessão web

### Cookies

- preferir cookie host-only, sem atributo `Domain`;
- usar prefixo `__Host-` quando a instalação controlar o host e puder usar `Path=/`;
- instalações múltiplas no mesmo host devem ter nome de cookie estável e único por instância; se precisarem de `Path` no subdiretório, usar cookie host-only sem `__Host-`, pois o prefixo exige `Path=/`;
- `Secure`, `HttpOnly`, SameSite conforme fluxo e `Path` coerente com a estratégia de isolamento escolhida;
- não confiar no cookie de perfil como autorização; ele pode existir somente para otimização/UX ou ser removido;
- validar host/proxy a partir de configuração confiável, não de cabeçalho arbitrário.

### Rotação e timeouts

- novo ID após login, 2FA, step-up, troca de perfil, redefinição de senha e elevação;
- idle timeout e absolute timeout distintos;
- renewal timeout para sessões longas;
- logout revoga token, sessão auxiliar e refresh tokens conforme escopo escolhido;
- mudança de senha/perfil incrementa `security_version` para revogação rápida.

### Fingerprint

User-Agent e bloco de IP são sinais úteis, mas não prova de identidade. IP muda legitimamente e User-Agent é copiável. Na v3, usar como evento de risco para alertar, pedir step-up ou revogar conforme criticidade, com política compatível com IPv6/proxies confiáveis.

## CSRF v3

- synchronizer token vinculado à sessão autenticada;
- gerar novo token com a rotação de sessão;
- aplicar a toda mutação autenticada por cookie;
- proibir mutações em `GET/HEAD/OPTIONS`;
- validar `Origin`/`Referer` e Fetch Metadata como defesa em profundidade;
- declarar isenção por rota/autenticador, não por prefixo amplo;
- token Bearer sem cookie não usa CSRF, mas continua sujeito a CORS, scope, replay e rate limit;
- login, cadastro, recuperação e OAuth callbacks recebem política específica.

## OAuth/API

### Access tokens

- scopes de allowlist, concedidos pelo servidor conforme cliente + usuário + perfil;
- audience e client ID obrigatórios;
- access tokens curtos e refresh tokens rotativos/reutilização detectada;
- revogação por usuário, cliente e sessão;
- scopes aplicados em cada endpoint, não apenas transportados no token;
- capability/ABAC continua depois do scope: scope limita delegação, não substitui permissão do usuário.

### Endpoints administrativos

`system.update`, deploy, exportação de banco/conteúdo, instalação de plugins e gestão de usuários exigem:

- scope dedicado;
- capability do usuário/service account;
- cliente explicitamente autorizado;
- autenticação recente ou credencial de workload apropriada;
- rate limit específico;
- auditoria e idempotência/ordem de etapas.

### CORS

CORS não é autorização. Manter allowlist exata por ambiente, `Vary: Origin`, métodos/headers mínimos e ausência de credenciais cross-origin salvo requisito explícito.

## Webhooks e máquina-a-máquina

- verificar assinatura sobre bytes crus, timestamp e identificador único do evento;
- rejeitar replay fora da janela ou evento já processado;
- usar segredo/chave por integração e rotação com sobreposição curta;
- preferir mTLS ou credencial de workload para canais administrativos internos quando viável;
- autorizar ação depois de autenticar o emissor;
- nunca aceitar IP encaminhado sem cadeia de proxies confiáveis configurada.

## 2FA e step-up

2FA de login e autorização recente são conceitos diferentes. Ações críticas podem exigir reautenticação recente mesmo numa sessão 2FA antiga. Definir níveis de garantia e janela curta para:

- alterar senha/e-mail/2FA;
- atribuir perfis/capabilities;
- atualizar sistema/instalar plugin;
- exportar dados;
- revelar/rotacionar segredos e chaves API.

## Respostas e UX

- `401` para identidade ausente/inválida/expirada;
- `403` para identidade válida sem autorização ou CSRF inválido;
- `404` opcional para ocultar objeto fora do escopo, de forma consistente;
- código de erro estável e localizado no cliente via C2FI18n;
- AJAX recebe `AUTH_REQUIRED` somente para expiração real;
- não revelar se usuário, token ou recurso sensível existe além do necessário.

## Migração

1. inventariar tipos de token/cookie/cliente e consumidores privados;
2. documentar ameaça, tempo de vida, armazenamento, revogação e proprietário de cada credencial;
3. corrigir primeiro os gaps 2.9.x do BL-033;
4. definir schema da sessão opaca, pepper versionado, índices, cookies por instância e estratégia de rotação;
5. introduzir serviço v3 por trás de fachada compatível;
6. novos logins passam a emitir somente token opaco;
7. aceitar o formato legado apenas por janela curta e, após validação completa, trocá-lo uma única vez por sessão opaca ou exigir novo login conforme threat model aprovado;
8. não estender silenciosamente a validade do token legado durante a ponte;
9. medir consumidores/sessões legadas e encerrar aceitação em data/versão explícita;
10. revogar sessões remanescentes, rotacionar/arquivar chaves não usadas e retirar o parser RSA próprio;
11. migrar painel, app, scripts e módulos distribuídos separadamente;
12. testar logout global, rollback operacional, perda/rotação de pepper e recuperação antes do cutover.

## Critérios de aceite

- nenhum token customizado é chamado de JWT sem cumprir o padrão adotado;
- painel/sessão web não emite nem aceita JWT após o encerramento da coexistência;
- bearer bruto não é persistido nem registrado em logs;
- cópia isolada da tabela de sessões não permite autenticação ou reconstrução de bearer;
- cookie contendo o digest persistido é rejeitado;
- sessões rotacionam em toda mudança de privilégio;
- scopes são allowlisted e aplicados por endpoint;
- capability e resource policy são aplicadas depois do scope;
- CSRF nunca depende apenas da presença de cookie inválido;
- webhooks têm anti-replay;
- ações críticas exigem step-up definido;
- testes cobrem expiração, revogação, replay, rotação concorrente e logout global.

## Próxima ação

Promover uma ADR de implementação da decisão já tomada: contrato da sessão opaca, schema/digest, cookies por instância, pepper/rotação, coexistência com o formato legado e testes de comprometimento. Outros canais continuam exigindo ADR própria e não herdam JWT por padrão.
