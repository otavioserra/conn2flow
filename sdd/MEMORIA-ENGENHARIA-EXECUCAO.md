# Memória de Engenharia — Execução

> **Propósito**: manter contexto operacional recente. Regras consolidadas vivem em `.claude/skills/` e `.cursor/skills/` e são carregadas sob demanda.
>
> **Política**: preservar 3 a 5 tarefas recentes, mirar ~5 KB e podar antes de 10 KB. A memória de Chefia permanece somente leitura.

## Skills Core destiladas

- `c2f-json-resources-sync`: versões/checksums de recursos são recalculados pelo deploy.
- `c2f-widget-development`: recursos desduplicados, contrato AJAX e tokens `item#var`.
- `c2f-gd-image-safety`: suporte opcional a formatos GD e captura de `\Throwable`.
- `c2f-database-testing`: SQLite em memória ou MySQL isolado `conn2flow_test`.
- `c2f-mysql-utf8-emoji-encoding`: JSON ASCII-safe para MySQL `utf8` de 3 bytes.

## Tarefas recentes

### 2026-08-10 — req-108: diagnóstico do 429 falso no deploy

- **`ParseError` É `Throwable`**: `require_once` de arquivo que não compila, feito DENTRO de um `try`, é
  capturado por `catch (Throwable)`. Um erro de sintaxe vira, silenciosamente, o desfecho de erro daquele
  bloco — aqui virou "Rate limit excedido" (HTTP 429) com a tabela vazia.
- Ambiente real é **PHP 8.3.32** (Apache e CLI do container). `banco-v2.php` e `interface-v2.php` declaram
  `@requires PHP 8.5+` e usam `clone $this with {}` e pipe `|>`: nunca compilaram aqui.
- Sintoma "tabela vazia + limite excedido" é assinatura de falha ANTES do INSERT, não de contagem.
- `catch` que devolve o mesmo valor para "falha ao avaliar" e "regra violada" custa horas de depuração;
  separar os dois desfechos vale mais que o fail-closed silencioso.
- `php -l` em massa no core (fora de `vendor/` e `temp/`) acha esse tipo de defeito em segundos —
  não havia essa guarda no CI.
- Resíduo de branch: `banco-v2`/`interface-v2`/`admin-paginas-v2` nasceram no MESMO commit `c8fefefa`
  (testes de 8.5 feitos na `main`, desacoplamento para a `3.0.x` iniciado mas incompleto). O
  `api.php` só os tornou alcançáveis em `b64bc18d` (BATCH-107).
- **Trocar de branch não resolveria**: `main`, `2.9.x` e `3.0.x` têm o mesmo `api.php` incluindo
  `banco-v2` na linha 87, e nenhuma versão do `banco-v2.php` compila abaixo de 8.5. Ao diagnosticar
  a partir de uma branch de desenvolvimento, confirmar o alcance nas demais antes de concluir que
  o defeito é dela.
- **`gestor/db/data/*.json` é GERADO** por `controladores/agents/arquitetura/atualizacao-dados-recursos.php`
  a partir dos recursos e do `.json` de cada módulo. Nunca editar à mão: remova na raiz e rode o gerador.
  Editar com `JSON.stringify` do Node destrói o escape `\/` do `json_encode` do PHP e gera ~2.000 linhas
  de ruído; com o gerador, o diff fica só nas remoções reais.
- Linha 2.x (`main`/`2.9.x`) e linha 3.x (`3.0.x`) são **independentes, não espelhadas**: 2.x usa as
  bibliotecas antigas e não conhece PHP 8.5; 3.0.x exige 8.5 e usa `banco-v2`. Nada de fallback ou
  `PHP_VERSION_ID` tentando servir as duas.
- No Git Bash, `docker exec ... php /tmp/x.php` tem o path convertido pelo MSYS; usar `MSYS_NO_PATHCONV=1`.

### BATCH-106 — painéis fixos opcionais do editor visual

- Para tirar um painel da flutuação, MOVA o nó existente (`appendChild`) e anule o posicionamento por
  classe de estado. Recriar o painel dentro do contêiner novo duplicaria handlers e lógica de render.
- O offset do topo no Live Editor é o `margin-top` do `<html>` (offset persistente da Editbar), nunca
  a altura do iframe `#c2f-site-toolbar` — ela cresce quando um dropdown abre.
- Todo elemento novo de UI do editor precisa entrar em três lugares: `isEditorOwned` (clique não
  vaza), `extractUserHtml` e o seletor de fallback do save em `html-editor-interface.js`.
