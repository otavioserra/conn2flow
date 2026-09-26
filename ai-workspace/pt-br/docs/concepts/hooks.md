---
title: "Hooks: actions e filters"
description: "Como módulos, plugins e projetos reagem a eventos do núcleo sem alterar seu código: declaração em JSON, tabela hooks, carregamento sob demanda e o catálogo dos eventos que o core dispara."
section: concepts
order: 40
sources:
  - gestor/bibliotecas/hooks.php
  - gestor/controladores/atualizacoes/atualizacoes-hooks.php
  - gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php
  - gestor/bibliotecas/interface.php
  - gestor/gestor.php
  - gestor/cron.php
verified_at: ef15c05d
---

# Hooks: actions e filters

Hooks deixam um projeto, plugin ou módulo reagir a um evento do núcleo sem editar o arquivo do núcleo. É o modelo do WordPress com uma diferença importante: **não existe `add_action()` em tempo de execução**. Todo hook é declarado em JSON, gravado na tabela `hooks` pelo deploy e carregado do banco quando o evento dispara.

- **Action**: efeito colateral. O retorno é ignorado. `hook_do_action('namespace', 'evento', ...$args)`.
- **Filter**: recebe um valor, devolve o valor (alterado ou não). `$v = hook_apply_filters('namespace', 'evento', $v, ...$args)`.

Um evento é identificado por **namespace + evento**. O namespace costuma ser o id do módulo (`admin-paginas`) ou de uma biblioteca (`gestor`, `ia`, `html-editor`).

## Declarar um hook

### Num projeto: `project/hooks/hooks.json`

Na raiz do Gestor do projeto, com os callbacks em `project/hooks/controllers/`:

```json
{
    "controllers": {
        "gestor": "gestor.hooks.php",
        "perfil-usuario": "perfil-usuario.hooks.php"
    },
    "actions": {
        "perfil-usuario": {
            "signup.start": "meu_projeto_signup_start"
        }
    },
    "filters": {
        "gestor": {
            "roteador.paginas": { "callback": "meu_projeto_roteador_paginas", "prioridade": 5 }
        }
    }
}
```

```php
// project/hooks/controllers/gestor.hooks.php
function meu_projeto_roteador_paginas($paginas) {
    // ... alterar $paginas ...
    return $paginas; // um filter SEMPRE devolve o valor
}
```

### Num módulo ou plugin: chave `hooks` do `<modulo>.json`

Mesmo formato, dentro de `"hooks": { "controllers": {…}, "actions": {…}, "filters": {…} }`. O arquivo do controller é relativo à pasta do módulo (ou `plugins/<plugin>/modules/<modulo>/`).

### Formas do callback

| Forma | Exemplo |
|---|---|
| Nome da função | `"editar.pagina": "minha_funcao"` |
| Objeto | `{ "callback": "minha_funcao", "prioridade": 5, "habilitado": 1 }` |
| Lista | `["funcao_a", { "callback": "funcao_b", "prioridade": 20 }]` |

A prioridade padrão é 10; **menor roda antes**. Empates seguem a ordem de inserção. `habilitado: 0` mantém o registro sem executar.

> [!WARNING]
> **Cada namespace usado precisa de uma entrada em `controllers`.** O arquivo PHP é incluído pelo namespace do evento, não pelo nome da função. Um filter no namespace `interface` cuja função mora em `multiusuario.hooks.php` só funciona se `controllers` tiver `"interface": "multiusuario.hooks.php"`. Sem isso, o callback só existe quando outro namespace já incluiu o arquivo na mesma requisição; nas outras, **é ignorado em silêncio** (`is_callable()` falso, sem log).

## Do JSON ao banco

A tabela `hooks` é reconstruída a partir dos JSONs, sem edição manual:

- em todo `project:update-all` / `manager:update-all` (fim do atualizador de banco; não roda com `--dry-run`);
- só os hooks: `php gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php --hooks-only`;
- no deploy de projeto pela API (`_api/project/update`) e na atualização do sistema.

A sincronização é declarativa: para cada módulo apaga os hooks dele e reinsere os do JSON (sem a chave `hooks`, os antigos somem); os do projeto são apagados e reinseridos de `project/hooks/hooks.json`. Os registros de projeto ficam com `projeto=1` e sem `modulo`.

Mudou um `hooks.json`? Rode o pipeline ou o `--hooks-only`. Sem isso, o banco continua com a versão anterior.

## Em tempo de execução

