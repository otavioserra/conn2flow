# BATCH-134 — Limpeza do HTML na entrega, com gate de três estados

- **Intake**: `sdd/human-requests/req-132.md`
- **Estado**: implementado; pendente homologação e a linha do `.env` (ver abaixo)
- **Alvo de validação**: `tests/Unit/PHP/HtmlSanitizeTest.php`

## Onde entra

`gestor_pagina_ultimas_operacoes()`, que já era a última etapa antes do `echo $_GESTOR['pagina']`.
A posição não é detalhe: **depois dela nada mais é injetado**, então remover comentário e indentação
não pode quebrar marcador nenhum do sistema — e vários marcadores do core são comentários HTML
(`<!-- pagina#css -->`).

As duas funções novas vivem em `gestor/bibliotecas/gestor.php`, não no roteador. `gestor.php` chama
`gestor_start()` no fim do arquivo e não pode ser carregado por um teste; a biblioteca já é o que o
`tests/bootstrap.php` carrega. `gestor_html_higienizar()` é **pura** — recebe e devolve string, sem
tocar em `$_GESTOR` — que é o que torna possível testá-la caso a caso.

## O gate

`HTML_SANITIZE` no `.env`, três estados:

| valor | efeito |
| --- | --- |
| `auto` (padrão) | liga em produção, desliga quando `development-env` |
| `on` | liga sempre — é assim que se **confere** o resultado no ambiente local |
| `off` | desliga sempre, como escape para depurar a própria limpeza |

**Valor desconhecido cai em `auto`, não em `off`.** Uma chave digitada errado no `.env` não pode
desligar em silêncio a limpeza de um site em produção: o erro de digitação apareceria como
vazamento de comentário interno, e ninguém ligaria uma coisa à outra.

## O que é preservado, e por quê

Cada exceção existe por um motivo diferente:

1. **`<pre>` e `<textarea>`** — espaço em branco ali é **conteúdo**. Reindentar um bloco de código
   exibido na tela muda o que o usuário lê; num `textarea`, muda o que ele envia de volta.
2. **`<script>`** — JavaScript não se limpa com expressão regular. Um `//` dentro de uma string,
   uma regex contendo `/*`, um template literal com quebras de linha: qualquer um vira código
   quebrado. O ganho não paga o risco, e o JS já vai minificado por outro caminho.
3. **Comentários condicionais** (`<!--[if ...]>`) — são **instrução**, não comentário. Removidos, o
   conteúdo que eles guardam passaria a valer para todos os navegadores.

### A quebra de linha permanece

Só a indentação sai. Entre dois elementos em linha, o espaço em branco é renderizado: colar
`<span>a</span>` em `<span>b</span>` faria "ab" aparecer junto na tela. Trocar a indentação por um
único `\n` tira os bytes sem mudar um pixel.

### Os blocos protegidos saem e voltam por marcador

Em vez de ensinar cada expressão regular a desviar de `<pre>`, `<textarea>` e `<script>`, os três
são retirados do texto e devolvidos no fim. Com a outra abordagem bastaria **uma** expressão
esquecer o desvio para o defeito aparecer numa página qualquer, muito depois. Um teste afirma que
nenhum marcador vaza para o HTML final.

## Configuração pela tela

`admin-environment` — apontado pelo operador — é a tela que já edita o `.env`. Ganhou o campo na aba
"Configurações do Site": leitura, `select` com os três modos, envio no payload e gravação
**restrita aos três valores válidos** (qualquer outro seria lido como `auto` pelo core, e a tela
passaria a mostrar um estado que não corresponde ao efeito).

## Medição sobre a página real

`temp/problema.html` do projeto Photon, 490 elementos:

```
antes:   138,7 KB
depois:   94,1 KB   (-32,1%)
tempo:     1,06 ms

elementos no DOM: 490 -> 490    ARVORE IDENTICA (mesma ordem, mesmos atributos)
<script>: 8 blocos, conteudo BYTE A BYTE IDENTICO
comentarios HTML: 95 -> 0
comentarios CSS:  45 -> 0
```

A comparação é da **árvore DOM**, não de contagem de texto: a primeira versão da prova acusou um
`<template>` "perdido" que na verdade era uma menção textual dentro de um comentário removido.
Contar ocorrências de string teria deixado passar um defeito real e inventado um inexistente.

## Validação

- `tests/Unit/PHP/HtmlSanitizeTest.php`: **17 testes, 24 asserções**, 0 falhas.
- Suíte do core: **719 testes, 3198 asserções**, 0 falhas (702 antes).
- **Captura medida**: removida a proteção de `<pre>`/`<textarea>`/`<script>`, a suíte falha.

Os testes cobrem os dois lados: o que sai (comentário HTML, comentário CSS, indentação) e — com
mais peso — o que **não pode ser tocado**: `<pre>` e `<textarea>` byte a byte, JavaScript com `//`
em string e regex com `/*`, comentário condicional, `<!-- -->` dentro de string JS, e os três
estados do gate incluindo o valor inválido.

## 2ª rodada — o JavaScript embutido também

Na homologação o operador apontou o que a 1ª rodada deixou de fora:

