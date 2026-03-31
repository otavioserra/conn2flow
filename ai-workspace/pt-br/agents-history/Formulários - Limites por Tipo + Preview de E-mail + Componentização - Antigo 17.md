# Formulários — Limites por Tipo + Preview de E-mail + Componentização - Antigo 17

## Contexto e Objetivos

Esta sessão de desenvolvimento focou na evolução da **biblioteca de formulários** do Conn2Flow (`gestor/bibliotecas/formulario.php`) e do seu módulo de administração (`gestor/modulos/forms/`), com três melhorias independentes mas complementares:

1. **Limites de caracteres por tipo de campo** — validação autoritativa no servidor (PHP), com mapeamento de padrões por tipo (`text`/`email` = 254, `textarea` = 10.000) e suporte a override por campo via JSON schema. Validação replicada no cliente (JS) com atributo `maxlength` e contador em tempo real.
2. **Preview de e-mail truncado** — e-mails de notificação exibem um preview do campo (`#valor#`), enquanto o valor completo (`#valor_full#`) fica disponível no template e preservado integralmente no banco de dados.
3. **Componentização** — o bloco de documentação inline das páginas de admin (`forms-adicionar`, `forms-editar`, `forms-visualizar`, `forms-clonar`) foi extraído para um componente reutilizável `#forms-info-definition#`, e o componente em inglês foi **criado** nesta sessão.

**Branch:** `main`  
**Repositório:** `conn2flow`  
**Data:** 2026-03-31

---

## Arquivos Modificados / Criados

| Arquivo | Tipo | Ação |
|---------|------|------|
| `gestor/bibliotecas/formulario.php` | PHP | ✏️ Modificado |
| `gestor/assets/interface/formulario.js` | JavaScript | ✏️ Modificado |
| `gestor/modulos/forms/forms.php` | PHP | ✏️ Modificado |
| `gestor/modulos/forms/resources/pt-br/pages/forms-adicionar/forms-adicionar.html` | HTML | ✏️ Modificado |
| `gestor/modulos/forms/resources/pt-br/pages/forms-editar/forms-editar.html` | HTML | ✏️ Modificado |
| `gestor/modulos/forms/resources/pt-br/pages/forms-visualizar/forms-visualizar.html` | HTML | ✏️ Modificado |
| `gestor/modulos/forms/resources/pt-br/pages/forms-clonar/forms-clonar.html` | HTML | ✏️ Modificado |
| `gestor/modulos/forms/resources/en/pages/forms-adicionar/forms-adicionar.html` | HTML | ✏️ Modificado |
| `gestor/modulos/forms/resources/en/pages/forms-editar/forms-editar.html` | HTML | ✏️ Modificado |
| `gestor/modulos/forms/resources/en/pages/forms-visualizar/forms-visualizar.html` | HTML | ✏️ Modificado |
| `gestor/modulos/forms/resources/en/pages/forms-clonar/forms-clonar.html` | HTML | ✏️ Modificado |
| `gestor/modulos/forms/resources/en/components/forms-info-definition/forms-info-definition.html` | HTML | ➕ Criado |
| `gestor/resources/pt-br/components/form-ui/form-ui.html` | HTML | ✏️ Modificado |
| `gestor/resources/en/components/form-ui/form-ui.html` | HTML | ✏️ Modificado |
| `gestor/db/data/ComponentesData.json` | JSON | ✏️ Modificado |
| `ai-workspace/pt-br/docs/modulos/forms.md` | MD | ➕ Criado |
| `ai-workspace/en/docs/modulos/forms.md` | MD | ➕ Criado |

---

## Fase 1 — Limites de Caracteres no Servidor

### Problema Original

O `formulario_processador` validava campos obrigatórios (presença e mínimo de 3 chars) mas **não impunha nenhum limite máximo** de caracteres. Um usuário mal-intencionado poderia submeter centenas de kilobytes em um único campo `textarea`, causando:
- Sobrecarga de armazenamento (campo `fields_values` JSON no banco).
- E-mails de notificação gigantes e ilegíveis.
- Potencial abuso de memória/processamento no servidor.

### Decisão de Design

Ao invés de um limite único global, optamos por **limites por tipo**, alinhados com padrões estabelecidos:

| Tipo | Limite | Justificativa |
|------|--------|---------------|
| `text` | 254 | Equivalente ao `VARCHAR(255)` SQL; maioria dos bancos de dados |
| `email` | 254 | RFC 5321 — limite real de endereços de e-mail |
| `textarea` | 10.000 | Conteúdo longo permitido, mas com proteção razoável |
| outros | 1.000 | Fallback conservador |

