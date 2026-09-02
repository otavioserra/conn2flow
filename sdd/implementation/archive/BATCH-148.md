# BATCH-148 — Fontes hospedadas pelo projeto e minificação de JavaScript no build

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-143.md` (fontes) + `sdd/human-requests/req-145.md` (minificação)
- **Data de abertura**: 2026-08-29
- **Classificação**: privacidade/LGPD, desempenho de entrega, arquitetura de derivados

---

## 1. Regressão corrigida antes de tudo (ícones do gestor)

O BATCH-146 migrou o CSS do Fomantic para o disco e **os ícones do layout administrativo sumiram**.

O `semantic.min.css` pede as fontes por caminho **relativo a ele próprio**
(`url(themes/default/assets/fonts/icons.woff2)`). Servido do CDN, isso resolvia contra o domínio do
CDN. Servido do disco, passou a resolver contra `vendor/fomantic-ui/2.9.4/` — onde os arquivos não
existiam, porque eu tinha migrado só o que aparece em `<link>`/`<script>`.

O registro ganhou a chave `arquivos`: dependências que a biblioteca pede sozinha e que **não viram
tag**. Uma biblioteca só está local de verdade quando tudo que ela pede está local. 22 arquivos
(icons, outline-icons, brand-icons e Lato, em woff2 e woff) — com eles, o Fomantic também deixa de
buscar a fonte Lato fora.

## 2. Google Fonts hospedado pelo projeto (req-143)

Um `<link>` para `fonts.googleapis.com` faz o navegador de **cada** visitante abrir conexão com o
Google e entregar IP, `Referer` (a página exata que a pessoa está lendo) e `User-Agent`, antes de
qualquer consentimento. Em 2022 o Landgericht München I condenou o operador de um site exatamente
por isso (Az. 3 O 17493/20). A licença não é obstáculo: as famílias são OFL ou Apache 2.0.

`c2f assets:fonts` descobre as URLs já declaradas nos recursos do projeto, busca o CSS com
User-Agent moderno (o Google devolve `woff2` só para quem sabe lê-lo; senão manda `ttf`), baixa cada
arquivo e reescreve o `src` para caminho relativo local.

**Filtro de subset**: das 78 faces devolvidas para as quatro famílias do `transformamp`, 50 são
cirílico, grego e vietnamita. O `unicode-range` das 28 sobreviventes é **preservado** — é ele que faz
o navegador baixar só o subset necessário, então removê-lo transformaria a economia em desperdício.

**Escreve na FONTE, não no espelho**: `ProjectEnvironmentResolver::resolve()` prefere `path_tests`,
mas o que o pipeline lê e o deploy publica é `path`. Gravar no espelho faria o trabalho sumir no
próximo sync, sem erro nenhum.

Aplicado: 28 arquivos (1,1 MB) + `fonts.css`; `<link>`/`preconnect` saíram de 7 layouts e um
`@import` saiu do CSS de destaques — esse, por ser `@import`, ainda **bloqueava a renderização** até
o Google responder.

## 3. Minificação de JavaScript (req-145)

A sugestão original era minificar no roteamento. **Não é ali**, e a razão é concreta: o
`arquivo-estatico` ganhou no BATCH-100 `Content-Length`, `Accept-Ranges`, `ETag` e `304`, e os
quatro dependem de o corpo entregue ser exatamente o arquivo em disco.

A solução separa as duas coisas:

- **minificar** é build (`c2f assets:minify`, com `terser` que já estava em devDependencies);
- **escolher** qual arquivo enviar é resolução (`arquivo_estatico_preferir_minificado()`).

Assim o envio continua sendo um envio de arquivo, e as quatro garantias seguem corretas — calculadas
sobre o arquivo realmente escolhido.

O derivado é irmão de `css_precompiled` (CR-002): gerado por build, nunca editado à mão, sempre
recalculável. Procedência (`sha1` do fonte) fica em `gestor/assets/minify-manifest.json`, e
`--verificar` acusa derivado velho.

Em `DEVELOPMENT_ENV=true` serve sempre o fonte: depurar com nomes destruídos pelo `--mangle` é pior
que baixar alguns KB a mais — e isso torna inofensivo o derivado envelhecer enquanto alguém edita.

**Limite honesto**: `node --check` valida sintaxe, não semântica. Código que dependa do nome de
funções em runtime pode minificar sem erro e falhar no navegador.

### Correção de uma afirmação minha

A primeira leitura da req-145 dizia que os 925 KB do DataTables sairiam "de graça". A medição
desmentiu: o runtime já inclui `datatables.min.js`. Aqueles arquivos são peso de repositório e
superfície exposta, **não** bytes entregues. O ganho real estava no JavaScript de autoria.

## 4. Validação

| Evidência | Resultado |
| --- | --- |
| JavaScript de autoria | 1.588,3 KB → **739,5 KB (-53%)**, 64/64 sem falha |
| `interface.js` entregue | 30,6 → 16,9 KB |
| `Content-Length` | 17305, batendo byte a byte com o derivado |
| `If-None-Match` | **304** |
| `Range: bytes=0-99` | **206**, 100 bytes |
| `node --check` no JS entregue | 7/7 válidos |
| Fontes do Fomantic | 4/4 respondendo 200 |
| Home, home-alternativa, artigos, dashboard | **zero** requisição a domínio externo |
| PHPUnit | 918/918 (16 novos) |
| Vitest | 378/378 |

## 5. Pendências

- Homologação visual do operador (ícones do gestor, tipografia do site público, telas com editor).
- O repositório `transformamp` tem alterações **não commitadas** (7 layouts, 1 CSS, 29 arquivos de
  fonte). Autorização de commit foi dada para o `conn2flow`, não para ele.
- `gestor/assets/datatables/`: 67 arquivos, 1,7 MB, dos quais o sistema usa 2. Remoção é decisão do
  operador — biblioteca em uso, ganho de repositório e não de banda.
- `html-editor-interface.js` ainda monta o iframe de preview com URLs de CDN fixas (BATCH-146).
