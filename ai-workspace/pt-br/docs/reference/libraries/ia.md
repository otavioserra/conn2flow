---
title: "Biblioteca ia.php"
label: "Inteligência artificial"
description: "O componente de prompt de IA dos editores (servidores, modelos, prompts e modos por alvo) e o envio do prompt à API do Gemini."
section: reference
order: 290
sources:
  - gestor/bibliotecas/ia.php
  - gestor/bibliotecas/html-editor.php
  - gestor/modulos/dashboard/dashboard.php
verified_at: d654b4c9
---

# Biblioteca `ia.php`

É a IA dentro dos editores: o componente onde o usuário escolhe servidor, modelo, prompt e modo, e a chamada que manda o texto para a API. Quem a usa é o editor HTML (`html-editor.php`) e o Live Editor do `dashboard`.

> [!IMPORTANT]
> Só o **Google Gemini** é suportado. Os servidores cadastrados (`servidores_ia`) guardam só a chave de API; a URL e o modelo padrão vêm de `apis.gemini` no `modulos/admin-ia/admin-ia.json`, e a lista de modelos de `modulos/admin-ia/gemini/<idioma>/data.json`.

## Conceitos

| Tabela | O que é |
|---|---|
| `servidores_ia` | Uma chave de API (cifrada) com nome; `padrao` define a primeira da lista. Cadastrada no `admin-ia` |
| `prompts_ia` | Textos prontos por **alvo** e idioma, como "gerar seção de preços" |
| `modos_ia` | Instruções de sistema por alvo (como a IA deve responder); o que tem `padrao` vem pré-selecionado |

O **alvo** é o contexto em que o componente aparece (por exemplo, `paginas` no editor de páginas). Prompts e modos são recursos: nascem dos módulos (`ai_prompts`, `ai_modes`, `ai_prompts_targets`) e podem ser criados no próprio editor ([recursos](../../concepts/resources.md)).

## Renderizar o componente

`ia_renderizar_prompt(['alvo' => 'paginas', 'prompt_controls' => $html])` devolve o HTML do componente `ia-prompt`:
- lista servidores, prompts e modos do alvo, e os modelos do Gemini, com o padrão selecionado;
- inclui o JS da biblioteca, o CodeMirror e os modais `ia-prompt-modais`;
- expõe ao JavaScript `gestor.ai.activated` e `gestor.ia` (alvo e mensagens);
- sem nenhum servidor ativo, devolve o componente `ia-sem-servidor` e `gestor.ai.activated = false`.

`ia_editor_dados($alvo)` devolve os mesmos dados em JSON, para o Live Editor montar a tela sozinho.

Filtros disponíveis ([hooks](../../concepts/hooks.md)): `ia.prompts.load.where` (WHERE da lista de prompts), `ia.prompt.option` (HTML de cada opção), `ia.models.available` (modelos) e `ia.config` (dados extras para o JS).

## Enviar o prompt

```php
$r = ia_enviar_prompt(['servidor_id' => 3, 'modelo' => 'models/gemini-3-flash-preview', 'prompt' => $texto]);
if ($r['status'] === 'success') {
    $texto = $r['data']['texto_gerado'];   // + modelo_usado, tokens_entrada/saida/total, resposta_completa
}
```

- Sem `modelo`, usa o `defaultModel` do `admin-ia.json` (hoje `models/gemini-3-flash-preview`). O nome do modelo leva o prefixo `models/`; o reserva fixo no código, `gemini-1.5-flash`, não tem o prefixo e não funcionaria na URL.
- Decifra a chave do servidor e chama `generateContent` da API do Gemini (tempo limite de 120 s).
- Devolve `['status' => 'success', 'data' => …]` ou `['status' => 'error', 'message' => …]`; em erro HTTP, a mensagem inclui o corpo da resposta da API.
- Não há histórico de conversa: cada chamada manda um único texto.
- `ia_processar_retorno(['dados_retorno' => $r['data'], 'formato' => 'texto'|'html'|'json'])` formata o retorno (`html` aplica `htmlspecialchars` + `nl2br`). Não tem chamadores.

