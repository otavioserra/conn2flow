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

- **Nome de ícone é endereço DENTRO de um catálogo, e o catálogo depende do framework.** O item do
  menu é `<i data-lucide="X" class="X icon">` e serve aos DOIS: `createIcons()` devolve o `<i>`
  intacto quando o nome não existe, e toda regra do `icon.min.css` é prefixada com `i.icon` (não
  alcança o `<svg>` convertido). **Valide sempre contra o catálogo REAL** — foi assim que 19 módulos
  sem glifo e um `settings2` inválido apareceram.
- **`style.marginLeft = ''` NÃO zera margem: devolve o controle à utility** (`lg:ml-[260px]`). Zere
  explicitamente (`'0px'`). Mesma família do que o BATCH-127 encontrou com `hidden`/`inline-flex`.
- **Item flex com `h-full` e `min-height:auto` não encolhe** e empurra o irmão para fora do viewport.
  O par é `min-h-0 flex-1`, com `min-h-0` no filho que rola.
- **`margin-top` no `<html>` não alcança `fixed`/`sticky`.** A compensação da Editbar é classe no
  `<body>` (`c2f-toolbar-ativa`) + CSS que reancore cada elemento em `top:0`.
- **Troca de marcador não pode casar marcação.** `modelo_var_troca($p,"<td>#historico#</td>",…)` só
  funcionava no componente Fomantic; no Tailwind o `<td>` tem classes e o token cru ia para a tela.
- **Classe aplicada por JS precisa do arquivo em `tailwind_sources`**, senão fica fora do bundle.
- **Verde é semântica, não marca.** Sucesso/ativo é `emerald`; botão, link, foco e aba ativa são azul
  Conn2Flow (`sky`).

## Pendências e Histórico

- O CLI universal `c2f` (REQ-013 / BATCH-016) e os detalhes anteriores ao BATCH-126 permanecem recuperáveis em `sdd/validation/archive/` e `sdd/implementation/archive/`.
