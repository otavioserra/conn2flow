---
title: "Biblioteca controles.php"
description: "Controles administrativos compartilhados, formulários nativos e máscaras."
section: reference
order: 100
sources:
  - gestor/bibliotecas/controles.php
  - gestor/assets/interface/controles.js
  - gestor/assets/interface/campo-moeda.js
verified_at: 914c7b10
---

# Biblioteca controles.php


Os ajudantes retornam HTML; o JavaScript adiciona comportamento preservando o envio nativo. As [convenções do painel](../../concepts/admin-interface.md) descrevem classes, máscaras, selects flutuantes e mensagens formatadas.

- `controles_textos`: Lê as variáveis globais controles-* para o JS.
- `controles_incluir`: Enfileira CSS, runtime, máscaras e textos uma vez por página.
- `controles_esc`: Escapa valores com ENT_QUOTES e UTF-8.
- `controles_select`: Cria select nativo, opções/grupos, busca, múltiplo e configuração AJAX.
- `controles_chave`: Cria checkbox; pode incluir valor oculto zero quando desligado.
- `controles_abas`: Escapa rótulos; conteúdo já pronto é HTML não escapado.
- `controles_atributos`: Valida nomes; true vira atributo sem valor, false/null são omitidos.
- `controles_campo`: Agrupa rótulo, ajuda, erro e aria-describedby; tipos incluem texto, área, select e chave.
- `controles_botao`: Gera botão com variante e ícone Lucide; escapa o rótulo.

## Runtime dos controles

O observador interno observarSelects monta selects inseridos depois da carga e reconstrói a casca de controles clonados, cujos eventos não são copiados por cloneNode. Selects dentro de template ficam de fora até serem inseridos no documento. A seleção conserva o select nativo para enviar o formulário.

c2fControles.formatado permite somente b, strong, i, em, u, br e code, sem atributos. As máscaras percentual e moeda são carregadas por controles_incluir: exibem números no padrão pt-BR e enviam decimais com ponto no evento formdata; percentual limita 0–100 com duas casas.

## Padrão único de abas, diálogos e avisos (REQ-308)

Módulos e projetos consomem estes comportamentos; não reimplementam.

- **Abas (`data-c2f-abas`)**: a partir de 640 px a faixa quebra linha, sem rolagem lateral. Abaixo de 640 px, grupos com 3 ou mais abas trocam a faixa por um seletor compacto: rótulo "Seção atual" (`controles-secao-atual`, ou `data-c2f-abas-rotulo-mobile` no grupo), nome e ícone da aba ativa e um painel com todas as abas (grade de 2 colunas a partir de 4). Ícone só do seletor: elemento `[data-c2f-aba-icone]` dentro da aba; sem ele, vale o primeiro `<svg>`. A escolha clica na aba original; se o módulo troca `aria-selected`, `hidden` ou `disabled` por conta própria, o seletor acompanha. Opt-out: `data-c2f-abas-sem-seletor-mobile`. Módulo que guarda a aba por conta própria usa `data-c2f-abas-sem-memoria`.
- **`<dialog>` modal**: todo `<dialog>` aberto com `showModal()` fecha com clique no fundo, desde que o gesto comece e termine fora da caixa (selecionar texto e soltar fora não fecha; clique de teclado também não). Antes dispara `cancel` cancelável e depois `dialog.close()`: a limpeza feita no evento `close` vale também aqui. Opt-out: `data-c2f-sem-fechar-fora`.
- **Avisos (`c2fControles.aviso(mensagem, tipo, tempo | {tempo})`)**: tipos `sucesso|erro|alerta|info` ou `success|error|warning|info`; ícone por tipo; a mesma mensagem não empilha; no máximo 3; o tempo pausa com o mouse em cima; com um `<dialog>` modal aberto o aviso aparece dentro dele. A distância da borda inferior é o token `--c2fc-avisos-base`.
- **Confirmações**: `c2fControles.dialogo.confirmar|alerta|perguntar` (Promise). O alerta não fecha pelo fundo.

## Referência extraída de funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/controles.php` por `c2f docs:extract` — 9 funções. Não edite dentro deste bloco.

- `controles_textos()` — [linha 14](../../../../../gestor/bibliotecas/controles.php#L14)
  Textos dos controles para o JavaScript (variáveis globais `controles-*`).
- `controles_incluir()` — [linha 25](../../../../../gestor/bibliotecas/controles.php#L25)
  Enfileira o runtime e os textos uma vez por página.
- `controles_esc($valor)` — [linha 41](../../../../../gestor/bibliotecas/controles.php#L41)
- `controles_select(array $params)` — [linha 52](../../../../../gestor/bibliotecas/controles.php#L52)
  Select com busca (e AJAX, opcional) sobre um <select> nativo.
  Parâmetros:
  - `$params`: name, id, opcoes ([valor => rótulo] ou [['valor','rotulo','grupo']]), valor (string ou lista),
- `controles_chave(array $params)` — [linha 96](../../../../../gestor/bibliotecas/controles.php#L96)
  Chave liga/desliga sobre um checkbox nativo (envia `1` quando ligada).
- `controles_abas(array $abas, $ativa = null)` — [linha 112](../../../../../gestor/bibliotecas/controles.php#L112)
  Abas: [['id' => 'dados', 'rotulo' => '...', 'conteudo' => '<html já pronto>'], ...]. O conteúdo é HTML do próprio sistema (não escapado); os rótulos são escapados.
- `controles_atributos(array $atributos)` — [linha 134](../../../../../gestor/bibliotecas/controles.php#L134)
  Atributos HTML escapados; `true` vira atributo sem valor e `false`/`null` some.
- `controles_campo(array $p)` — [linha 150](../../../../../gestor/bibliotecas/controles.php#L150)
  Campo completo do formulário.
  Parâmetros:
  - `$p`: tipo (texto|email|senha|numero|data|data-hora|url|area|select|chave|oculto), name, id, rotulo, ajuda,
- `controles_botao(array $p)` — [linha 197](../../../../../gestor/bibliotecas/controles.php#L197)
  Botão padrão. variante: primario (padrão) | secundario | perigo | fantasma; tipo: button|submit; url vira <a>. `icone` é o nome de um ícone Lucide (`data-lucide`), desenhado pelo layout.

<!-- c2f:extract:end -->
