# CR-002 — Procedência do CSS derivado: autoria x derivado, assinatura e regeneração

- **Status**: proposto
- **Data**: 2026-08-28
- **Origem**: req-141 (intake do Arquiteto) + reanálise conduzida com o Engenheiro Chefe
- **Impacto**: `gestor.php`, `bibliotecas/gestor.php`, `bibliotecas/html-editor.php`, política de
  atualização (`atualizacoes-banco-de-dados.php` + `schema-metadata.json`), editor online,
  compilador offline
- **Batch de execução**: BATCH-144 (fase 1)

---

## 1. Problema normativo

Hoje o sistema trata `html`, `css`, `css_precompiled` e `css_compiled` como **quatro campos
independentes**. Eles não são independentes: os dois primeiros são AUTORIA e os dois últimos são
DERIVADOS da autoria.

Três produtores escrevem nesses campos, em momentos diferentes e sem coordenação:

| Produtor | Escreve | Lê como fonte |
| --- | --- | --- |
| Compilador offline (Tailwind CLI, via `resources:sync`/deploy) | `css_precompiled` | arquivos em `resources/` (disco) |
| Editor online (Tailwind Browser/CDN) | `html`, `css`, `css_compiled` | HTML do BANCO |
| Política de atualização (deploy com `user_modified`) | preserva uns, sobrescreve outros | `schema-metadata.json` |

**Nenhum dos três registra de que entrada aquele CSS foi derivado.** Sem procedência, é impossível
decidir se o conjunto é coerente — e o runtime entrega estados híbridos sem emitir erro algum.

## 2. Evidência medida (ambiente local `transformamp`, `DEVELOPMENT_ENV=false`)

- Cobertura de classes na resposta HTTP: **80 de 248 (32%)** sem CSS na home; **85 de 182 (47%)**
  em `/artigos/teste-de-pagina/`.
- `paginas` de publicação nascem com `css_precompiled = 0` e `css_compiled = 0`: o compilador
  offline só varre arquivos físicos e essas páginas nunca existiram em disco.
- 14 registros de `paginas` têm `css_compiled` contendo CSS do **editor Quill** (com `!important`),
  servido na página pública.
- `preserve_on_user_modified` de `paginas` inclui `css_compiled` (um DERIVADO) e
  `aplicarPoliticaCssPrecompiled()` remove `css_precompiled` da preservação. Resultado: atualização
  de sistema em recurso editado pelo usuário entrega **HTML do usuário + CSS derivado do sistema**.

## 3. Decisão normativa proposta

### 3.1 Classificação dos campos (novo contrato)

- **Autoria** (`html`, `css`): pertence a quem escreveu. Preservada conforme `user_modified` e
  `project`, como já é hoje.
- **Derivado** (`css_precompiled`, `css_compiled`): **nunca é autoria**. Não deve ser preservado
  como se fosse; deve ser sempre recalculável a partir da autoria vigente.

Consequência direta: `css_compiled` sai de `preserve_on_user_modified`. O que protege o trabalho do
usuário é a preservação da AUTORIA — o derivado se refaz a partir dela.

### 3.2 Assinatura de procedência

Todo CSS derivado passa a carregar a assinatura das entradas que o geraram:

```
assinatura = hash(html + css autoral + identidade do baseline)
```

Em runtime a comparação é barata. Assinatura ausente ou divergente significa **stale**: o sistema
passa a SABER que o CSS não corresponde ao HTML, em vez de renderizar um híbrido em silêncio.

### 3.3 Regeneração

- **Online**: o editor (Tailwind Browser) regenera e reassina ao salvar — paridade imediata, sem
  depender de toolchain no servidor.
- **Offline**: o compilador passa a poder usar como fonte o HTML EFETIVO do banco, regenerando em
  lote o que estiver stale (roda no deploy e no ambiente local, onde há `node`).

Ambos escrevem a mesma assinatura, então convergem em vez de disputar.

### 3.4 Invalidação em vez de descarte

Enquanto um derivado está stale, o valor antigo é MANTIDO (degradação graciosa) e marcado. Apagar
deixaria a página sem CSS nenhum em produção, onde não há como recompilar na hora.

## 4. Ordem temporal resolvida

```
T1  usuário edita online         -> derivado assinado com o baseline de T1
T2  layout alterado no projeto   -> assinatura das páginas daquele layout diverge = STALE
T2' deploy regenera do banco     -> html do usuário (preservado) + CSS derivado DELE
T3  usuário edita online         -> reassina
```

"O último vence" passa a ser verificável, porque o derivado é sempre função da autoria vigente e a
assinatura prova a procedência.

## 5. Fatiamento

1. **Fase 1 (BATCH-144)**: assinatura + auditoria (tornar o problema mensurável) e correção da
   captura que grava CSS do Quill.
2. **Fase 2**: atomicidade na política de atualização (`css_compiled` deixa de ser preservado).
3. **Fase 3**: regeneração offline a partir do banco.
4. **Fase 4**: `publisher-pages` passa a extrair CSS; baseline do editor passa a incluir as
   dependências, como o docblock de `html_editor_css_precompiled_baseline()` já promete.

## 6. Aprovação

Arquitetura aprovada pelo Engenheiro Chefe em 2026-08-28, com a correção de que `css_precompiled` e
`css_compiled` são recalculáveis e não devem ser preservados como autoria.
