# BATCH-157 — Checksum idempotente e independente de plataforma no AdminCronReq032Test (REQ-035 / req-155)

- **Status**: implemented-pending-review
- **Intake**: `sdd/human-requests/req-155.md` e `conn2flow-site/sdd/human-requests/host-manager/req-035-provisionamento-assincrono-hestiacp-e-alinhamento-testes-cron.md`
- **Data**: 2026-09-02
- **Classificação**: correção de teste, integridade de recursos, estabilização de CI
- **Lote irmão**: `BATCH-028` no `conn2flow-site`

---

## 1. Diagnóstico causal

O lote atravessou **duas** causas. A primeira era a do intake; a segunda só apareceu quando a
primeira saiu da frente, e é a que fechava o laço.

### 1.1 O checksum é derivado, não autoria (REQ-035)

`testChecksumsNascemVaziosParaOPipelineCalcular` exigia que **todo** campo de `checksum` em
`admin-cron.json` fosse string vazia. A leitura por trás disso era razoável — um valor ali só
poderia ter sido escrito à mão — e estava errada.

`atualizacao-dados-recursos.php` grava o md5 do HTML de volta no manifesto do módulo, como
histórico incremental (`ORIGIN_UPDATE_MODULE`, `atualizarArquivosOrigemModulos()`). Não foi
digitado por ninguém: foi produzido pelo próprio pipeline. É o que a ressalva da seção 4 do
[BATCH-155](BATCH-155.md) já havia registrado — zerar o campo (req-153) fazia o CI passar por uma
tarde, e o `resources:sync` seguinte o preenchia de novo.

O invariante que o teste queria proteger continua valendo; estava expresso na forma errada.

### 1.2 O md5 dependia do fim de linha da árvore de trabalho (req-155)

Corrigida a asserção, o CI da release `gestor-v2.10.3` falhou de novo — agora com **dois hashes
concretos** em vez de um contra vazio:

```
-'6a4af6df04b77f8693400757bc7858df'   (esperado: md5 do arquivo, calculado no runner)
+'387ee81b1f9dd115a8d96c7ca2b92d72'   (gravado no manifesto)
```

Medido: os dois são do **mesmo arquivo**, em duas formas.

```
$ git ls-files --eol .../admin-cron.html
i/lf    w/crlf                          # índice em LF, árvore de trabalho em CRLF
md5 do conteúdo com CRLF → 387ee81b1f9dd115a8d96c7ca2b92d72
md5 do conteúdo com LF   → 6a4af6df04b77f8693400757bc7858df
```

`buildChecksum()` calcula o md5 dos **bytes lidos do disco**, e o disco não é o mesmo em todo
lugar. O repositório guarda o HTML com LF; a árvore de trabalho no Windows tinha CRLF. São 233
quebras de linha neste arquivo — 233 bytes de diferença, hash completamente distinto. O compilador
rodando no Windows gravava o md5 de CRLF, e o runner Linux comparava com o de LF.

**A solução exigida pela req-155 — regravar o hash com `manager:update-all` — não fecharia o
laço**: rodado no Windows com a árvore em CRLF, o valor novo seria de novo o de CRLF, e o CI
voltaria a divergir. Mesmo laço da req-153, uma volta adiante.

## 2. Implementação

### Asserção por coincidência, tolerante à quebra de linha

`testChecksumsNascemVaziosParaOPipelineCalcular` → `testChecksumHtmlCoincideComMd5DoArquivo`.

Por idioma:

1. **Coincidência.** `checksum.html`, quando preenchido, tem de ser o md5 do HTML do recurso — em
   qualquer uma das duas formas de fim de linha (`md5DoArquivoEmAmbasAsQuebras()`). Um valor
   digitado à mão não bate com nenhuma delas, que era o ponto original; e a asserção deixa de
   depender de onde a suíte roda.
2. **Forma.** Todo campo é vazio ou 32 dígitos hexadecimais. Texto arbitrário não passa por
   nenhum dos dois lados.
3. **Derivação do `combined`.** `buildChecksum()` calcula `md5($html . $css . $cssPrecompiled)`;
   sem CSS, `combined` colapsa em `checksum.html`. Divergir aí significaria que um dos três campos
   foi editado isoladamente — a forma que a edição manual tomaria hoje.

