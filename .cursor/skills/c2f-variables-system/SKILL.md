---
name: c2f-variables-system
description: "LEIA ANTES de escrever qualquer texto, rótulo, título, tooltip, mensagem de erro ou alerta no código. Se não ler: strings literais violam a governança i18n, impedem personalização por tenant e o PR é rejeitado."
user-invocable: false
---

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Escrever, alterar ou adicionar qualquer texto visível, label, mensagem de validação, título ou alerta em arquivos PHP, HTML ou JavaScript.
- **SKIP APENAS SE**: Identificadores técnicos estritos (nomes de colunas SQL, chaves de array, classes CSS ou slugs internos).
- **CONSEQUÊNCIA DE IGNORAR**: Rejeição sumária do PR por inclusão de texto hardcoded, quebra da internacionalização (i18n) e impossibilidade de alteração de textos via painel de variáveis.

---

﻿---
name: c2f-variables-system
description: "Use SEMPRE que for escrever textos, mensagens de erro, alertas, labels ou qualquer string de interface. Consulte ANTES de usar texto literal em PHP, HTML ou JS."
user-invocable: false
---

# Sistema de VariÃ¡veis e Mensagens MultilÃ­ngues do Conn2Flow

> [!CAUTION]
> **REGRA MANDATÃ“RIA â€” PROIBIDO TEXTO LITERAL FIXO**:
> Ã‰ ESTRITAMENTE PROIBIDO inserir textos literais fixos (strings hardcoded) em cÃ³digo PHP, HTML ou JavaScript para:
> - Mensagens de erro ou sucesso
> - Alertas e warnings de interface
> - Labels de formulÃ¡rios e botÃµes
> - TÃ­tulos, descriÃ§Ãµes e placeholders
> - Qualquer texto visÃ­vel ao usuÃ¡rio final
>
> TODOS os textos DEVEM ser declarados no **Sistema de VariÃ¡veis** e consumidos via API.

## 1. DeclaraÃ§Ã£o de VariÃ¡veis

### 1.1. VariÃ¡veis Globais
Arquivo: `gestor/resources/<lang>/variables.json`

```json
{
  "minha-variavel": {
    "valor": "Texto visÃ­vel ao usuÃ¡rio",
    "descricao": "DescriÃ§Ã£o interna para o administrador"
  },
  "erro-permissao": {
    "valor": "VocÃª nÃ£o tem permissÃ£o para acessar este recurso.",
    "descricao": "Mensagem de erro de permissÃ£o"
  }
}
```

### 1.2. VariÃ¡veis de MÃ³dulo
Arquivo: `modulos/<modulo-id>/<modulo-id>.json`, dentro da chave `resources.<lang>.variables`:

```json
{
  "resources": {
    "pt-br": {
      "variables": {
        "titulo-listagem": {
          "valor": "Lista de Registros",
          "descricao": "TÃ­tulo da pÃ¡gina de listagem"
        }
      }
    }
  }
}
```

---

## 2. Consumo de VariÃ¡veis

### 2.1. Em PHP
```php
// VariÃ¡vel global
$texto = gestor_variaveis(['id' => 'minha-variavel']);

// VariÃ¡vel de mÃ³dulo
$texto = gestor_variaveis(['modulo' => 'meu-modulo', 'id' => 'titulo-listagem']);
```

### 2.2. Em HTML / Templates de Recurso
```html
<!-- SubstituiÃ§Ã£o direta no template engine -->
<h1>@[[titulo-listagem]]@</h1>
<p>[[descricao-pagina]]</p>

<!-- Alternativa: substituiÃ§Ã£o via modelo_var_troca() no PHP que renderiza o template -->
```

### 2.3. Em JavaScript (via atributos data ou variÃ¡veis PHP injetadas)
```html
<div data-msg-erro="@[[erro-permissao]]@"></div>
```
```javascript
const msg = element.dataset.msgErro;
```

---

## 3. Fluxo de CompilaÃ§Ã£o

1. Editar `variables.json` (global) ou `<modulo>.json` (mÃ³dulo)
2. `atualizacao-dados-recursos.php` compila para `VariaveisData.json`
3. `atualizacoes-banco-de-dados.php` aplica Upsert na tabela `variaveis`
4. Runtime consome via `gestor_variaveis()` ou substituiÃ§Ã£o de template
