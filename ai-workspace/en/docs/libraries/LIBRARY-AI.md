# Library: ia.php

> 🤖 AI (Artificial Intelligence) Integration

## Overview

The `ia.php` library provides integration with AI services, allowing prompt rendering, request sending, and response processing. Supports multiple AI providers.

**Location**: `gestor/bibliotecas/ia.php`  
**Total Functions**: 9

## Dependencies

- **Libraries**: gestor.php, banco.php
- **Global Variables**: `$_GESTOR`, `$_CONFIG`
- **External API**: OpenAI, Anthropic, or similar

---

## Main Functions

### ia_renderizar_prompt()

Renders prompt template with variables.

**Signature:**
```php
function ia_renderizar_prompt($params = false)
```

**Parameters (Associative Array):**
- `id` (int) - **Optional** - Prompt ID in database
- `template` (string) - **Optional** - Direct template
- `variaveis` (array) - **Optional** - Variables for substitution

**Return:**
- (string) - Rendered prompt

**Usage Example:**
```php
// Render prompt from database
$prompt = ia_renderizar_prompt(Array(
    'id' => 5,
    'variaveis' => Array(
        '[[name]]' => 'John',
        '[[product]]' => 'Notebook'
    )
));

// Direct prompt
$prompt = ia_renderizar_prompt(Array(
    'template' => 'Write a description for [[product]]',
    'variaveis' => Array(
        '[[product]]' => 'Smartphone'
    )
));
```

---

### ia_enviar_prompt()

Sends prompt to AI service and returns response.

**Signature:**
```php
function ia_enviar_prompt($params = false)
```

**Parameters (Associative Array):**
- `prompt` (string) - **Required** - Prompt text
- `modelo` (string) - **Optional** - AI Model (e.g., 'gpt-4')
- `temperatura` (float) - **Optional** - Creativity (0-1)
- `max_tokens` (int) - **Optional** - Maximum response size

**Return:**
- (array) - AI response

**Usage Example:**
```php
// Send to AI
$response = ia_enviar_prompt(Array(
    'prompt' => 'Write a slogan for an electronics store',
    'modelo' => 'gpt-4',
    'temperatura' => 0.7,
    'max_tokens' => 100
));

echo $response['texto'];  // "Technology connecting you to the future"
```

---

### ia_processar_retorno()

Processes and formats AI response.

**Signature:**
```php
function ia_processar_retorno($params = false)
```

**Parameters (Associative Array):**
- `resposta` (array) - **Required** - Raw AI response
- `formato` (string) - **Optional** - Output format ('texto', 'json', 'html')

**Return:**
- (mixed) - Processed response

**Usage Example:**
```php
$raw_response = ia_enviar_prompt(Array(
    'prompt' => 'List 3 benefits in JSON'
));

$processed = ia_processar_retorno(Array(
    'resposta' => $raw_response,
    'formato' => 'json'
));

print_r($processed);  // Structured array
```

---

### ia_ajax_interface()

AJAX interface for AI interaction.

**Signature:**
```php
function ia_ajax_interface($params = false)
```

**Usage Example:**
```php
// AJAX Endpoint
if (isset($_GET['action']) && $_GET['action'] === 'ia') {
    ia_ajax_interface();
    exit;
}
```

---

### ia_ajax_prompts()

Lists available prompts via AJAX.

**Signature:**
```php
function ia_ajax_prompts($params = false)
```

---

### ia_ajax_modos()

Lists AI modes/models via AJAX.

**Signature:**
```php
function ia_ajax_modos($params = false)
```

---

### ia_ajax_prompt_edit()

Edits existing prompt via AJAX.

**Signature:**
```php
function ia_ajax_prompt_edit($params = false)
```

---

### ia_ajax_prompt_novo()

Creates new prompt via AJAX.

**Signature:**
```php
function ia_ajax_prompt_novo($params = false)
```

---

### ia_ajax_prompt_del()

Deletes prompt via AJAX.

**Signature:**
```php
function ia_ajax_prompt_del($params = false)
```

---

## Common Use Cases

### 1. Product Description Generator

