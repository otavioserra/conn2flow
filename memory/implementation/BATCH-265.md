# BATCH-265 — Miniaturas dos modelos do cadastro de modelos (linha 3.0)

- **Requisição:** [REQ-256](../human-requests/req-256.md)
- **Status:** `implemented-pending-homologation`
- **Linha:** `3.0` (branch `feat/req-256`, entregue na `3.0` e na `main`)
- **Data:** 2026-10-07
- **Ambiente de teste:** `https://conn2flow.local/` (projeto `conn2flow-site-local`)

## Live Todo List

- [x] Gerador: modelo renderizado com dados de exemplo, foto e conversão para WebP
- [x] Conferência visual por família, com três rodadas de ajuste
- [x] 120 miniaturas do core e 64 do site, com `thumbnail` declarado em cada modelo
- [x] Teste de guarda e gerador versionado
- [x] Publicação no Lab da 3.0
- [ ] Homologação humana

## O que foi feito

| Item | Resultado |
|---|---|
| **Modelos sem miniatura** | 93 por idioma no Lab da 3.0: 61 do core e 32 do site. Um deles (`galleries-estados`) não aparece na lista de modelos e ficou de fora. |
| **Miniaturas geradas** | 184 (92 por idioma): 120 do core, 64 do site. |
| **Formato** | WebP, 580 × 394 px (o dobro das 55 antigas, de 290 × 197, mesma proporção). A maior tem 21 KB. |
| **Onde ficam** | `gestor/assets/templates/images/<idioma>/<id>.webp`, no core e no site. |
| **Metadados** | `thumbnail` em cada modelo, no recurso que o define: 8 módulos e os dois `templates.json` no core; 6 módulos e os dois `templates.json` no site. |
| **Teste de guarda** | `TemplatesMiniaturasReq256Test`: modelo listado no painel sem miniatura, com caminho fora do padrão, sem arquivo, que não seja WebP ou acima de 100 KB, falha. |
| **Gerador** | `sdd/validation/req256/` (`gerar-miniaturas.cjs`, `montar.py`, `integrar.py`, `vazias.py`, `README.md`). |

## Como a imagem é feita

A prévia crua do editor não serve: mostra um item só, os marcadores (`[[item#titulo]]`) e até o bloco de "nenhum resultado". O gerador monta uma amostra antes de fotografar:

- repete o bloco de item (4 a 6 vezes, conforme o alvo) e tira os blocos de estado vazio;
- troca cada marcador por um valor de exemplo escolhido pelo nome (título, resumo, data, preço, rótulo de menu, campo de formulário), no idioma do modelo;
- usa um desenho em gradiente no lugar das imagens;
- em formulários, deixa um tipo de campo por linha;
- amplia e centra o que é baixo (menu, busca, aviso);
- usa palco escuro nos modelos feitos para fundo escuro.

A renderização é no quadro de prévia do editor de modelos, que já carrega o framework CSS de cada modelo.

## Conferência visual

Três rodadas, olhando folhas de conferência por alvo:

1. Menus minúsculos num quadro branco; formulários com todos os tipos de campo para cada rótulo.
2. Aviso de cookies em branco (nasce escondido e o script do widget é que mostra); sobras de sub-blocos de formulário; barra de navegação quebrando linha com a ampliação; formulários de checkout com texto claro em fundo branco.
3. Três slides genéricos de apresentação com texto claro em fundo branco; rodapé em Fomantic espremido.

Depois da terceira, todas as famílias em pt-br foram vistas. **As folhas em inglês não foram abertas uma a uma**: usam o mesmo código, com os textos de exemplo em inglês.

## Correção de enquadramento (mesmo dia, depois da conferência do Engenheiro Chefe)

A primeira entrega cortava a área útil em parte dos modelos: nos menus e na barra lateral o conteúdo ficava colado à esquerda com um vazio à direita, e os dois formulários de checkout encostavam nas bordas e no topo. A causa era a ampliação por `zoom` com largura reduzida, que encolhia a área do conteúdo em vez de centrá-lo, e o palco escuro sem margem.

O gerador passou a enquadrar: mede a caixa do conteúdo pintado, amplia o que é pequeno, **reduz o que passa do quadro** e centra com margem. As 184 miniaturas foram refeitas (102 arquivos do core mudaram), conferidas de novo em folhas de pt-br e numa amostra em inglês (menus, formulários, planos e destaques), e republicadas no Lab da 3.0; o Lab serve os arquivos novos (tamanho conferido em três deles) e o roteiro de navegador repetiu 16/16.

Quem já abriu o painel pode ver a imagem antiga do cache do navegador: o endereço do arquivo é o mesmo.

## Limites

- **O valor de exemplo vem do nome do marcador.** Alguns botões da loja saíram com um título no lugar do rótulo, e a página de produto repete "Frete grátis" em campos de variação.
- Peça alta e estreita (barra lateral vertical) fica pequena na miniatura, porque é reduzida para caber inteira; lista longa de produtos aparece só até onde o quadro alcança.
- **O que depende de script** (carrossel, apresentação) aparece no estado inicial.
- As 55 miniaturas antigas (alvo `paginas` e duas do `publisher`) não foram refeitas e continuam em 290 × 197.
- Os módulos distribuídos do site (`gestor-distribuido/`) não foram alterados.

## Defeito achado no caminho

Os `templates.json` globais já traziam `"thumbnail": ""` nos 20 modelos de layout e de componente. A inserção criou a chave duas vezes e a vazia vencia. O teste de guarda pegou no primeiro modelo; `vazias.py` tira a linha vazia.

## Validação

| Checagem | Resultado |
|---|---|
| PHPUnit do core na `3.0` (suíte completa) | 1.688 testes, sem falha |
| Vitest do core na `3.0` | 614 testes, sem falha |
| `resources:sync` | código de saída 0; `TemplatesData.json` com 230 modelos, só os 2 de `galleries-estados` sem miniatura |
| `project:update-all conn2flow-site-local` | ver "Publicação" abaixo |

### Publicação

`project:update-all conn2flow-site-local` com código de saída 0 (árvore nova: recompilou o CSS inteiro, mais de 10 minutos). No Lab da 3.0:

- banco: 300 modelos ativos, 298 com miniatura; os 2 sem são o `galleries-estados` nos dois idiomas;
- arquivos entregues como `image/webp` (conferido um do core e um do site);
- `req256-browser.cjs`: **16/16**. A aba Modelos do editor mostra as miniaturas dos modelos de layout e de componente; a tela de edição do cadastro de modelos mostra a miniatura de 12 modelos de 10 alvos, do core e do site.

Imagem da aba Modelos conferida. Nos módulos com seletor próprio de modelo (menus, índices, formulários) a aba Modelos do editor não aparece: ali a miniatura só é vista no cadastro de modelos.

## Critérios de aceite

- [x] Todo modelo ativo listado no painel tem miniatura, nos dois idiomas.
- [x] WebP de até 100 KB, na proporção das existentes.
- [x] A aba Modelos do editor e o cadastro de modelos mostram as miniaturas no Lab da 3.0.
- [x] Gerador versionado e teste de guarda.
- [x] PHPUnit e Vitest verdes.
