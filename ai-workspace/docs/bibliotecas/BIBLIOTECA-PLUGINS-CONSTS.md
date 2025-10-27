# Biblioteca: plugins-consts.php

> 🔧 Constantes e códigos de status do sistema de plugins

## Visão Geral

A biblioteca `plugins-consts.php` define constantes essenciais para o sistema de instalação e gerenciamento de plugins do Conn2Flow. Estabelece códigos de saída padronizados e status de execução que permitem rastreamento e debugging consistente durante operações com plugins.

**Localização**: `gestor/bibliotecas/plugins-consts.php`  
**Versão**: Fase 1  
**Total de Funções**: 1 (helper)  
**Total de Constantes**: 12

## Dependências

- Nenhuma dependência de outras bibliotecas
- Alinhado com documentação em `ai-workspace/prompts/plugins/modificar-plugins.md`

## Constantes Definidas

### Códigos de Saída (Exit Codes)

Constantes que indicam o resultado de operações com plugins:

#### PLG_EXIT_OK
```php
define('PLG_EXIT_OK', 0);
```
**Descrição**: Operação concluída com sucesso  
**Uso**: Retornado quando plugin é instalado/atualizado sem erros

#### PLG_EXIT_PARAMS_OR_FILE
```php
define('PLG_EXIT_PARAMS_OR_FILE', 10);
```
**Descrição**: Erro de parâmetros ou arquivo não encontrado  
**Causas Comuns**:
- Parâmetros obrigatórios não fornecidos
- Arquivo de plugin não encontrado
- Caminho inválido

#### PLG_EXIT_VALIDATE
```php
define('PLG_EXIT_VALIDATE', 11);
```
**Descrição**: Falha na validação do plugin  
**Causas Comuns**:
- Estrutura de diretórios inválida
- Arquivos obrigatórios faltando
- Metadados (metadata.json) inválidos
- Versão incompatível

#### PLG_EXIT_MOVE
```php
define('PLG_EXIT_MOVE', 12);
```
**Descrição**: Falha ao mover arquivos do plugin  
**Causas Comuns**:
- Permissões insuficientes no diretório de destino
- Espaço em disco insuficiente
- Diretório de destino não existe

#### PLG_EXIT_DOWNLOAD
```php
define('PLG_EXIT_DOWNLOAD', 20);
```
**Descrição**: Falha no download do plugin  
**Causas Comuns**:
- URL inválida ou inacessível
- Problema de conectividade
- Timeout na requisição
- Servidor remoto indisponível

#### PLG_EXIT_ZIP_INVALID
```php
define('PLG_EXIT_ZIP_INVALID', 21);
```
**Descrição**: Arquivo ZIP inválido ou corrompido  
**Causas Comuns**:
- Arquivo ZIP corrompido
- Não é um arquivo ZIP válido
- Falha ao extrair conteúdo

#### PLG_EXIT_CHECKSUM
```php
define('PLG_EXIT_CHECKSUM', 22);
```
**Descrição**: Falha na verificação de checksum/integridade  
**Causas Comuns**:
- Hash SHA256 não corresponde
- Arquivo modificado após criação
- Arquivo corrompido durante download

---

### Status de Execução

Constantes que indicam o estado atual de uma operação com plugin:

#### PLG_STATUS_IDLE
```php
define('PLG_STATUS_IDLE', 'idle');
```
**Descrição**: Sistema em repouso, nenhuma operação em andamento  
**Transições**: `idle` → `instalando` ou `atualizando`

#### PLG_STATUS_INSTALANDO
```php
define('PLG_STATUS_INSTALANDO', 'instalando');
```
**Descrição**: Plugin sendo instalado  
**Transições**: `instalando` → `ok` ou `erro`

#### PLG_STATUS_ATUALIZANDO
```php
define('PLG_STATUS_ATUALIZANDO', 'atualizando');
```
**Descrição**: Plugin sendo atualizado  
**Transições**: `atualizando` → `ok` ou `erro`

#### PLG_STATUS_ERRO
```php
define('PLG_STATUS_ERRO', 'erro');
```
**Descrição**: Operação falhou  
**Transições**: `erro` → `idle` (após tratamento)

