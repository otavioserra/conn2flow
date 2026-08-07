# BL-038 — Migração arquitetural do legado procedural para orientação a objetos

- **Tipo:** Epic/Architecture/Maintainability/Migration
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** `gestor`, `gestor-instalador` e contratos públicos consumidos pelos overlays
- **Relacionados:** BL-011, BL-012, BL-013, BL-015, BL-020, BL-023, BL-031, BL-034, BL-035, BL-037, BL-039

## Interpretação da decisão

O objetivo é sair gradualmente da infraestrutura procedural herdada do BitMaker e fazer a v3 nascer orientada a objetos. Classes, interfaces, composição, tipos e outros recursos modernos do PHP serão usados para explicitar responsabilidades e dependências. Não se trata de converter cada função mecanicamente em método nem de criar uma grande árvore de herança.

O código procedural continuará existindo apenas nas fachadas de compatibilidade necessárias durante a migração. Essas fachadas devem delegar ao núcleo novo, ser observáveis e ter prazo/critério de remoção.

## Evidências do estado atual

- `interface.php` possui aproximadamente 5.675 linhas e `interface-v2.php`, 3.014;
- `banco.php` possui aproximadamente 1.639 linhas e `banco-v2.php`, 2.509;
- `interface-v2.php` já contém enums e classes, porém a própria `InterfaceV2` ainda concentra configuração, CRUD, validação, AJAX, renderização, assets, histórico e backup;
- `admin-paginas-v2.php` continua organizado em funções procedurais e acessa globais/request, mesmo consumindo a fachada orientada a objetos;
- o inventário preliminar do core encontrou cerca de 34 funções de entrada `*_start`, 30 pares `interface_iniciar/interface_finalizar`, 24 rotinas `*_adicionar` e 26 `*_editar`;
- existem poucos contratos arquiteturais explícitos: a contagem de classes é inflada pelas migrations do Phinx, enquanto interfaces e traits são praticamente inexistentes no código próprio.

Esses números são baseline de planejamento e devem ser reproduzidos automaticamente antes da execução.

## Princípios da arquitetura v3

1. **Modular monolith:** preservar a simplicidade operacional do Gestor, separando pacotes internos por responsabilidade; não introduzir microserviços.
2. **Contratos pequenos:** consumidores dependem de interfaces estáveis; implementações de MySQL, PostgreSQL, HTTP, sessão, arquivos e renderização ficam nas bordas.
3. **Composição antes de herança:** serviços recebem dependências explícitas. Herança só representa relação estável de especialização.
4. **Estado explícito:** `$_GESTOR`, `$_REQUEST`, `$_SESSION` e demais globais ficam restritos a adapters/contextos de entrada, não atravessam domínio e casos de uso.
5. **Imutabilidade útil:** DTOs, comandos, identificadores, resultados e configurações preferem `readonly`/value objects quando compatíveis com a versão mínima.
6. **Segurança como contrato:** autenticação, autorização, CSRF, validação, transação e auditoria não são chamadas opcionais espalhadas pelos módulos; integram o pipeline canônico dos BL-034 a BL-037.
7. **Sem service locator:** evitar singletons e métodos estáticos usados como container global. O bootstrap resolve dependências e as injeta.
8. **Compatibilidade mensurável:** toda fachada procedural emite telemetria de uso e não contém regra de negócio própria.

## Mapa inicial de pacotes

```text
gestor/src/C2F/
  Core/             bootstrap, container/composição e configuração
  Http/             request, response, middleware e protocolo AJAX
  Routing/          manifesto de rotas e dispatch
  Security/         identidade, autorização, CSRF e políticas
  Database/         contratos, drivers, query, schema e transações
  Module/           descoberta, contexto, lifecycle e manifests
  Crud/             plataforma reutilizável descrita no BL-039
  History/          histórico funcional, versões e restauração
  Audit/            trilha de segurança imutável
  Event/            eventos tipados e handlers
  I18n/             C2FI18n e catálogos
  AdminInterface/   formulários, renderer e respostas de interface
  DataGrid/         C2FDataGrid
  Upload/           C2FUpload
  Update/           atualização e release
  Installer/        casos de uso compartilhados com gestor-instalador
```

O namespace final e a possibilidade de extrair um package Composer compartilhado dependem de ADR. `gestor-instalador` não deve copiar classes do Gestor: contratos realmente comuns devem residir em pacote pequeno, versionado e sem dependência da interface administrativa.

## Uso dos recursos modernos do PHP

