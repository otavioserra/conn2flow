# BL-033 — Hardening de segurança imediato na linha 2.9.x

- **Tipo:** Security/Corrective Maintenance/Compatibility
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** correções pequenas e compatíveis que não devem esperar a reestruturação da v3
- **Origem:** revisão pós-req-107 do roteador, CRUD legado, sessão e API
- **Relacionado:** BL-040 para desacoplamento do probe de cookies e acesso anônimo

## Regra de governança

Este item registra achados e proposta de priorização. Não autoriza alteração de código. Cada correção deve ser promovida para uma requisição própria na linha 2.9.x, caracterizada por testes antes da mudança e posteriormente incorporada à `3.0.x`.

## Conclusão executiva

O hardening da req-107 melhorou de forma material o sistema: CSPRNG, CSRF para métodos mutáveis, contenção de caminhos, TLS/checksum de releases, headers HTTP, cookies protegidos, Bearer obrigatório na API, rate limit persistente e validação adicional de tokens. A revisão arquitetural encontrou, porém, lacunas que atravessam esses controles.

As duas primeiras são de prioridade máxima:

1. o CRUD legado ainda altera status e exclui registros por `GET`, portanto essas operações não passam pelo gate CSRF que protege apenas `POST`, `PUT`, `PATCH` e `DELETE`;
2. endpoints OAuth de alto impacto autenticam qualquer access token válido, mas não aplicam o `scope` retornado pela validação nem a permissão do perfil/módulo.

## Evidências do estado atual

### 1. Mutações por GET não cobertas pelo CSRF

- `gestor/bibliotecas/interface.php` lê o identificador de exclusão e de status quando `REQUEST_METHOD === GET`.
- `interface_excluir_finalizar()` e `interface_status_finalizar()` persistem a alteração sem exigir outro método.
- vários módulos geram URLs como `?opcao=excluir&id=...` e `?opcao=status&...`.
- `seguranca_csrf_requisicao_validar()` permite `GET`, como deve fazer para métodos HTTP seguros.

Resultado: um navegador autenticado pode ser induzido a visitar uma URL que altera dados. `SameSite=Lax` não resolve esse caso porque navegações GET de alto nível são justamente uma das situações compatíveis com Lax.

### 2. API autentica, mas não autoriza por capacidade

- `api_authenticate(true)` retorna `id_usuarios`, perfil e `scope`.
- os endpoints `/_api/project/update`, `/_api/project/recover`, `/_api/system/update` e o dispatcher de hooks apenas verificam se o token é válido.
- a busca repo-wide mostra que o `scope` OAuth é emitido e devolvido, mas não é usado como gate nesses endpoints.
- o fluxo de autorização OAuth aceita `scope` recebido da requisição e o persiste, sem uma allowlist de scopes por cliente/usuário observada nesses handlers.

Resultado: o token representa autenticação, mas não limita de maneira verificável a operação de deploy, exportação ou atualização do sistema.

### 3. Operação desconhecida faz fallback permissivo

`gestor_acesso($operacao, $modulo)` retorna a permissão ampla do módulo quando a operação informada não está cadastrada em `modulos_operacoes`. Um erro de digitação, dado de seed ausente ou divergência entre idiomas pode, assim, transformar uma permissão fina em permissão ampla.

O inventário atual encontrou 37 chamadas a `gestor_acesso()` concentradas em cinco módulos e apenas três capacidades funcionais no seed (duplicadas por idioma): `permissao-pagina` para dois módulos e `acesso-api` para `admin-environment`. A infraestrutura existe, mas ainda não é um contrato geral de CRUD.

### 4. CSRF é avaliado antes da autenticação efetiva

No bootstrap atual, a presença do cookie de autenticação é suficiente para exigir CSRF antes de `gestor_permissao_token()` validar se o login continua ativo. Um cookie vencido ou revogado pode, portanto, receber `403 CSRF` em vez de `401 AUTH_REQUIRED`, prejudicando o fallback de login implantado após a req-107.

### 5. Sessão auxiliar não é rotacionada no login

O login gera/renova o token de autenticação, mas mantém o identificador de `sessoes` que já existia no contexto anônimo. Como CSRF, redirects e marcadores de segurança vivem nessa sessão auxiliar, ela deve ser substituída após login, 2FA, elevação de privilégio, troca de perfil e recuperação de conta.

### 6. Rotas especiais fora do contrato explícito

- qualquer rota aceita o parâmetro `hotfix`, que interrompe o roteamento antes de autenticação e hoje apenas imprime uma mensagem;
- `admin-arquivos/emissao-teste/` está marcado como público e inclui o módulo administrativo, embora atualmente não dispare uma opção funcional;
- `sem_permissao` é um atributo das páginas localizadas no banco/JSON, misturando conteúdo traduzível com uma decisão de segurança.

