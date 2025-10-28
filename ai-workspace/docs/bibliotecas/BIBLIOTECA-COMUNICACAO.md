# Biblioteca: comunicacao.php

> 📧 Envio de emails e gestão de impressão

## Visão Geral

A biblioteca `comunicacao.php` fornece funções para envio de emails via SMTP usando PHPMailer e gerenciamento de impressão de páginas. Suporta HTML rico, anexos, imagens embutidas e configuração multi-tenant.

**Localização**: `gestor/bibliotecas/comunicacao.php`  
**Versão**: 1.1.0  
**Total de Funções**: 2  
**Biblioteca Base**: PHPMailer

## Dependências

- **Biblioteca Externa**: PHPMailer (incluída)
  - Localização: `gestor/bibliotecas/PHPMailer/`
- **Bibliotecas**: gestor.php, modelo.php, configuracao.php (opcional)
- **Variáveis Globais**: `$_GESTOR`, `$_CONFIG`, `$_CRON`

## Variáveis Globais

```php
$_GESTOR['biblioteca-comunicacao'] = Array(
    'versao' => '1.1.0',
);

// Configuração de email
$_CONFIG['email'] = Array(
    'ativo' => true,
    'server' => Array(
        'host' => 'smtp.example.com',
        'user' => 'user@example.com',
        'pass' => 'senha',
        'port' => 587,
        'secure' => true  // SSL
    ),
    'sender' => Array(
        'from' => 'noreply@example.com',
        'fromName' => 'Sistema',
        'replyTo' => 'contato@example.com',
        'replyToName' => 'Suporte'
    )
);
```

---

## Funções Principais

### comunicacao_email()

Envia email HTML via SMTP com suporte a anexos e imagens embutidas.

**Assinatura:**
```php
function comunicacao_email($params = false)
```

**Parâmetros (Array Associativo):**

**Configuração de Servidor:**
- `servidor` (array) - **Opcional** - Configurações SMTP personalizadas
  - `debug` (bool) - Ativar debug
  - `hospedeiro` (string) - Host SMTP
  - `usuario` (string) - Usuário SMTP
  - `senha` (string) - Senha SMTP
  - `porta` (int) - Porta SMTP (587 ou 465)
  - `seguro` (bool) - Usar SSL/TLS

**Configuração de Remetente:**
- `remetente` (array) - **Opcional** - Dados do remetente
  - `de` (string) - Email de origem
  - `deNome` (string) - Nome do remetente
  - `responderPara` (string) - Email para resposta
  - `responderParaNome` (string) - Nome para resposta

**Destinatários:**
- `destinatarios` (array) - **Opcional** - Lista de destinatários
  - `email` (string) - Email do destinatário
  - `nome` (string) - Nome do destinatário
  - `tipo` (string) - 'normal', 'cc' ou 'bcc'

**Mensagem:**
- `mensagem` (array) - **Opcional** - Conteúdo do email
  - `assunto` (string) - Assunto do email
  - `html` (string) - Corpo HTML
  - `htmlLayoutID` (string) - ID do componente de layout
  - `htmlTitulo` (string) - Título da página HTML
  - `htmlVariaveis` (array) - Variáveis para substituição
  - `htmlAssinaturaAutomatica` (bool) - Incluir assinatura
  - `imagens` (array) - Imagens embutidas (embedded)
  - `anexos` (array) - Anexos do email

**Multi-tenancy:**
- `id_hosts` (int) - **Opcional** - ID do host específico
- `hostPersonalizacao` (bool) - **Opcional** - Usar config do host

**Teste:**
- `EMAIL_TESTS` (bool) - **Opcional** - Usar configurações de teste
- `EMAIL_DEBUG`, `EMAIL_HOST`, `EMAIL_USER`, etc. - Configs de teste

**Retorno:**
- (bool) - true se enviado com sucesso, false caso contrário

**Exemplo de Uso Básico:**
```php
// Email simples
$enviado = comunicacao_email(Array(
    'destinatarios' => Array(
        Array(
            'email' => 'cliente@example.com',
            'nome' => 'João Silva'
        )
    ),
    'mensagem' => Array(
        'assunto' => 'Bem-vindo ao Sistema',
        'html' => '<h1>Olá João!</h1><p>Bem-vindo ao nosso sistema.</p>'
    )
));

if ($enviado) {
    echo "Email enviado com sucesso!";
}
```

