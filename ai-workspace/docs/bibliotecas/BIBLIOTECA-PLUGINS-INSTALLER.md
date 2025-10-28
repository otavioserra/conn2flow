# Biblioteca: plugins-installer.php

> 🔌 Sistema de instalação e gerenciamento de plugins

## Visão Geral

A biblioteca `plugins-installer.php` fornece 43 funções para gerenciar todo o ciclo de vida de plugins: instalação, atualização, desinstalação, ativação/desativação e gerenciamento de dependências.

**Localização**: `gestor/bibliotecas/plugins-installer.php`  
**Total de Funções**: 43

## Principais Funcionalidades

### Instalação
- `plugins_installer_install()` - Instala plugin
- `plugins_installer_download()` - Download de repositório
- `plugins_installer_extract()` - Extrai arquivos ZIP
- `plugins_installer_verificar_requisitos()` - Verifica dependências
- `plugins_installer_copiar_arquivos()` - Copia arquivos para destino
- `plugins_installer_criar_tabelas()` - Cria tabelas do banco

### Atualização
- `plugins_installer_update()` - Atualiza plugin
- `plugins_installer_verificar_versao()` - Compara versões
- `plugins_installer_backup_antes_update()` - Backup automático
- `plugins_installer_migrar_dados()` - Migração de dados

### Desinstalação
- `plugins_installer_uninstall()` - Desinstala plugin
- `plugins_installer_remover_arquivos()` - Remove arquivos
- `plugins_installer_remover_tabelas()` - Remove tabelas
- `plugins_installer_limpar_cache()` - Limpa cache

### Ativação/Desativação
- `plugins_installer_activate()` - Ativa plugin
- `plugins_installer_deactivate()` - Desativa plugin
- `plugins_installer_verificar_ativo()` - Verifica se está ativo

### Gerenciamento de Dependências
- `plugins_installer_verificar_dependencias()` - Verifica deps
- `plugins_installer_instalar_dependencia()` - Instala dep
- `plugins_installer_resolver_conflitos()` - Resolve conflitos

### Listagem e Busca
- `plugins_installer_listar()` - Lista plugins instalados
- `plugins_installer_buscar()` - Busca em repositório
- `plugins_installer_detalhes()` - Detalhes do plugin

## Exemplos de Uso

### Instalar Plugin

```php
$resultado = plugins_installer_install(Array(
    'plugin_id' => 'ecommerce-gateway',
    'versao' => '2.1.0',
    'source' => 'https://repo.conn2flow.com/plugins/ecommerce-gateway.zip'
));

if ($resultado['sucesso']) {
    echo "Plugin instalado com sucesso!";
    
    // Ativar automaticamente
    plugins_installer_activate(Array(
        'plugin_id' => 'ecommerce-gateway'
    ));
} else {
    echo "Erro: " . $resultado['erro'];
}
```

### Atualizar Plugin

```php
// Verificar se há atualização disponível
$update_disponivel = plugins_installer_verificar_versao(Array(
    'plugin_id' => 'ecommerce-gateway',
    'versao_atual' => '2.0.0',
    'versao_nova' => '2.1.0'
));

if ($update_disponivel) {
    // Fazer backup antes de atualizar
    plugins_installer_backup_antes_update(Array(
        'plugin_id' => 'ecommerce-gateway'
    ));
    
    // Atualizar
    plugins_installer_update(Array(
        'plugin_id' => 'ecommerce-gateway',
        'versao' => '2.1.0'
    ));
}
```

### Listar Plugins

```php
$plugins = plugins_installer_listar(Array(
    'status' => 'ativo',  // ou 'inativo', 'todos'
    'categoria' => 'ecommerce'
));

foreach ($plugins as $plugin) {
    echo "{$plugin['nome']} - v{$plugin['versao']} - {$plugin['status']}<br>";
}
```

### Verificar Dependências

```php
$deps = plugins_installer_verificar_dependencias(Array(
    'plugin_id' => 'advanced-analytics',
    'dependencias' => Array(
        'php_version' => '7.4',
        'plugins' => Array('core-stats'),
        'extensoes_php' => Array('gd', 'curl')
    )
));

if (!$deps['ok']) {
    echo "Dependências não atendidas: ";
    print_r($deps['faltando']);
}
```

## Estrutura de Plugin

```php
// plugin.json
{
    "id": "meu-plugin",
    "nome": "Meu Plugin",
    "versao": "1.0.0",
    "autor": "Desenvolvedor",
    "descricao": "Plugin exemplo",
    "dependencias": {
        "php": ">=7.4",
        "conn2flow": ">=2.0",
        "plugins": ["plugin-base"]
    },
    "tabelas": [
        {
            "nome": "meu_plugin_dados",
            "sql": "CREATE TABLE ..."
        }
    ]
}
```

## Hooks e Eventos

Plugins podem registrar hooks:

```php
// No plugin
plugins_installer_registrar_hook(Array(
    'evento' => 'produto_criado',
    'callback' => 'meu_plugin_ao_criar_produto'
));

function meu_plugin_ao_criar_produto($produto) {
    // Executar ação customizada
}
```

## Padrões de Segurança

### Validação
- Verificar assinatura de plugins
- Validar origem do download
- Sanitizar arquivos

### Isolamento
- Plugins rodam em namespace isolado
- Permissões restritas de arquivo
- Limites de recursos

---

## Veja Também

- [BIBLIOTECA-PLUGINS.md](./BIBLIOTECA-PLUGINS.md) - Template de plugin
- [BIBLIOTECA-BANCO.md](./BIBLIOTECA-BANCO.md) - Operações de banco

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