- adotar `declare(strict_types=1)`, propriedades e retornos tipados no código novo;
- usar enums para conjuntos fechados, `readonly` para valores imutáveis, promoção de propriedades e `match` quando aumentarem clareza;
- usar exceptions tipadas nas fronteiras e resultados explícitos nos casos em que falha é parte normal do fluxo;
- avaliar attributes apenas para metadados estáticos simples, como rotas/capabilities; regras de negócio, SQL e traduções não devem ficar escondidos em annotations;
- usar traits com parcimônia: trait compartilha implementação, não define contrato. Deve ser pequeno, coeso, preferencialmente sem estado e nunca ocultar acesso a globais/container;
- não exigir sintaxe exclusiva do PHP 8.5 antes da decisão de compatibilidade mínima do BL-031. O conjunto de recursos permitido deve ser validado pelo CI na menor versão suportada.

## Estratégia de migração

### Etapa 0 — Caracterização e regras

- gerar inventário de funções globais, includes, acesso a superglobais, SQL, hooks por string e dependências entre módulos;
- criar testes de caracterização para os fluxos existentes antes de extraí-los;
- aprovar ADR de namespaces, autoload PSR-4, dependency injection, exceptions, eventos, compatibilidade e depreciação;
- adicionar regras progressivas de análise estática: primeiro baseline, depois bloqueio de novas violações.

### Etapa 1 — Bootstrap e bordas

- introduzir composition root/container mínimo no entrypoint, sem transformar o container em API de negócio;
- encapsular configuração, request, sessão, resposta e contexto de módulo;
- ligar o reference monitor do BL-034 ao dispatch de controllers/casos de uso;
- manter as URLs e entrypoints atuais por adapters enquanto os consumidores migram.

### Etapa 2 — Fundações

- decompor banco v2 e interface v2 conforme BL-011 a BL-013;
- criar contratos compartilhados de transação, clock, IDs, eventos, auditoria, tradução e arquivos;
- extrair atualização/instalação somente depois de caracterizar os comportamentos hoje compartilhados por includes/globais.

### Etapa 3 — Módulos

- migrar módulo a módulo junto das ondas do BL-015 e da plataforma CRUD do BL-039;
- controllers fazem adaptação HTTP; casos de uso orquestram; domínio/policies decidem; repositories persistem; presenters/renderers produzem saída;
- substituir hooks textuais por eventos/handlers tipados de modo gradual e com adapter para extensões antigas;
- não misturar uma conversão estrutural massiva com rename físico de schema no mesmo lote.

### Etapa 4 — Remoção

- bloquear novas funções globais/APIs procedurais no código v3;
- publicar depreciação e mapa de substituição para overlays;
- remover fachadas apenas quando telemetria, análise estática e testes de composição mostrarem zero consumidores.

## Política de tamanho e coesão

Não haverá meta cega de linhas, mas arquivos acima de aproximadamente 800–1.000 linhas exigem justificativa arquitetural. Uma classe não deve ser dividida em subclasses apenas para reduzir tamanho. Extrair quando houver responsabilidade, dependência ou ciclo de vida distinto e um teste independente fizer sentido.

## Gates de qualidade

- PHPStan/Psalm ou ferramenta equivalente com baseline decrescente;
- regras arquiteturais para impedir ciclos, acesso a internals, superglobais fora dos adapters e SQL fora do pacote de dados;
- PHPUnit para unidades/contratos e testes de composição por módulo/overlay;
- mutation testing seletivo nas políticas e validações críticas;
- métricas de acoplamento, número de globais/funções procedurais e uso das fachadas publicadas por batch;
- mesma suíte na versão mínima de PHP e no PHP 8.5.

## Antipadrões explicitamente rejeitados

- uma classe `Gestor` ou `BaseModule` que concentre o sistema inteiro;
- converter funções em métodos estáticos sem mudar dependências/estado;
- usar traits como múltipla herança ou para acessar banco, sessão e container implicitamente;
- repositories genéricos que exponham tabela/coluna arbitrária recebida da request;
- reescrita total sem checkpoints, compatibilidade e testes de caracterização;
- duplicar o núcleo OO em cada repositório privado.

## Critérios de aceite da Epic

- toda API nova da v3 é namespaced, tipada e possui contrato/testes;
- `gestor` e `gestor-instalador` compartilham apenas pacotes deliberados, sem cópia de implementação;
- código de aplicação não acessa superglobais, conexão ou renderer por variável global;
- módulos privados consomem somente APIs públicas versionadas do core;
- fachadas procedurais possuem inventário, aviso de depreciação e contador decrescente até zero;
- banco/interface v1 e os entrypoints procedurais removíveis não têm consumidores antes do RC;
- documentação pt-br/en explica como criar serviço, controller, handler, policy e módulo v3.

## Próxima decisão

Promover primeiro um ADR e um lote de caracterização/guardrails. A extração do bootstrap e dos contratos fundamentais vem depois e deve anteceder a conversão dos módulos.
