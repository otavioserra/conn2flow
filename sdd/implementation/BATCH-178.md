# BATCH-178: Preservação de Query Strings em Redirecionamentos 301 e Contenção de Blocos-Fragmento no Widget de Formulários

Execução da [req-173](../human-requests/req-173.md).

---

## Atividades e Checklist

### 1. [x] Roteamento: Preservação de Query String em `paginas_301`
- [x] Em `gestor/gestor.php` (`gestor_roteador_301_ou_404()`):
  - `'querystring' => true` passado na chamada de `gestor_roteador_erro()` do ramo 301.
- [x] Em `gestor/bibliotecas/gestor.php` (`gestor_redirecionar()`):
  - Concatenação extraída para `gestor_redirecionar_montar_url()`, que usa `&` quando o destino já
    tem `?`, aceita a query com ou sem prefixo e devolve o destino intacto quando não há parâmetros.
  - Status HTTP 301 preservado: ele vem de `http_response_code()` em `gestor_roteador_erro()` e não
    foi tocado.

### 2. [x] Widget Forms: Expurgamento de Blocos-Fragmento e Sanitização de Marcadores
- [x] `forms_widget_limpar_fragmentos()` criada e aplicada no retorno de `forms_widget_render_inline()`:
  - remove por inteiro os três blocos-modelo conhecidos;
  - remove o invólucro `<template>` que ficar vazio (contorno adotado no conn2flow-site);
  - de blocos desconhecidos tira só os comentários, preservando o markup do autor do template;
  - rede de segurança contra `@[[item#*]]@`, `@[[option#*]]@` e `@[[password#*]]@` remanescentes.
- [x] Selects, radios, checkboxes e o botão de senha continuam operacionais (coberto por teste).

### 3. [x] Validação e Testes
- [x] Adicionar testes unitários no PHPUnit cobrindo:
  - Redirecionamento 301 com query string simples e composta (UTMs, múltiplos parâmetros).
  - Redirecionamento 301 sem query string (sem `?` órfão).
  - Renderização do widget `forms` com múltiplos tipos de campo verificando ausência de marcadores crus e integridade do markup funcional gerado.
- [x] Executar suítes locais:
  - PHPUnit completo: **1202/1202** (ver nota de ambiente abaixo).
  - `npx vitest run`: **426/426** em 30 arquivos.
  - `git diff --check` sem apontamentos; `assets:minify --verificar` com 0 derivados desatualizados.

---

## Critérios de Aceite e Validação

1. **Query String em 301**: Requisições para caminhos com 301 preservam integralmente os parâmetros de busca no destino.
2. **HTML Limpo no Widget Forms**: Nenhum marcador `<!-- option-` ou `@[[...]]@` vaza na saída pública renderizada.
3. **Compatibilidade Total**: Nenhuma quebra em formulários existentes ou em rotas com 301 ativo.
4. **Suíte 100% Aprovada**: PHPUnit e Vitest sem falhas ou regressões.

---

## Evidências de Execução

### Arquivos alterados

| Arquivo | Mudança |
|---|---|
| `gestor/gestor.php` | `'querystring' => true` no ramo 301 de `gestor_roteador_301_ou_404()` |
| `gestor/bibliotecas/gestor.php` | nova `gestor_redirecionar_montar_url()`; `gestor_redirecionar()` passa a usá-la |
| `gestor/modulos/forms/forms.widget.php` | nova `forms_widget_limpar_fragmentos()`, aplicada no retorno de `forms_widget_render_inline()` |
| `tests/Unit/PHP/Req173Redirecionamento301Test.php` | 7 testes (query simples, composta com UTMs, sem query, destino com `?`, prefixos `?`/`&`, URL externa, contrato do roteador) |
| `tests/Unit/PHP/Req173FormsWidgetFragmentosTest.php` | 5 testes (saída sem bloco nem marcador, campos operacionais, `<template>` sem sobra, bloco desconhecido preservado, idempotência) |

### Resultados

- REQ-173 isolada: 12 testes, 33 asserções, 0 falhas.
- PHPUnit completo: **1202 testes, 7.869 asserções, 0 falhas**, 5 pulados, 4 deprecações e 2 deprecações
  do PHPUnit — todas preexistentes, de `InstaladorV21Req027Test` (`ReflectionProperty::setAccessible()`).
- Vitest: **426 testes em 30 arquivos, 0 falhas**.

### Validação em runtime (Lab do conn2flow-site, `https://conn2flow.local/`)

O core corrigido foi sincronizado para o Lab pelo pipeline do projeto e exercitado contra os dois
casos que originaram os achados:

| Requisição | Resultado |
|---|---|
| `/pro-checkout/?plan=starter&utm_source=adwords&utm_medium=cpc` | **301** ➔ `/subscription/?plan=starter&utm_source=adwords&utm_medium=cpc` |
| `/pro-checkout/` | **301** ➔ `/subscription/` (sem `?` órfão) |
| `/welcome-pro/?ref=abc` | **301** ➔ `/user-subscription/?ref=abc` |
| `/subscription-checkout/?plan=starter` **sem o contorno `<template>` do site** | 0 blocos-modelo, 0 marcadores `@[[option#*]]@`/`@[[password#*]]@`, 0 `<template>` vazio; todos os campos presentes, inclusive o botão de exibir senha |
| Contratação completa no Stripe Sandbox após a correção | checkout ➔ pagamento ➔ sucesso, sem erros de console |

O contorno `<template data-forms-fragments>` foi **restaurado** no conn2flow-site depois do teste: ele
deixa de ser necessário quando esta correção for publicada, mas o site não pode depender de uma
release do core que ainda não saiu. O widget corrigido trata as duas formas.

### Nota de ambiente (importante para reproduzir)

No Windows a suíte acusa **1 erro em `CoreHelpersTest`**: `openssl_pkey_new()` falha com
`configuration file routines::no such file` (PHP 8.5.8 + OpenSSL 3.5.7 sem `openssl.cnf` acessível).
O erro **não tem relação com este lote** — reproduz isolado (`--filter CoreHelpersTest`), no mesmo
commit, em arquivo que o lote não toca. A suíte foi então executada no Lab (Linux, PHP 8.5.10), onde
fica **100% verde**. É a mesma armadilha já registrada na memória de execução do conn2flow-site.

### Achado colateral (não altera o escopo)

Ao escrever o teste do widget ficou visível **por que** os blocos vazavam sem nunca serem usados:
`forms_widget_options_html()` e `forms_widget_wrap_password()` procuram os blocos DENTRO do bloco
`item` (é o `item` que chega a `forms_widget_render_field()`), e nos templates do core eles estão
FORA dele, no fim do arquivo. Ou seja, o widget sempre renderizou opções e botão de senha a partir
dos modelos embutidos no PHP; os blocos do fim do template eram apenas peso morto que ia para a tela.
Se a intenção do design era permitir que o template personalizasse esses controles, isso nunca
funcionou e merece uma decisão à parte — a correção deste lote não muda esse comportamento.

## Estado

complete