```php
function generate_product_description($product) {
    $prompt = ia_renderizar_prompt(Array(
        'template' => 'Write an attractive description for: [[name]]. Category: [[category]]. Price: [[price]]',
        'variaveis' => Array(
            '[[name]]' => $product['name'],
            '[[category]]' => $product['category'],
            '[[price]]' => 'R$ ' . $product['price']
        )
    ));
    
    $response = ia_enviar_prompt(Array(
        'prompt' => $prompt,
        'modelo' => 'gpt-4',
        'temperatura' => 0.7,
        'max_tokens' => 200
    ));
    
    $description = ia_processar_retorno(Array(
        'resposta' => $response,
        'formato' => 'texto'
    ));
    
    // Save to database
    banco_update(
        "description='" . banco_escape_field($description) . "'",
        'products',
        "WHERE id='{$product['id']}'"
    );
    
    return $description;
}
```

### 2. Customer Service Chatbot

```php
function process_chat_message($user_message) {
    // Conversation context
    $history = fetch_chat_history($_SESSION['chat_id']);
    
    $prompt = "You are a customer service assistant.\n\n";
    $prompt .= "History:\n";
    foreach ($history as $msg) {
        $prompt .= "{$msg['role']}: {$msg['text']}\n";
    }
    $prompt .= "User: $user_message\n";
    $prompt .= "Assistant: ";
    
    $response = ia_enviar_prompt(Array(
        'prompt' => $prompt,
        'modelo' => 'gpt-3.5-turbo',
        'temperatura' => 0.5,
        'max_tokens' => 150
    ));
    
    $response_text = ia_processar_retorno(Array(
        'resposta' => $response,
        'formato' => 'texto'
    ));
    
    // Save to history
    save_chat_message($_SESSION['chat_id'], 'user', $user_message);
    save_chat_message($_SESSION['chat_id'], 'assistant', $response_text);
    
    return $response_text;
}
```

### 3. Sentiment Analysis

```php
function analyze_review_sentiment($text) {
    $prompt = ia_renderizar_prompt(Array(
        'template' => 'Analyze the sentiment of this review and return JSON with sentiment (positive/neutral/negative) and score (0-1): "[[text]]"',
        'variaveis' => Array(
            '[[text]]' => $text
        )
    ));
    
    $response = ia_enviar_prompt(Array(
        'prompt' => $prompt,
        'modelo' => 'gpt-4',
        'temperatura' => 0.3
    ));
    
    $analysis = ia_processar_retorno(Array(
        'resposta' => $response,
        'formato' => 'json'
    ));
    
    return $analysis;
    // ['sentiment' => 'positive', 'score' => 0.85]
}
```

### 4. Personalized Recommendations

```php
function generate_recommendations($user) {
    $history = fetch_user_purchases($user['id']);
    
    $prompt = "Based on these previous purchases:\n";
    foreach ($history as $purchase) {
        $prompt .= "- {$purchase['product']}\n";
    }
    $prompt .= "\nRecommend 5 similar products in JSON format.";
    
    $response = ia_enviar_prompt(Array(
        'prompt' => $prompt,
        'modelo' => 'gpt-4'
    ));
    
    $recommendations = ia_processar_retorno(Array(
        'resposta' => $response,
        'formato' => 'json'
    ));
    
    return $recommendations;
}
```

---

## Configuration

### API Keys

```php
// In configuracao.php
$_CONFIG['ia'] = Array(
    'provider' => 'openai',  // or 'anthropic', 'google'
    'api_key' => 'sk-xxxxxxxxxxxxxxxx',
    'modelo_padrao' => 'gpt-4',
    'temperatura_padrao' => 0.7,
    'max_tokens_padrao' => 500
);
```

---

## Patterns and Best Practices

### Response Caching

```php
// ✅ Cache expensive responses
function get_ai_description($product_id) {
    $cache_key = "ai_desc_$product_id";
    
    if ($cached = cache_get($cache_key)) {
        return $cached;
    }
    
    $description = generate_product_description($product_id);
    cache_set($cache_key, $description, 86400);  // 24h
    
    return $description;
}
```

### Cost Control

```php
// ✅ Limit usage per user
function check_ai_limit($user_id) {
    $usage_today = count_ai_requests($user_id, date('Y-m-d'));
    
    if ($usage_today >= 100) {
        throw new Exception('Daily AI limit reached');
    }
}
```

---

## See Also

- [OpenAI API](https://platform.openai.com/docs)
- [LIBRARY-DATABASE.md](./LIBRARY-DATABASE.md)

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
