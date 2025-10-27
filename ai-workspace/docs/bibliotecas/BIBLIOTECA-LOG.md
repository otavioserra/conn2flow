# Biblioteca: log.php

> 📝 Sistema de logging e auditoria

## Visão Geral

A biblioteca `log.php` fornece funções para registro de ações e alterações no sistema, suportando logging em banco de dados (histórico/auditoria) e em disco (arquivos de log). Essencial para rastreabilidade e debug.

**Localização**: `gestor/bibliotecas/log.php`  
**Versão**: 1.1.0  
**Total de Funções**: 5

## Dependências

- **Bibliotecas**: banco.php, gestor.php
- **Variáveis Globais**: `$_GESTOR`
- **Tabela**: `historico` (banco de dados)

## Variáveis Globais

```php
$_GESTOR['biblioteca-log'] = Array(
    'versao' => '1.1.0',
);

// Configurações de log
$_GESTOR['debug'] // Se true, imprime logs em tela ao invés de arquivo
$_GESTOR['logs-path'] // Diretório para arquivos de log (padrão: gestor/logs/)
```

---

## Funções Principais

### log_debugar()

Registra alterações no histórico com contexto do usuário atual.

**Assinatura:**
```php
function log_debugar($params = false)
```

**Parâmetros (Array Associativo):**
- `alteracoes` (array) - **Opcional** - Lista de alterações a registrar
  - `alteracao` (string) - ID da alteração (chave de linguagem)
  - `alteracao_txt` (string) - Texto literal da alteração
  - `modulo` (string) - Módulo de origem
  - `id` (string) - ID do registro afetado

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
// Registrar alteração simples
log_debugar(Array(
    'alteracoes' => Array(
        Array(
            'alteracao' => 'product-name-changed',
            'alteracao_txt' => 'Nome alterado para: Notebook Dell',
            'modulo' => 'produtos',
            'id' => '123'
        )
    )
));

// Múltiplas alterações
log_debugar(Array(
    'alteracoes' => Array(
        Array(
            'alteracao' => 'price-changed',
            'alteracao_txt' => 'Preço: R$ 1.500,00 → R$ 1.200,00'
        ),
        Array(
            'alteracao' => 'stock-updated',
            'alteracao_txt' => 'Estoque: 10 → 15'
        )
    )
));
```

**Comportamento:**
- Obtém usuário atual via `gestor_usuario()`
- Registra `id_usuarios` automaticamente
- Timestamp com `NOW()`
- Insere na tabela `historico`

---

### log_controladores()

Registra ações de controladores com versionamento.

**Assinatura:**
```php
function log_controladores($params = false)
```

**Parâmetros (Array Associativo):**
- `id_hosts` (int) - **Obrigatório** - ID do host
- `controlador` (string) - **Obrigatório** - Nome do controlador
- `id` (int) - **Obrigatório** - ID do registro
- `alteracoes` (array) - **Obrigatório** - Lista de alterações
- `tabela` (array) - **Obrigatório** - Definição da tabela
  - `nome` (string) - Nome da tabela
  - `versao` (string) - Campo de versão
  - `id_numerico` (string) - Campo ID
- `sem_id` (bool) - **Opcional** - Não vincular ID
- `versao` (int) - **Opcional** - Versão manual (se `sem_id=true`)

**Exemplo de Uso:**
```php
// Registrar alteração em produto
log_controladores(Array(
    'id_hosts' => 1,
    'controlador' => 'produtos-admin',
    'id' => 456,
    'tabela' => Array(
        'nome' => 'produtos',
        'versao' => 'versao',
        'id_numerico' => 'id_produtos'
    ),
    'alteracoes' => Array(
        Array(
            'alteracao' => 'product-updated',
            'alteracao_txt' => 'Produto atualizado via admin',
            'modulo' => 'produtos'
        )
    )
));