#### PLG_STATUS_OK
```php
define('PLG_STATUS_OK', 'ok');
```
**Descrição**: Operação concluída com sucesso  
**Transições**: `ok` → `idle`

---

## Função Helper

### plg_exit_code_label()

Converte código de saída numérico em label descritivo para debugging.

**Assinatura:**
```php
function plg_exit_code_label(int $code): string
```

**Parâmetros:**
- `$code` (int) - **Obrigatório** - Código de saída numérico

**Retorno:**
- (string) - Label descritivo do código ou 'UNKNOWN' se não reconhecido

**Códigos Suportados:**

| Código | Label | Descrição |
|--------|-------|-----------|
| 0 | `OK` | Sucesso |
| 10 | `PARAMS_OR_FILE` | Erro de parâmetros/arquivo |
| 11 | `VALIDATE` | Falha na validação |
| 12 | `MOVE` | Falha ao mover arquivos |
| 20 | `DOWNLOAD` | Falha no download |
| 21 | `ZIP_INVALID` | ZIP inválido |
| 22 | `CHECKSUM` | Falha no checksum |
| Outro | `UNKNOWN` | Código desconhecido |

**Exemplo de Uso:**
```php
$result_code = instalar_plugin($plugin_data);

if($result_code !== PLG_EXIT_OK) {
    $label = plg_exit_code_label($result_code);
    error_log("Falha na instalação: " . $label . " (código: " . $result_code . ")");
}

// Exemplo de output de log:
// "Falha na instalação: CHECKSUM (código: 22)"
```

---

## Casos de Uso

### 1. Instalação de Plugin

```php
function instalar_plugin_wrapper($plugin_url, $plugin_name) {
    // Atualizar status
    atualizar_status_plugin($plugin_name, PLG_STATUS_INSTALANDO);
    
    // Tentar instalação
    $result = instalar_plugin(Array(
        'url' => $plugin_url,
        'nome' => $plugin_name
    ));
    
    // Processar resultado
    switch($result) {
        case PLG_EXIT_OK:
            atualizar_status_plugin($plugin_name, PLG_STATUS_OK);
            log_plugin("Plugin $plugin_name instalado com sucesso");
            break;
            
        case PLG_EXIT_DOWNLOAD:
            atualizar_status_plugin($plugin_name, PLG_STATUS_ERRO);
            log_plugin("Falha no download do plugin $plugin_name", 'error');
            break;
            
        case PLG_EXIT_VALIDATE:
            atualizar_status_plugin($plugin_name, PLG_STATUS_ERRO);
            log_plugin("Plugin $plugin_name falhou na validação", 'error');
            break;
            
        case PLG_EXIT_CHECKSUM:
            atualizar_status_plugin($plugin_name, PLG_STATUS_ERRO);
            log_plugin("Checksum inválido para plugin $plugin_name", 'error');
            break;
            
        default:
            atualizar_status_plugin($plugin_name, PLG_STATUS_ERRO);
            $label = plg_exit_code_label($result);
            log_plugin("Erro desconhecido: $label (código: $result)", 'error');
    }
    
    return $result;
}
```

### 2. Sistema de Logging Detalhado

```php
function log_operacao_plugin($operacao, $codigo_saida, $detalhes = '') {
    global $_BANCO;
    
    $status_label = plg_exit_code_label($codigo_saida);
    $sucesso = ($codigo_saida === PLG_EXIT_OK) ? 1 : 0;
    
    banco_query(
        "INSERT INTO plugins_log 
         (operacao, codigo_saida, status_label, sucesso, detalhes, data_hora) 
         VALUES 
         ('" . banco_escape_field($operacao) . "',
          " . $codigo_saida . ",
          '" . banco_escape_field($status_label) . "',
          " . $sucesso . ",
          '" . banco_escape_field($detalhes) . "',
          NOW())"
    );
}

// Uso:
$result = instalar_plugin($data);
log_operacao_plugin('instalacao', $result, "Plugin: $plugin_name");
```

### 3. Interface de Progresso

