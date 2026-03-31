# Módulo: forms

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `forms` |
| **Nome** | Formulários Dinâmicos |
| **Versão** | `1.0.4` |
| **Categoria** | Módulo de Comunicação |
| **Complexidade** | 🟡 Média |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html`, `banco`, `comunicacao`, `formulario` (biblioteca) |

---

## 🎯 Propósito

O módulo **forms** gerencia a criação e processamento de **formulários dinâmicos** no Conn2Flow. Permite que o administrador configure formulários com campos personalizados (nome, tipo, obrigatoriedade, limites) via JSON schema, que são renderizados no front-end, validados cliente/servidor e armazenados no banco de dados. Notificações por e-mail são enviadas automaticamente a cada submissão.

---

## 🏗️ Funcionalidades Principais

### 📋 **Gerenciamento de Formulários (Admin)**
- **Criar / Editar / Clonar**: Formulários configuráveis via JSON schema.
- **Visualizar**: Preview de campos, comportamento e configurações.
- **Ativar / Desativar**: Controle de status do formulário.
- **Multilíngue**: Cada formulário é criado por idioma (`language`).

### 📤 **Submissão e Processamento**
- **Recebimento via AJAX**: Submissões assíncronas com feedback de loading/erro.
- **Anti-spam**: Honeypot, rate limiting por IP, reCAPTCHA v3 e v2 como fallback.
- **Validação dupla**: Cliente (JS) e servidor (PHP) — o servidor é autoritativo.
- **Armazenamento**: Todos os campos e valores salvos na tabela `forms_submissions`.
- **Email de notificação**: Enviado automaticamente após submissão válida.

### 🛡️ **Validação e Limites de Caracteres**
- Limites por tipo de campo com padrões sólidos e suporte a override por campo.
- Preview truncado no e-mail × valor completo preservado no banco.
- Contador de caracteres em tempo real no cliente.

### 📧 **E-mail de Notificação**
- Template configurável por formulário (campo `message_component` no schema).
- Suporte a campos processados como células repetidas (`<!-- cel < --> ... <!-- cel > -->`).
- Embedding automático de imagens locais (via CID/PHPMailer).
- Campo `#valor#` = preview; `#valor_full#` = conteúdo completo.

---

## 🛡️ Limites de Caracteres por Tipo

Esta funcionalidade foi adicionada na versão **1.0.4** e garante proteção contra submissões abusivas, ao mesmo tempo que preserva a legibilidade dos e-mails de notificação.

### Limites Padrão por Tipo

| Tipo | Mínimo (obrigatório) | Máximo Padrão | Comentário |
|------|----------------------|---------------|------------|
| `text` | 3 caracteres | **254** | Equivalente ao limite de `VARCHAR(255)` do SQL |
| `email` | — | **254** | Padrão RFC 5321 para endereços de e-mail |
| `textarea` | 3 caracteres | **10.000** | Textos longos; suporte a multi-linha no banco |
| outros | — | **1.000** | Fallback para tipos não mapeados |

> Os limites podem ser **sobrescritos por campo** via `max_length` no JSON schema.

### Override por Campo no Schema

```json
{
  "fields": [
    {
      "name": "mensagem",
      "type": "textarea",
      "label": "Mensagem",
      "required": true,
      "max_length": 500
    }
  ]
}
```

### Fluxo de Validação Servidor (`formulario_processador`)

A validação acontece em **duas varreduras** distintas para garantir robustez:

**1ª Varredura — Campos obrigatórios:**
```php
// Mínimo de 3 caracteres para text/textarea obrigatórios
if(in_array($field['type'], ['text', 'textarea']) && mb_strlen($fieldValue, 'UTF-8') < 3){
    return false; // mensagem: ajax-message-min-length
}

// Máximo por tipo (ou override individual)
$maxLength = isset($field['max_length']) ? (int)$field['max_length'] : null;
if(!$maxLength) {
    if(in_array($field['type'], ['text','email'])) $maxLength = 254;
    elseif($field['type'] === 'textarea')           $maxLength = 10000;
}
if($maxLength && mb_strlen($fieldValue, 'UTF-8') > $maxLength){
    return false; // mensagem: ajax-message-max-length
}
```

