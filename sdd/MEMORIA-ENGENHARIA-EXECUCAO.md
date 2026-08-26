# Memória de Engenharia — Execução

> **Propósito**: manter contexto operacional recente. Regras consolidadas vivem nas skills e são carregadas sob demanda.
> **Política**: preservar 3 a 5 tarefas recentes, mirar ~3 KB a 4.5 KB e podar antes de 5 KB / 50 linhas.

## Skills Core destiladas

- `c2f-json-resources-sync`: versões/checksums de recursos são recalculados pelo deploy.
- `c2f-widget-development`: recursos desduplicados, contrato AJAX e tokens `item#var`.
- `c2f-database-testing`: SQLite em memória ou MySQL isolado `conn2flow_test`.
- `c2f-variables-system`: proibição estrita de strings/textos literais hardcoded em PHP/HTML/JS.
- `c2f-module-crud-scaffolding`: scaffolding canônico baseado em `gestor/modulos/modulos-grupos/`.

## Tarefas recentes

### 2026-08-25 — BATCH-134 (req-132): limpar o HTML sem quebrar a pagina

- **`gestor/gestor.php` e `gestor/bibliotecas/gestor.php` sao arquivos DIFERENTES.** O primeiro e o
  roteador e chama `gestor_start()` no fim — nao pode ser carregado por teste. O `tests/bootstrap.php`
  carrega a **biblioteca**. Funcao que precisa de teste vai para la; o roteador so a chama.
- **`bibliotecas/gestor.php` termina com `?>`.** Codigo acrescentado depois dessa tag vira texto
  literal e a funcao simplesmente nao existe — sem erro de sintaxe e sem aviso.
- **Contar ocorrencias de string nao prova integridade de HTML.** A primeira prova acusou um
  `<template>` perdido que era mencao textual dentro de um comentario removido. Comparar a **arvore
  DOM** (`DOMDocument` + XPath, elementos + atributos ordenados) acusa o que importa e nao inventa o
  que nao houve.
- **Em limpeza de HTML, cada excecao tem motivo proprio**: `<pre>`/`<textarea>` porque espaco ali e
  conteudo; `<script>` porque JS nao se limpa com regex (um `//` em string, uma regex com `/*`);
  condicionais porque sao INSTRUCAO. Tirar os blocos do texto e devolve-los por marcador e mais
  seguro que ensinar cada expressao a desviar deles — basta uma esquecer.
- **A quebra de linha nao e lixo**: entre elementos inline ela e renderizada. So a indentacao sai.
- **Gate com valor invalido deve cair no modo SEGURO, nao no desligado.** `HTML_SANITIZE=talvez`
  precisa limpar em producao: senao um erro de digitacao vira vazamento de comentario interno, e
  ninguem liga uma coisa a outra.
- **O update do sistema faz merge ADITIVO do `.env`** a partir de
  `autenticacoes.exemplo/<dominio>/.env` (ver `atualizacoes-sistema.php`). Chave nova de ambiente
  entra ali para chegar as instalacoes. **O agente e barrado por permissao em `.env*`** — a linha vai
  para o operador aplicar.
- **A leitura de `.env*` foi LIBERADA para o agente em 2026-08-26.** A regra antiga (entregar a
  linha ao operador) nao vale mais: escrever a chave no template
  `gestor/autenticacoes.exemplo/dominio/.env` e trabalho do proprio agente.
- **O `.env` ativo do projeto local fica em**
  `dev-environment/data/sites/localhost/<projeto>/autenticacoes/localhost/.env` — o
  `autenticacoes.exemplo/dominio/.env` e so o TEMPLATE do merge aditivo. Mexer no primeiro para
  testar um gate e legitimo; restaurar depois e obrigatorio.
- **A pagina publica do ambiente local responde a `curl` sem autenticacao** (`http://localhost/photon/`
  = HTTP 200 com HTML real). Da para validar em RUNTIME qualquer coisa que nao exija sessao, em vez
  de deixar "pendente do operador" — foi assim que os tres modos do `HTML_SANITIZE` foram medidos.
- **`admin-environment` e a tela que edita o `.env`.** Quando a demanda pedir "configuravel via
  gestor" e a configuracao for de ambiente, o caminho e esse — nao uma tabela nova.
- **"Nao da para limpar com regex" quase nunca significa "nao da para limpar".** Significa que a
  ferramenta esta errada. Comentario de JavaScript exige um **scanner com estado** (string simples,
  dupla, template literal, comentario de linha, de bloco e regex literal) — com regex, o erro nao
  aparece para quem escreveu: aparece como codigo truncado na pagina de outra pessoa.
- **A barra do JavaScript e o caso dificil**: `/` abre regex ou divide, e so o token anterior decide
  (depois de identificador, numero, `)` ou `]` e divisao). Isolar essa decisao numa funcao propria e
  faze-la errar **para o lado do regex**: tratar regex como divisao faz o conteudo virar codigo.
- **Quebra de linha em JS nao e cosmetica** (ASI): juntar linhas muda o programa. So a indentacao
  sai, e comentario de bloco com quebras precisa deixar uma quebra no lugar.