> "Os comentários de scripts e JavaScript diretamente no HTML ficou. Então a gente precisa
> atualizar... pra tirar também os comentários JS e também a opção do JS ficar comprimido."

A 1ª rodada preservava `<script>` inteiro, e o batch justificava isso dizendo que "JavaScript não se
limpa com expressão regular". A frase continua verdadeira — a conclusão é que estava errada. **Não
se limpa com expressão regular; limpa-se com um scanner.**

### Por que um scanner, e não uma regex

Em JavaScript, `//` e `/*` só são comentário em **um** dos cinco contextos possíveis. Nos outros
quatro são conteúdo:

```js
var url = 'http://exemplo';        // string simples — o `//` é da URL
var msg = "diga /* isso";          // string dupla
var t   = `linha // não comenta`;  // template literal
var re  = /\/\*/;                  // regex literal: começa com barra-barra
```

Uma expressão regular não distingue esses casos, e o erro **não aparece para quem escreveu**:
aparece na página de alguém, como JavaScript truncado no meio. `gestor_js_higienizar()` percorre o
texto caractere a caractere, com estado para string simples, string dupla, template literal,
comentário de linha, comentário de bloco e regex literal.

**O caso difícil é a barra.** `/` pode abrir um regex literal ou ser divisão, e só o token anterior
decide: depois de identificador, número, `)` ou `]` é divisão; em qualquer outra posição é regex.
`gestor_js_barra_inicia_regex()` isola essa decisão — e ela erra **para o lado do regex** de
propósito: tratar um regex como divisão faria seu conteúdo ser lido como código, e um `//` dentro
dele apagaria o resto da linha.

### A quebra de linha permanece, e aqui não é cosmético

JavaScript insere ponto e vírgula automaticamente (ASI). Juntar duas linhas que dependem disso
**muda o programa**. Só a indentação sai. Pelo mesmo motivo, um comentário de bloco que continha
quebras deixa **uma** quebra no lugar: sem ela, as instruções antes e depois dele ficariam na mesma
linha.

### Nem todo `<script>` é JavaScript

`<script>` também é usado como depósito: `application/json` guarda dado, `text/template` guarda
markup. Passar o scanner por cima corromperia o conteúdo — um `//` dentro de uma URL no JSON viraria
comentário e o resto da linha sumiria. `gestor_html_script_e_javascript()` processa apenas o que não
tem `src` e cujo `type` é ausente, `module` ou um dos tipos de JavaScript. **Na dúvida, não
processa**: deixar um comentário custa bytes; corromper um bloco de dados custa a funcionalidade.

### Chave própria: `HTML_SANITIZE_JS`

Separada do `HTML_SANITIZE` porque o risco é de outra ordem. Remover comentário de HTML e CSS, no
pior caso, deixa a página feia; um erro no JavaScript derruba a tela inteira. A chave permite
desligar **só** essa parte diante de uma suspeita, sem devolver os comentários internos de HTML e
CSS ao visitante. Ausente, segue a chave principal.

## Medição atualizada

```
antes:   138,7 KB
depois:   92,3 KB   (-33,5%)   [era -32,1% sem o JS]
tempo:     2,06 ms

DOM: 490 -> 490 elementos, ARVORE IDENTICA
blocos JS inline validados com `node --check`: 2/2 TODOS VALIDOS
comentarios HTML: 95 -> 0
```

A validação do JavaScript é feita **rodando `node --check` em cada bloco inline da página já
limpa**. É a única forma honesta de afirmar que o scanner não quebrou código: comparar strings diria
apenas que algo mudou, não que o que sobrou ainda é um programa válido.

## Validação (atualizada)

- `tests/Unit/PHP/HtmlSanitizeTest.php`: de 17 para **35 testes, 57 asserções**, 0 falhas.
- Suíte do core: **737 testes, 3231 asserções**, 0 falhas (719 antes).

Os 18 testes novos são quase todos casos-armadilha: `//` dentro de URL, `/*` dentro de string,
template literal com quebras, `/\/\*/`, `/[/]/`, divisão confundida com regex, aspa escapada,
comentário de bloco não fechado, ASI, `application/json`, `text/template`, `src=`, `module`, e o
gate desligando só o JavaScript.

**Um teste mudou de contrato**: `testJavaScriptNaoEAlteradoDeFormaAlguma` afirmava que `<script>`
era preservado byte a byte. Foi reescrito como `testJavaScriptPerdeComentariosMasNaoPerdeSemantica`
— o que se afirma agora é mais forte do que "não mexe": **mexe, e não quebra**.

### Pendente do operador

- **A linha do `.env`**: o agente é impedido por regra de permissão de escrever `.env*`. Adicionar
  ao template `gestor/autenticacoes.exemplo/dominio/.env`, de onde `atualizacoes-sistema.php` faz
  merge aditivo para as instalações:

  ```
  # Limpeza do HTML entregue ao navegador: auto | on | off
  # auto = limpa em producao e mantem legivel em desenvolvimento
  HTML_SANITIZE=auto

  # Limpeza do JavaScript embutido: auto (segue a chave acima) | off
  HTML_SANITIZE_JS=auto
  ```

- Conferir a tela em `admin-environment`, salvar nos três modos e verificar o código-fonte de uma
  página pública com `on` no ambiente local.
