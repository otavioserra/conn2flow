# BL-029 — Infraestrutura Docker isolada para v3 e legado 2.9.x

- **Tipo:** Architecture/Developer Experience/Infrastructure
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** `dev-environment/docker`, dados locais, tarefas VS Code e scripts que controlam os containers
- **Relacionados:** BL-011, BL-012, BL-020, BL-021, BL-027, BL-028, BL-030

## Objetivo

Transformar a infraestrutura local em duas linhas explicitamente isoladas:

- **v3 (padrão):** ambiente atual, reproduzível e preparado para desenvolver/testar o Contao Flow 3.x;
- **legacy 2.9.x:** fotografia congelada do ambiente que sustenta a linha estável atual, disponível para regressões e correções LTS.

O ambiente v3 deve ser uma das primeiras entregas da branch `3.0.x`, antes das migrações de banco, interface, Tailwind, DataGrid e upload. O ambiente legado deve ser preservado sem mover ou converter automaticamente os dados locais existentes.

## Inventário encontrado

| Componente | Estado atual |
| --- | --- |
| `Dockerfile` | `php:8.3-apache`, com Apache e extensões MySQL |
| `Dockerfile.php85` | `php:8.5-apache`, quase duplicado, adicionando PostgreSQL |
| `docker-compose.yml` | PHP 8.3 + MySQL 8.0 como ambiente corrente |
| `docker-compose.php85-mysql.yml` | PHP 8.5 + MySQL 8.0 |
| `docker-compose.php85-pgsql.yml` | PHP 8.5 + PostgreSQL 17 |
| runtime inspecionado | PHP 8.3.32 e MySQL 8.0.43 |
| serviços auxiliares | phpMyAdmin, Redis, Memcached, Cloudflared e FTP |
| armazenamento de sites | bind mount compartilhado em `dev-environment/data/sites` |
| banco MySQL | volume lógico `docker_mysql_data` |

As três composições passam na validação sintática do Docker Compose, mas isso não comprova isolamento nem compatibilidade funcional.

## Problemas arquiteturais encontrados

### 1. As variantes não são ambientes isolados

Os arquivos Compose usam implicitamente o mesmo nome de projeto (`docker`), portas sobrepostas e nomes fixos de containers. As duas variantes MySQL também resolvem o volume declarado `mysql_data` para o mesmo volume real `docker_mysql_data`.

Consequências:

- uma variante pode recriar ou substituir serviços da outra;
- não é possível executar v3 e legado lado a lado com segurança;
- uma migração v3 pode alterar irreversivelmente a base que seria usada numa regressão 2.9.x;
- os dois ambientes compartilham arquivos de sites, inclusive `.env`, uploads e logs;
- trocar somente o arquivo `-f` transmite uma falsa sensação de isolamento.

### 2. A variante PHP 8.5 ainda não representa o runtime v3

O arquivo PHP 8.5 mantém MySQL 8.0 e não define um conjunto fechado de versões, extensões e ferramentas. Composer e Node não estão disponíveis no container web inspecionado. `intl`, necessária para uma implementação robusta de locale, números e datas no `C2FI18n`, também não está presente.

O uso de tags como `php:8.5-apache`, `phpmyadmin/phpmyadmin` e `cloudflare/cloudflared:latest` permite que builds iguais em datas diferentes gerem runtimes diferentes.

### 3. A configuração MySQL montada não está ativa

O MySQL registra repetidamente:

```text
World-writable config file '/etc/mysql/conf.d/my.cnf' is ignored.
```

Esse comportamento decorre das permissões observadas no arquivo montado a partir do Windows. Além disso, o arquivo contém opções removidas no MySQL 8 (`query_cache_size` e `query_cache_type`) e desativa TLS de forma geral. Portanto, não basta reutilizá-lo na imagem 8.4.

### 4. Há acoplamento aos nomes físicos de containers

Tarefas do VS Code, scripts do `ai-workspace`, documentação e rotinas FTP executam comandos como:

```text
docker exec conn2flow-app ...
docker exec conn2flow-mysql ...
```

Remover `container_name` sem uma camada de compatibilidade quebraria esse ferramental. Manter os nomes fixos, porém, impede projetos Compose isolados e execução simultânea.

