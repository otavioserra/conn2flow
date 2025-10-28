# Biblioteca: plugins.php

> 🔌 Template base para funções de plugins

## Visão Geral

A biblioteca `plugins.php` serve como **template e exemplo** para criação de novas funções relacionadas a plugins no sistema Conn2Flow. Contém uma função de exemplo que demonstra a estrutura padrão a ser seguida.

**Localização**: `gestor/bibliotecas/plugins.php`  
**Versão**: 1.0.0  
**Total de Funções**: 1 (template)  
**Tipo**: Template/Exemplo

## Dependências

- **Variáveis Globais**: `$_GESTOR`

## Variáveis Globais

```php
$_GESTOR['biblioteca-template'] = Array(
    'versao' => '1.0.0',
);
```

---

## Funções Template

### template_opcao()

Função template que serve como exemplo de estrutura.

**Assinatura:**
```php
function template_opcao($params = false)
```

**Parâmetros (Array Associativo):**
- `variavel` (tipo) - Obrigatoriedade - Descrição da variável

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
template_opcao(Array(
    'variavel' => 'valor'
));
```

**Notas:**
- Esta é uma função de exemplo
- Deve ser renomeada conforme necessidade
- Segue padrão de nomenclatura do sistema

---

## Padrão para Novas Funções de Plugin

### Estrutura Recomendada

```php
/**
 * Descrição breve da função.
 *
 * Descrição detalhada do que a função faz,
 * incluindo comportamentos especiais e casos de uso.
 *
 * @param array|false $params Array de parâmetros nomeados ou false.
 * @return tipo Descrição do retorno.
 */
function nome_funcao($params = false){
    global $_GESTOR;
    
    // Extrai variáveis do array de parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // ===== Parâmetros esperados:
    // 
    // parametro1 - String - Obrigatório - Descrição do parâmetro 1.
    // parametro2 - Int - Opcional - Descrição do parâmetro 2.
    // parametro3 - Bool - Opcional - Descrição do parâmetro 3.
    // 
    // ===== 
    
    // Validação de parâmetros obrigatórios
    if(!isset($parametro1)){
        return false;
    }
    
    // Implementação da lógica
    // ...
    
    return $resultado;
}
```

### Convenções de Nomenclatura

1. **Nome da Função**:
   - Use prefixo descritivo do módulo/contexto
   - Exemplo: `plugin_instalar`, `plugin_validar`, `plugin_ativar`

2. **Parâmetros**:
   - Sempre use array associativo
   - Documente claramente obrigatoriedade
   - Use tipos PHP padrão

3. **Documentação**:
   - PHPDoc no topo da função
   - Seção de parâmetros comentada
   - Exemplos de uso quando relevante

---

## Exemplos de Implementação

### Função de Plugin Simples

```php
function plugin_validar($params = false){
    global $_GESTOR;
    
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // ===== Parâmetros:
    // plugin_id - Int - Obrigatório - ID do plugin.
    // ===== 
    
    if(!isset($plugin_id)){
        return false;
    }
    
    // Buscar plugin
    $plugin = banco_select(Array(
        'campos' => Array('nome', 'versao', 'ativo'),
        'tabela' => 'plugins',
        'extra' => "WHERE id='$plugin_id'",
        'unico' => true
    ));
    
    if(!$plugin){
        return false;
    }
    
    // Validar estrutura
    $path = $_GESTOR['plugins-path'] . $plugin['nome'];
    if(!file_exists($path . '/plugin.json')){
        return false;
    }
    
    return true;
}
```

### Função com Múltiplas Opções

```php
function plugin_gerenciar($params = false){
    global $_GESTOR;
    
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // ===== Parâmetros:
    // acao - String - Obrigatório - 'ativar', 'desativar', 'reinstalar'.
    // plugin_id - Int - Obrigatório - ID do plugin.
    // ===== 
    
    if(!isset($acao) || !isset($plugin_id)){
        return false;
    }
    
    switch($acao){
        case 'ativar':
            banco_update(
                "ativo=1",
                'plugins',
                "WHERE id='$plugin_id'"
            );
            return true;
        
        case 'desativar':
            banco_update(
                "ativo=0",
                'plugins',
                "WHERE id='$plugin_id'"
            );
            return true;
        
        case 'reinstalar':
            // Lógica de reinstalação
            return plugin_reinstalar($plugin_id);
        
        default:
            return false;
    }
}
```

---

## Veja Também

- [BIBLIOTECA-PLUGINS-INSTALLER.md](./BIBLIOTECA-PLUGINS-INSTALLER.md) - Sistema de instalação
- [BIBLIOTECA-PLUGINS-CONSTS.md](./BIBLIOTECA-PLUGINS-CONSTS.md) - Constantes de plugins
- [Arquitetura de Plugins](../CONN2FLOW-PLUGIN-ARCHITECTURE.md) - Documentação completa

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