**Exemplo com Layout:**
```php
// Email com layout personalizado
comunicacao_email(Array(
    'destinatarios' => Array(
        Array('email' => 'cliente@example.com', 'nome' => 'Cliente')
    ),
    'mensagem' => Array(
        'assunto' => 'Confirmação de Pedido #123',
        'htmlLayoutID' => 'email-pedido-confirmacao',
        'htmlVariaveis' => Array(
            Array('variavel' => '[[numero-pedido]]', 'valor' => '123'),
            Array('variavel' => '[[total]]', 'valor' => 'R$ 150,00'),
            Array('variavel' => '[[data]]', 'valor' => date('d/m/Y'))
        ),
        'htmlAssinaturaAutomatica' => true
    )
));
```

**Exemplo com Anexos:**
```php
// Email com anexo PDF
comunicacao_email(Array(
    'destinatarios' => Array(
        Array('email' => 'cliente@example.com')
    ),
    'mensagem' => Array(
        'assunto' => 'Seu Relatório Mensal',
        'html' => '<p>Segue em anexo o relatório.</p>',
        'anexos' => Array(
            Array(
                'caminho' => '/tmp/relatorio.pdf',
                'nome' => 'Relatorio-Janeiro-2025.pdf',
                'tmpCaminho' => '/tmp/relatorio.pdf'  // Será deletado após envio
            )
        )
    )
));
```

**Exemplo com Imagens Embutidas:**
```php
// Email com logo embutido
comunicacao_email(Array(
    'destinatarios' => Array(
        Array('email' => 'cliente@example.com')
    ),
    'mensagem' => Array(
        'assunto' => 'Newsletter',
        'html' => '<img src="cid:logo"><p>Conteúdo da newsletter</p>',
        'imagens' => Array(
            Array(
                'caminho' => '/var/www/images/logo.png',
                'cid' => 'logo',
                'nome' => 'logo.png'
            )
        )
    )
));
```

**Exemplo com Múltiplos Destinatários:**
```php
// Email para múltiplos destinatários com CC e BCC
comunicacao_email(Array(
    'destinatarios' => Array(
        Array(
            'email' => 'principal@example.com',
            'nome' => 'Destinatário Principal',
            'tipo' => 'normal'
        ),
        Array(
            'email' => 'copia@example.com',
            'nome' => 'Cópia',
            'tipo' => 'cc'
        ),
        Array(
            'email' => 'admin@example.com',
            'tipo' => 'bcc'  // Cópia oculta
        )
    ),
    'mensagem' => Array(
        'assunto' => 'Notificação Importante',
        'html' => '<p>Mensagem para todos.</p>'
    )
));
```

**Exemplo com Configuração Personalizada:**
```php
// Usar servidor SMTP diferente
comunicacao_email(Array(
    'servidor' => Array(
        'hospedeiro' => 'smtp.gmail.com',
        'usuario' => 'meuapp@gmail.com',
        'senha' => 'senha_app',
        'porta' => 587,
        'seguro' => true
    ),
    'remetente' => Array(
        'de' => 'noreply@meuapp.com',
        'deNome' => 'Meu Aplicativo',
        'responderPara' => 'suporte@meuapp.com',
        'responderParaNome' => 'Equipe de Suporte'
    ),
    'destinatarios' => Array(
        Array('email' => 'cliente@example.com')
    ),
    'mensagem' => Array(
        'assunto' => 'Teste',
        'html' => '<p>Email de teste</p>'
    )
));
```

**Comportamento:**
- Usa layout 'layout-emails' automaticamente se existir
- Suporta variáveis globais do sistema ([[variavel]])
- Aplica CSS inline para compatibilidade
- Deleta arquivos temporários após envio
- Registra erros em log se debug ativo

**Notas:**
- Requer `$_CONFIG['email']['ativo'] = true`
- Usa PHPMailer para envio SMTP
- Suporta UTF-8 por padrão
- Layout de email é opcional mas recomendado

---

### comunicacao_impressao()

Prepara dados para impressão de página.

**Assinatura:**
```php
function comunicacao_impressao($params = false)
```

**Parâmetros (Array Associativo):**
- `pagina` (string) - **Obrigatório** - Página a ser impressa
- `titulo` (string) - **Opcional** - Título da impressão

**Retorno:**
- (void) - Armazena dados na sessão

