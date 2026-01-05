# Estratégias de Segurança para IA - Casos Públicos

## 🎯 Cenário Atual

Quando um usuário **não está logado**, ainda pode querer usar funcionalidades de IA (ex: gerar layouts, componentes, etc.). Precisamos balancear:

- **Acessibilidade**: Permitir uso básico sem login obrigatório
- **Segurança**: Prevenir abuso e ataques automatizados
- **Custos**: Controlar uso de recursos de IA

## 🛡️ Estratégias Implementadas

### **1. Rate Limiting por IP + Fingerprinting**

```php
function ia_rate_limit_publico($ip, $fingerprint = null) {
    // Limites por IP
    $limites = [
        'requests_por_hora' => 10,
        'requests_por_dia' => 50,
        'tokens_max_por_request' => 1000
    ];

    // Verificar histórico
    // Implementar lógica de bloqueio progressivo
}
```

### **2. CAPTCHA Adaptativo**

```php
function ia_captcha_publico($dificuldade = 'baixo') {
    // CAPTCHA simples para poucos usos
    // CAPTCHA complexo após vários usos
    // CAPTCHA invisível para usuários "confiáveis"
}
```

### **3. Tokens Temporários Limitados**

```php
function ia_token_publico_limitado() {
    // Token válido por 5 minutos
    // Máximo 3 usos
    // Rate limit de 1 request por minuto
    // Sem renovação automática
}
```

### **4. Autenticação Opcional Progressiva**

```php
function ia_autenticacao_progressiva($nivel_uso) {
    if ($nivel_uso <= 3) {
        // Uso livre
        return 'livre';
    } elseif ($nivel_uso <= 10) {
        // Pedir CAPTCHA
        return 'captcha';
    } else {
        // Exigir login
        return 'login_obrigatorio';
    }
}
```

## 🔄 Fluxo para Usuários Públicos

### **Primeiro Uso:**
1. ✅ Gerar token público limitado
2. ✅ Rate limit básico
3. ✅ Sem CAPTCHA

### **Uso Moderado (4-10 requests):**
1. ⚠️ Apresentar CAPTCHA simples
2. ✅ Token com limite reduzido
3. ✅ Rate limit mais restritivo

### **Uso Elevado (10+ requests):**
1. 🚫 Exigir autenticação completa
2. ✅ Ou bloquear temporariamente
3. ✅ Sugerir criação de conta

## 💡 Implementação Técnica

### **Frontend (JavaScript):**

```javascript
async function enviarPromptPublico() {
    // Verificar nível de uso
    const nivelUso = getNivelUso();

    if (nivelUso >= 10) {
        // Exigir login
        mostrarModalLogin();
        return;
    }

    if (nivelUso >= 4) {
        // Mostrar CAPTCHA
        const captchaValido = await verificarCaptcha();
        if (!captchaValido) return;
    }

    // Prosseguir com requisição
    await fazerRequisicaoIA();
}
```

### **Backend (PHP):**

```php
function api_ia_publico_generate() {
    // Verificar autenticação (opcional)
    $usuario_logado = isset($_GESTOR['usuario-id']);

    if (!$usuario_logado) {
        // Aplicar regras para usuários públicos
        $pode_usar = ia_verificar_acesso_publico($_SERVER['REMOTE_ADDR']);

        if (!$pode_usar['permitido']) {
            api_response_error($pode_usar['mensagem'], 429);
        }

        // Aplicar limites mais restritivos
        $limites = [
            'max_tokens' => 500,  // Metade do limite normal
            'timeout' => 30       // Timeout maior
        ];
    }

    // Processar geração...
}
```

## 📊 Métricas de Monitoramento

### **Indicadores a Monitorar:**
- Taxa de uso por IP não autenticado
- Percentual de CAPTCHAs resolvidos
- Conversão para contas criadas
- Custos por usuário público vs logado

### **Alertas:**
- Spike súbito no uso público
- Altas taxas de falha em CAPTCHA
- IPs suspeitos com alto volume

## 🎯 Benefícios da Estratégia

### **Para Usuários:**
- ✅ Acesso fácil sem burocracia
- ✅ Progressão natural para criação de conta
- ✅ Experiência fluida

### **Para o Sistema:**
- ✅ Controle de custos
- ✅ Prevenção de abuso
- ✅ Dados para análise de conversão

### **Para Segurança:**
- ✅ Proteção contra bots
- ✅ Rate limiting inteligente
- ✅ Autenticação progressiva

## 🚀 Conclusão

A estratégia híbrida permite **acessibilidade máxima** com **segurança robusta**, incentivando naturalmente a criação de contas através da progressão de limites.