**2ª Varredura — Todos os campos (incluindo opcionais):**
```php
$maxLength = isset($field['max_length']) ? (int)$field['max_length'] : (
    in_array($field['type'], ['text','email']) ? 254 : (
        ($field['type'] === 'textarea') ? 10000 : 1000
    )
);
if($maxLength && mb_strlen($fieldValue, 'UTF-8') > $maxLength){
    return false; // mensagem: ajax-message-max-length
}
```

> Ambas as varreduras usam `mb_strlen(..., 'UTF-8')` para suporte correto a acentos e emojis.

---

## 📱 Contador de Caracteres (Cliente)

O JavaScript (`gestor/assets/interface/formulario.js`) aplica `maxlength` dinamicamente e exibe um contador em tempo real abaixo de cada campo.

### Lógica de Atribuição do `maxlength`

```javascript
// Detecta maxLength: schema > defaults (text/email=254, textarea=10000)
var maxLength = field.max_length
    ? parseInt(field.max_length, 10)
    : (['text', 'email'].indexOf(field.type) !== -1 ? 254
        : (field.type === 'textarea' ? 10000 : null));

if (maxLength) {
    input.attr('maxlength', maxLength);
    // Injetar contador abaixo do campo
    input.after('<div class="field-counter"><small class="char-counter">0 / ' + maxLength + '</small></div>');
    updateCharCounter(input, maxLength);
    input.on('input', function () { updateCharCounter($(this), maxLength); });
}
```

### Função `updateCharCounter`

Busca resiliente do elemento contador — suporta diferentes estruturas de DOM (Fomantic UI / Tailwind):

```javascript
function updateCharCounter(input, maxLength) {
    var $input = (input instanceof jQuery) ? input : $(input);
    var val = $input.val() || '';
    var length = val.length;

    // Busca em cascata: .field > .char-counter → siblings → nextAll → parent
    var counter = $input.closest('.field').find('.char-counter');
    if (!counter.length) counter = $input.siblings('.field-counter').find('.char-counter');
    if (!counter.length) counter = $input.nextAll('.field-counter').find('.char-counter');
    if (!counter.length) counter = $input.parent().find('.char-counter');

    if (counter.length) {
        counter.text(length + ' / ' + maxLength);
        // Vermelho quando excede (embora maxlength impeça via browser)
        counter.css('color', length > maxLength ? '#dc2626' : '');
    }
}
```

---

## 📧 Preview de E-mail × Valor Completo no Banco

Para evitar e-mails de notificação excessivamente longos (especialmente para campos `textarea`), o sistema gera dois valores distintos por campo:

| Variável de Template | Conteúdo | Onde usar |
|---------------------|----------|-----------|
| `#valor#` | Preview formatado (truncado para `textarea`, quebras como `<br>`) | Template de e-mail |
| `#valor_full#` | Valor completo e formatado (sem truncamento) | Templates de admin, futuras telas de detalhe |

### Configuração do Preview

O comprimento máximo do preview é configurável via `config.php` ou `.env`:

```php
$_CONFIG['formularios-email-preview-length'] // padrão: 800 caracteres
```

### Processamento por Tipo no PHP

```php
// Para textarea: preserva quebras de linha no preview
if($field['type'] === 'textarea'){
    $plainForPreview = preg_replace("/\r\n|\r/", "\n", $fieldValue);
    $preview = nl2br($plainForPreview); // <br> para HTML do e-mail
} else {
    $plainForPreview = strip_tags($fieldValueFormatted);
    $preview = $plainForPreview;
}

$camposProcessados[] = [
    '#label#'       => $fieldLabel,
    '#valor#'       => $preview,          // usado no template de e-mail
    '#valor_full#'  => $fieldValueFormatted  // completo, para uso futuro
];
```

---

## 🗄️ Estrutura do Banco de Dados

### Tabela `forms`

Armazena a definição e configuração de cada formulário.

