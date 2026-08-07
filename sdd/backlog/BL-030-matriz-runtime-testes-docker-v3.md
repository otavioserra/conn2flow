# BL-030 — Matriz de runtime, compatibilidade e testes Docker da v3

- **Tipo:** Quality/Compatibility/Release Engineering
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** validação local/CI dos runtimes 2.9.x e 3.x, upgrade de PHP/MySQL e composição com overlays privados
- **Relacionados:** BL-011, BL-013, BL-014, BL-020, BL-021, BL-022, BL-025, BL-029

## Objetivo

Definir a evidência necessária para:

1. manter a 2.9.x corrigível num ambiente congelado;
2. tornar PHP 8.5 + MySQL 8.4 LTS o padrão seguro da v3;
3. descobrir se a 2.9.x também funciona no runtime novo, sem presumir retrocompatibilidade;
4. validar instalação limpa, atualização, rollback e overlays privados em ambientes descartáveis;
5. impedir que diferenças entre Docker local, CI e release sejam descobertas somente em produção.

Esta matriz é separada da construção do BL-029: infraestrutura “sobe” não significa que a aplicação é compatível.

## Baselines propostos

| ID | Aplicação | PHP | Banco | Uso |
| --- | --- | --- | --- | --- |
| `L29` | Gestor 2.9.x | 8.3.32 congelado | MySQL 8.0.43 congelado | gate de manutenção/LTS |
| `C29-V3` | Gestor 2.9.x | 8.5 aprovado | MySQL 8.4 LTS aprovado | compatibilidade progressiva |
| `V3` | Gestor 3.x | 8.5 aprovado | MySQL 8.4 LTS aprovado | gate principal da v3 |
| `V3-PG` | Gestor 3.x | PHP mínimo/novo | PostgreSQL mínimo/18.4 | alvo oficial do BL-032 |

Versões patch devem ser fixadas pelo BL-029 e atualizadas de forma controlada. A matriz referencia IDs de ambiente para não espalhar números em scripts e documentos.

## Status de cada combinação

| Combinação | Fase inicial | Condição futura |
| --- | --- | --- |
| `L29` | bloqueante para releases 2.9.x | permanece durante a janela LTS |
| `C29-V3` | informativa, gera inventário de falhas/depreciações | pode virar bloqueante antes de reduzir uso do legado |
| `V3` | bloqueante desde o primeiro alpha | bloqueante em todo release v3 |
| `V3-PG` | informativa durante PoC/alpha | bloqueante antes do RC e release estável |

O perfil legado não deve ser removido só porque um smoke superficial de `C29-V3` passou.

## Por que PHP 8.5 não é automaticamente retrocompatível

O código antigo pode falhar mesmo que a linguagem preserve grande parte da compatibilidade:

- avisos/depreciações podem revelar chamadas que serão removidas ou alterar fluxos tratados como erro;
- extensões podem ter APIs, defaults ou disponibilidade diferentes;
- dependências Composer podem restringir a versão PHP ou usar código incompatível;
- coerções, validações e mensagens de erro podem mudar;
- bibliotecas nativas e imagem-base podem mudar comportamento de TLS, locale, imagens e arquivos;
- configurações Apache/PHP podem deixar de ser aceitas;
- o MySQL 8.4 pode introduzir palavras reservadas, remover opções e expor SQL dependente de comportamento 8.0.

Portanto, `C29-V3` é uma hipótese testável, não uma decisão já comprovada.

## Contrato mínimo da imagem PHP v3

O build deve verificar, e não apenas documentar:

- versão PHP e SAPI esperadas;
- módulos Apache necessários, inclusive `rewrite`;
- PDO e drivers aprovados;
- `mysqli` enquanto existir legado;
- mbstring, intl, DOM/XML, curl, GD, zip, fileinfo, sodium, bcmath e OPcache;
- timezone/locale e leitura das configurações corretas;
- Composer versionado no perfil de desenvolvimento/teste;
- ausência de Xdebug no perfil padrão e presença somente no opt-in;
- limite de memória/upload/timeout correspondente ao perfil selecionado.

Um script de diagnóstico deve emitir JSON ou saída estável contendo versões, extensões e configurações críticas. Esse artefato deve acompanhar falhas do CI e relatórios de homologação.

## Contrato mínimo do MySQL 8.4

