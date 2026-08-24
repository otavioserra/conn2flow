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

### 2026-08-21 — BATCH-127: onde o dado mora decide se a correção existe (req-125)

- **Id inventado não cadastra nada, e não avisa.** A F2 do intake nomeia os módulos em português
  (`catalogo-3d`, `conexoes-sociais`), mas os ids REAIS são os do `conn2flow-site`: `3d-catalog`,
  `social-connections`, `publisher-social-media` e `modulos-grupos-distribuido` (SINGULAR). Gravar o
  par no `ModulosData.json` do NÚCLEO com id em português cria linha órfã em `modulos` de todo
  ambiente — sem página associada, o menu a descarta em silêncio e o ícone continua faltando.
  **Ícone de módulo de projeto se grava no `*Data.json` do projeto; a migração no núcleo é o que
  alcança bancos já existentes** (UPDATE sem correspondência = zero linhas, então rodar no núcleo é
  inócuo por construção).
- **`classList.add('hidden')` NÃO esconde um `inline-flex`.** No bundle, `.inline-flex` é emitida
  depois de `.hidden`: mesma especificidade, mesma camada, ganha a última. Quem apaga é o atributo
  booleano `hidden` — o preflight o serve como `display:none!important` em `@layer base`, e
  `!important` INVERTE a ordem das camadas. Vale para `lg:hidden` também: é media query emitida
  depois, então precisa ser removida no boot (igual ao `lg:translate-x-0` da barra lateral) para o
  runtime conseguir mostrar o botão de novo.
- **Atributo vazio não é omissão.** `createIcons()` seleciona `[data-lucide]` pela PRESENÇA do
  atributo: `data-lucide=""` gera o mesmo warning que o nome errado. O backend precisa montar o
  ATRIBUTO INTEIRO (marcador `#icon-lucide#` no template), nunca só o valor.
- **Função pura no bootstrap é função não testável.** `gestor/gestor.php` termina em
  `gestor_start()` e não pode ser incluído por um caso de teste — o que sobra é procurar o nome da
  função no arquivo, e isso passa mesmo com o corpo errado. O lar é `gestor/bibliotecas/gestor.php`,
  carregada pelo `config.php`.
- **Comentário que cita o código antigo quebra o guard que procura por ele.** O mesmo tipo de
  armadilha do marcador citado dentro do layout: o teste acusou código que já não existia.
- **Cuidado com heredoc e barra invertida nesta ferramenta**: uma sequência de escape de padrão PCRE (o word-boundary) virou 0x08
  dentro do arquivo e o teste falhou contra código correto. Varrer caracteres de controle depois de
  editar por script.

### 2026-08-21 — BATCH-126: vocabulário errado não dá erro, dá tela vazia

- **Nome de ícone é endereço dentro de um catálogo, e o catálogo depende do framework.** `createIcons()`
  devolve o `<i>` intacto quando o nome não existe, e `icon.min.css` prefixa tudo com `i.icon` (não
  alcança o `<svg>` convertido). Validar contra o catálogo REAL — foi assim que 19 módulos sem glifo
  apareceram.
- **`style.marginLeft = ''` não zera margem: devolve o controle à utility.** Zere com `'0px'`. Mesma
  família do `hidden` vs `inline-flex` do BATCH-127.
- **`margin-top` no `<html>` não alcança `fixed`/`sticky`** (a compensação da Editbar é classe no
  `<body>`); **item flex com `h-full` não encolhe** (o par é `min-h-0 flex-1`); **classe aplicada por
  JS precisa do arquivo em `tailwind_sources`**, senão fica fora do bundle.
- **Troca de marcador não pode casar marcação.** `modelo_var_troca($p,"<td>#historico#</td>",…)` só
  valia no Fomantic; no Tailwind o `<td>` tem classes e o token cru foi para a tela.
- **Verde é semântica, não marca.** Sucesso/ativo é `emerald`; botão, link, foco e aba ativa são azul
  Conn2Flow (`sky`).

## Pendências e Histórico

- O CLI universal `c2f` (REQ-013 / BATCH-016) e os detalhes anteriores ao BATCH-126 permanecem recuperáveis em `sdd/validation/archive/` e `sdd/implementation/archive/`.