**Exemplo de Uso:**
```php
// Preparar impressão de pedido
comunicacao_impressao(Array(
    'pagina' => '/pedidos/imprimir/123',
    'titulo' => 'Pedido #123'
));

// Redirecionar para página de impressão
gestor_redirecionar('/imprimir');

// Na página de impressão, recuperar:
$impressao = gestor_sessao_variavel('impressao');
// Array(
//     'pagina' => '/pedidos/imprimir/123',
//     'titulo' => 'Pedido #123'
// )
```

**Comportamento:**
- Armazena dados em `$_SESSION` via `gestor_sessao_variavel()`
- Usado em conjunto com página de impressão dedicada
- Permite passar contexto para janela de impressão

---

## Casos de Uso Comuns

### 1. Confirmação de Cadastro

```php
function enviar_email_confirmacao($usuario) {
    $token = gerar_token_confirmacao($usuario['id']);
    $link = host_url(Array('opcao' => 'full')) . "confirmar/$token";
    
    return comunicacao_email(Array(
        'destinatarios' => Array(
            Array(
                'email' => $usuario['email'],
                'nome' => $usuario['nome']
            )
        ),
        'mensagem' => Array(
            'assunto' => 'Confirme seu cadastro',
            'htmlLayoutID' => 'email-confirmacao',
            'htmlVariaveis' => Array(
                Array('variavel' => '[[nome]]', 'valor' => $usuario['nome']),
                Array('variavel' => '[[link]]', 'valor' => $link)
            ),
            'htmlAssinaturaAutomatica' => true
        )
    ));
}
```

### 2. Recuperação de Senha

```php
function enviar_recuperacao_senha($email) {
    $usuario = buscar_usuario_por_email($email);
    
    if (!$usuario) {
        return false;
    }
    
    $token = gerar_token_recuperacao($usuario['id']);
    $link = host_url(Array('opcao' => 'full')) . "redefinir-senha/$token";
    
    return comunicacao_email(Array(
        'destinatarios' => Array(
            Array('email' => $email, 'nome' => $usuario['nome'])
        ),
        'mensagem' => Array(
            'assunto' => 'Recuperação de Senha',
            'html' => "
                <h2>Olá {$usuario['nome']},</h2>
                <p>Clique no link abaixo para redefinir sua senha:</p>
                <p><a href='$link'>Redefinir Senha</a></p>
                <p>O link expira em 1 hora.</p>
            "
        )
    ));
}
```

### 3. Notificação de Pedido

```php
function notificar_pedido_confirmado($pedido) {
    // Gerar PDF do pedido
    $pdf_path = gerar_pdf_pedido($pedido['id']);
    
    // Enviar para cliente
    $enviado_cliente = comunicacao_email(Array(
        'destinatarios' => Array(
            Array(
                'email' => $pedido['cliente_email'],
                'nome' => $pedido['cliente_nome']
            )
        ),
        'mensagem' => Array(
            'assunto' => "Pedido #{$pedido['numero']} Confirmado",
            'htmlLayoutID' => 'email-pedido-confirmado',
            'htmlVariaveis' => Array(
                Array('variavel' => '[[numero]]', 'valor' => $pedido['numero']),
                Array('variavel' => '[[total]]', 'valor' => $pedido['total']),
                Array('variavel' => '[[itens]]', 'valor' => gerar_html_itens($pedido))
            ),
            'anexos' => Array(
                Array(
                    'caminho' => $pdf_path,
                    'nome' => "Pedido-{$pedido['numero']}.pdf",
                    'tmpCaminho' => $pdf_path
                )
            ),
            'htmlAssinaturaAutomatica' => true
        )
    ));
    
    // Notificar admin com BCC
    comunicacao_email(Array(
        'destinatarios' => Array(
            Array('email' => $pedido['cliente_email'], 'tipo' => 'normal'),
            Array('email' => 'admin@loja.com', 'tipo' => 'bcc')
        ),
        'mensagem' => Array(
            'assunto' => "Novo Pedido #{$pedido['numero']}",
            'html' => "<p>Novo pedido recebido!</p>"
        )
    ));
    
    return $enviado_cliente;
}
```

### 4. Newsletter em Massa