Esses casos não equivalem automaticamente a exploração, mas ampliam o risco de uma mudança futura transformar um atalho inofensivo em bypass.

## Lotes corretivos propostos para 2.9.x

### S29-A — Remover mutações por GET

1. caracterizar todos os links e handlers de `status`, `excluir`, cancelamento e demais mudanças;
2. aceitar mutação somente por `POST`, `PUT`, `PATCH` ou `DELETE` conforme o contrato escolhido;
3. devolver `405 Method Not Allowed` para GET e métodos inesperados;
4. enviar token CSRF nos formulários/AJAX de confirmação;
5. manter GET apenas para abrir tela de confirmação sem alterar estado;
6. testar módulos core e overlays privados que gerem as URLs antigas.

### S29-B — Autorizar endpoints OAuth de alto impacto

1. inventariar clientes e tokens existentes antes de bloquear;
2. criar allowlist fechada de scopes, sem aceitar texto arbitrário pedido pelo cliente;
3. separar no mínimo `project.deploy`, `project.export`, `system.update` e capacidades de hooks de módulos;
4. vincular scope permitido ao cliente, usuário, perfil e endpoint;
5. exigir autorização no próprio endpoint depois da autenticação;
6. registrar uso e negação sem gravar tokens;
7. adotar rollout em modo observação apenas se necessário para descobrir clientes legítimos, com prazo explícito para enforcement.

### S29-C — Fechar fallbacks de autorização

1. registrar telemetria quando uma operação solicitada não existe;
2. corrigir seeds e consumidores encontrados;
3. mudar operação desconhecida para `deny`;
4. manter a consulta sem operação como verificação intencional de acesso amplo ao módulo;
5. criar testes negativos para typo, operação inativa, idioma ausente, plugin e perfil de host.

### S29-D — Ordenar autenticação e CSRF

Pipeline desejado para rota administrativa autenticada:

1. resolver a rota e seu contrato sem executar o módulo;
2. validar o mecanismo de autenticação declarado;
3. se inválido, retornar `401/AUTH_REQUIRED` sem tentar CSRF;
4. se válido e baseado em cookie, validar CSRF para mutações;
5. autorizar ação/recurso;
6. executar o handler.

Rotas públicas de login/cadastro devem ter política própria contra login CSRF e abuso, incluindo validação de origem e rate limit, sem reaproveitar implicitamente a regra de rotas autenticadas.

### S29-E — Rotacionar sessão em fronteiras de privilégio

Criar uma operação atômica que gere novo ID CSPRNG, mova apenas dados permitidos, invalide o ID anterior e gere novo CSRF. Aplicar após login/2FA, troca de perfil, elevação administrativa, redefinição de senha e reautorização sensível.

### S29-F — Remover/gatear exceções

- remover o parâmetro web `hotfix` ou limitá-lo a ambiente de desenvolvimento explicitamente configurado;
- revisar `admin-arquivos/emissao-teste/` e toda rota `sem_permissao` com módulo PHP;
- gerar inventário automatizado de rotas públicas e falhar CI quando uma nova rota pública não trouxer justificativa e testes.

### S29-G — Tornar o isolamento do DocumentRoot verificável

O modelo do instalador já separa o front controller público do `install_path` do Gestor. Transformar isso em requisito validado:

- recusar instalação quando `config.php`, `modulos/`, `controladores/`, `vendor/`, `logs/`, `temp/` ou `.env` ficarem publicamente servíveis;
- permitir PHP web somente no front controller público;
- adicionar probe de instalação que tente acessar diretamente arquivos sentinela e exija `403/404`;
- manter guards de include como defesa secundária, nunca como fronteira principal.

### S29-H — Corrigir o desafio global de cookies

Executar o BL-040 como requisito próprio: ausência de cookie deve representar usuário anônimo, não disparar redirect global. O teste de capacidade de cookie fica restrito ao lifecycle de login, sem liberar conteúdo privado para crawlers e sem alterar autorização por User-Agent.

## Critérios de aceite

- nenhuma operação de escrita do painel funciona por GET;
- todo endpoint OAuth não público possui escopo/capacidade explícita e teste negativo;
- operação desconhecida nunca herda acesso amplo;
- sessão vencida em AJAX retorna `AUTH_REQUIRED`, não falso erro de CSRF;
- o ID da sessão e o CSRF mudam no login/elevação;
- inventário de rotas públicas não possui exceção sem justificativa;
- acesso anônimo a página pública/protegida não entra no desafio de cookies antes de uma tentativa de sessão;
- uma instalação padrão prova que o backend está fora do DocumentRoot;
- correções são mescladas de 2.9.x para 3.0.x.

## Próxima ação

Promover primeiro S29-A e S29-B como requisições corretivas separadas. São mudanças menores que a arquitetura v3, mas fecham os riscos de maior impacto observados nesta revisão.