// Sem vincular ID específico
log_controladores(Array(
    'id_hosts' => 1,
    'controlador' => 'import-produtos',
    'id' => 0,
    'tabela' => Array(
        'nome' => 'produtos',
        'versao' => 'versao',
        'id_numerico' => 'id_produtos'
    ),
    'sem_id' => true,
    'versao' => 1,
    'alteracoes' => Array(
        Array(
            'alteracao' => 'bulk-import',
            'alteracao_txt' => 'Importação em massa: 100 produtos'
        )
    )
));
```

**Comportamento:**
- Busca versão atual do registro no banco
- Incrementa versão automaticamente
- Vincula ao controlador específico
- Suporta multi-tenant (id_hosts)

---

### log_usuarios()

Registra ações de usuários específicos.

**Assinatura:**
```php
function log_usuarios($params = false)
```

**Parâmetros (Array Associativo):**
- `id_hosts` (int) - **Obrigatório** - ID do host
- `id_usuarios` (int) - **Obrigatório** - ID do usuário
- `id` (int) - **Obrigatório** - ID do registro
- `alteracoes` (array) - **Obrigatório** - Lista de alterações
- `tabela` (array) - **Obrigatório** - Definição da tabela
- `sem_id` (bool) - **Opcional** - Não vincular ID
- `versao` (int) - **Opcional** - Versão manual

**Exemplo de Uso:**
```php
// Registrar edição de perfil
log_usuarios(Array(
    'id_hosts' => 1,
    'id_usuarios' => 789,
    'id' => 789,
    'tabela' => Array(
        'nome' => 'usuarios',
        'versao' => 'versao',
        'id_numerico' => 'id_usuarios'
    ),
    'alteracoes' => Array(
        Array(
            'alteracao' => 'profile-updated',
            'alteracao_txt' => 'Email alterado',
            'modulo' => 'usuarios'
        )
    )
));
```

---

### log_hosts_usuarios()

Registra ações de usuários de hosts (multi-tenant).

**Assinatura:**
```php
function log_hosts_usuarios($params = false)
```

**Parâmetros (Array Associativo):**
- `id_hosts` (int) - **Obrigatório** - ID do host
- `id_hosts_usuarios` (int) - **Obrigatório** - ID do usuário do host
- `id` (int) - **Obrigatório** - ID do registro
- `alteracoes` (array) - **Obrigatório** - Lista de alterações
- `tabela` (array) - **Obrigatório** - Definição da tabela
- `sem_id` (bool) - **Opcional** - Não vincular ID
- `versao` (int) - **Opcional** - Versão manual

**Exemplo de Uso:**
```php
// Usuário de tenant alterando configuração
log_hosts_usuarios(Array(
    'id_hosts' => 5,
    'id_hosts_usuarios' => 42,
    'id' => 100,
    'tabela' => Array(
        'nome' => 'hosts_configuracoes',
        'versao' => 'versao',
        'id_numerico' => 'id'
    ),
    'alteracoes' => Array(
        Array(
            'alteracao' => 'config-changed',
            'alteracao_txt' => 'Logo da loja atualizado'
        )
    )
));
```

---

### log_disco()

Grava mensagens em arquivo de log no disco.

**Assinatura:**
```php
function log_disco($msg, $logFilename = "gestor", $deleteFileAfter = false)
```

**Parâmetros:**
- `$msg` (string) - **Obrigatório** - Mensagem a registrar
- `$logFilename` (string) - **Opcional** - Nome base do arquivo (padrão: "gestor")
- `$deleteFileAfter` (bool) - **Opcional** - Excluir arquivo antes de gravar

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
// Log simples
log_disco("Processo iniciado");
// Grava em: logs/gestor-2025-10-27.log

// Log específico
log_disco("Erro ao conectar SMTP", "email");
// Grava em: logs/email-2025-10-27.log

// Log de erro com detalhes
try {
    // operação
} catch (Exception $e) {
    log_disco(
        "Erro crítico: " . $e->getMessage() . "\nStack: " . $e->getTraceAsString(),
        "errors"
    );
}

// Limpar e gravar
log_disco("Novo processo", "cron", true);
// Apaga logs/cron-2025-10-27.log e cria novo
```

**Formato do Log:**
```
[2025-10-27 15:30:45] Processo iniciado
[2025-10-27 15:30:46] Erro ao conectar SMTP
```