- **`<script>` tambem e deposito**: `application/json` e `text/template` guardam dado e markup.
  Rodar limpeza de codigo neles corrompe conteudo. Na duvida, nao processar.
- **Validar minificacao comparando strings nao prova nada.** Rodar `node --check` em cada bloco
  inline da pagina ja limpa e o que afirma que o resultado **ainda e um programa valido**.
- **Docblock PHP nao pode conter a sequencia de fecha-comentario** num exemplo de codigo: ela fecha
  o proprio comentario e o parse quebra longe dali.
- **Heredoc do Bash destroi barras invertidas e escapes** (`\b` virou byte 0x08 numa regex PHP).
  Para codigo com regex ou strings escapadas: escrever em arquivo separado, ou montar com
  `chr(92)`/`chr(39)` no Python, ou editar por indice de linha.


### 2026-08-24 — BATCH-129: onde o recurso NASCE decide se a edição vale (req-127)

- **`gestor/db/data/*Data.json` é ARTEFATO, nunca fonte.** O intake mandava editar `ModosIaData.json`;
  a fonte real dos modos de IA é `gestor/modulos/<mod>/resources/<lang>/ai_modes/<id>/<id>.md`, e o
  JSON é compilado por `atualizacao-dados-recursos.php` (`c2f resources:sync`). Editar o JSON à mão
  seria desfeito no próximo sync — sem erro, sem aviso.
- **`versao` de `ai_modes`/`ai_prompts` nunca incrementa** (bug pré-existente, não corrigido):
  `carregarDadosExistentes()` indexa como `modos_ia`/`prompts_ia`, `versaoChecksumPrompt()` consulta
  como `ai_modes`/`ai_prompts`. **Não bloqueia propagação**: o sync do banco decide pelo md5 do
  ARQUIVO e faz UPSERT campo a campo; `modos_ia` tem `preserve_on_user_modified: []`, então o prompt
  novo sobrescreve inclusive o que o operador editou no painel.
- **Corte sequencial mata o namespace pequeno.** No `transformamp` são 63 cores contra 3
  `--shadow-*`: o laço em ordem consome o orçamento nas cores e o outro namespace some inteiro.
  Round-robin entre namespaces resolve; a saída volta à ordem natural depois.
- **Marcador anexado DEPOIS do laço tem de caber no orçamento DO laço.** O `/* +N */` de restantes
  fechava o `transformamp` em 1.502 bytes contra teto de 1.500. Reservar `strlen("\n/* +" . (total - i) . " */")`
  a cada iteração é conservador e sempre suficiente (nunca fica curto por mudança de dígito).
- **Bloco condicional em prompt: a tag tem de sair mesmo quando o marcador não está lá.** O modo é
  editável no painel de IA e vive no banco. `modelo_tag_del()` sem par não corta nada e a tag vaza
  literal para o payload; e com o par INVERTIDO ele corta por posição e leva o resto do prompt junto.
  Guard de ordem antes, troca por vazio sempre depois.
- **Heredoc via stdin nesta ferramenta é lido como cp1252 e come `\\n`.** Âncora de patch com escape
  ou acento falha em silêncio (`replace` não casa). Escrever o script Python em ARQUIVO com o Write
  e executá-lo — foi assim que os 8 patches deste lote foram aplicados sem corromper nada.
- **`componentes.md` é CRLF; os outros `.md` de modo são LF.** Inserir bloco sem respeitar o fim de
  linha do arquivo mistura os dois no mesmo recurso.
- **Prompt só existe dentro da função que o envia.** Para provar o critério de aceite sem homologação
  manual, o teste de integração declara um dublê de `ia_enviar_prompt()` (a suíte nunca carrega
  `ia.php`) e inspeciona o que foi montado.

### 2026-08-21 — BATCH-127 (req-125)

Integral em `implementation/BATCH-127.md`. Destilado: onde o dado MORA decide se a correcao existe —
icone de modulo de projeto vive no banco do projeto, entao migracao no core nao o alcanca; `hidden`
perde para `inline-flex` de utility mais especifica; e recarregar a pagina em erro de CSRF sem
limpar o estado repete o erro em looping.


### 2026-08-21 — BATCH-126 (req-124)

Integral em `implementation/BATCH-126.md`. Destilado: nome de icone e endereco num catalogo, e
`createIcons()` devolve o `<i>` intacto quando o nome nao existe — validar contra o catalogo REAL;
`style.marginLeft = ''` devolve o controle a utility em vez de zerar (use `'0px'`); `margin-top` no
`<html>` nao alcanca `fixed`/`sticky`; item flex com `h-full` nao encolhe (o par e `min-h-0 flex-1`);
classe aplicada por JS precisa do arquivo em `tailwind_sources`; troca de marcador nao pode casar
marcacao (o `<td>` ganhou classes no Tailwind e o token cru foi para a tela); verde e semantica
(sucesso/ativo `emerald`), botao/link/foco sao azul Conn2Flow (`sky`).


## Pendências e Histórico

- O CLI universal `c2f` (REQ-013 / BATCH-016) e os detalhes anteriores ao BATCH-126 permanecem recuperáveis em `sdd/validation/archive/` e `sdd/implementation/archive/`.
