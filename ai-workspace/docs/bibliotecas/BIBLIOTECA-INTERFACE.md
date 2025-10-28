# Biblioteca: interface.php

> 🎨 Componentes de interface e UI

## Visão Geral

A biblioteca `interface.php` fornece 52 funções para gerar componentes de interface do usuário, incluindo menus, formulários, tabelas, modais, alertas e outros elementos visuais do CMS.

**Localização**: `gestor/bibliotecas/interface.php`  
**Total de Funções**: 52

## Principais Categorias

### Menus e Navegação
- `interface_menu_principal()` - Menu principal do sistema
- `interface_menu_lateral()` - Menu lateral retrátil
- `interface_breadcrumb()` - Trilha de navegação
- `interface_menu_usuario()` - Menu dropdown do usuário

### Formulários
- `interface_form_input()` - Campo de input
- `interface_form_select()` - Select/dropdown
- `interface_form_textarea()` - Área de texto
- `interface_form_checkbox()` - Checkbox
- `interface_form_radio()` - Radio button
- `interface_form_file()` - Upload de arquivo

### Tabelas
- `interface_tabela()` - Tabela de dados
- `interface_tabela_linha()` - Linha de tabela
- `interface_tabela_celula()` - Célula de tabela
- `interface_tabela_header()` - Cabeçalho de tabela
- `interface_tabela_paginacao()` - Controles de paginação

### Componentes Visuais
- `interface_card()` - Card/painel
- `interface_modal()` - Janela modal
- `interface_alerta()` - Mensagem de alerta
- `interface_badge()` - Badge/etiqueta
- `interface_botao()` - Botão estilizado
- `interface_tabs()` - Abas/tabs
- `interface_accordion()` - Accordion/expansível

### Widgets de Dados
- `interface_grafico()` - Gráfico (Chart.js)
- `interface_estatistica()` - Card de estatística
- `interface_progresso()` - Barra de progresso
- `interface_timeline()` - Linha do tempo

## Exemplos de Uso

### Tabela com Paginação

```php
$dados = banco_select(Array(
    'campos' => Array('id', 'nome', 'email', 'status'),
    'tabela' => 'usuarios',
    'extra' => "LIMIT 20"
));

echo interface_tabela(Array(
    'colunas' => Array('ID', 'Nome', 'Email', 'Status'),
    'dados' => $dados,
    'acoes' => Array('editar', 'excluir'),
    'paginacao' => true
));
```

### Card de Estatística

```php
echo interface_card(Array(
    'titulo' => 'Vendas Hoje',
    'valor' => 'R$ 12.500',
    'icone' => 'fa-shopping-cart',
    'cor' => 'success',
    'variacao' => '+15%'
));
```

### Modal de Confirmação

```php
echo interface_modal(Array(
    'id' => 'modal-confirmar',
    'titulo' => 'Confirmar Exclusão',
    'conteudo' => 'Deseja realmente excluir este item?',
    'botoes' => Array(
        Array('texto' => 'Cancelar', 'classe' => 'btn-secondary'),
        Array('texto' => 'Excluir', 'classe' => 'btn-danger', 'acao' => 'confirmarExclusao()')
    )
));
```

### Formulário Completo

```php
echo interface_form_input(Array(
    'nome' => 'nome',
    'label' => 'Nome Completo',
    'obrigatorio' => true,
    'placeholder' => 'Digite seu nome'
));

echo interface_form_select(Array(
    'nome' => 'estado',
    'label' => 'Estado',
    'opcoes' => Array('SP' => 'São Paulo', 'RJ' => 'Rio de Janeiro'),
    'obrigatorio' => true
));

echo interface_form_textarea(Array(
    'nome' => 'observacoes',
    'label' => 'Observações',
    'linhas' => 5
));

echo interface_botao(Array(
    'texto' => 'Salvar',
    'tipo' => 'submit',
    'classe' => 'btn-primary'
));
```

## Padrões

### Responsividade
Todos os componentes são responsivos e adaptam-se a dispositivos móveis.

### Acessibilidade
Componentes incluem atributos ARIA para acessibilidade.

### Temas
Suportam temas personalizados via CSS variables.

---

## Veja Também

- [BIBLIOTECA-HTML.md](./BIBLIOTECA-HTML.md) - Geração HTML
- [BIBLIOTECA-FORMULARIO.md](./BIBLIOTECA-FORMULARIO.md) - Validação

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
