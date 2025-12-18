# AI Security Strategies - Public Cases

## 🎯 Current Scenario

When a user **is not logged in**, they may still want to use AI features (e.g., generate layouts, components, etc.). We need to balance:

- **Accessibility**: Allow basic usage without mandatory login
- **Security**: Prevent abuse and automated attacks
- **Costs**: Control AI resource usage

## 🛡️ Implemented Strategies

### **1. Rate Limiting by IP + Fingerprinting**

```php
function ia_rate_limit_publico($ip, $fingerprint = null) {
    // Limits per IP
    $limites = [
        'requests_por_hora' => 10,
        'requests_por_dia' => 50,
        'tokens_max_por_request' => 1000
    ];

    // Check history
    // Implement progressive blocking logic
}
```

### **2. Adaptive CAPTCHA**

```php
function ia_captcha_publico($dificuldade = 'baixo') {
    // Simple CAPTCHA for few uses
    // Complex CAPTCHA after several uses
    // Invisible CAPTCHA for "trusted" users
}
```

### **3. Limited Temporary Tokens**

```php
function ia_token_publico_limitado() {
    // Token valid for 5 minutes
    // Max 3 uses
    // Rate limit of 1 request per minute
    // No automatic renewal
}
```

### **4. Progressive Optional Authentication**

```php
function ia_autenticacao_progressiva($nivel_uso) {
    if ($nivel_uso <= 3) {
        // Free usage
        return 'livre';
    } elseif ($nivel_uso <= 10) {
        // Ask for CAPTCHA
        return 'captcha';
    } else {
        // Require login
        return 'login_obrigatorio';
    }
}
```

## 🔄 Flow for Public Users

### **First Use:**
1. ✅ Generate limited public token
2. ✅ Basic rate limit
3. ✅ No CAPTCHA

### **Moderate Use (4-10 requests):**
1. ⚠️ Present simple CAPTCHA
2. ✅ Token with reduced limit
3. ✅ More restrictive rate limit

### **High Use (10+ requests):**
1. 🚫 Require complete authentication
2. ✅ Or temporarily block
3. ✅ Suggest account creation

## 💡 Technical Implementation

### **Frontend (JavaScript):**

```javascript
async function enviarPromptPublico() {
    // Check usage level
    const nivelUso = getNivelUso();

    if (nivelUso >= 10) {
        // Require login
        mostrarModalLogin();
        return;
    }

    if (nivelUso >= 4) {
        // Show CAPTCHA
        const captchaValido = await verificarCaptcha();
        if (!captchaValido) return;
    }

    // Proceed with request
    await fazerRequisicaoIA();
}
```

### **Backend (PHP):**

```php
function api_ia_publico_generate() {
    // Check authentication (optional)
    $usuario_logado = isset($_GESTOR['usuario-id']);

    if (!$usuario_logado) {
        // Apply rules for public users
        $pode_usar = ia_verificar_acesso_publico($_SERVER['REMOTE_ADDR']);

        if (!$pode_usar['permitido']) {
            api_response_error($pode_usar['mensagem'], 429);
        }

        // Apply more restrictive limits
        $limites = [
            'max_tokens' => 500,  // Half of normal limit
            'timeout' => 30       // Higher timeout
        ];
    }

    // Process generation...
}
```

## 📊 Monitoring Metrics

### **Indicators to Monitor:**
- Usage rate by unauthenticated IP
- Percentage of solved CAPTCHAs
- Conversion to created accounts
- Costs per public user vs logged user

### **Alerts:**
- Sudden spike in public usage
- High CAPTCHA failure rates
- Suspicious IPs with high volume

## 🎯 Strategy Benefits

### **For Users:**
- ✅ Easy access without bureaucracy
- ✅ Natural progression to account creation
- ✅ Fluid experience

### **For the System:**
- ✅ Cost control
- ✅ Abuse prevention
- ✅ Conversion analysis data

### **For Security:**
- ✅ Bot protection
- ✅ Intelligent rate limiting
- ✅ Progressive authentication

## 🚀 Conclusion

The hybrid strategy allows **maximum accessibility** with **robust security**, naturally encouraging account creation through limit progression.