### 5. Configuração de desenvolvimento esconde problemas da migração

O `php.ini` atual:

- remove `E_DEPRECATED` e `E_STRICT` do relatório de erros;
- desliga `display_errors`;
- embute valores padrão de credenciais MySQL;
- usa parâmetros de OPcache únicos para desenvolvimento e execução semelhante à produção;
- aceita upload/post de 1 GB e execução de 900 segundos em todos os fluxos.

Para migrar a PHP 8.5, depreciações precisam ficar visíveis no desenvolvimento e ser tratadas como sinal verificável no CI. Limites altos continuam úteis para testes específicos, mas não devem mascarar timeout, loop ou consumo excessivo.

### 6. Inicialização e permissões precisam ser revistas

O `entrypoint.sh` usa espera fixa e altera permissões recursivamente, principalmente em `/var/www/html`, embora os sites principais estejam montados em `/var/www/sites`. Isso pode acrescentar latência, alterar arquivos do host e ainda não preparar o diretório realmente usado.

Readiness deve ser expresso por health checks, e permissões devem atingir apenas diretórios graváveis conhecidos.

## Decisões propostas

### Perfis oficiais

| Perfil | Finalidade | Runtime inicial proposto | Política |
| --- | --- | --- | --- |
| `v3` | desenvolvimento padrão e testes do 3.x | PHP 8.5 estável + MySQL 8.4 LTS | bloqueante para a v3 |
| `legacy` | correções/regressões 2.9.x | fotografia PHP 8.3.32 + MySQL 8.0.43 | congelado e suportado durante a janela LTS |
| `v3-pgsql` | desenvolvimento e homologação PostgreSQL da v3 | PHP mais novo aprovado + PostgreSQL 18.4 | torna-se bloqueante antes do RC |

PHP 8.5 não garante que todo código 2.9.x funcione sem adaptação. Mudanças de linguagem, depreciações, restrições do Composer e extensões nativas precisam ser testadas. O perfil legado só poderá ser aposentado após a matriz do BL-030 provar compatibilidade e a política LTS permitir sua remoção.

### Política de versões

- o padrão v3 deve usar a última versão **aprovada** na linha estável escolhida, e não a tag móvel `latest`;
- adotar inicialmente PHP 8.5 em patch estável explicitamente fixado e MySQL 8.4 LTS;
- no momento desta análise, os candidatos são PHP 8.5.8 e MySQL 8.4.10 LTS;
- fixar também a variante do sistema-base e, quando praticável, o digest da imagem;
- atualizar versões em batches próprios, após build, smoke, migração e testes de overlays;
- serviços auxiliares também precisam de versão fixa ou perfil opcional;
- PostgreSQL 18.4 é a referência nova do BL-032; o perfil pode começar como PoC, mas deve virar gate oficial antes do RC.

“Versão mais nova” para banco de dados significa a versão mais nova aceita da linha LTS, não a linha Innovation publicada como `mysql:latest`.

## Topologia proposta

```text
compose.yaml                         # v3 padrão
compose.legacy.yaml                  # override/perfil legado 2.9.x
compose.postgres.yaml                # experimento explícito
docker/
  php/
    Dockerfile                       # targets/args versionados v3 e legacy
    conf.d/development.ini
    conf.d/test.ini
    conf.d/production-like.ini
  mysql/
    Dockerfile                       # copia configuração com permissão válida
    conf.d/v3.cnf
    conf.d/legacy.cnf
  apache/
  scripts/
    c2f-env.ps1
    c2f-env.sh
```

Os nomes finais podem ser ajustados na promoção da requisição. O contrato importante é separar perfil, dados, rede e comandos.

### Identidade e isolamento

- projetos Compose explícitos: `c2f-v3` e `c2f-v29`;
- remover `container_name` dos serviços novos e usar nomes de serviço DNS (`app`, `db`, `redis`);
- volumes distintos: por exemplo, `c2f_v3_mysql_data` e `c2f_v29_mysql_data`;
- redes distintas por projeto;
- diretórios de sites distintos;
- manter inicialmente `dev-environment/data/sites` como origem do legado para evitar uma movimentação destrutiva;
- criar um novo diretório de runtime v3, ignorado pelo Git, sem copiar segredos ou dados automaticamente;
- portas configuráveis e defaults que permitam execução simultânea, com v3 ocupando as portas usuais e legado usando alternativas;
- bind de banco e painéis administrativos em `127.0.0.1`, não em todas as interfaces.