```php
function obter_status_instalacao($plugin_id) {
    global $_BANCO;
    
    $status = banco_select_one(
        "SELECT status_execucao, ultimo_codigo_erro 
         FROM plugins_instalacao 
         WHERE plugin_id = '" . banco_escape_field($plugin_id) . "'"
    );
    
    $resposta = Array(
        'status' => $status['status_execucao'],
        'em_progresso' => in_array($status['status_execucao'], [
            PLG_STATUS_INSTALANDO,
            PLG_STATUS_ATUALIZANDO
        ]),
        'finalizado' => in_array($status['status_execucao'], [
            PLG_STATUS_OK,
            PLG_STATUS_ERRO,
            PLG_STATUS_IDLE
        ]),
        'sucesso' => ($status['status_execucao'] === PLG_STATUS_OK)
    );
    
    if($status['ultimo_codigo_erro']) {
        $resposta['erro_label'] = plg_exit_code_label($status['ultimo_codigo_erro']);
        $resposta['erro_codigo'] = $status['ultimo_codigo_erro'];
    }
    
    return $resposta;
}

// Uso em AJAX:
// GET /api/plugin/status/123
$status = obter_status_instalacao($_GET['plugin_id']);
echo json_encode($status);

// Resposta:
// {
//   "status": "instalando",
//   "em_progresso": true,
//   "finalizado": false,
//   "sucesso": false
// }
```

### 4. Validação e Tratamento de Erros

```php
function processar_resultado_plugin($codigo) {
    $mensagens = Array(
        PLG_EXIT_OK => Array(
            'tipo' => 'success',
            'titulo' => 'Sucesso',
            'mensagem' => 'Plugin instalado com sucesso!'
        ),
        PLG_EXIT_PARAMS_OR_FILE => Array(
            'tipo' => 'error',
            'titulo' => 'Erro de Parâmetros',
            'mensagem' => 'Parâmetros inválidos ou arquivo não encontrado.'
        ),
        PLG_EXIT_VALIDATE => Array(
            'tipo' => 'error',
            'titulo' => 'Validação Falhou',
            'mensagem' => 'O plugin não passou na validação. Verifique a estrutura.'
        ),
        PLG_EXIT_DOWNLOAD => Array(
            'tipo' => 'error',
            'titulo' => 'Erro de Download',
            'mensagem' => 'Não foi possível baixar o plugin. Verifique a conexão.'
        ),
        PLG_EXIT_ZIP_INVALID => Array(
            'tipo' => 'error',
            'titulo' => 'ZIP Inválido',
            'mensagem' => 'O arquivo ZIP está corrompido ou inválido.'
        ),
        PLG_EXIT_CHECKSUM => Array(
            'tipo' => 'error',
            'titulo' => 'Verificação de Integridade',
            'mensagem' => 'O checksum do arquivo não corresponde. Arquivo pode estar corrompido.'
        )
    );
    
    return $mensagens[$codigo] ?? Array(
        'tipo' => 'error',
        'titulo' => 'Erro Desconhecido',
        'mensagem' => 'Erro: ' . plg_exit_code_label($codigo)
    );
}

// Uso:
$resultado_instalacao = instalar_plugin($data);
$feedback = processar_resultado_plugin($resultado_instalacao);

// Exibir ao usuário:
echo '<div class="alert alert-' . $feedback['tipo'] . '">';
echo '<strong>' . $feedback['titulo'] . '</strong>: ';
echo $feedback['mensagem'];
echo '</div>';
```

### 5. Máquina de Estados

