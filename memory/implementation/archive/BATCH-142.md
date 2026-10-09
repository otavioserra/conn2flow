# BATCH-142 — Refinamentos de densidade, edição rápida e alinhamento vertical no galleries

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-139.md`
- **Data de abertura**: 2026-08-26
- **Classificação**: implementação incremental / ergonomia do CRUD e renderização do widget `galleries`

## Objetivo

Refinar os modos compactos do gerenciador de imagens sem alterar a ordenação ou o contrato de
persistência existente, devolver acesso à edição de legenda/link por um controle em overlay e
permitir escolher o corte vertical aplicado às imagens nos quatro modelos públicos da galeria.

## Slice aprovado

1. Grade desktop com seis cards no modo médio e dez no pequeno, miniaturas de aproximadamente
   110 px e 65 px e breakpoints responsivos preservados.
2. Highlight estável no hover e overlay de ações alinhado ao tratamento visual do
   `admin-arquivos`, sem mudança de geometria.
3. Engrenagem nos modos compactos abrindo edição rápida de legenda e configuração de link do item,
   atualizando `items` e o preview imediato.
4. Controle global `image_position` (`top|center|bottom`) nos três CRUDs localizados, serializado e
   hidratado com fallback `center`.
5. Resolução segura do valor e da classe Tailwind no backend, aplicada às imagens dos quatro
   templates em `pt-br` e `en` e respeitada no preview/runtime do widget.

## Fora do escopo

- Alterar a ordem ou o formato dos itens persistidos, o Sortable ou o canal do picker de arquivos.
- Criar alinhamento horizontal, posicionamento livre ou configuração por imagem.
- Reestruturar os quatro modelos de galeria além da aplicação do alinhamento vertical.
- Fazer commit, push ou deploy remoto.

## Decisões de implementação

- O código-fonte atual é a autoridade para nomes de campos, ciclo do preview e marcadores dos
  templates; divergências literais do intake serão registradas nas evidências.
- Textos visíveis novos serão recursos localizados do módulo, nunca literais fixos no JavaScript.
- O valor de `image_position` será normalizado por allowlist no backend antes de virar estilo ou
  classe, mantendo `center` para schemas antigos ou inválidos.
- O overlay continuará fora do fluxo do card; a validação geométrica deve provar zero layout shift.

## Contrato de validação

- `php -l` nos PHP do módulo, `node --check` nos dois JavaScript e parse de `galleries.json`.
- Testes focados para grade/overlay/edição rápida, serialização/hidratação e resolução segura de
  `image_position` nos quatro templates.
- `c2f resources:sync`, com inspeção dos artefatos regenerados; suítes PHPUnit e Vitest verdes.
- Validação visual autônoma em galeria com 15+ fotos: densidade, geometria no hover, edição rápida,
  persistência no preview e corte `top|bottom` no preview e no runtime público.
- Review findings-first, `git diff --check` e confirmação de que nenhum commit, push ou deploy
  remoto foi executado.

## Evidências

- CRUD e schema: seis páginas localizadas (`adicionar`, `editar`, `clonar` em `pt-br`/`en`),
  normalização allowlist de `image_position` e nove variáveis novas por idioma.
- Renderer: oito templates localizados recebem valor/classe/atributo; o widget PHP normaliza
  `top|center|bottom` e o JavaScript público aplica o corte antes da inicialização do modelo.
- Testes focados: Vitest **7/7**; PHPUnit **5/5**, com **74 asserções** sobre normalização, globais,
  templates e CRUDs.
- Suítes completas: Vitest **366/366** (25 arquivos) e PHPUnit **789/789** (3.497 asserções;
  1 depreciação e 4 skips preexistentes).
- Lint/estrutura: `node --check` **2/2**, `php -l` **3/3**, JSON válido e `git diff --check` limpo.
- `c2f resources:sync`: **2.678** recursos, **233/233** Tailwind em cache na rodada final, zero
  erros e quatro avisos preexistentes fora do módulo.
- Runtime local autenticado (`/photon/galleries/adicionar/`, viewport 1920 px, 18 imagens):
  **6** cards/linha e thumb **110 px** no médio; **10** cards/linha e thumb **65 px** no pequeno;
  geometria idêntica antes/depois do hover; borda `rgb(33,133,208)`, sombra azul, backdrop
  `rgba(0,0,0,.55)`, raio 4 px e overlay absoluto com `opacity:1`/`pointer-events:auto`.
- Modal rápido: rótulos pt-BR, legenda, URL, tipo e `_blank` persistiram no mesmo item ao fechar;
  preview recebeu payloads `top`/`bottom` e mediu respectivamente `50% 0%`/`50% 100%` em 18 imgs.
- Screenshots: `temp/req-139-medium-overlay.png`, `temp/req-139-quick-settings.png` e
  `temp/req-139-small-bottom.png`; console e `pageerror` vazios.
- Finding do review: o primeiro CSS deixava `pointer-events:none` no contêiner mesmo no hover;
  corrigido para `auto`, provado por teste e estilo computado. Nenhum finding restante.
- Restrição local: a base Photon não possuía galeria e estava com páginas/templates/variáveis antigos;
  o dry-run do atualizador avançou checksums sem aplicar as linhas. Para não usar `force-all` sobre
  recursos do tenant, o Playwright carregou no editor o template e os rótulos versionados deste lote;
  o endpoint PHP real e o `galleries.widget.js` real fizeram a renderização. O ambiente foi restaurado
  para `PRODUCTION` (`DEVELOPMENT_ENV=false`).
- Nível 1 respeitado: nenhum commit, push ou deploy remoto.