1. No primeiro disparo de `namespace.evento` na requisição, o `HookManager` busca na tabela os registros `status='A'` e `habilitado=1` desse namespace **e do namespace coringa `*`**, ordenados por prioridade.
2. Inclui (uma vez) o controller de cada registro.
3. Executa os callbacks. Se a função pede mais parâmetros obrigatórios do que o evento passa, os que faltam chegam como `null`.
4. Uma exceção num callback é gravada em `logs/hooks-errors.log` (e no `error_log` em desenvolvimento) e **não interrompe** os demais. Num filter, o valor segue como estava antes do callback que falhou.

Para saber se vale a pena montar dados caros antes de disparar: `hook_has_actions('ns', 'evento')` e `hook_has_filters('ns', 'evento')`.

> [!CAUTION]
> Um filter que esquece o `return` transforma o valor em `null` para todos os callbacks seguintes e para o núcleo. Não há verificação de tipo.

## Catálogo: eventos que o core dispara

### Módulos de CRUD (`interface.php`)

Todo módulo que usa `interface_iniciar()`/`interface_finalizar()` dispara, com namespace = **id do módulo** e evento = `<opcao>.<momento>`:

| Evento | Quando | Argumentos |
|---|---|---|
| `<opcao>.pre-banco` | POST, antes de o módulo gravar | — |
| `<opcao>.parametros` | GET, antes de a interface montar a tela | — |
| `<opcao>.pagina` | GET, depois de a interface montar a tela | — |
| `excluir.banco` | depois de excluir | `$id` |
| `status.banco` | depois de trocar o status | `$id`, `$status` |

`<opcao>` é `listar`, `adicionar`, `editar`, `clonar`, `visualizar`, `config`… Quando o módulo define `$_GESTOR['interface-opcao']` diferente da `opcao`, o evento dispara para as duas. Os hooks recebem os dados por `$_GESTOR`/`$_REQUEST`, já que não há argumentos: um `editar.parametros` típico acrescenta campos ou altera `$_GESTOR['interface']`.

### Módulos específicos

| Namespace | Evento | Tipo | Argumentos |
|---|---|---|---|
| `admin-paginas` | `adicionar.banco`, `editar.banco`, `clonar.banco` | action | `$id`, array com `nome`, `caminho`, `tipo`, `modulo` (já escapados) |
| `dashboard` | `start` | action | — |
| `dashboard` | `site-toolbar.permissao-pagina` | filter | `true`, `$pagina` → pode mostrar a barra do Live Editor |
| `perfil-usuario` | `signup.start`, `signup.pos_banco`, `signup.end` | action | — |
| `perfil-usuario` | `signup.banco` | action | `$id_usuarios`, array com `nome`, `email`, `id`, `plano`, `domain` |
| `perfil-usuario` | `signup.email` | filter | `true`, `$id_usuarios`, dados do cadastro → `false` não envia o e-mail |
| `perfil-usuario` | `signup.redirect` | filter | URL, `$id_usuarios` |
| `admin-prompts-ia` | `padrao.update.where` | filter | cláusula WHERE |
| `publisher-pages` | `listar.publisher-select` | filter | lista de publishers do seletor |

### Bibliotecas e núcleo

| Namespace | Evento | Tipo | Valor filtrado / argumentos |
|---|---|---|---|
| `gestor` | `roteador.paginas` | filter | as páginas encontradas para a URL ([ciclo](request-lifecycle.md)) |
| `formulario` | `email.mensagem` | filter | a mensagem; array com `form_id`, `language`, `origem` |
| `interface` | `formulario_campos.imagepickJS` | filter | JS do seletor de imagem |
| `html-editor` | `templates.load.where`, `html_editor_include.projectJS`, `html_editor_include.imagepickJS` | filter | WHERE dos templates; JS do projeto; JS do seletor de imagem |
| `ia` | `config`, `models.available`, `prompts.load.where`, `prompt.option` | filter | configuração; modelos; WHERE dos prompts; HTML da opção (`$prompt`) |
| `cron` | `<frequencia>` (`diario`, `minutario`…) | action | legado: prefira a chave `cron` do módulo ([recursos](resources.md)) |

Os filters de `ia`, `html-editor`, `interface` e `admin-prompts-ia` foram criados para isolar dados por usuário (multiusuário): um projeto restringe o WHERE ao dono do registro.

## Detalhes

- Não há API para registrar hooks por código. Um plugin que precise de um hook declara-o no JSON do seu módulo.
- A sincronização de um módulo apaga por **id do módulo**, sem considerar o plugin: dois módulos com o mesmo id (um do core e outro de plugin) apagam os hooks um do outro.
- Hooks rodam dentro da requisição, com as mesmas globais: `global $_GESTOR;` funciona normalmente.

## Veja também

- [Ciclo de uma requisição](request-lifecycle.md) e [biblioteca interface.php](../reference/libraries/interface.md).
- Skill `c2f-hooks-system`.