```php
class PluginStateMachine {
    private $plugin_id;
    private $status_atual;
    
    public function __construct($plugin_id) {
        $this->plugin_id = $plugin_id;
        $this->status_atual = $this->obter_status();
    }
    
    public function pode_iniciar_instalacao() {
        return $this->status_atual === PLG_STATUS_IDLE;
    }
    
    public function pode_atualizar() {
        return in_array($this->status_atual, [
            PLG_STATUS_IDLE,
            PLG_STATUS_OK
        ]);
    }
    
    public function transicao_para($novo_status) {
        $transicoes_validas = Array(
            PLG_STATUS_IDLE => [PLG_STATUS_INSTALANDO, PLG_STATUS_ATUALIZANDO],
            PLG_STATUS_INSTALANDO => [PLG_STATUS_OK, PLG_STATUS_ERRO],
            PLG_STATUS_ATUALIZANDO => [PLG_STATUS_OK, PLG_STATUS_ERRO],
            PLG_STATUS_OK => [PLG_STATUS_ATUALIZANDO, PLG_STATUS_IDLE],
            PLG_STATUS_ERRO => [PLG_STATUS_IDLE, PLG_STATUS_INSTALANDO]
        );
        
        if(in_array($novo_status, $transicoes_validas[$this->status_atual] ?? [])) {
            $this->atualizar_status($novo_status);
            $this->status_atual = $novo_status;
            return true;
        }
        
        return false;
    }
    
    private function obter_status() {
        // Consultar banco de dados
    }
    
    private function atualizar_status($status) {
        // Atualizar banco de dados
    }
}

// Uso:
$state = new PluginStateMachine($plugin_id);

if($state->pode_iniciar_instalacao()) {
    $state->transicao_para(PLG_STATUS_INSTALANDO);
    // ... realizar instalação ...
    $state->transicao_para(PLG_STATUS_OK);
}
```

## Diagrama de Fluxo de Estados

```
┌─────────────────────────────────────────────────────────┐
│                    PLG_STATUS_IDLE                       │
│                  (Estado Inicial)                        │
└──────────────────┬──────────────────────────────────────┘
                   │
     ┌─────────────┴──────────────┐
     │                            │
     ▼                            ▼
┌─────────────┐          ┌──────────────┐
│ INSTALANDO  │          │ ATUALIZANDO  │
└──────┬──────┘          └──────┬───────┘
       │                        │
       │    ┌──────────────────┘
       │    │
       ▼    ▼
    ┌────────┐
    │  OK    │────────┐
    └────────┘        │
                      │
       ┌──────────────┘
       │
       ▼
    ┌────────┐
    │ ERRO   │
    └───┬────┘
        │
        ▼
    (volta para IDLE após tratamento)
```

## Mapeamento de Códigos de Saída

| Categoria | Códigos | Recuperável? | Ação Sugerida |
|-----------|---------|--------------|---------------|
| **Sucesso** | 0 | N/A | Nenhuma |
| **Parâmetros** | 10-12 | Sim | Corrigir entrada/permissões |
| **Download/ZIP** | 20-22 | Parcial | Tentar novamente ou verificar arquivo |

## Referências de Documentação

Esta biblioteca está alinhada com:
- `ai-workspace/prompts/plugins/modificar-plugins.md` - Documentação principal do sistema de plugins
- `gestor/bibliotecas/plugins-installer.php` - Implementação do instalador
- `gestor/bibliotecas/plugins.php` - Utilitários de plugins

## Extensões Futuras

Possíveis adições a esta biblioteca:

```php
// Códigos adicionais para operações específicas
define('PLG_EXIT_DEPENDENCY', 30);      // Dependência não satisfeita
define('PLG_EXIT_INCOMPATIBLE', 31);    // Versão incompatível
define('PLG_EXIT_ALREADY_INSTALLED', 32); // Já instalado

// Status adicionais
define('PLG_STATUS_DESINSTALANDO', 'desinstalando');
define('PLG_STATUS_PAUSADO', 'pausado');
define('PLG_STATUS_AGUARDANDO', 'aguardando_dependencia');
```

## Veja Também

- [BIBLIOTECA-PLUGINS-INSTALLER.md](./BIBLIOTECA-PLUGINS-INSTALLER.md) - Sistema de instalação
- [BIBLIOTECA-PLUGINS.md](./BIBLIOTECA-PLUGINS.md) - Utilitários de plugins
- [Plugin Architecture](../../CONN2FLOW-PLUGIN-ARCHITECTURE.md) - Arquitetura geral

---

**Última Atualização**: Outubro 2025  
**Documentado por**: Equipe Conn2Flow