- `styler.querySelector('input')` era frágil: ao acrescentar campos na coluna visual, o "primeiro
  input" deixou de ser o de classes. Prefira seletor por classe própria (`.he-class-input`).
- Painéis do editor clássico ficam na janela pai e falam com o motor por `postMessage`; como o
  preview usa `srcdoc`, iframe e pai compartilham `localStorage` (mesma origem).
- Rodada 2: clique dentro do iframe da Editbar NÃO dispara `mousedown` na página hospedeira nem
  atinge backdrop nenhum — todo fechamento "ao clicar fora" precisa de um aviso explícito da barra
  (`ui-dismiss`), inclusive para os painéis do motor. Como o `mousedown` precede o `click`, fechar
  tudo no aviso não atrapalha o botão que abre um painel.
- `closeEmbedModal()` zera `isModalActive` mesmo com o modal de embed fechado: ao encadear
  fechamentos, leia o estado do modal de edição ANTES de chamá-lo.
- Rodada 3: em caixa redimensionável, o CodeMirror não pode receber "todo o espaço até o fundo" —
  desconte a altura dos irmãos posteriores (dele e dos ancestrais), senão o rodapé/status sai da área
  visível. NÃO use `scrollHeight` nessa conta: ele nunca é menor que `clientHeight`, então com o
  conteúdo cabendo a expressão colapsa na altura atual do editor e ele encolhe a cada disparo do
  `ResizeObserver`, matando o acompanhamento do arraste. O termo descontado precisa ser independente
  da altura do editor. E o ajuste não pode viver só no observador: chame também ao trocar de aba e ao
  abrir o painel, senão o editor recém-exibido fica na altura inicial.
- `disable()` do motor é usado tanto para SAIR da edição quanto para o preview de dispositivo —
  comportamento que só vale ao sair precisa de flag explícita (`{manterPaineis:true}`).

### 2026-07-31 — Rodada de análise de segurança/sistêmica (backlog)

- Auditoria do core (`gestor`) e do instalador (`gestor-instalador`) gerou 10 itens em `sdd/backlog/` (BL-001..BL-010), **sem tocar código** — aguardam promoção humana.
- Achados de maior severidade: instalador baixa o release com `SSL_VERIFYPEER=false` e sem checksum (o updater do core já verifica SHA256 — usar de referência); IDs de sessão/token via `md5(uniqid(rand()))` enquanto o CSRF já usa `random_bytes`; CSRF é código morto (definido em `seguranca.php`, nunca validado).
- Padrão a lembrar: acesso a dados de runtime é por concatenação de `WHERE` com `banco_escape_field` (fallback `addslashes` sem conexão); prepared statements só em `banco-v2`/migrations. Existem pares v1/v2 paralelos (banco, interface, admin-paginas) = débito de migração.
- Nenhum cabeçalho de segurança HTTP no core (CSP/HSTS/X-Frame-Options ausentes).

### BATCH-103 — busca normalizada e paginação sem salto

- Comparações textuais de UI devem usar lowercase + NFD sem marcas combinantes. Monte o range Unicode por código (`String.fromCharCode`) para não depender da normalização do arquivo-fonte.
- Em paginação AJAX, não esconda a lista já renderizada: a perda de altura desloca a rolagem para o topo. Mostre o indicador depois da lista e esconda apenas na primeira carga/substituição.
- Assets do menu precisam ser incluídos antes de `gestor_pagina_extra_head_e_javascript()` ou concatenados ao HTML do menu; a fila já foi consumida quando `gestor_pagina_menu()` roda.

### BATCH-102 — precedência do diretório no picker

- O diretório inicial do `admin-arquivos` segue `?dir=` explícito > cache > raiz. O modo iframe não elimina a intenção do usuário e também deve ler/gravar o cache quando não há diretório explícito.

### BATCH-101 — mídia sem fonte vazia

- Nunca gere `src=""` ou `data=""`: o navegador requisita a própria página como mídia, falha na decodificação e pode colapsar o player.
- Em geradores de markup, omita completamente o atributo de fonte quando não houver valor.
- Hipóteses não sustentadas pela evidência devem ser revertidas; a assinatura `; ` no style ajudou a provar que o elemento havia passado pelo modal.

## Pendências

- Testes que executam o compilador de recursos podem regenerar data files/checksums. Conferir `git status` e manter apenas alterações pertencentes ao batch corrente.
- Detalhes anteriores à BATCH-099 permanecem recuperáveis no histórico Git.
