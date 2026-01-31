# Biblioteca PayPal REST API para Conn2Flow

Este diretório contém toda a documentação e exemplos para a biblioteca de integração com PayPal REST API desenvolvida para o Conn2Flow CMS.

## 📁 Arquivos

### 1. `biblioteca-paypal.md`
Documentação completa da biblioteca incluindo:
- Arquitetura e estrutura
- Funcionalidades implementadas
- Configuração e setup
- Exemplos de uso
- Notas de desenvolvimento

### 2. `paypal.env.example`
Arquivo exemplo de configuração de variáveis de ambiente para integração com PayPal:
- Credenciais Sandbox (testes)
- Credenciais Live (produção)
- Configurações de webhook

### 3. `exemplos-uso.php`
Arquivo com 13 exemplos práticos de uso da biblioteca:
- Autenticação
- Criação de pedidos
- Captura de pagamentos
- Reembolsos
- Webhooks
- Integração com módulos

## 🚀 Início Rápido

### 1. Configurar Variáveis de Ambiente

Copie o conteúdo de `paypal.env.example` para seu arquivo `.env` em `autenticacoes/[seu-dominio]/`:

```bash
cp ai-workspace/prompts/paypal/paypal.env.example autenticacoes/seudominio.com/.env
```

### 2. Obter Credenciais do PayPal

1. Acesse: https://developer.paypal.com/developer/applications
2. Crie uma nova aplicação
3. Copie o Client ID e Secret
4. Configure no arquivo .env

### 3. Usar a Biblioteca

```php
// Incluir biblioteca
require_once $_GESTOR['bibliotecas-path'] . 'paypal.php';

// Criar pedido
$pedido = paypal_criar_pedido(Array(
    'valor' => 100.00,
    'moeda' => 'BRL',
    'descricao' => 'Meu Produto'
));

// Redirecionar para aprovação
header('Location: ' . $pedido['approve_url']);
```

## 📚 Documentação Completa

Consulte `biblioteca-paypal.md` para documentação detalhada de todas as funcionalidades.

## 🧪 Exemplos

Consulte `exemplos-uso.php` para ver 13 exemplos completos de uso.

## 🔧 Biblioteca Principal

A biblioteca principal está localizada em:
```
gestor/bibliotecas/paypal.php
```

### Funções Disponíveis

#### Auxiliares
- `paypal_obter_url_api()` - Obtém URL da API
- `paypal_obter_credenciais()` - Obtém credenciais
- `paypal_requisicao()` - Executa requisições HTTP

#### Principais
- `paypal_autenticar()` - Autenticação OAuth 2.0
- `paypal_criar_pedido()` - Cria pedidos
- `paypal_capturar_pedido()` - Captura pagamentos
- `paypal_consultar_pedido()` - Consulta pedidos
- `paypal_reembolsar()` - Processa reembolsos
- `paypal_consultar_reembolso()` - Consulta reembolsos
- `paypal_validar_webhook()` - Valida webhooks
- `paypal_processar_webhook()` - Processa webhooks

## 💡 Suporte

Para dúvidas ou problemas:
1. Consulte a documentação em `biblioteca-paypal.md`
2. Veja os exemplos em `exemplos-uso.php`
3. Consulte a documentação oficial do PayPal: https://developer.paypal.com/docs/api/overview/

## 📝 Versão

**Versão da Biblioteca**: 1.0.0  
**Data de Criação**: 07 de Novembro de 2025  
**Status**: Produção

## 🔗 Links Úteis

- [PayPal Developer Portal](https://developer.paypal.com/)
- [PayPal REST API Docs](https://developer.paypal.com/docs/api/overview/)
- [PayPal Sandbox](https://developer.paypal.com/developer/accounts/)
- [PayPal Webhooks](https://developer.paypal.com/docs/api-basics/notifications/webhooks/)

---

**Desenvolvido para Conn2Flow CMS v2.4+**