```sql
CREATE TABLE forms (
    id_forms INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    language VARCHAR(10) NOT NULL DEFAULT 'pt-br',
    name VARCHAR(255) NOT NULL,
    fields_schema TEXT NOT NULL,   -- JSON schema com campos, redirecionamentos e configurações de e-mail
    status CHAR(1) DEFAULT 'A',    -- A = Ativo, I = Inativo
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

### Tabela `forms_submissions`

Armazena cada submissão recebida.

```sql
CREATE TABLE forms_submissions (
    id_forms_submissions INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    form_id VARCHAR(255) NOT NULL,   -- FK para forms.id
    name VARCHAR(255) NOT NULL,      -- Valor do campo configurado como field_name
    language VARCHAR(10) NOT NULL DEFAULT 'pt-br',
    fields_values JSON,              -- Array de {name, value} + email_status
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

### Estrutura do `fields_values` (JSON)

```json
{
  "fields": [
    { "name": "nome",     "value": "João Silva" },
    { "name": "email",    "value": "joao@example.com" },
    { "name": "mensagem", "value": "Olá, gostaria de saber..." }
  ],
  "email_status": "email-sent"
}
```

---

## 📁 Estrutura de Arquivos

```
gestor/
├── bibliotecas/
│   └── formulario.php                         # Biblioteca principal (controlador, processador, validação)
├── assets/
│   └── interface/
│       └── formulario.js                      # JS client-side (validação, contador, AJAX)
├── modulos/
│   └── forms/
│       ├── forms.php                          # Controlador do módulo admin
│       ├── forms.js                           # JS específico do admin
│       ├── forms.json                         # Configuração do módulo
│       └── resources/
│           ├── pt-br/
│           │   ├── components/
│           │   │   ├── forms-info-definition/ # 📌 Documentação inline (componente reutilizável)
│           │   │   └── forms-prepared-email/  # Template HTML do e-mail de notificação
│           │   └── pages/
│           │       ├── forms-adicionar/       # Página: novo formulário
│           │       ├── forms-editar/          # Página: editar formulário
│           │       ├── forms-visualizar/      # Página: visualizar formulário
│           │       └── forms-clonar/          # Página: clonar formulário
│           └── en/
│               ├── components/
│               │   ├── forms-info-definition/ # 📌 Versão EN do componente de docs
│               │   └── forms-prepared-email/
│               └── pages/
│                   └── ... (mesma estrutura)
└── resources/
    ├── pt-br/
    │   └── components/
    │       └── form-ui/                       # Componente de UI do formulário público
    └── en/
        └── components/
            └── form-ui/                       # Versão EN do form-ui
```

---

## 🧩 Componente `form-ui`

O componente `form-ui` contém todas as strings usadas pelo JavaScript e pelo PHP para validação e feedback visual.

### Células do Componente

| Célula | Tipo | Descrição |
|--------|------|-----------|
| `<!-- prompts < --> ... <!-- prompts > -->` | HTML | Mensagens de validação inline (empty, email, maxLength) |
| `<!-- ui-texts < --> ... <!-- ui-texts > -->` | HTML | Textos da interface (loading, errors, timeout) |
| `<!-- ui-components < --> ... <!-- ui-components > -->` | HTML | Componentes HTML reutilizáveis (dimmer, error element, reCAPTCHA v2) |
| `<!-- block-wrapper < --> ... <!-- block-wrapper > -->` | HTML | Wrapper de bloqueio por IP/rate limit |
| `<!-- ajax-messages < --> ... <!-- ajax-messages > -->` | HTML | Mensagens JSON devolvidas via AJAX |

### Mensagem `maxLength` (adicionada na v1.0.4)

**Prompt (inline, para o cliente):**
```html
<!-- prompt-max-length < -->
O campo #label# deve ter no máximo #max# caracteres.
<!-- prompt-max-length > -->
```

**AJAX message (servidor → cliente):**
```html
<!-- ajax-message-max-length < -->
O campo #fieldLabel# excedeu o limite máximo de #max# caracteres.
<!-- ajax-message-max-length > -->
```

---

## 🔧 Schema JSON do Formulário

Cada formulário é definido por um JSON schema armazenado no campo `fields_schema` da tabela `forms`.

### Estrutura Completa

```json
{
  "form_action": "/api/forms/",
  "force_recaptcha": false,
  "access_max": 10,
  "access_max_simple": 5,
  "field_name": "nome",
  "field_email": "email",
  "fields": [
    {
      "name": "nome",
      "type": "text",
      "label": "Nome",
      "required": true,
      "max_length": 120
    },
    {
      "name": "email",
      "type": "email",
      "label": "E-mail",
      "required": true
    },
    {
      "name": "mensagem",
      "type": "textarea",
      "label": "Mensagem",
      "required": true,
      "max_length": 800
    }
  ],
  "redirects": {
    "success": {
      "path": "/obrigado/"
    }
  },
  "email": {
    "subject": "Nova mensagem: #formName# — #code#",
    "recipients": "admin@meusite.com",
    "reply_to": "",
    "reply_to_name": "",
    "message_component": "forms-prepared-email"
  }
}
```

### Campos do Schema

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `form_action` | string | Endpoint AJAX para submissão |
| `force_recaptcha` | boolean | Força reCAPTCHA v3 mesmo em IPs "livres" |
| `access_max` | int | Máximo de submissões antes de bloquear por IP |
| `access_max_simple` | int | Máximo de submissões simples (sem CAPTCHA) |
| `field_name` | string | Nome do campo usado como identificador da submissão |
| `field_email` | string | Campo de e-mail para `reply-to` automático |
| `fields[].name` | string | Nome do campo POST |
| `fields[].type` | string | `text`, `email`, `textarea` |
| `fields[].label` | string | Label para mensagens de erro e e-mail |
| `fields[].required` | boolean | Se o campo é obrigatório |
| `fields[].max_length` | int | Limite máximo de caracteres (optional — sobrescreve padrão) |
| `redirects.success.path` | string | URL de redirecionamento pós-submissão |
| `email.subject` | string | Assunto do e-mail (suporta `#formName#`, `#code#`) |
| `email.recipients` | string | Destinatários separados por `;` |
| `email.message_component` | string | ID do componente de template do e-mail |

---

## 🔐 Segurança

### Camadas de Proteção

| Nível | Mecanismo | Detalhe |
|-------|-----------|---------|
| **1. Honeypot** | Campo oculto `honeypot` | Bots que preenchem o campo são rejeitados silenciosamente |
| **2. Timestamp** | Campo `timestamp` no submit | Submissões com mais de 2 dias (172.800s) são rejeitadas |
| **3. CAPTCHA v3** | Google reCAPTCHA v3 | Score < 0.5 ativa CAPTCHA v2 como fallback |
| **4. Rate Limiting** | IP + `forms_access` | Bloqueia IPs que excedem `access_max` submissões |
| **5. Validação** | Client + Server | JS valida, PHP **sempre** re-valida autoritativamente |
| **6. Escape SQL** | `banco_escape_field()` | IDs e valores sanitizados antes de queries |
| **7. Escape HTML** | `htmlspecialchars()` | Valores de campo sanitizados antes de e-mail |
| **8. Limite de tamanho** | `mb_strlen()` | Rejeita campos além do limite configurado |

### Mensagens de Erro

Todas as mensagens de erro são lidas do componente `form-ui` (HTML), nunca hardcoded no PHP, garantindo que sejam multilíngues e customizáveis.

---

## 🌐 Integração com a Biblioteca `formulario.php`

O módulo `forms` usa duas funções principais da biblioteca:

### `formulario_controlador($params)`

Prepara o estado do formulário para ser renderizado no front-end. Inclui:
- Carregamento do schema do banco.
- Verificação de bloqueio por IP.
- Preparação de campos e variáveis JS (`formDefinition`, `fields`, `framework`, etc.).

### `formulario_processador($params)`

Processa a submissão recebida via POST/AJAX. Executa:
1. Validação de `formId`, honeypot, timestamp.
2. Verificação de reCAPTCHA v3 e v2.
3. Validação de campos obrigatórios e tipos.
4. **Validação de tamanho máximo** (2 varreduras + `mb_strlen` UTF-8).
5. Inserção em `forms_submissions`.
6. Formatação de e-mail com células processadas.
7. Envio via `comunicacao_email()` com embedding de imagens.

---

## 🧩 Componente `forms-info-definition`

Componente de documentação inline exibido nas páginas de administração do módulo (adicionar, editar, visualizar, clonar). Criado para evitar duplicação de HTML de documentação entre as 4 páginas e os 2 idiomas.

```
gestor/modulos/forms/resources/
├── pt-br/components/forms-info-definition/forms-info-definition.html
└── en/components/forms-info-definition/forms-info-definition.html  ← (adicionado na v1.0.4)
```

Nas páginas, ele é incluído via variável de template:
```html
<!-- Placeholder nas páginas HTML -->
#forms-info-definition#
```

No controller PHP (`forms.php`), o componente é injetado:
```php
$pagina = modelo_var_troca($pagina, '#forms-info-definition#', gestor_componente(['id' => 'forms-info-definition']));
```

---

## 📊 Fluxo Completo de uma Submissão

```
[Usuário preenche o formulário]
         │
         ▼
[JS valida: required, email, maxlength]
    ↓ válido?
         │
         ▼
[AJAX: POST /api/forms/ com fingerprint + token reCAPTCHA]
         │
         ▼
[formulario_processador()]
    ├─ ✅ formId presente?
    ├─ ✅ honeypot vazio?
    ├─ ✅ timestamp < 2 dias?
    ├─ ✅ reCAPTCHA v3 score >= 0.5? → ou v2?
    ├─ ✅ campos obrigatórios preenchidos?
    ├─ ✅ mínimo 3 chars (text/textarea)?
    ├─ ✅ máximo por tipo (mb_strlen UTF-8)?
    └─ ✅ email válido (FILTER_VALIDATE_EMAIL)?
         │
         ▼
[Inserção em forms_submissions (banco)]
         │
         ▼
[Formatação do e-mail]
    ├─ #valor# = preview formatado (textarea → nl2br)
    └─ #valor_full# = conteúdo completo
         │
         ▼
[comunicacao_email() → PHPMailer → SMTP]
         │
         ▼
[AJAX response: { status: success, redirect: /obrigado/ }]
         │
         ▼
[JS redireciona ou exibe mensagem de sucesso]
```

---

## ⚙️ Configuração

### Variáveis de `$_CONFIG` / `.env`

| Chave | Padrão | Descrição |
|-------|--------|-----------|
| `formularios-maximo-cadastros` | — | Limite de submissões por IP antes de bloquear |
| `formularios-maximo-cadastros-simples` | — | Limite de submissões "simples" (sem CAPTCHA) por IP |
| `formularios-email-preview-length` | `800` | Comprimento máximo do preview de campo no e-mail |
| `usuario-recaptcha-active` | — | Habilita reCAPTCHA v3 |
| `usuario-recaptcha-server` | — | Chave secreta reCAPTCHA v3 |
| `usuario-recaptcha-v2-active` | — | Habilita fallback reCAPTCHA v2 |
| `usuario-recaptcha-v2-server` | — | Chave secreta reCAPTCHA v2 |

---

## 📝 Histórico de Versões

| Versão | Data | Mudanças |
|--------|------|----------|
| `1.0.0` | — | Versão inicial do módulo forms |
| `1.0.4` | 2026-03-31 | ➕ Limites de caracteres por tipo (`text`=254, `textarea`=10.000, `email`=254) — server + client. ➕ Contador de caracteres em tempo real. ➕ Preview truncado no e-mail + `#valor_full#`. ➕ Componente `forms-info-definition` EN. 🐛 Fix: contra UTF-8 via `mb_strlen`. 🐛 Fix: quebras de linha do `textarea` preservadas no e-mail. |
