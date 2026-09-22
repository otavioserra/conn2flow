# Relatório — REQ-173 / BATCH-178: query string em 301 e blocos-modelo no widget de formulários

- **Data**: 2026-09-22
- **Repositório**: `conn2flow` (core) · **Raiz**: `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`
- **Requisição**: [req-173](../human-requests/req-173.md) · **Lote**: [BATCH-178](../implementation/BATCH-178.md)
- **Origem dos achados**: homologação do `conn2flow-site` (BATCH-046 e BATCH-047)
- **Estado**: `implemented-pending-review` — **sem commit e sem push**, conforme a regra do despacho
- **Executor**: agente de implementação (Claude Opus 5)

---

## 1. O que foi entregue

Duas correções independentes, ambas nascidas de comportamento observado em produção-de-teste, não de
leitura de código.

### 1.1 `paginas_301` deixava o contexto para trás (BL-016)

O roteador redirecionava caminhos aposentados sem repassar a query string. Uma visita a
`/pro-checkout/?plan=starter&utm_source=adwords` chegava ao destino sem plano e sem UTM: o cliente via
uma página genérica e a campanha perdia a origem.

- `gestor/gestor.php`: o ramo 301 de `gestor_roteador_301_ou_404()` passa `'querystring' => true`.
- `gestor/bibliotecas/gestor.php`: a montagem da URL saiu de dentro de `gestor_redirecionar()` para
  `gestor_redirecionar_montar_url()`, que decide entre `?` e `&` conforme o destino já tenha
  parâmetros, aceita a query com ou sem prefixo e devolve o destino intacto quando não há nada a
  anexar. A extração existe por um motivo prático: a função original termina em `header()` + `exit`,
  o que a torna inverificável em teste.

**Correção de rumo em relação ao que eu havia reportado antes:** o BL-016 original, escrito por mim,
afirmava que o redirecionamento respondia 302. Está errado — `gestor_roteador_erro()` chama
`http_response_code(301)` antes do `Location`, e a medição no Lab confirma 301. O backlog foi
reescrito antes da promoção, e o status HTTP não foi tocado neste lote.

### 1.2 O widget de formulários desenhava os próprios blocos-modelo (BL-017)

Os templates de `target=forms` guardam no fim do arquivo os blocos `option-choice`, `option-select` e
`password-toggle`. O conteúdo entre os marcadores é markup normal, e o renderizador nunca o removia da
saída: o navegador desenhava `<label>`, `<option>` e o botão de senha soltos no fim do formulário, com
marcadores crus (`@[[option#type]]@`, `@[[password#input]]@`) à mostra na página pública.

`forms_widget_limpar_fragmentos()` passou a ser aplicada no retorno de `forms_widget_render_inline()`:

- remove por inteiro os três blocos conhecidos;
- remove o invólucro `<template>` que sobrar vazio (é o contorno que o site adotou);
- de blocos **desconhecidos** tira só os comentários e preserva o markup — o template é de quem o
  escreveu, e apagar conteúdo alheio seria pior que o vazamento;
- como rede de segurança, elimina `@[[item#*]]@`, `@[[option#*]]@` e `@[[password#*]]@` remanescentes.

---

## 2. Achado colateral que merece sua decisão

Ao escrever o teste ficou visível **por que** os blocos vazavam sem nunca serem usados:
`forms_widget_options_html()` e `forms_widget_wrap_password()` procuram esses blocos **dentro** do
bloco `item` — é o `item` que chega a `forms_widget_render_field()`. Nos templates do core eles estão
**fora**, no fim do arquivo. Ou seja: o widget sempre renderizou opções e botão de senha a partir dos
modelos embutidos no PHP, e os blocos do template eram peso morto que ia para a tela.

Se a intenção do design era permitir que cada template personalizasse esses controles, isso nunca
funcionou. Este lote não muda o comportamento — só para de exibir o que não é conteúdo. Decidir se os
blocos passam a ser respeitados (e onde devem morar no template) é um item à parte.

---

## 3. Verificação

| Verificação | Resultado |
|---|---|
| PHPUnit completo (Lab Linux, PHP 8.5.10) | **1202 testes, 7.869 asserções, 0 falhas**, 5 pulados |
| Testes novos da REQ-173 | 12 testes, 33 asserções, 0 falhas |
| Vitest | **426 testes em 30 arquivos, 0 falhas** |
| `git diff --check` | sem apontamentos |
| `assets:minify --verificar` | 0 derivados desatualizados |
| Runtime: `/pro-checkout/?plan=starter&utm_source=adwords&utm_medium=cpc` | 301 ➔ `/subscription/?plan=starter&utm_source=adwords&utm_medium=cpc` |
| Runtime: `/pro-checkout/` | 301 ➔ `/subscription/`, sem `?` órfão |
| Runtime: checkout com o contorno do site **removido** | 0 blocos-modelo, 0 marcadores crus, campos e botão de senha operacionais |

**Ambiente, para reproduzir sem susto:** no Windows a suíte acusa **1 erro em `CoreHelpersTest`** —
`openssl_pkey_new()` falha com `configuration file routines::no such file` (PHP 8.5.8 + OpenSSL 3.5.7
sem `openssl.cnf` acessível). O erro reproduz isolado, no mesmo commit, em arquivo que este lote não
toca. A suíte foi executada no Lab (Linux), onde fica 100% verde.

---

## 4. Arquivos alterados (nenhum commitado)

| Arquivo | Natureza |
|---|---|
| `gestor/gestor.php` | 1 chave no array da chamada do 301 |
| `gestor/bibliotecas/gestor.php` | função nova + `gestor_redirecionar()` passa a usá-la |
| `gestor/modulos/forms/forms.widget.php` | função nova + 1 linha no retorno do renderizador |
| `tests/Unit/PHP/Req173Redirecionamento301Test.php` | novo, 7 testes |
| `tests/Unit/PHP/Req173FormsWidgetFragmentosTest.php` | novo, 5 testes |
| `sdd/implementation/BATCH-178.md` | evidências e estado |

O repositório tem alterações de outro agente em curso; nada foi adicionado ao índice nem commitado.

---

## 5. Pendente com você

1. **Revisar e commitar** o lote (o despacho proibiu commit pelo executor).
2. **Decidir** sobre o achado do §2 (blocos-modelo do template nunca respeitados).
3. **Agendar o [BL-015](../backlog/BL-015-pipeline-projeto-nao-sincroniza-hooks.md)**, que continua
   aberto e é o mais sério dos três achados vindos do site: `project:update-all` **não sincroniza a
   tabela `hooks`** em nenhum modo (`ssh`, `host`, `docker`). Um hook novo declarado no JSON de um
   módulo de projeto fica inerte depois de um deploy bem-sucedido, sem erro em log nenhum. No Lab isso
   custou uma investigação inteira: o evento era emitido, ninguém escutava e nada era registrado.
   Enquanto não for corrigido, **todo deploy de projeto em produção precisa da sincronização manual**
   descrita no item.
