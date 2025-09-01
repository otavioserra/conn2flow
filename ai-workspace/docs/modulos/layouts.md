# Módulo: layouts

## 🎯 Propósito
O módulo **layouts** gerencia os layouts do sistema, permitindo criar, editar e organizar templates de layout com código HTML e CSS personalizados para cada host.

## 📁 Arquivos Principais
- **`layouts.php`** - Controlador principal com CRUD de layouts e integração com CodeMirror
- **`layouts.json`** - Configurações do módulo e variáveis de formulário
- **`layouts.js`** - Interface com CodeMirror para edição de HTML/CSS

## 🗄️ Tabela do Banco de Dados
**Tabela principal**: `hosts_layouts`
- `id_hosts_layouts` - ID primário
- `id_hosts` - ID do host
- `id_usuarios` - ID do usuário criador
- `nome` - Nome do layout
- `id` - Identificador único do layout
- `html` - Código HTML do layout
- `css` - Código CSS do layout
- `status` - Status (A=Ativo, I=Inativo, D=Deletado)
- `versao` - Versão do layout
- `data_criacao` - Data de criação
- `data_modificacao` - Data de modificação
- `template_padrao` - Indica se é template padrão
- `template_categoria` - Categoria do template
- `template_id` - ID do template base
- `template_modificado` - Indica se foi modificado
- `template_versao` - Versão do template

## 🔧 Principais Funções (layouts.php)

### `hosts_layouts_adicionar()`
Gerencia a criação de novos layouts. Valida campos obrigatórios (nome), gera identificador único baseado no nome, processa variáveis globais no HTML/CSS, salva no banco de dados e sincroniza com o cliente via API.

### `hosts_layouts_editar()`
Gerencia a edição de layouts existentes. Carrega dados do banco, valida alterações, processa mudanças no nome (alterando ID), atualiza HTML/CSS, mantém backup de campos alterados, registra no histórico e sincroniza com API cliente.

### `hosts_layouts_status()`
Altera o status de um layout (ativo/inativo) via API cliente.

### `hosts_layouts_excluir()`
Exclui um layout do sistema via API cliente usando o ID numérico.

### `hosts_layouts_interfaces_padroes()`
Define as interfaces padrão para diferentes operações (listar, status, excluir). Configura listagem com colunas, botões de ação e opções de CRUD.

### `hosts_layouts_start()`
Função de inicialização do módulo. Verifica permissões de host, inclui bibliotecas, gerencia requisições AJAX e direciona para funções apropriadas baseado na opção.

## 🎨 Interface (layouts.js)

### Editor de Código
- **CodeMirror CSS**: Editor com syntax highlighting para CSS
- **CodeMirror HTML**: Editor com syntax highlighting para HTML
- **Configuração**: Tema dark, números de linha, word wrap, fullscreen (F11)
- **Instâncias**: Gerenciamento de múltiplas instâncias do editor

### Navegação por Abas
- **Tab System**: Sistema de abas para alternar entre HTML e CSS
- **LocalStorage**: Salva aba ativa para persistência
- **Refresh**: Atualiza editores quando aba é carregada

### Sistema de Backup
- **Backup Campo**: Sistema para restaurar versões anteriores
- **Event Listener**: Escuta eventos para mudança de backup
- **Integração**: Carrega conteúdo do backup diretamente no editor

## ⚙️ Configurações (layouts.json)

### Informações do Módulo
- **Versão**: 1.0.0
- **Bibliotecas**: `interface`, `html`
- **Tabela**: Configuração completa da tabela `hosts_layouts`

### Páginas do Sistema
- **Layouts**: Listagem principal
- **Layouts - Adicionar**: Formulário de criação
- **Layouts - Editar**: Formulário de edição

### Variáveis de Interface
- **Labels**: Rótulos de campos (Nome, HTML, CSS)
- **Placeholders**: Textos de ajuda
- **Tooltips**: Dicas para elementos da interface

## 🔄 Integração com Sistema
- **API Cliente**: Sincronização automática de layouts
- **CodeMirror**: Editor avançado de código
- **Histórico**: Registro de todas as alterações
- **Backup**: Sistema de backup por campo
- **Templates**: Suporte a templates padrão e modificados
- **Versionamento**: Controle de versão automático