Qualquer campo pode **sobrescrever** o padrão com `max_length` no JSON schema.

### Implementação — Dupla Varredura

O processador executa a validação em duas passagens separadas para cobrir todos os cenários:

**1ª Varredura — Apenas campos obrigatórios** (junto com validação de obrigatoriedade):
```php
// No bloco de validação de campos required:
$maxLength = null;
if(isset($field['max_length'])){
    $maxLength = (int)$field['max_length'];
} else {
    if(in_array($field['type'], ['text','email'])) $maxLength = 254;
    elseif($field['type'] === 'textarea')           $maxLength = 10000;
}
if($maxLength && mb_strlen($fieldValue, 'UTF-8') > $maxLength){
    formulario_acesso_falha([...]);
    $_GESTOR['ajax-json'] = ['status' => 'error', 'message' => $msg];
    return false;
}
```

**2ª Varredura — Todos os campos** (incluindo os não-obrigatórios que foram preenchidos):
```php
// Loop separado que percorre todos os fields:
$maxLength = isset($field['max_length']) ? (int)$field['max_length'] : (
    in_array($field['type'], ['text','email']) ? 254 : (
        ($field['type'] === 'textarea') ? 10000 : 1000
    )
);
if($maxLength && mb_strlen($fieldValue, 'UTF-8') > $maxLength){
    // rejeitar e registrar falha de acesso
    return false;
}
```

> **Por que `mb_strlen` ao invés de `strlen`?** `strlen` conta bytes, não caracteres. Para texto UTF-8 com acentos e emojis, um caractere pode ocupar 2, 3 ou 4 bytes. `mb_strlen(..., 'UTF-8')` conta caracteres reais, garantindo que o limite seja semântico e não técnico.

### Mensagem de Erro `maxLength`

A mensagem foi adicionada ao componente `form-ui` (arquivo HTML) para manter o padrão multilíngue:

```html
<!-- ajax-message-max-length < -->
O campo #fieldLabel# excedeu o limite máximo de #max# caracteres.
<!-- ajax-message-max-length > -->
```

No PHP, ela é lida e processada com `modelo_var_troca`:
```php
$msg = $form_ui_ajax_messages['maxLength'] ?? 'Field exceeded max length.';
$msg = modelo_var_troca($msg, '#fieldLabel#', $field['label'] ?? $fieldName);
$msg = modelo_var_troca($msg, '#max#', $maxLength);
```

---

## Fase 2 — Contador de Caracteres Client-Side

### Problema Original

O JavaScript em `formulario.js` não aplicava `maxlength` ao renderizar os campos, deixando o usuário sem nenhum feedback visual sobre o limite enquanto digitava. A validação do servidor rejeitava a submissão, mas apenas após o formulário ser enviado.

### Decisão de Design

Duplicar a lógica de limites no cliente para UX imediata: o browser bloqueia a digitação ao atingir o `maxlength` e um contador visual mostra o progresso. O servidor **sempre** re-valida — o cliente é conveniente, não confiável.

### Implementação em `formulario.js`

#### Atribuição de `maxlength`

Dentro do `initFormController`, no loop de campos:
```javascript
var maxLength = field.max_length
    ? parseInt(field.max_length, 10)
    : (['text', 'email'].indexOf(field.type) !== -1 ? 254
        : (field.type === 'textarea' ? 10000 : null));

if (maxLength) {
    input.attr('maxlength', maxLength);
    // Inserir contador logo após o input
    input.after('<div class="field-counter"><small class="char-counter">0 / ' + maxLength + '</small></div>');
    updateCharCounter(input, maxLength);             // estado inicial
    input.on('input', function () {
        updateCharCounter($(this), maxLength);        // uso de $(this) para robustez
    });
}
```

#### Busca Resiliente do Contador

O maior desafio foi que a estrutura do DOM varia entre os frameworks CSS (Fomantic UI e Tailwind). A solução foi uma busca em cascata:

```javascript
function updateCharCounter(input, maxLength) {
    var $input = (input instanceof jQuery) ? input : $(input);
    var val = $input.val() || '';
    var length = val.length;

    // Cascata de seletores para encontrar o .char-counter em qualquer estrutura
    var counter = $input.closest('.field').find('.char-counter');
    if (!counter.length) counter = $input.siblings('.field-counter').find('.char-counter');
    if (!counter.length) counter = $input.nextAll('.field-counter').find('.char-counter');
    if (!counter.length) counter = $input.parent().find('.char-counter');

    if (counter.length) {
        counter.text(length + ' / ' + maxLength);
        counter.css('color', length > maxLength ? '#dc2626' : '');
    }
}
```

