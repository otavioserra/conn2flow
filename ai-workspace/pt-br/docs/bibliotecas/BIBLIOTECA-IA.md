# Biblioteca: ia.php

> 🤖 Integração com IA (Inteligência Artificial)

## Visão Geral

A biblioteca `ia.php` fornece integração com serviços de IA, permitindo renderização de prompts, envio de requisições e processamento de respostas. Suporta múltiplos provedores de IA.

**Localização**: `gestor/bibliotecas/ia.php`  
**Total de Funções**: 9

## Dependências

- **Bibliotecas**: gestor.php, banco.php
- **Variáveis Globais**: `$_GESTOR`, `$_CONFIG`
- **API Externa**: OpenAI, Anthropic, ou similar

---

## Funções Principais

### ia_renderizar_prompt()

Renderiza template de prompt com variáveis.

**Assinatura:**
```php
function ia_renderizar_prompt($params = false)
```

**Parâmetros (Array Associativo):**
- `id` (int) - **Opcional** - ID do prompt no banco
- `template` (string) - **Opcional** - Template direto
- `variaveis` (array) - **Opcional** - Variáveis para substituição

**Retorno:**
- (string) - Prompt renderizado

**Exemplo de Uso:**
```php
// Renderizar prompt do banco
$prompt = ia_renderizar_prompt(Array(
    'id' => 5,
    'variaveis' => Array(
        '[[nome]]' => 'João',
        '[[produto]]' => 'Notebook'
    )
));

// Prompt direto
$prompt = ia_renderizar_prompt(Array(
    'template' => 'Escreva uma descrição para [[produto]]',
    'variaveis' => Array(
        '[[produto]]' => 'Smartphone'
    )
));
```

---

### ia_enviar_prompt()

Envia prompt para serviço de IA e retorna resposta.

**Assinatura:**
```php
function ia_enviar_prompt($params = false)
```

**Parâmetros (Array Associativo):**
- `prompt` (string) - **Obrigatório** - Texto do prompt
- `modelo` (string) - **Opcional** - Modelo de IA (ex: 'gpt-4')
- `temperatura` (float) - **Opcional** - Criatividade (0-1)
- `max_tokens` (int) - **Opcional** - Tamanho máximo da resposta

**Retorno:**
- (array) - Resposta da IA

**Exemplo de Uso:**
```php
// Enviar para IA
$resposta = ia_enviar_prompt(Array(
    'prompt' => 'Escreva um slogan para uma loja de eletrônicos',
    'modelo' => 'gpt-4',
    'temperatura' => 0.7,
    'max_tokens' => 100
));

echo $resposta['texto'];  // "Tecnologia que conecta você ao futuro"
```

---

### ia_processar_retorno()

Processa e formata resposta da IA.

**Assinatura:**
```php
function ia_processar_retorno($params = false)
```

**Parâmetros (Array Associativo):**
- `resposta` (array) - **Obrigatório** - Resposta bruta da IA
- `formato` (string) - **Opcional** - Formato de saída ('texto', 'json', 'html')

**Retorno:**
- (mixed) - Resposta processada

**Exemplo de Uso:**
```php
$resposta_bruta = ia_enviar_prompt(Array(
    'prompt' => 'Liste 3 benefícios em JSON'
));

$processado = ia_processar_retorno(Array(
    'resposta' => $resposta_bruta,
    'formato' => 'json'
));

print_r($processado);  // Array estruturado
```

---

### ia_ajax_interface()

Interface AJAX para interação com IA.

**Assinatura:**
```php
function ia_ajax_interface($params = false)
```

**Exemplo de Uso:**
```php
// Endpoint AJAX
if (isset($_GET['action']) && $_GET['action'] === 'ia') {
    ia_ajax_interface();
    exit;
}
```

---

### ia_ajax_prompts()

Lista prompts disponíveis via AJAX.

**Assinatura:**
```php
function ia_ajax_prompts($params = false)
```

---

### ia_ajax_modos()

Lista modos/modelos de IA via AJAX.

**Assinatura:**
```php
function ia_ajax_modos($params = false)
```

---

### ia_ajax_prompt_edit()

Edita prompt existente via AJAX.

**Assinatura:**
```php
function ia_ajax_prompt_edit($params = false)
```

---

### ia_ajax_prompt_novo()

Cria novo prompt via AJAX.

**Assinatura:**
```php
function ia_ajax_prompt_novo($params = false)
```

---

### ia_ajax_prompt_del()

Deleta prompt via AJAX.

**Assinatura:**
```php
function ia_ajax_prompt_del($params = false)
```

---

## Casos de Uso Comuns

### 1. Gerador de Descrições de Produtos