Nenhum volume de banco, diretório de site ou arquivo `.env` pode ser compartilhado entre as linhas 2.9.x e 3.x.

### Comando único de operação

Criar um wrapper multiplataforma com comandos equivalentes a:

```text
c2f-env v3 up
c2f-env legacy up
c2f-env v3 exec app php -v
c2f-env v3 logs app
c2f-env v3 down
```

O wrapper deve chamar `docker compose -p <projeto> ...`, resolver o perfil e eliminar a necessidade de scripts conhecerem IDs ou nomes de container. Tarefas VS Code, scripts de sync, FTP e documentação devem migrar para esse contrato.

### Imagem PHP

- reduzir duplicação entre `Dockerfile` e `Dockerfile.php85` por targets ou argumento de imagem-base controlado;
- declarar extensões por perfil e verificar sua presença no build;
- baseline v3: PDO, `pdo_mysql`, `mysqli` temporário, mbstring, intl, DOM/XML, curl, GD, zip, fileinfo, sodium, bcmath e OPcache;
- `pdo_pgsql`/`pgsql` no target `v3-pgsql`, com contratos e versões próprias;
- disponibilizar Composer de forma versionada para desenvolvimento/teste;
- preferir Node em um serviço/estágio de build dedicado em vez de inflar o container Apache;
- adicionar Xdebug somente por perfil opt-in;
- manter `mysqli` até o contador de consumidores legados chegar a zero.

### Configuração PHP por finalidade

- `development`: `E_ALL`, depreciações visíveis/logadas, OPcache com revalidação e diagnóstico amigável;
- `test`: erros e depreciações capturados, assertions/test runners e limites determinísticos;
- `production-like`: `display_errors=Off`, logs ativos, OPcache próximo do release e nenhum Xdebug;
- credenciais e portas em `.env` local ignorado pelo Git, com `.env.example` sem segredo;
- limites de upload e timeout parametrizados por cenário, incluindo um perfil de arquivos grandes para `C2FUpload`.

### MySQL 8.4 LTS

- criar imagem/configuração própria e copiar o `.cnf` durante o build com permissões válidas;
- remover opções de query cache e todas as opções removidas/depreciadas;
- não desativar TLS globalmente sem um motivo de teste documentado;
- manter `utf8mb4` e testar explicitamente collation, timezone e sql modes;
- documentar mudanças de palavras reservadas e comportamento 8.0→8.4 relevantes ao banco v2;
- executar upgrade checker antes de qualquer migração de dados real;
- preferir dump lógico + restore em volume novo para o primeiro ensaio;
- nunca anexar o volume 8.0 existente diretamente ao serviço 8.4 como primeira tentativa.

### Serviços auxiliares

- phpMyAdmin, Cloudflared, FTP, Redis, Memcached e ferramentas de debug devem ser profiles opt-in quando não forem necessários ao smoke principal;
- Cloudflared não deve iniciar no fluxo padrão sem credencial/necessidade explícita;
- FTP deve permanecer disponível para testar instalador/atualizador legado, mas isolado por projeto e diretório;
- todo serviço deve ter versão aprovada, health check e justificativa de exposição de porta.

## Plano de implementação futuro

### Lote A — Congelar e caracterizar o legado

1. registrar imagens, digests, versões, extensões, módulos Apache e sql modes atuais;
2. renomear conceitualmente a composição atual como `legacy`, sem mover dados;
3. executar smoke da 2.9.x e guardar o relatório;
4. documentar backup/restauração do volume e dos sites;
5. tornar explícito que o legado não recebe migrations v3.

### Lote B — Criar o ambiente v3 isolado

1. criar projeto, rede, volumes, portas e diretório de sites próprios;
2. construir PHP 8.5 e MySQL 8.4 LTS versionados;
3. corrigir configurações PHP/MySQL/Apache e health checks;
4. instalar o core limpo e validar login, módulos, banco e logs;
5. provar execução simultânea com o legado sem recurso compartilhado.