```php
function enviar_newsletter($template_id, $destinatarios) {
    $sucesso = 0;
    $falhas = 0;
    
    foreach ($destinatarios as $destinatario) {
        $enviado = comunicacao_email(Array(
            'destinatarios' => Array(
                Array(
                    'email' => $destinatario['email'],
                    'nome' => $destinatario['nome']
                )
            ),
            'mensagem' => Array(
                'assunto' => 'Newsletter - ' . date('F Y'),
                'htmlLayoutID' => $template_id,
                'htmlVariaveis' => Array(
                    Array('variavel' => '[[nome]]', 'valor' => $destinatario['nome']),
                    Array('variavel' => '[[link-descadastro]]', 
                          'valor' => gerar_link_descadastro($destinatario['id']))
                ),
                'htmlAssinaturaAutomatica' => true
            )
        ));
        
        if ($enviado) {
            $sucesso++;
            
            // Registrar envio
            banco_insert_name(Array(
                Array('destinatario_id', $destinatario['id']),
                Array('template_id', $template_id),
                Array('enviado_em', 'NOW()', true, false)
            ), 'newsletter_envios');
        } else {
            $falhas++;
        }
        
        // Delay para evitar sobrecarga
        usleep(100000); // 0.1 segundo
    }
    
    return Array('sucesso' => $sucesso, 'falhas' => $falhas);
}
```

### 5. Email com QR Code

```php
function enviar_ingresso($venda) {
    // Gerar QR Code
    require_once 'phpqrcode/qrlib.php';
    
    $qr_file = sys_get_temp_dir() . '/qr-' . $venda['id'] . '.png';
    QRcode::png($venda['codigo_validacao'], $qr_file, QR_ECLEVEL_L, 10);
    
    return comunicacao_email(Array(
        'destinatarios' => Array(
            Array('email' => $venda['cliente_email'])
        ),
        'mensagem' => Array(
            'assunto' => 'Seu Ingresso',
            'html' => "
                <h2>Ingresso - {$venda['evento_nome']}</h2>
                <p>Apresente este QR Code na entrada:</p>
                <p><img src='cid:qrcode' width='300'></p>
                <p>Código: {$venda['codigo_validacao']}</p>
            ",
            'imagens' => Array(
                Array(
                    'caminho' => $qr_file,
                    'cid' => 'qrcode',
                    'nome' => 'qrcode.png',
                    'imagemTmpCaminho' => $qr_file  // Deletar após envio
                )
            )
        )
    ));
}
```

### 6. Impressão de Nota Fiscal

```php
function preparar_impressao_nf($pedido_id) {
    comunicacao_impressao(Array(
        'pagina' => "/admin/pedidos/nf/$pedido_id",
        'titulo' => "Nota Fiscal - Pedido #$pedido_id"
    ));
    
    // Redirecionar para página de impressão
    gestor_redirecionar('/admin/impressao');
}

// Na página /admin/impressao
function exibir_impressao() {
    $dados = gestor_sessao_variavel('impressao');
    
    if ($dados) {
        // Incluir página a ser impressa
        include($dados['pagina']);
        
        // JavaScript para imprimir automaticamente
        echo "<script>window.print();</script>";
        
        // Limpar sessão
        gestor_sessao_variavel_del('impressao');
    }
}
```

---

## Configuração Multi-Tenant

### Por Host

```php
// Configurar email específico para cada host
function configurar_email_host($id_hosts) {
    gestor_incluir_biblioteca('configuracao');
    
    // Salvar configurações do host
    configuracao_hosts_variaveis_salvar(Array(
        'id_hosts' => $id_hosts,
        'modulo' => 'comunicacao-configuracoes',
        'variaveis' => Array(
            'email-personalizado-ativo' => '1',
            'servidor-host' => 'smtp.hostpersonalizado.com',
            'servidor-usuario' => 'noreply@host.com',
            'servidor-senha' => 'senha',
            'servidor-porta' => '587',
            'remetente-de' => 'contato@host.com',
            'remetente-de-nome' => 'Loja Personalizada'
        )
    ));
}

// Enviar email usando configuração do host
comunicacao_email(Array(
    'id_hosts' => 5,
    'hostPersonalizacao' => true,
    'destinatarios' => Array(
        Array('email' => 'cliente@example.com')
    ),
    'mensagem' => Array(
        'assunto' => 'Email Personalizado',
        'html' => '<p>Email com config do host</p>'
    )
));
```

---

## Debug e Testes

### Modo Debug

