---
title: "ia.php library"
label: "Artificial intelligence"
description: "The editors' AI prompt component (servers, models, prompts and modes per target) and sending the prompt to the Gemini API."
section: reference
order: 290
sources:
  - gestor/bibliotecas/ia.php
  - gestor/bibliotecas/html-editor.php
  - gestor/modulos/dashboard/dashboard.php
verified_at: d654b4c9
---

# `ia.php` library

It is the AI inside the editors: the component where the user picks server, model, prompt and mode, and the call that sends the text to the API. It is used by the HTML editor (`html-editor.php`) and by the `dashboard` Live Editor.

> [!IMPORTANT]
> Only **Google Gemini** is supported. Registered servers (`servidores_ia`) store only the API key; the URL and default model come from `apis.gemini` in `modulos/admin-ia/admin-ia.json`, and the model list from `modulos/admin-ia/gemini/<language>/data.json`.

## Concepts

| Table | What it is |
|---|---|
| `servidores_ia` | An (encrypted) API key with a name; `padrao` sets the first one in the list. Registered in `admin-ia` |
| `prompts_ia` | Ready-made texts per **target** and language, such as "generate a pricing section" |
| `modos_ia` | System instructions per target (how the AI should answer); the one with `padrao` comes preselected |

The **target** is the context where the component shows up (for example, `paginas` in the page editor). Prompts and modes are resources: they are born from modules (`ai_prompts`, `ai_modes`, `ai_prompts_targets`) and can be created in the editor itself ([resources](../../concepts/resources.md)).

## Rendering the component

`ia_renderizar_prompt(['alvo' => 'paginas', 'prompt_controls' => $html])` returns the HTML of the `ia-prompt` component:
- it lists the target's servers, prompts and modes, and the Gemini models, with the default selected;
- it includes the library JS, CodeMirror and the `ia-prompt-modais` modals;
- it exposes `gestor.ai.activated` and `gestor.ia` (target and messages) to JavaScript;
- with no active server, it returns the `ia-sem-servidor` component and `gestor.ai.activated = false`.

`ia_editor_dados($target)` returns the same data as JSON, for the Live Editor to build the screen by itself.

Available filters ([hooks](../../concepts/hooks.md)): `ia.prompts.load.where` (WHERE of the prompt list), `ia.prompt.option` (HTML of each option), `ia.models.available` (models) and `ia.config` (extra data for the JS).

## Sending the prompt

```php
$r = ia_enviar_prompt(['servidor_id' => 3, 'modelo' => 'models/gemini-3-flash-preview', 'prompt' => $text]);
if ($r['status'] === 'success') {
    $text = $r['data']['texto_gerado'];   // + modelo_usado, tokens_entrada/saida/total, resposta_completa
}
```

- Without `modelo`, it uses the `defaultModel` from `admin-ia.json` (currently `models/gemini-3-flash-preview`). The model name carries the `models/` prefix; the hard-coded fallback, `gemini-1.5-flash`, lacks the prefix and would not work in the URL.
- It decrypts the server key and calls the Gemini API `generateContent` (120 s timeout).
- It returns `['status' => 'success', 'data' => …]` or `['status' => 'error', 'message' => …]`; on an HTTP error, the message includes the API response body.
- There is no conversation history: each call sends a single text.
- `ia_processar_retorno(['dados_retorno' => $r['data'], 'formato' => 'texto'|'html'|'json'])` formats the result (`html` applies `htmlspecialchars` + `nl2br`). It has no callers.

> [!NOTE]
> The API key is stored encrypted with the site's private key and decrypted with the **public** one (`autenticacao_decriptar_chave_publica()`). That hides the key from someone who only sees the database, but whoever has the database and the `publica.key` file recovers it.

## Editor AJAX

`ia_ajax_interface()` dispatches, by `ajax-opcao`: `ia-prompts` → `ia_ajax_prompts()` and `ia-modos` → `ia_ajax_modos()` (text of a prompt/mode), `ia-prompt-edit` → `ia_ajax_prompt_edit()`, `ia-prompt-new` → `ia_ajax_prompt_novo()` and `ia-prompt-del` → `ia_ajax_prompt_del()`.

> [!WARNING]
> Editing and deleting a prompt filter only by `id`, target and language, without checking the owner, and a newly created prompt does not store `id_usuarios`. In projects that separate prompts per user, this is item A10 of req-181.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/ia.php` by `c2f docs:extract` — 10 functions. Do not edit inside this block.

- `ia_renderizar_prompt(array $params = false)` — [line 22](../../../../../gestor/bibliotecas/ia.php#L22)
  Renderizar Prompt.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['alvo']`: Nome do alvo do pré-prompt.
  - `$params['prompt_controls']`: Controles extras do prompt.
- `ia_enviar_prompt($params = false)` — [line 252](../../../../../gestor/bibliotecas/ia.php#L252)
  Enviar Prompt.
  Parameters:
  - `$var`: descrição.
  - `$params['servidor_id']`: Identificador numérico do servidor IA.
  - `$params['modelo']`: Nome do modelo IA.
  - `$params['prompt']`: Prompt a ser enviado.
- `ia_processar_retorno(array $params = false)` — [line 424](../../../../../gestor/bibliotecas/ia.php#L424)
  Processar Retorno.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['dados_retorno']`: Dados retornados pela API de IA.
  - `$params['formato']`: Formato desejado para o retorno (texto, json, html).
- `ia_editor_dados(string $alvo): array` — [line 493](../../../../../gestor/bibliotecas/ia.php#L493)
  Dados do assistente de IA para UI vanilla (Live Editor — BATCH-080).
  Parameters:
  - `$alvo`: Alvo do prompt/modo (ex.: 'paginas').
  Returns: Estrutura `{status, data|message}`.
- `ia_ajax_interface($params = false)` — [line 563](../../../../../gestor/bibliotecas/ia.php#L563)
- `ia_ajax_prompts(array $params = false)` — [line 584](../../../../../gestor/bibliotecas/ia.php#L584)
  AJAX Prompts.
  Parameters:
  - `$params`: Parâmetros da função.
- `ia_ajax_modos(array $params = false)` — [line 645](../../../../../gestor/bibliotecas/ia.php#L645)
  AJAX Modos.
  Parameters:
  - `$params`: Parâmetros da função.
- `ia_ajax_prompt_edit(array $params = false)` — [line 709](../../../../../gestor/bibliotecas/ia.php#L709)
  AJAX Prompt Edit.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['target']`: Nome do alvo do pré-prompt.
  - `$params['prompt_id']`: Identificador do prompt IA.
  - `$params['prompt']`: Conteúdo do prompt IA.
- `ia_ajax_prompt_novo(array $params = false)` — [line 759](../../../../../gestor/bibliotecas/ia.php#L759)
  AJAX Prompt Novo.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['target']`: Nome do alvo do pré-prompt.
  - `$params['nome']`: Nome do prompt IA.
  - `$params['prompt']`: Conteúdo do prompt IA.
- `ia_ajax_prompt_del(array $params = false)` — [line 840](../../../../../gestor/bibliotecas/ia.php#L840)
  AJAX Prompt Delete.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['target']`: Nome do alvo do pré-prompt.
  - `$params['prompt_id']`: Identificador do prompt IA.

<!-- c2f:extract:end -->