Validar em runtime:

- versão exata e digest aprovados;
- charset/collation do servidor, conexão, schema e tabelas;
- timezone e sql modes efetivos;
- carregamento real do arquivo de configuração;
- engine, constraints, índices e limites esperados;
- usuário da aplicação com privilégios mínimos necessários;
- ausência de opções ignoradas/removidas no log;
- readiness por query real, não apenas processo/porta aberta;
- backup lógico e restauração em volume vazio.

O teste deve falhar se surgir no log a mensagem de configuração ignorada ou opção desconhecida.

## Suítes de validação

### 1. Build e smoke de infraestrutura

- validar todos os arquivos Compose;
- construir imagens sem cache e com cache;
- iniciar cada perfil de dados vazios;
- aguardar health checks de app/banco;
- validar Apache, PHP, banco e serviços opcionais;
- provar que `v3` e `legacy` podem executar simultaneamente;
- provar que volumes, redes, sites e `.env` são distintos;
- parar/reiniciar sem perda indevida de dados.

### 2. Instalação limpa

Para `L29` e `V3`:

- instalar core do zero;
- autenticar e acessar módulos essenciais;
- criar, ler, alterar e excluir fixtures controladas;
- executar migrations e seeds;
- validar logs sem fatal, warning inesperado ou erro SQL;
- conferir permissões de arquivos, uploads e caches após restart.

### 3. Caracterização 2.9.x no runtime v3

Executar a mesma suíte 2.9.x em `C29-V3` com `E_ALL`:

- registrar depreciações por arquivo/símbolo;
- classificar falha de linguagem, extensão, Composer, Apache, SQL ou configuração;
- corrigir apenas em requisito autorizado, mantendo o relatório como baseline;
- executar módulos administrativos, AJAX, login/sessão e atualizador;
- testar bibliotecas usadas pelos overlays, inclusive integrações OAuth e migrations.

O relatório deve separar “funciona” de “funciona sem depreciações” e “apto a dispensar o legado”.

### 4. Upgrade MySQL 8.0 → 8.4

Fluxo seguro inicial:

1. gerar uma fixture/snapshot sanitizado representativo da 2.9.x;
2. validar integridade no MySQL 8.0;
3. executar o upgrade checker oficial e guardar relatório;
4. produzir dump lógico com checksum;
5. restaurar em volume MySQL 8.4 novo;
6. executar migrations v3;
7. comparar contagens, constraints, encoding e invariantes de domínio;
8. executar aplicação e testes funcionais;
9. descartar o volume de ensaio ou marcá-lo inequivocamente como v3.

O primeiro ensaio não deve reutilizar nem atualizar in-place o volume local da 2.9.x.

### 5. Upgrade da aplicação 2.9.x → 3.x

Cobrir pelo menos:

- preflight de PHP/extensões/banco;
- seleção de canal e manifesto correto;
- staging/download/checksum;
- deploy de arquivos e merge `.env`;
- migrations e transações;
- finalize/status AJAX;
- expiração de sessão durante execução;
- falha em cada etapa e retomada/rollback;
- cache de assets/JS antigo após atualização;
- site grande com uploads e dados representativos.

Os incidentes observados no atualizador 2.9.x — CSRF durante troca de versão, loop de status e expiração de login — devem virar casos de regressão explícitos.

### 6. Banco v2

- prepared statements para valores e allowlist de identificadores;
- transações, rollback e concorrência;
- erros/exceções determinísticos;
- strict modes do MySQL 8.4;
- paginação, ordenação e filtros usados por `C2FDataGrid`;
- migrations Phinx e instalador apontando para o mesmo contrato;
- contador zero de APIs v1 nos módulos declarados migrados.

### 7. Interface, i18n e frontend

- sessão, autorização e CSRF em request normal e AJAX;
- protocolo de erro JSON e redirecionamento de login expirado;
- `intl` para locale, datas, números e pluralização do `C2FI18n`;
- build Tailwind e assets reprodutíveis em serviço Node versionado;
- `C2FDataGrid`/DataTables 3 sem jQuery/Fomantic no piloto;
- `C2FUpload`/Uppy com limites compatíveis entre browser, PHP e Apache;
- Playwright nos fluxos administrativos críticos.

### 8. Performance e recursos

Guardar baseline antes das migrações e comparar por marco:

- tempo de build/startup e readiness;
- TTFB/login/listagem representativa;
- duração de atualização completa e migrations;
- CPU/memória do app e banco;
- custo do entrypoint sobre diretórios com muitos arquivos;
- upload pequeno e grande;
- query count e queries lentas do piloto;
- efeito de OPcache nos perfis development e production-like.

Regressão deve ser investigada antes de aumentar indiscriminadamente timeout ou memória.

### 9. Segurança do ambiente local

- portas administrativas ligadas somente a loopback por padrão;
- credenciais locais fora do Git;
- nenhum segredo real copiado do legado ao v3;
- containers sem mounts além do necessário;
- artefatos sem `.git` aninhado;
- imagens e dependências com versões conhecidas;
- teste de cabeçalhos/Apache compatível com o BL-013;
- logs e dumps sanitizados antes de virar fixture.

## Matriz de overlays privados

Cada projeto que contém pasta `gestor` deve ser testado como soma:

```text
core v3 na revisão X + overlay privado na revisão compatível Y
```

Para cada overlay suportado:

- instalação/merge num site v3 limpo;
- migrations combinadas e conflitos de schema;
- rotas, permissões, recursos/i18n e assets;
- módulos que usam SQL/MySQL específico;
- compatibilidade de DataGrid, uploads e Tailwind;
- upgrade a partir de fixture 2.9.x correspondente;
- relatório com commits/tags exatos do core e overlay.

Não declarar compatibilidade do core isolado como compatibilidade do produto composto.

## Execução local e CI

- o mesmo wrapper do BL-029 deve controlar ambientes locais e jobs de CI;
- scripts de teste recebem o ID `L29`, `C29-V3`, `V3` ou `V3-PG`, não nome físico de container;
- jobs rápidos cobrem build/lint/unit/contract em todo push;
- jobs de instalação, upgrade, browser e overlays podem rodar por batch, merge e release;
- falhas guardam logs, diagnóstico de runtime, relatório de migrations e screenshots quando aplicável;
- nenhuma suíte deve depender do volume ou site pessoal do desenvolvedor;
- fixtures devem ser determinísticas, sanitizadas e recriáveis.

## Gates por marco

### Antes de iniciar banco/interface v2

- ambiente `V3` sobe limpo;
- PHP/extensões e MySQL efetivos correspondem ao contrato;
- configuração MySQL é carregada;
- instalação core atual executa smoke suficiente para produzir baseline;
- `L29` continua reproduzível.

### Alpha v3

- `V3` bloqueante em build, instalação e módulos piloto;
- upgrade checker/dump/restore 8.0→8.4 automatizados em fixture;
- atualização 2.9→3 exercitada, mesmo que gaps estejam documentados;
- pelo menos um overlay representativo composto.

### Beta v3

- todos os overlays suportados na matriz;
- upgrades e migrations idempotentes;
- depreciações PHP 8.5 conhecidas resolvidas no escopo suportado;
- orçamento de performance aprovado;
- documentação bilíngue de instalação/upgrade validada.

### Release candidate

- instalação limpa, upgrade, falhas/rollback e restauração passam repetidamente;
- zero compartilhamento acidental com `L29`;
- logs sem opção MySQL ignorada, fatal ou depreciação não aceita;
- imagens/digests, fixtures e relatórios congelados com o RC;
- decisão humana explícita sobre manutenção ou eventual aposentadoria do legado.

## Critérios de aceite

- todos os IDs de ambiente têm definição versionada e diagnóstico automático;
- `L29` e `V3` são bloqueantes nos seus respectivos canais; `V3-PG` torna-se bloqueante antes do RC;
- `C29-V3` produz evidência suficiente para decidir, não uma afirmação presumida;
- upgrade MySQL usa cópia/volume novo e possui verificação de integridade;
- atualização da aplicação cobre CSRF, sessão expirada, status/finalize e rollback;
- core e overlays são testados como composição;
- regressões de performance têm baseline e orçamento;
- a documentação `pt-br`/`en` usa os mesmos comandos e matriz da automação.

## Próxima ação

Promover junto do Lote A do BL-029 um batch de caracterização que gere os baselines `L29` e `C29-V3`. Depois que o ambiente v3 isolado existir, tornar `V3` o primeiro gate bloqueante da branch `3.0.x`.
