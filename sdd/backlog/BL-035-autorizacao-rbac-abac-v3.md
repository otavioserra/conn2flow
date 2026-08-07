# BL-035 — Autorização v3: RBAC, capacidades e contexto de recurso

- **Tipo:** Architecture/Security/Data Model
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** perfis, módulos, operações, hosts/projetos e autorização em nível de objeto

## Regra de governança

Este item não autoriza alteração das tabelas nem do comportamento atual. O novo modelo precisa de dicionário, ADR, migração reversível e matriz de paridade antes de ser implementado.

## Conclusão

O modelo atual por perfil e módulo é uma base RBAC válida e deve ser preservado como primeiro nível. Ele não é suficiente sozinho para a v3 porque “pode entrar no módulo” não responde necessariamente:

- qual ação pode executar;
- sobre qual registro, host, tenant, projeto ou proprietário;
- em qual estado do recurso;
- por qual canal (painel, API, automação);
- se a operação crítica exige autenticação recente/2FA.

A proposta é uma composição simples:

```text
RBAC: perfil permite capacidade
  + ABAC/ReBAC: sujeito + recurso + relação + contexto satisfazem política
  = decisão final allow/deny
```

Não se recomenda substituir tudo por ABAC genérico. Perfis continuam fáceis de administrar; atributos entram apenas onde módulo/ação não bastam.

## Diagnóstico do modelo atual

### Pontos positivos

- tabelas separam perfil, módulo e operações;
- o backend consulta a permissão, não depende de menu oculto;
- existe distinção para perfis de gestores ligados a host;
- capacidades especiais já são possíveis por `modulos_operacoes`.

### Lacunas

- o gate principal valida módulo, enquanto operações são chamadas manualmente por poucos módulos;
- operação não cadastrada herda acesso do módulo;
- CRUD genérico não exige `create/read/update/delete/status` distintos;
- `gestor_permissao_modulo()` e `gestor_acesso()` resolvem perfil/host por caminhos diferentes, permitindo decisões inconsistentes;
- autorização de API não reutiliza de forma explícita as permissões do usuário;
- consultas/mutações genéricas não têm contrato obrigatório de escopo por host/tenant/owner;
- a policy é reconstruída por SQL procedural e depende de strings/idioma;
- não há evidência de auditoria central de mudança de perfil/permissões e de negações.

## Modelo conceitual proposto

### Principal

Identidade autenticada normalizada:

- `user_id`;
- `account/host/tenant_id`;
- perfis e grupos ativos;
- tipo de cliente e `client_id`;
- scopes/capacidades delegadas;
- nível/instante da autenticação (senha, OAuth, 2FA, step-up);
- status e versão de segurança do usuário/perfil.

### Capability

Identificador técnico estável e em inglês, por exemplo:

- `pages.read`, `pages.create`, `pages.update`, `pages.delete`, `pages.publish`;
- `files.upload`, `files.rename`, `files.delete`;
- `users.manage`, `roles.assign`;
- `system.update`, `plugins.install`, `project.export`.

O acesso ao módulo pode virar uma capability agregadora de navegação (`module.pages.access`), mas não concede automaticamente todas as mutações.

### Resource

Objeto mínimo carregado antes da decisão:

- tipo e ID;
- host/tenant/projeto;
- owner/creator;
- status/workflow;
- classificação/sensibilidade;
- relações relevantes.

### Policy

Regra determinística e testável, por exemplo:

```text
allow pages.update when
  principal has pages.update
  and resource.host_id == principal.host_id
  and resource.status != locked
```

## Regras obrigatórias

1. deny by default;
2. capability desconhecida ou inativa nega;
3. cada request valida autorização, inclusive AJAX, chamada interna e API;
4. IDs previsíveis nunca substituem autorização do objeto;
5. filtro de listagem e check de item usam a mesma policy de escopo;
6. ocultar botão é UX, nunca controle suficiente;
7. mudança de perfil/capability invalida cache e sessões/tokens afetados;
8. ações administrativas críticas exigem step-up e auditoria;
9. plugins não podem inventar capabilities em runtime sem manifesto/migração aprovada;
10. idioma traduz somente nome/descrição, nunca o ID técnico da policy.

## Persistência a estudar

O desenho físico será fechado junto do BL-024, mas deve representar:

- roles/perfis;
- capabilities estáveis;
- grants `role -> capability`;
- assignments `principal -> role` com escopo opcional;
- constraints por tenant/host/relação;
- versão/revisão da policy para invalidação;
- trilha de quem concedeu/revogou, quando e por quê.

Evitar listas JSON opacas como fonte canônica quando consultas, integridade referencial e auditoria forem necessárias.

## Compatibilidade com o modelo de hosts

Antes de migrar, produzir uma tabela de decisão única para:

- usuário raiz sem host;
- dono do host;
- usuário filho que herda perfil do dono;
- usuário filho com `gestor_perfil` próprio;
- administrador do host;
- usuário suspenso/inativo;
- acesso vindo de OAuth ou módulo distribuído.

O resolvedor de principal deve executar essa regra uma vez por request. `gestor_permissao_modulo()` e `gestor_acesso()` passam a consumir o mesmo resultado, eliminando duplicação semântica.

## Autorização no ponto de ação

O roteador valida que a rota exige `pages.update`; o serviço que altera a página valida a mesma capability sobre o recurso carregado. Isso não é duplicação acidental:

- o roteador impede entrada externa indevida;
- o serviço impede bypass por hook, cron, controller interno ou reutilização futura.

O serviço recebe `Principal` e `ResourceContext`; não lê cookies, `$_REQUEST` ou perfil global diretamente.

## Migração proposta

1. inventariar módulos, opções, handlers AJAX/API e efeitos de banco/filesystem;
2. gerar matriz `perfil x módulo x ação x escopo` do comportamento real;
3. cadastrar capabilities equivalentes sem enforcement;
4. rodar decisão nova em modo sombra e comparar com a antiga, sem registrar dados sensíveis;
5. corrigir divergências e fallbacks;
6. aplicar no piloto `admin-paginas-v2` incluindo ownership/host;
7. migrar ações críticas (`system.update`, plugins, usuários/perfis, exportação, uploads);
8. migrar CRUD comum por ondas;
9. remover fallback amplo e código duplicado;
10. migrar overlays privados usando o mesmo catálogo de capabilities.

## Testes obrigatórios

- matriz positiva e negativa por perfil;
- operação desconhecida/inativa;
- cross-host/cross-tenant e IDOR/BOLA;
- usuário desativado ou perfil alterado durante sessão;
- listagem não revela objeto que a leitura individual nega;
- OAuth com scope insuficiente;
- hook/chamada interna sem contexto autorizado;
- concorrência/cache de policy;
- tentativa de atribuir ao próprio perfil uma capability superior;
- paridade entre idiomas e bancos MySQL/PostgreSQL.

## Critérios de aceite

- todo efeito sensível possui capability explícita;
- toda capability desconhecida nega;
- um resolvedor único determina perfil/host/tenant;
- filtros de banco aplicam escopo de recurso sem SQL textual de request;
- painel, API e chamadas internas produzem a mesma decisão para o mesmo principal/ação/recurso;
- mudança de permissão tem auditoria e revogação previsível;
- a matriz do piloto passa integralmente antes da primeira onda.

## Próxima ação

Promover um spike de inventário e matriz de autorização, começando pelo `admin-paginas-v2`, `admin-atualizacoes`, `admin-plugins`, `usuarios-perfis`, `admin-arquivos` e endpoints `project/system` da API.
