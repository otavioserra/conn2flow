# Módulo: menus

## 🎯 Propósito
O módulo **menus** gerencia a configuração e exibição de menus do sistema, permitindo ativar/desativar itens de menu específicos para cada host e manter histórico de alterações.

## 📁 Arquivos Principais
- **`menus.php`** - Controlador principal com configuração e gerenciamento de menus
- **`menus.json`** - Configurações do módulo e variáveis de texto
- **`menus.js`** - Interface para manipulação de menus com drag-and-drop

## 🗄️ Tabela do Banco de Dados
**Tabela principal**: `hosts_menus_itens`
- `id_hosts_menus_itens` - ID primário
- `id_hosts` - ID do host
- `menu_id` - Identificador do menu
- `id` - ID do item do menu
- `label` - Rótulo do item
- `tipo` - Tipo do item do menu
- `url` - URL do item
- `inativo` - Status ativo/inativo do item
- `versao` - Versão do registro

## 🔧 Principais Funções (menus.php)

### `menus_config()`
Função principal que gerencia a configuração dos menus. Processa atualizações vindas do formulário, compara com dados existentes no banco, atualiza status de itens (ativo/inativo), registra alterações no histórico e sincroniza com o cliente via API.

### `menus_interfaces_padroes()`
Define as interfaces padrão do módulo baseado na opção selecionada. Atualmente contém estrutura para opção genérica.

### `menus_ajax_opcao()`
Função para processar requisições AJAX, retornando status em formato JSON.

### `menus_start()`
Função de inicialização do módulo. Inclui bibliotecas necessárias, verifica se é requisição AJAX ou normal, e direciona para as funções apropriadas baseado na opção escolhida.

## 🎨 Interface (menus.js)

### Manipulação de Dados
- **Dados do Servidor**: Carrega e salva dados de menu em formato JSON
- **`iniciarDadosServidor()`**: Carrega dados do campo hidden `#dadosServidor`
- **`salvarDadosServidor()`**: Salva dados atualizados no campo hidden

### Renderização de Menus
- **`menus_iniciar()`**: Função principal que inicializa a interface
- Itera pelos menus do servidor e cria elementos HTML para cada item
- Configura checkboxes para ativar/desativar itens
- Aplica títulos personalizados quando disponíveis

### Funcionalidades de UI
- **Checkboxes**: Sistema de ativação/desativação de itens com callback
- **Accordion**: Interface expansível para organização
- **Drag & Drop**: Funcionalidade de arrastar e soltar para reordenação
- **Clone Visual**: Criação de elemento clone durante o arraste

## ⚙️ Configurações (menus.json)

### Informações do Módulo
- **Versão**: 1.0.27
- **Bibliotecas**: `interface`, `html`
- **Tabela**: Configuração referencia modelo genérico

### Recursos (pt-br)
- **Páginas**: Uma página "Menus" com layout administrativo
- **Variáveis**: Labels para diferentes tipos de menu (Sobre, Ingressos, Mapa, etc.)
- **Histórico**: Variável para registro de alterações

### Variáveis de Interface
Contém labels e tooltips para elementos como:
- Menu Página Inicial
- Menu Minha Conta  
- Tipos de menu (Padrão/Personalizado)
- Opções de navegação (Meus Pedidos, Meus Dados, Sair)
- Módulos específicos (Agendamentos, Escalas)

## 🔄 Integração com Sistema
- **API Cliente**: Sincronização automática de alterações
- **Histórico**: Registro de todas as modificações
- **Versionamento**: Controle de versão por host e módulo
- **Cache**: Sistema baseado em versão para otimização