> [!NOTE]
> A chave de API é gravada cifrada com a chave privada do site e decifrada com a **pública** (`autenticacao_decriptar_chave_publica()`). Isso esconde a chave de quem só vê o banco, mas quem tem o banco e o arquivo `publica.key` a recupera.

## AJAX do editor

`ia_ajax_interface()` despacha, pela `ajax-opcao`: `ia-prompts` → `ia_ajax_prompts()` e `ia-modos` → `ia_ajax_modos()` (texto de um prompt/modo), `ia-prompt-edit` → `ia_ajax_prompt_edit()`, `ia-prompt-new` → `ia_ajax_prompt_novo()` e `ia-prompt-del` → `ia_ajax_prompt_del()`.

> [!WARNING]
> Alterar e excluir prompt filtram só por `id`, alvo e idioma, sem conferir o dono, e o prompt criado não grava `id_usuarios`. Em projetos que separam prompts por usuário, isso é o item A10 da req-181.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/ia.php` por `c2f docs:extract` — 10 funções. Não edite dentro deste bloco.

- `ia_renderizar_prompt(array $params = false)` — [linha 22](../../../../../gestor/bibliotecas/ia.php#L22)
  Renderizar Prompt.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['alvo']`: Nome do alvo do pré-prompt.
  - `$params['prompt_controls']`: Controles extras do prompt.
- `ia_enviar_prompt($params = false)` — [linha 252](../../../../../gestor/bibliotecas/ia.php#L252)
  Enviar Prompt.
  Parâmetros:
  - `$var`: descrição.
  - `$params['servidor_id']`: Identificador numérico do servidor IA.
  - `$params['modelo']`: Nome do modelo IA.
  - `$params['prompt']`: Prompt a ser enviado.
- `ia_processar_retorno(array $params = false)` — [linha 424](../../../../../gestor/bibliotecas/ia.php#L424)
  Processar Retorno.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['dados_retorno']`: Dados retornados pela API de IA.
  - `$params['formato']`: Formato desejado para o retorno (texto, json, html).
- `ia_editor_dados(string $alvo): array` — [linha 493](../../../../../gestor/bibliotecas/ia.php#L493)
  Dados do assistente de IA para UI vanilla (Live Editor — BATCH-080).
  Parâmetros:
  - `$alvo`: Alvo do prompt/modo (ex.: 'paginas').
  Retorno: Estrutura `{status, data|message}`.
- `ia_ajax_interface($params = false)` — [linha 563](../../../../../gestor/bibliotecas/ia.php#L563)
- `ia_ajax_prompts(array $params = false)` — [linha 584](../../../../../gestor/bibliotecas/ia.php#L584)
  AJAX Prompts.
  Parâmetros:
  - `$params`: Parâmetros da função.
- `ia_ajax_modos(array $params = false)` — [linha 645](../../../../../gestor/bibliotecas/ia.php#L645)
  AJAX Modos.
  Parâmetros:
  - `$params`: Parâmetros da função.
- `ia_ajax_prompt_edit(array $params = false)` — [linha 709](../../../../../gestor/bibliotecas/ia.php#L709)
  AJAX Prompt Edit.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['target']`: Nome do alvo do pré-prompt.
  - `$params['prompt_id']`: Identificador do prompt IA.
  - `$params['prompt']`: Conteúdo do prompt IA.
- `ia_ajax_prompt_novo(array $params = false)` — [linha 759](../../../../../gestor/bibliotecas/ia.php#L759)
  AJAX Prompt Novo.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['target']`: Nome do alvo do pré-prompt.
  - `$params['nome']`: Nome do prompt IA.
  - `$params['prompt']`: Conteúdo do prompt IA.
- `ia_ajax_prompt_del(array $params = false)` — [linha 840](../../../../../gestor/bibliotecas/ia.php#L840)
  AJAX Prompt Delete.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['target']`: Nome do alvo do pré-prompt.
  - `$params['prompt_id']`: Identificador do prompt IA.

<!-- c2f:extract:end -->