#### Bug: Contador Não Atualizava

**Problema identificado**: o handler original usava a variável `input` capturada no closure em vez de `$(this)`, o que em alguns contextos de jQuery resultava em referências desatualizadas.

**Correção**:
```javascript
// Antes (problemático):
input.on('input', function () { updateCharCounter(input, maxLength); });

// Depois (correto):
input.on('input', function () { updateCharCounter($(this), maxLength); });
```

---

## Fase 3 — Preview de E-mail × Valor Completo

### Problema Original

O template de e-mail usava `#valor#` para exibir o conteúdo do campo. Para campos `textarea` com textos longos (e-mails de suporte, mensagens elaboradas), o e-mail podia conter centenas ou milhares de caracteres em um único campo, tornando-o ilegível.

Além disso, campos `textarea` perdiam suas quebras de linha no e-mail HTML, pois o valor bruto não era convertido para `<br>`.

### Decisão de Design

Manter **dois registros por campo** no processamento do template:

| Variável | O que contém | Onde usar |
|----------|-------------|-----------|
| `#valor#` | Preview formatado: truncado + quebras como `<br>` | Template de e-mail de notificação |
| `#valor_full#` | Conteúdo completo formatado | Templates admin, futuros detalhes |

Configuração do comprimento do preview via `$_CONFIG['formularios-email-preview-length']` (padrão 800).

### Implementação em PHP

#### Processamento por Tipo

```php
// Para email: link clicável (sem truncamento — e-mails são curtos)
if($field['type'] === 'email' && filter_var($rawValue, FILTER_VALIDATE_EMAIL)){
    $fieldValueFormatted = '<a href="mailto:' . htmlspecialchars($rawValue, ENT_COMPAT, 'UTF-8') . '">'
                         . htmlspecialchars($rawValue, ENT_COMPAT, 'UTF-8') . '</a>';
} else {
    $fieldValue = htmlspecialchars($rawValue, ENT_QUOTES, 'UTF-8');
    $fieldValueFormatted = $fieldValue;
}

// Para textarea: normalizar quebras de linha e preservar para preview HTML
if($field['type'] === 'textarea'){
    $plainForPreview = preg_replace("/\r\n|\r/", "\n", $fieldValue);
    $preview = nl2br($plainForPreview);  // quebras → <br> para o e-mail HTML
} else {
    $plainForPreview = strip_tags($fieldValueFormatted);
    $preview = $plainForPreview;
}

$camposProcessados[] = [
    '#label#'      => $fieldLabel,
    '#valor#'      => $preview,            // para o e-mail
    '#valor_full#' => $fieldValueFormatted // completo, preservado
];
```

#### Bug: Quebras de Linha Perdidas no E-mail

**Problema**: o preview de `textarea` era processado com `strip_tags`, que removia os `<br>` e colapsava o texto em uma linha única.

**Causa raiz**: a ordem das operações — `htmlspecialchars` → `strip_tags($fieldValueFormatted)` — removia qualquer tag antes de `nl2br`.

**Correção**: para `textarea`, usar `$fieldValue` (já sanitizado por `htmlspecialchars`, mas SEM tags HTML) como base para `nl2br`:
```php
// Usa $fieldValue (texto puro sanitizado) como base, não $fieldValueFormatted (que pode ter tags)
$plainForPreview = preg_replace("/\r\n|\r/", "\n", $fieldValue);
$preview = nl2br($plainForPreview);
```

---

## Fase 4 — Componentização `forms-info-definition`

### Problema Original

As 4 páginas de admin (`adicionar`, `editar`, `visualizar`, `clonar`) × 2 idiomas (`pt-br`, `en`) = **8 arquivos HTML** continham o mesmo bloco de documentação inline. Qualquer atualização na documentação precisava ser replicada manualmente em todos os 8 arquivos.

### Decisão de Design

Extrair o bloco para um **componente reutilizável** `forms-info-definition`. As páginas ficam com apenas o placeholder `#forms-info-definition#` e o controller PHP injeta o conteúdo.

### Implementação

#### Substituição nas Páginas HTML

Nos 8 arquivos HTML de páginas, o bloco de documentação foi substituído por:
```html
#forms-info-definition#
```

#### Injeção no Controller PHP (`forms.php`)