**Comportamento:**
- Adiciona timestamp automaticamente
- Um arquivo por dia (formato: `nome-YYYY-MM-DD.log`)
- Se `$_GESTOR['debug']=true`, imprime em tela ao invés de gravar
- Cria diretório automaticamente se não existir
- Append por padrão (não sobrescreve)

---

## Casos de Uso Comuns

### 1. Auditoria de Alterações

```php
function atualizar_produto($produto_id, $dados_novos) {
    // Buscar dados antigos
    $produto_antigo = banco_select(Array(
        'campos' => Array('nome', 'preco'),
        'tabela' => 'produtos',
        'extra' => "WHERE id='$produto_id'",
        'unico' => true
    ));
    
    // Atualizar
    banco_update(
        "nome='{$dados_novos['nome']}', preco='{$dados_novos['preco']}'",
        'produtos',
        "WHERE id='$produto_id'"
    );
    
    // Registrar alterações
    $alteracoes = Array();
    
    if ($produto_antigo['nome'] != $dados_novos['nome']) {
        $alteracoes[] = Array(
            'alteracao' => 'product-name-changed',
            'alteracao_txt' => "Nome: {$produto_antigo['nome']} → {$dados_novos['nome']}"
        );
    }
    
    if ($produto_antigo['preco'] != $dados_novos['preco']) {
        $alteracoes[] = Array(
            'alteracao' => 'product-price-changed',
            'alteracao_txt' => "Preço: R$ {$produto_antigo['preco']} → R$ {$dados_novos['preco']}"
        );
    }
    
    if (!empty($alteracoes)) {
        log_debugar(Array(
            'alteracoes' => $alteracoes
        ));
    }
}
```

### 2. Log de Processamento em Lote

```php
function processar_importacao($arquivo_csv) {
    $total = 0;
    $erros = 0;
    
    log_disco("Iniciando importação: $arquivo_csv", "import");
    
    $linhas = file($arquivo_csv);
    
    foreach ($linhas as $linha) {
        try {
            processar_linha($linha);
            $total++;
        } catch (Exception $e) {
            $erros++;
            log_disco("Erro linha $total: " . $e->getMessage(), "import");
        }
    }
    
    $resumo = "Importação concluída: $total sucessos, $erros erros";
    log_disco($resumo, "import");
    
    // Também registrar no histórico
    log_debugar(Array(
        'alteracoes' => Array(
            Array(
                'alteracao' => 'import-completed',
                'alteracao_txt' => $resumo,
                'modulo' => 'import'
            )
        )
    ));
}
```

### 3. Rastreamento de Ações Críticas

```php
function excluir_usuario($usuario_id) {
    // Buscar dados antes de excluir
    $usuario = banco_select(Array(
        'campos' => Array('nome', 'email'),
        'tabela' => 'usuarios',
        'extra' => "WHERE id='$usuario_id'",
        'unico' => true
    ));
    
    // Excluir
    banco_delete('usuarios', "WHERE id='$usuario_id'");
    
    // Log crítico em disco
    log_disco(
        "EXCLUSÃO: Usuário #{$usuario_id} ({$usuario['nome']} - {$usuario['email']}) excluído",
        "critical"
    );
    
    // Histórico
    log_usuarios(Array(
        'id_hosts' => $_GESTOR['host-id'],
        'id_usuarios' => gestor_usuario()['id_usuarios'],
        'id' => $usuario_id,
        'tabela' => Array(
            'nome' => 'usuarios',
            'versao' => 'versao',
            'id_numerico' => 'id_usuarios'
        ),
        'alteracoes' => Array(
            Array(
                'alteracao' => 'user-deleted',
                'alteracao_txt' => "Usuário excluído: {$usuario['nome']}",
                'modulo' => 'usuarios'
            )
        )
    ));
}
```

### 4. Debug de Sistema

```php
function debug_processo_pagamento($pedido) {
    if ($_GESTOR['debug']) {
        log_disco("=== DEBUG PAGAMENTO ===", "debug");
        log_disco("Pedido: " . print_r($pedido, true), "debug");
        log_disco("Gateway: {$pedido['gateway']}", "debug");
        log_disco("Valor: R$ {$pedido['total']}", "debug");
    }
    
    try {
        $resultado = processar_gateway($pedido);
        log_disco("Pagamento processado: {$resultado['transaction_id']}", "payments");
        return $resultado;
    } catch (Exception $e) {
        log_disco("ERRO no pagamento: " . $e->getMessage(), "payments");
        log_disco("Stack trace: " . $e->getTraceAsString(), "payments");
        throw $e;
    }
}
```

