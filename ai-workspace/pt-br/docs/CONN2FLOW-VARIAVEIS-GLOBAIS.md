# Conn2Flow - Glossário de Variáveis Globais

## 📋 Índice
- [🎯 Introdução](#🎯-introdução)
- [⚡ Resumo Rápido](#⚡-resumo-rápido)
- [📝 Sintaxe e Formato](#📝-sintaxe-e-formato)
- [📚 Categorias de Variáveis](#📚-categorias-de-variáveis)
  - [Variáveis de Página (pagina#)](#variáveis-de-página-pagina)
  - [Variáveis de Usuário (usuario#)](#variáveis-de-usuário-usuario)
  - [Variáveis do Sistema (gestor#)](#variáveis-do-sistema-gestor)
  - [Variáveis de Widgets (widgets#)](#variáveis-de-widgets-widgets)
- [🔍 Referência Técnica](#🔍-referência-técnica)
- [💡 Exemplos de Uso](#💡-exemplos-de-uso)

---

## 🎯 Introdução

Este documento é um **glossário completo** das **Variáveis Globais** do sistema Conn2Flow. Estas variáveis são processadas dinamicamente pelo núcleo do sistema (`gestor.php`) e permitem a injeção de conteúdo dinâmico em layouts, páginas e componentes.

### O que são Variáveis Globais?

As **Variáveis Globais** são marcadores especiais no formato `@[[FUNCAO#VARIAVEL]]@` que são **substituídos em tempo de execução** por valores dinâmicos do sistema. Elas permitem que templates HTML sejam reutilizáveis e adaptativos sem necessidade de código PHP embutido.

### Arquitetura de Processamento

1. **Requisição HTTP** → `gestor.php` recebe a requisição
2. **Carregamento** → Layout e página são carregados do banco de dados
3. **Detecção** → Sistema varre o HTML em busca de padrões `@[[...]]@`
4. **Substituição** → Cada variável é substituída pelo seu valor real
5. **Renderização** → HTML final é enviado ao navegador

---

## ⚡ Resumo Rápido

Referência rápida de todas as variáveis globais disponíveis no sistema:

### Variáveis de Página
1. `@[[pagina#corpo]]@` - Marca onde o conteúdo da página deve ser inserido no layout
2. `@[[pagina#titulo]]@` - Título da página (usado em `<title>` e breadcrumbs)
3. `@[[pagina#menu]]@` - Menu principal do sistema gerado dinamicamente
4. `@[[pagina#url-raiz]]@` - URL base do sistema (raiz da aplicação)
5. `@[[pagina#url-full-http]]@` - URL completa incluindo protocolo e domínio
6. `@[[pagina#url-caminho]]@` - Caminho relativo da página atual (sem domínio)
7. `@[[pagina#contato-url]]@` - URL da página de contato do sistema
8. `@[[pagina#modulo-id]]@` - ID do módulo associado à página atual
9. `@[[pagina#registro-id]]@` - ID do registro sendo visualizado/editado

### Variáveis de Usuário
10. `@[[usuario#nome]]@` - Nome completo do usuário autenticado

### Variáveis do Sistema
11. `@[[gestor#versao]]@` - Versão atual do Conn2Flow instalado

### Variáveis de Widgets
12. `@[[widgets#WIDGET_ID]]@` - Inclui um widget específico na página (substitua WIDGET_ID pelo identificador real)

---

## 📝 Sintaxe e Formato

### Formato Padrão
```
@[[CATEGORIA#IDENTIFICADOR]]@
```

### Componentes da Sintaxe

| Elemento | Descrição | Exemplo |
|----------|-----------|---------|
| `@[[` | Delimitador de abertura (segurança) | `@[[` |
| `CATEGORIA` | Tipo/função da variável | `pagina`, `usuario`, `gestor` |
| `#` | Separador categoria/identificador | `#` |
| `IDENTIFICADOR` | Nome específico da variável | `titulo`, `nome`, `versao` |
| `]]@` | Delimitador de fechamento (segurança) | `]]@` |

### Regras de Processamento

1. **Case-Sensitive**: `pagina#titulo` ≠ `pagina#Titulo`
2. **Ordem de Processamento**: Variáveis globais → Variáveis de módulo → Variáveis customizadas
3. **Proteção de Segurança**: Os delimitadores `@` são obrigatórios no backend (banco de dados)
4. **Interface Limpa**: No frontend (editor visual), o usuário vê apenas `[[...]]` (sem `@`)

### 🔄 Formato de Armazenamento vs. Formato de Edição

O sistema Conn2Flow utiliza **dois formatos diferentes** para as variáveis dependendo do contexto:

#### 📦 **Formato de Armazenamento (Backend/Banco de Dados)**
- **Formato**: `@[[CATEGORIA#IDENTIFICADOR]]@`
- **Contexto**: Banco de dados, arquivos de recursos, processamento interno
- **Exemplo**: `@[[pagina#titulo]]@`, `@[[usuario#nome]]@`
- **Função**: Formato seguro para armazenamento e processamento pelo sistema

#### ✏️ **Formato de Edição (Frontend/Usuário)**
- **Formato**: `[[CATEGORIA#IDENTIFICADOR]]` (sem os `@`)
- **Contexto**: Interface de edição, formulários, editores visuais
- **Exemplo**: `[[pagina#titulo]]`, `[[usuario#nome]]`
- **Função**: Interface limpa e amigável para o usuário

#### 🔄 **Fluxo de Conversão**

```
┌─────────────────────────────────────────────────────────┐
│  BANCO DE DADOS → FRONTEND (Carregar para Edição)      │
├─────────────────────────────────────────────────────────┤
│  @[[pagina#titulo]]@  →  [[pagina#titulo]]              │
│  (Remove delimitadores @)                                │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  FRONTEND → BANCO DE DADOS (Salvar Alterações)         │
├─────────────────────────────────────────────────────────┤
│  [[pagina#titulo]]  →  @[[pagina#titulo]]@              │
│  (Adiciona delimitadores @)                              │
└─────────────────────────────────────────────────────────┘
```

#### 🛠️ **Implementação Técnica**

O middleware de conversão é implementado nos módulos através de funções como `admin_templates_editar()` no arquivo `gestor/modulos/admin-templates/admin-templates.php`:

```php
// === AO CARREGAR DADOS DO BANCO (Backend → Frontend) ===
// Remove os @ para o usuário editar
$html_limpo = str_replace('@[[', '[[', $html_banco);
$html_limpo = str_replace(']]@', ']]', $html_limpo);

// === AO SALVAR NO BANCO (Frontend → Backend) ===
// Adiciona os @ antes de persistir
$open = $_GESTOR['variavel-global']['open'];      // '@[['
$close = $_GESTOR['variavel-global']['close'];    // ']]@'
$openText = $_GESTOR['variavel-global']['openText'];  // '[['
$closeText = $_GESTOR['variavel-global']['closeText']; // ']]'

$_REQUEST['html'] = preg_replace(
    "/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", 
    strtolower($open."$1".$close), 
    $_REQUEST['html']
);
```

#### ⚠️ **Regras Importantes**

1. **Backend (Banco/Recursos)**: SEMPRE use `@[[...]]@`
2. **Frontend (Interface Usuário)**: SEMPRE use `[[...]]` (sem `@`)
3. **Conversão Automática**: Os módulos devem implementar o middleware de conversão
4. **Processamento do Sistema**: O `gestor.php` processa apenas variáveis com `@[[...]]@`
5. **Recursos Físicos**: Arquivos `.html` e `.css` em `gestor/resources/` usam `@[[...]]@`

---

## 📚 Categorias de Variáveis

### Variáveis de Página (pagina#)

Variáveis relacionadas ao contexto da página atual e navegação.

#### `@[[pagina#corpo]]@`
- **Tipo**: Estrutural
- **Descrição**: Marca onde o conteúdo da página deve ser inserido no layout
- **Contexto**: Obrigatória em todos os layouts
- **Processamento**: Função `gestor_pagina_variaveis()`
- **Linha de código**: `gestor.php:451`
- **Exemplo**:
  ```html
  <div class="main-content">
      @[[pagina#corpo]]@
  </div>
  ```

#### `@[[pagina#titulo]]@`
- **Tipo**: Metadado
- **Descrição**: Título da página (usado em `<title>` e breadcrumbs)
- **Fonte**: Campo `titulo` da tabela `paginas`
- **Processamento**: Função `gestor_pagina_variaveis()`
- **Linha de código**: `gestor.php:447, 487`
- **Exemplo**:
  ```html
  <title>@[[pagina#titulo]]@ - Conn2Flow</title>
  <h1>@[[pagina#titulo]]@</h1>
  ```

#### `@[[pagina#menu]]@`
- **Tipo**: Componente Dinâmico
- **Descrição**: Menu principal do sistema (gerado dinamicamente)
- **Fonte**: Função `gestor_pagina_menu()` baseada em permissões
- **Processamento**: Carrega módulos, grupos e permissões do usuário
- **Linha de código**: `gestor.php:483`
- **Exemplo**:
  ```html
  <nav class="sidebar">
      @[[pagina#menu]]@
  </nav>
  ```

#### `@[[pagina#url-raiz]]@`
- **Tipo**: URL
- **Descrição**: URL base do sistema (raiz da aplicação)
- **Fonte**: Variável global `$_GESTOR['url-raiz']`
- **Processamento**: Configurado em `config.php`
- **Linha de código**: `gestor.php:484`
- **Exemplo**:
  ```html
  <link rel="stylesheet" href="@[[pagina#url-raiz]]@assets/style.css">
  <a href="@[[pagina#url-raiz]]@dashboard/">Dashboard</a>
  ```

#### `@[[pagina#url-full-http]]@`
- **Tipo**: URL
- **Descrição**: URL completa incluindo protocolo e domínio
- **Fonte**: Variável global `$_GESTOR['url-full-http']`
- **Uso**: Links absolutos, compartilhamento, APIs
- **Linha de código**: `gestor.php:485`
- **Exemplo**:
  ```html
  <meta property="og:url" content="@[[pagina#url-full-http]]@">
  ```

#### `@[[pagina#url-caminho]]@`
- **Tipo**: URL
- **Descrição**: Caminho relativo da página atual (sem domínio)
- **Fonte**: Variável `$_GESTOR['caminho-total']`
- **Processamento**: Normalizado com `/` no final
- **Linha de código**: `gestor.php:486`
- **Exemplo**:
  ```html
  <span class="breadcrumb">Você está em: @[[pagina#url-caminho]]@</span>
  ```

#### `@[[pagina#contato-url]]@`
- **Tipo**: URL
- **Descrição**: URL da página de contato do sistema
- **Fonte**: Variável `$_GESTOR['pagina#contato-url']`
- **Uso**: Links de suporte e contato
- **Linha de código**: `gestor.php:488`
- **Exemplo**:
  ```html
  <a href="@[[pagina#contato-url]]@">Entre em contato</a>
  ```

#### `@[[pagina#modulo-id]]@`
- **Tipo**: Identificador
- **Descrição**: ID do módulo associado à página atual
- **Fonte**: Variável `$_GESTOR['modulo-id']`
- **Condição**: Somente se página tiver módulo vinculado
- **Linha de código**: `gestor.php:497`
- **Exemplo**:
  ```html
  <div data-modulo="@[[pagina#modulo-id]]@">
      <!-- Conteúdo do módulo -->
  </div>
  ```

#### `@[[pagina#registro-id]]@`
- **Tipo**: Identificador
- **Descrição**: ID do registro sendo visualizado/editado
- **Fonte**: Variável `$_GESTOR['modulo-registro-id']`
- **Condição**: Somente em páginas de edição/visualização
- **Linha de código**: `gestor.php:498`
- **Exemplo**:
  ```html
  <form action="salvar/@[[pagina#registro-id]]@/" method="post">
      <!-- Campos do formulário -->
  </form>
  ```

---

### Variáveis de Usuário (usuario#)

Variáveis relacionadas ao usuário autenticado no sistema.

#### `@[[usuario#nome]]@`
- **Tipo**: Dados do Usuário
- **Descrição**: Nome completo do usuário autenticado
- **Fonte**: Função `gestor_usuario()` → Campo `nome` da tabela `usuarios`
- **Processamento**: Carregado da sessão ativa
- **Linha de código**: `gestor.php:495`
- **Exemplo**:
  ```html
  <div class="user-profile">
      Bem-vindo, <strong>@[[usuario#nome]]@</strong>
  </div>
  ```

---

### Variáveis do Sistema (gestor#)

Variáveis relacionadas ao sistema Conn2Flow como um todo.

#### `@[[gestor#versao]]@`
- **Tipo**: Informação do Sistema
- **Descrição**: Versão atual do Conn2Flow instalado
- **Fonte**: Variável global `$_GESTOR['versao']`
- **Formato**: Semantic Versioning (ex: `1.2.3`)
- **Linha de código**: `gestor.php:489`
- **Exemplo**:
  ```html
  <footer>
      Conn2Flow v@[[gestor#versao]]@ - © 2026
  </footer>
  ```

---

### Variáveis de Widgets (widgets#)

Variáveis para inclusão de widgets dinâmicos do sistema.

#### `@[[widgets#WIDGET_ID]]@`
- **Tipo**: Componente Dinâmico
- **Descrição**: Inclui um widget específico na página
- **Fonte**: Função `widgets_get()` da biblioteca `widgets.php`
- **Processamento**: Sistema detecta padrão e busca widget no banco
- **Linha de código**: `gestor.php:460-476`
- **ID Dinâmico**: Substitua `WIDGET_ID` pelo identificador real do widget
- **Exemplo**:
  ```html
  <div class="dashboard-stats">
      @[[widgets#estatisticas-vendas]]@
  </div>
  ```

#### Fluxo de Processamento de Widgets

1. **Detecção**: Regex busca padrão `@[[widgets#(.+?)]]@`
2. **Biblioteca**: Sistema carrega `gestor/bibliotecas/widgets.php`
3. **Busca**: Função `widgets_get(Array('id' => $match))` busca widget
4. **Substituição**: Se widget existe, substitui marcador pelo HTML do widget
5. **Renderização**: Widget é renderizado na página

---

## 🔍 Referência Técnica

### Localização no Código

#### Função Principal: `gestor_pagina_variaveis()`
- **Arquivo**: `gestor/gestor.php`
- **Linha**: 432-560
- **Responsabilidade**: Processar e substituir todas as variáveis globais

#### Ordem de Processamento

```php
// 1. Variáveis estruturais (titulo, corpo)
$layout = modelo_var_troca($layout, '<!-- pagina#titulo -->', ...);
$_GESTOR['pagina'] = modelo_var_troca($layout, '@[[pagina#corpo]]@', ...);

// 2. Widgets dinâmicos
preg_match_all("/\@\[\[widgets#(.+?)\]\]@/i", $_GESTOR['pagina'], $matchesWidgets);

// 3. Variáveis de página e sistema
$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '@[[pagina#menu]]@', ...);
$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '@[[usuario#nome]]@', ...);

// 4. Variáveis globais customizadas
$valor = gestor_variaveis_globais(Array('id' => $match));

// 5. Variáveis de módulo específico
$valor = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'], 'id' => $match));
```

### Funções Auxiliares

| Função | Descrição |
|--------|-----------|
| `modelo_var_troca()` | Substitui primeira ocorrência da variável |
| `modelo_var_troca_tudo()` | Substitui todas as ocorrências da variável |
| `gestor_variaveis_globais()` | Busca variável global no banco de dados |
| `gestor_variaveis()` | Busca variável específica de módulo |
| `gestor_usuario()` | Retorna dados do usuário autenticado |
| `gestor_pagina_menu()` | Gera menu dinâmico baseado em permissões |

### Tabelas do Banco de Dados

| Tabela | Relação com Variáveis |
|--------|----------------------|
| `paginas` | Fornece `titulo`, `caminho`, conteúdo HTML |
| `layouts` | Fornece estrutura base com `@[[pagina#corpo]]@` |
| `usuarios` | Fornece dados do usuário (`nome`, etc) |
| `variaveis` | Armazena variáveis customizadas globais e de módulos |
| `modulos` | Define módulos e suas permissões |

---

## 💡 Exemplos de Uso

### Exemplo 1: Layout Base com Variáveis

```html
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>@[[pagina#titulo]]@ - Sistema Conn2Flow</title>
    <meta property="og:url" content="@[[pagina#url-full-http]]@">
    <link rel="stylesheet" href="@[[pagina#url-raiz]]@assets/css/main.css">
</head>
<body>
    <header>
        <nav>@[[pagina#menu]]@</nav>
        <div class="user-info">
            Olá, <strong>@[[usuario#nome]]@</strong>
        </div>
    </header>
    
    <main class="container">
        <h1>@[[pagina#titulo]]@</h1>
        @[[pagina#corpo]]@
    </main>
    
    <footer>
        <p>Conn2Flow v@[[gestor#versao]]@ - Todos os direitos reservados</p>
        <a href="@[[pagina#contato-url]]@">Contato</a>
    </footer>
</body>
</html>
```

### Exemplo 2: Página com Widgets

```html
<div class="dashboard">
    <h2>Dashboard Principal</h2>
    
    <div class="widgets-row">
        @[[widgets#total-vendas]]@
        @[[widgets#usuarios-ativos]]@
        @[[widgets#pendencias]]@
    </div>
    
    <div class="content-area">
        <p>Você está em: @[[pagina#url-caminho]]@</p>
        <!-- Conteúdo da página -->
    </div>
</div>
```

### Exemplo 3: Formulário de Edição

```html
<form action="@[[pagina#url-raiz]]@modulo/salvar/@[[pagina#registro-id]]@/" method="post">
    <input type="hidden" name="modulo-id" value="@[[pagina#modulo-id]]@">
    
    <div class="form-group">
        <label>Nome:</label>
        <input type="text" name="nome" required>
    </div>
    
    <button type="submit">Salvar Alterações</button>
    <a href="@[[pagina#url-raiz]]@modulo/listar/">Cancelar</a>
</form>
```

### Exemplo 4: Breadcrumb Dinâmico

```html
<nav class="breadcrumb">
    <a href="@[[pagina#url-raiz]]@">Home</a>
    <span class="separator">/</span>
    <span class="current">@[[pagina#url-caminho]]@</span>
</nav>
```

### Exemplo 5: Conversão de Variáveis (Backend ↔ Frontend)

#### Cenário: Edição de Template no Módulo admin-templates

```php
// PASSO 1: Carregar do Banco (Backend)
$template_db = banco_select([
    'tabela' => 'templates',
    'campos' => ['html', 'css'],
    'extra' => "WHERE id='meu-template'"
]);

// Conteúdo no banco: @[[pagina#titulo]]@ e @[[usuario#nome]]@
echo $template_db['html']; 
// Output: <h1>@[[pagina#titulo]]@</h1><p>Olá @[[usuario#nome]]@</p>

// PASSO 2: Converter para Frontend (Remove @)
$html_frontend = str_replace('@[[', '[[', $template_db['html']);
$html_frontend = str_replace(']]@', ']]', $html_frontend);

echo $html_frontend;
// Output: <h1>[[pagina#titulo]]</h1><p>Olá [[usuario#nome]]</p>
// ↑ Usuário edita neste formato

// PASSO 3: Usuário Edita e Salva
$_POST['html'] = '<h1>[[pagina#titulo]]</h1><p>Bem-vindo [[usuario#nome]]</p>';

// PASSO 4: Converter para Backend (Adiciona @)
$open = '@[[';
$close = ']]@';
$openText = '[[';
$closeText = ']]';

$html_backend = preg_replace(
    "/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/",
    strtolower($open."$1".$close),
    $_POST['html']
);

echo $html_backend;
// Output: <h1>@[[pagina#titulo]]@</h1><p>Bem-vindo @[[usuario#nome]]@</p>
// ↑ Salvo no banco neste formato

// PASSO 5: Sistema Processa Automaticamente
// gestor.php detecta @[[...]]@ e substitui pelos valores reais:
// <h1>Dashboard Principal</h1><p>Bem-vindo João Silva</p>
```

---

## 🔐 Segurança e Boas Práticas

### Delimitadores de Segurança

Os delimitadores `@` servem para:
1. **Identificação Única**: Evitar conflitos com texto comum
2. **Processamento Seguro**: Garantir que apenas variáveis válidas sejam processadas
3. **Proteção contra XSS**: Sistema valida e sanitiza valores antes da substituição
4. **Separação de Contextos**: Diferencia armazenamento seguro (`@[[...]]@`) de edição amigável (`[[...]]`)

### Arquitetura de Segurança

#### 🔒 **Backend (Armazenamento Seguro)**
- Variáveis protegidas com `@[[...]]@`
- Processamento restrito pelo sistema
- Validação em tempo de execução
- Proteção contra injeção de código

#### ✏️ **Frontend (Interface do Usuário)**
- Variáveis limpas `[[...]]` para melhor UX
- Conversão automática via middleware
- Validação antes de persistir
- Sanitização de entrada

### Boas Práticas

✅ **FAZER:**
- Usar variáveis para conteúdo dinâmico
- Manter sintaxe exata (case-sensitive)
- Documentar variáveis customizadas criadas em módulos
- Testar variáveis após criação/modificação

❌ **NÃO FAZER:**
- Criar variáveis com nomes genéricos que conflitem com as globais
- Incluir código PHP dentro de variáveis
- Modificar delimitadores `@[[` e `]]@` no frontend
- Processar variáveis manualmente sem usar as funções do sistema

---

## 📖 Referências

- **Documentação Geral**: `ai-workspace/pt-br/docs/CONN2FLOW-GESTOR-DETALHAMENTO.md`
- **Sistema de Templates**: `ai-workspace/pt-br/docs/CONN2FLOW-LAYOUTS-PAGINAS-COMPONENTES.md`
- **Código Fonte**: `gestor/gestor.php` (função `gestor_pagina_variaveis()`)
- **Biblioteca de Modelos**: `gestor/bibliotecas/modelo.php`
- **Biblioteca de Widgets**: `gestor/bibliotecas/widgets.php`

---

**Última atualização:** 26 de janeiro de 2026  
**Versão do Sistema:** Conn2Flow 2.5.x  
**Autor:** Documentação Técnica Conn2Flow