No `forms.php`, nos handlers de cada ação (adicionar, editar, visualizar, clonar):
```php
$pagina = modelo_var_troca(
    $pagina,
    '#forms-info-definition#',
    gestor_componente(['id' => 'forms-info-definition'])
);
```

#### Criação do Componente EN

O componente `pt-br` já existia. O arquivo EN foi **criado** nesta sessão:
```
gestor/modulos/forms/resources/en/components/forms-info-definition/forms-info-definition.html
```

---

## Fase 5 — Seeds e Componente `form-ui`

### Problema

O componente `form-ui` (tanto `pt-br` quanto `en`) e o seed `ComponentesData.json` não continham as novas chaves de mensagem de `maxLength`.

### Implementação

Foram adicionadas aos arquivos HTML dos componentes `form-ui` (pt-br e en):

1. **Prompt inline** (para validação cliente):
   ```html
   <!-- prompt-max-length < -->
   O campo #label# deve ter no máximo #max# caracteres.
   <!-- prompt-max-length > -->
   ```

2. **Mensagem AJAX** (resposta servidor para cliente):
   ```html
   <!-- ajax-message-max-length < -->
   O campo #fieldLabel# excedeu o limite máximo de #max# caracteres.
   <!-- ajax-message-max-length > -->
   ```

O `ComponentesData.json` foi atualizado para refletir as novas versões dos componentes, garantindo que instalações fresh e atualizações de banco recebam os dados corretos.

---

## Decisões Transversais

### Por que Validação Dupla (Client + Server)?

O cliente oferece feedback imediato e boa UX. O servidor é o guardião real — qualquer request direto à API (sem browser) não passaria pela validação JS. Portanto, ambas são necessárias.

### Por que `mb_strlen` e não `strlen`?

`strlen` em PHP conta bytes, não caracteres. Para UTF-8:
- `ã` = 2 bytes, mas 1 caractere.
- Um emoji como 🚀 = 4 bytes, mas 1 caractere.

Usar `strlen` quebraria o limite para conteúdo multilíngue. `mb_strlen(..., 'UTF-8')` é a alternativa correta.

### Por que Dupla Varredura no Servidor?

A 1ª varredura valida campos obrigatórios e aborta cedo. A 2ª varredura pega campos opcionais que foram preenchidos com mais conteúdo do que permitido. Juntas, cobrem todos os cenários sem criar um loop único mais complexo de entender e manter.

### Por que Componente em Vez de Include Direto?

O sistema de templates do Conn2Flow usa variáveis (`#nome-do-componente#`) processadas em runtime pelo PHP. Componentes são resolvidos por idioma automaticamente — `gestor_componente(['id' => 'forms-info-definition'])` busca a versão correta para o idioma ativo sem nenhuma lógica adicional nos templates HTML.

---

## Verificações Realizadas

- ✅ `get_errors` após modificações em `formulario.php` → **nenhum erro encontrado**
- ✅ Estrutura de arquivos dos componentes EN verificada
- ✅ Verificação de existência do componente `forms-info-definition` em pt-br (já existia) e en (criado)
- ✅ Seeds `ComponentesData.json` confirmados com as novas chaves

---

## Próximos Passos Recomendados

1. **Atualizar templates de e-mail admin** para usar `#valor_full#` onde o admin precisa do conteúdo completo (ex.: detalhe de ticket de suporte).
2. **Testes automatizados** para:
   - Enforcement de limites por tipo (text=254, textarea=10.000, email=254).
   - Truncamento correto do preview no e-mail.
   - Comportamento com caracteres UTF-8 complexos (emojis, acentos compostos).
3. **Documentar** `$_CONFIG['formularios-email-preview-length']` na documentação do config.
4. **Considerar** expor o comprimento do preview como configuração visual no painel admin.
5. **Commit + PR** para revisão e CI.

---

## Resumo Executivo

| O que foi feito | Benefício |
|-----------------|-----------|
| Limite máximo por tipo no servidor (2 varreduras, `mb_strlen` UTF-8) | Proteção contra submissões abusivas e dados inconsistentes |
| `maxlength` + contador de caracteres no cliente | Feedback imediato ao usuário; UX profissional |
| Preview truncado no e-mail + `#valor_full#` no template | E-mails legíveis; dados completos preservados no banco |
| Componentização `#forms-info-definition#` (8 arquivos → 2 componentes) | Documentação admin centralizada; fácil de manter |
| Componente `forms-info-definition` EN criado | Paridade entre pt-br e en |
| Seeds e `form-ui` atualizados com mensagens `maxLength` | Instalações fresh e atualizações recebem os dados corretos |