### 5. Relatório de Histórico

```php
function gerar_relatorio_alteracoes($data_inicio, $data_fim) {
    $historico = banco_select(Array(
        'campos' => Array(
            'h.*',
            'u.nome as usuario_nome'
        ),
        'tabela' => 'historico h',
        'extra' => "
            LEFT JOIN usuarios u ON h.id_usuarios = u.id_usuarios
            WHERE h.data BETWEEN '$data_inicio' AND '$data_fim'
            ORDER BY h.data DESC
        "
    ));
    
    echo "<h2>Alterações de $data_inicio a $data_fim</h2>";
    echo "<table>";
    echo "<tr><th>Data</th><th>Usuário</th><th>Alteração</th></tr>";
    
    foreach ($historico as $item) {
        echo "<tr>";
        echo "<td>{$item['data']}</td>";
        echo "<td>{$item['usuario_nome']}</td>";
        echo "<td>{$item['alteracao_txt']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}
```

---

## Estrutura de Tabela

### Tabela: historico

```sql
CREATE TABLE historico (
    id_historico INT PRIMARY KEY AUTO_INCREMENT,
    id_hosts INT,
    id_usuarios INT,
    id_hosts_usuarios INT,
    controlador VARCHAR(100),
    versao INT,
    id INT,
    modulo VARCHAR(100),
    alteracao VARCHAR(100),
    alteracao_txt TEXT,
    data DATETIME,
    INDEX idx_data (data),
    INDEX idx_usuario (id_usuarios),
    INDEX idx_modulo (modulo)
);
```

---

## Padrões e Melhores Práticas

### Logging Consistente

```php
// ✅ BOM - Sempre logar alterações críticas
function atualizar_preco($id, $novo_preco) {
    // atualizar
    banco_update("preco='$novo_preco'", 'produtos', "WHERE id='$id'");
    
    // logar
    log_debugar(Array(
        'alteracoes' => Array(
            Array('alteracao_txt' => "Preço alterado: $novo_preco")
        )
    ));
}

// ❌ EVITAR - Esquecer de logar
function atualizar_preco($id, $novo_preco) {
    banco_update("preco='$novo_preco'", 'produtos', "WHERE id='$id'");
    // Sem log - impossível rastrear
}
```

### Mensagens Descritivas

```php
// ✅ BOM - Mensagem clara e útil
log_disco("Pagamento aprovado - Pedido #123, Gateway: PayPal, Valor: R$ 150,00", "payments");

// ❌ EVITAR - Mensagem genérica
log_disco("Pagamento OK", "payments");
```

### Rotação de Logs

```php
// Limpar logs antigos (cron diário)
function limpar_logs_antigos($dias = 30) {
    $path = $_GESTOR['logs-path'];
    $arquivos = glob($path . '*.log');
    $limite = time() - ($dias * 24 * 60 * 60);
    
    foreach ($arquivos as $arquivo) {
        if (filemtime($arquivo) < $limite) {
            unlink($arquivo);
            log_disco("Log antigo removido: " . basename($arquivo), "system");
        }
    }
}
```

---

## Limitações e Considerações

### Performance

- Logs em banco podem impactar performance
- Use logs em disco para operações frequentes
- Considere indexação adequada na tabela histórico

### Espaço em Disco

- Logs crescem indefinidamente
- Implemente rotação de logs
- Monitore uso de disco

### Privacidade

- Não logar dados sensíveis (senhas, cartões)
- Cumprir LGPD/GDPR
- Implementar retenção de dados

---

## Veja Também

- [BIBLIOTECA-BANCO.md](./BIBLIOTECA-BANCO.md) - Operações de banco
- [BIBLIOTECA-GESTOR.md](./BIBLIOTECA-GESTOR.md) - Usuário atual

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