```php
function gerar_descricao_produto($produto) {
    $prompt = ia_renderizar_prompt(Array(
        'template' => 'Escreva uma descrição atraente para: [[nome]]. Categoria: [[categoria]]. Preço: [[preco]]',
        'variaveis' => Array(
            '[[nome]]' => $produto['nome'],
            '[[categoria]]' => $produto['categoria'],
            '[[preco]]' => 'R$ ' . $produto['preco']
        )
    ));
    
    $resposta = ia_enviar_prompt(Array(
        'prompt' => $prompt,
        'modelo' => 'gpt-4',
        'temperatura' => 0.7,
        'max_tokens' => 200
    ));
    
    $descricao = ia_processar_retorno(Array(
        'resposta' => $resposta,
        'formato' => 'texto'
    ));
    
    // Salvar no banco
    banco_update(
        "descricao='" . banco_escape_field($descricao) . "'",
        'produtos',
        "WHERE id='{$produto['id']}'"
    );
    
    return $descricao;
}
```

### 2. Chatbot de Atendimento

```php
function processar_mensagem_chat($mensagem_usuario) {
    // Contexto da conversa
    $historico = buscar_historico_chat($_SESSION['chat_id']);
    
    $prompt = "Você é um assistente de atendimento.\n\n";
    $prompt .= "Histórico:\n";
    foreach ($historico as $msg) {
        $prompt .= "{$msg['role']}: {$msg['text']}\n";
    }
    $prompt .= "Usuário: $mensagem_usuario\n";
    $prompt .= "Assistente: ";
    
    $resposta = ia_enviar_prompt(Array(
        'prompt' => $prompt,
        'modelo' => 'gpt-3.5-turbo',
        'temperatura' => 0.5,
        'max_tokens' => 150
    ));
    
    $resposta_texto = ia_processar_retorno(Array(
        'resposta' => $resposta,
        'formato' => 'texto'
    ));
    
    // Salvar no histórico
    salvar_mensagem_chat($_SESSION['chat_id'], 'usuario', $mensagem_usuario);
    salvar_mensagem_chat($_SESSION['chat_id'], 'assistente', $resposta_texto);
    
    return $resposta_texto;
}
```

### 3. Análise de Sentimento

```php
function analisar_sentimento_avaliacao($texto) {
    $prompt = ia_renderizar_prompt(Array(
        'template' => 'Analise o sentimento desta avaliação e retorne JSON com sentiment (positivo/neutro/negativo) e score (0-1): "[[texto]]"',
        'variaveis' => Array(
            '[[texto]]' => $texto
        )
    ));
    
    $resposta = ia_enviar_prompt(Array(
        'prompt' => $prompt,
        'modelo' => 'gpt-4',
        'temperatura' => 0.3
    ));
    
    $analise = ia_processar_retorno(Array(
        'resposta' => $resposta,
        'formato' => 'json'
    ));
    
    return $analise;
    // ['sentiment' => 'positivo', 'score' => 0.85]
}
```

### 4. Recomendações Personalizadas

```php
function gerar_recomendacoes($usuario) {
    $historico = buscar_compras_usuario($usuario['id']);
    
    $prompt = "Com base nestas compras anteriores:\n";
    foreach ($historico as $compra) {
        $prompt .= "- {$compra['produto']}\n";
    }
    $prompt .= "\nRecomende 5 produtos similares em formato JSON.";
    
    $resposta = ia_enviar_prompt(Array(
        'prompt' => $prompt,
        'modelo' => 'gpt-4'
    ));
    
    $recomendacoes = ia_processar_retorno(Array(
        'resposta' => $resposta,
        'formato' => 'json'
    ));
    
    return $recomendacoes;
}
```

---

## Configuração

### API Keys

```php
// Em configuracao.php
$_CONFIG['ia'] = Array(
    'provider' => 'openai',  // ou 'anthropic', 'google'
    'api_key' => 'sk-xxxxxxxxxxxxxxxx',
    'modelo_padrao' => 'gpt-4',
    'temperatura_padrao' => 0.7,
    'max_tokens_padrao' => 500
);
```

---

## Padrões e Melhores Práticas

### Cache de Respostas

```php
// ✅ Cachear respostas caras
function obter_descricao_ia($produto_id) {
    $cache_key = "ia_desc_$produto_id";
    
    if ($cached = cache_get($cache_key)) {
        return $cached;
    }
    
    $descricao = gerar_descricao_produto($produto_id);
    cache_set($cache_key, $descricao, 86400);  // 24h
    
    return $descricao;
}
```

### Controle de Custos

```php
// ✅ Limitar uso por usuário
function verificar_limite_ia($usuario_id) {
    $uso_hoje = contar_requisicoes_ia($usuario_id, date('Y-m-d'));
    
    if ($uso_hoje >= 100) {
        throw new Exception('Limite diário de IA atingido');
    }
}
```

---

## Veja Também

- [OpenAI API](https://platform.openai.com/docs)
- [BIBLIOTECA-BANCO.md](./BIBLIOTECA-BANCO.md)

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