O campo vazio segue aceito: é o estado de um recurso que ainda não passou pela compilação.

### Recompilação a partir da forma canônica

Com o HTML restaurado ao estado do repositório (`git checkout` devolveu LF, já que
`core.autocrlf = input`), `resources:sync` regravou o manifesto com
`6a4af6df04b77f8693400757bc7858df` — exatamente o hash que o runner calcula.

O mesmo alcançou o `PaginasData.json`, que carregava o HTML com `\r\n` embutido: verificado campo
a campo que o conteúdo é **idêntico após normalizar a quebra de linha**; mudaram apenas o checksum
e o bump de versão que ele provoca (`versao 1 → 2`, `file_version 1.3 → 1.4`).

As duas correções são complementares, e é isso que fecha o laço: o dado ficou na forma que o CI
espera, **e** o teste deixou de reprovar a outra forma se alguém compilar com CRLF na árvore.

## 3. Validação

| Alvo | Resultado |
| ---- | --------- |
| Falha reproduzida antes (REQ-035) | `[pt-br] checksum html não pode ser escrito à mão`, esperado `''`, obtido `387ee81b…` |
| Falha reproduzida antes (req-155) | `checksum html divergente do arquivo`, `6a4af6df…` vs `387ee81b…` |
| `AdminCronReq032Test` | **44/44**, 302 asserções |
| **Simulação do runner Linux** | HTML convertido para LF em disco → 44/44; restaurado depois |
| PHPUnit completo | **1.073/1.073**, 7.418 asserções, 4 skipped |
| Vitest completo | **382/382** (27 arquivos) |
| Idempotência do `resources:sync` | Executado com árvore limpa: manifesto inalterado na 2ª passagem |
| Diff do `PaginasData.json` | 2 páginas (`admin-cron` pt-br/en); HTML idêntico após normalizar CRLF |

### Revalidação local final (2026-09-02)

- `php cli/c2f.php resources:sync --force` concluiu com sucesso: 2.844 recursos, 237 recursos
  Tailwind em cache e nenhum problema de recurso detectado.
- Os dois `checksum.html` e `checksum.combined` do `admin-cron.json` são
  `6a4af6df04b77f8693400757bc7858df`, igual ao MD5 dos HTMLs em `pt-br` e `en` presentes na
  árvore de trabalho.
- O teste focal, executado depois do sync, passou em **44/44** com **302 asserções**.
- `composer test` passou em **1.073/1.073**, com **7.418 asserções**, 4 skips e 2 deprecações
  reportadas pelo PHPUnit; não houve falhas.
- A publicação opcional de assets em `dist/` não foi executada porque `PUBLIC_PATH`/`--public` não
  está configurado. Isso não impediu a sincronização dos recursos: o próprio CLI confirmou que as
  URLs continuam resolvendo por `arquivo-estatico`.

## 4. Observações

- A primeira rodada deste lote foi **arrastada para o commit `3d705ee7`** (`feat(sdd): finalizar
  req-153 (BATCH-155) e BATCH-156…`), de outro agente trabalhando em paralelo. Foi assim que o
  teste novo chegou ao CI da release 2.10.3 e produziu o segundo diagnóstico — de graça, e antes
  de alguém depender dele. É também o cenário que a regra de proibição de `git add -A` existe para
  evitar.
- **Prevenção mais forte, fora do escopo**: um `.gitattributes` com `*.html text eol=lf` fixaria a
  forma na árvore de trabalho de todas as plataformas, e o checksum deixaria de ter duas formas
  possíveis. Vale como intake próprio — mexe em todos os recursos do repositório, não só neste.
- `resources:sync` reporta `Tarefas de Cron: 0` no núcleo: nenhum módulo do core declara a chave
  `cron`. A tarefa da fila de provisionamento vive no `conn2flow-site` e é compilada por
  `project:sync-resources`.

## 5. Arquivos tocados

- `tests/Unit/PHP/AdminCronReq032Test.php`
- `gestor/modulos/admin-cron/admin-cron.json` (checksum recompilado da forma canônica)
- `gestor/db/data/PaginasData.json` (idem; HTML sem `\r\n` embutido)
- `gestor/resources/.tailwind-build-manifest.json` (derivado do mesmo sync)