### Lote C — Padronizar operação e ferramentas

1. criar wrapper `c2f-env` e `.env.example`;
2. migrar tasks VS Code e scripts que usam `docker exec conn2flow-*`;
3. atualizar scripts FTP/sync/release que dependem do ambiente;
4. adicionar comandos de diagnóstico e impressão da matriz ativa;
5. atualizar documentação `pt-br` e `en` no mesmo batch.

### Lote D — Tornar v3 o padrão

1. executar a matriz bloqueante do BL-030;
2. tornar `docker compose up`/task principal equivalente ao perfil v3;
3. deixar o legado acessível apenas por comando explícito;
4. comunicar portas, caminhos, backup e compatibilidade;
5. manter rollback simples para o perfil legado durante a janela LTS.

## Fora do escopo desta análise

- alterar agora containers, volumes ou arquivos dos sites em uso;
- converter dados reais automaticamente de MySQL 8.0 para 8.4;
- anunciar PostgreSQL como produção antes dos gates do BL-032;
- remover o ambiente legado antes da política de suporte;
- garantir compatibilidade 2.9.x/PHP 8.5 sem executar a matriz;
- escolher uma arquitetura de produção a partir do Docker local de desenvolvimento.

## Critérios de aceite

- `v3` e `legacy` executam simultaneamente sem compartilhar nomes, portas, redes, volumes ou sites;
- o perfil v3 usa PHP 8.5 e MySQL 8.4 LTS em versões reproduzíveis;
- o perfil legado reproduz PHP 8.3.32/MySQL 8.0.43 ou a fotografia aprovada equivalente;
- `my.cnf` é carregado e suas opções são válidas para cada versão;
- depreciações PHP 8.5 ficam visíveis no desenvolvimento e verificáveis no CI;
- nenhum script operacional depende de `container_name` físico;
- o ambiente v3 sobe a partir de dados vazios e passa health/smoke tests;
- existe procedimento testado de backup, restauração e ensaio 8.0→8.4;
- a composição core + cada overlay privado suportado é testada no mesmo runtime v3;
- documentação bilíngue informa comandos, versões, portas e política do legado.

## Riscos e mitigação

| Risco | Mitigação |
| --- | --- |
| perda/contaminação do volume 8.0 | novo volume, backup e dump/restore; não fazer upgrade in-place inicial |
| scripts quebrarem após remover nomes fixos | wrapper de compatibilidade antes do cutover |
| imagem mudar sem alteração no repositório | tag completa + digest e rotina controlada de atualização |
| 2.9.x falhar em PHP 8.5 | manter perfil legado e executar matriz, sem presumir retrocompatibilidade |
| configuração MySQL continuar ignorada no Windows | configuração copiada na imagem com teste de variável efetiva |
| depreciações ficarem invisíveis | `E_ALL` no perfil de desenvolvimento/teste e gate no CI |
| PostgreSQL ampliar o caminho crítico | dividir BL-032 em ondas e bloquear o anúncio até matriz completa |

## Referências oficiais consultadas

- [PHP 8.5 release](https://www.php.net/releases/8.5/en.php) e [versões suportadas](https://www.php.net/supported-versions.php);
- [imagem oficial PHP](https://hub.docker.com/_/php);
- [modelo de releases do MySQL](https://dev.mysql.com/doc/refman/8.4/en/mysql-releases.html), [release notes 8.4](https://dev.mysql.com/doc/relnotes/mysql/8.4/en/) e [imagem oficial](https://hub.docker.com/_/mysql);
- [pré-requisitos oficiais de upgrade para MySQL 8.4](https://dev.mysql.com/doc/refman/8.4/en/upgrade-prerequisites.html);
- [política de versões suportadas do PostgreSQL](https://www.postgresql.org/support/versioning/).

As versões candidatas registram o estado observado em 2026-08-07 e devem ser revalidadas ao promover o backlog para requisição.

## Próxima ação

Promover primeiro o Lote A como caracterização não destrutiva e, na branch `3.0.x`, promover o Lote B antes das implementações de banco/interface. Nenhuma alteração de volume ou dados atuais deve ocorrer sem backup verificado.