```php
// Ativar debug para ver detalhes do envio
$enviado = comunicacao_email(Array(
    'servidor' => Array(
        'debug' => true  // Ativa SMTP::DEBUG_SERVER
    ),
    'destinatarios' => Array(
        Array('email' => 'teste@example.com')
    ),
    'mensagem' => Array(
        'assunto' => 'Teste Debug',
        'html' => '<p>Teste</p>'
    )
));

// Erros serão registrados no histórico via log_debugar()
```

### Testes em Desenvolvimento

```php
// Usar configurações de teste sem alterar $_CONFIG
$EMAIL_TESTS = true;
$EMAIL_DEBUG = true;
$EMAIL_HOST = 'smtp.mailtrap.io';
$EMAIL_USER = 'usuario_teste';
$EMAIL_PASS = 'senha_teste';
$EMAIL_PORT = 2525;
$EMAIL_FROM = 'teste@localhost';
$EMAIL_FROM_NAME = 'Ambiente de Teste';

comunicacao_email(Array(
    'EMAIL_TESTS' => $EMAIL_TESTS,
    'EMAIL_DEBUG' => $EMAIL_DEBUG,
    'EMAIL_HOST' => $EMAIL_HOST,
    'EMAIL_USER' => $EMAIL_USER,
    'EMAIL_PASS' => $EMAIL_PASS,
    'EMAIL_PORT' => $EMAIL_PORT,
    'EMAIL_FROM' => $EMAIL_FROM,
    'EMAIL_FROM_NAME' => $EMAIL_FROM_NAME,
    'destinatarios' => Array(
        Array('email' => 'dev@localhost')
    ),
    'mensagem' => Array(
        'assunto' => 'Email de Teste',
        'html' => '<h1>Teste</h1>'
    )
));
```

---

## Padrões e Melhores Práticas

### Validação de Email

```php
// ✅ Validar email antes de enviar
function enviar_com_validacao($email, $mensagem) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    return comunicacao_email(Array(
        'destinatarios' => Array(Array('email' => $email)),
        'mensagem' => $mensagem
    ));
}
```

### Tratamento de Erros

```php
// ✅ Sempre verificar retorno
$enviado = comunicacao_email($params);

if (!$enviado) {
    error_log("Falha ao enviar email para: " . $params['destinatarios'][0]['email']);
    // Tentar reenviar ou notificar admin
}
```

### Filas de Email

```php
// ✅ Para grandes volumes, usar fila
function adicionar_email_fila($params) {
    banco_insert_name(Array(
        Array('params', serialize($params)),
        Array('status', 'pendente'),
        Array('tentativas', 0),
        Array('criado_em', 'NOW()', true, false)
    ), 'email_fila');
}

// Processar fila em cron
function processar_fila_emails() {
    $emails = banco_select(Array(
        'campos' => '*',
        'tabela' => 'email_fila',
        'extra' => "WHERE status='pendente' LIMIT 10"
    ));
    
    foreach ($emails as $email) {
        $params = unserialize($email['params']);
        $enviado = comunicacao_email($params);
        
        if ($enviado) {
            banco_update("status='enviado'", 'email_fila', "WHERE id='{$email['id']}'");
        } else {
            $tentativas = $email['tentativas'] + 1;
            $status = $tentativas >= 3 ? 'falha' : 'pendente';
            banco_update("tentativas=$tentativas, status='$status'", 
                        'email_fila', "WHERE id='{$email['id']}'");
        }
    }
}
```

---

## Limitações e Considerações

### Performance

- Envio síncrono pode ser lento
- Para volumes altos, use fila assíncrona
- Considere limites do servidor SMTP

### Tamanho de Anexos

- Limite padrão PHPMailer: sem restrição, mas SMTP pode ter
- Considere compactar arquivos grandes
- Use serviços de storage + links para arquivos muito grandes

### HTML

- Nem todos clientes suportam CSS avançado
- Use tabelas para layout (compatibilidade)
- Teste em vários clientes de email

### Segurança

- Não envie credenciais por email
- Use HTTPS para links
- Valide sempre emails de entrada

---

## Veja Também

- [PHPMailer Documentation](https://github.com/PHPMailer/PHPMailer) - Documentação oficial
- [BIBLIOTECA-GESTOR.md](./BIBLIOTECA-GESTOR.md) - Layouts e componentes
- [BIBLIOTECA-PDF.md](./BIBLIOTECA-PDF.md) - Geração de anexos PDF
- [BIBLIOTECA-HOST.md](./BIBLIOTECA-HOST.md) - Multi-tenancy

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
