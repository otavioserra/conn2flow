# Módulo: variaveis

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `variaveis` |
| **Nome** | Gerenciamento de Variáveis |
| **Versão** | `1.0.0` |
| **Categoria** | Módulo Core |
| **Complexidade** | 🟡 Média |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html` |

## 🎯 Propósito

O módulo **variaveis** gerencia as **variáveis dinâmicas do sistema** no Conn2Flow. Variáveis são valores reutilizáveis que podem ser inseridos em páginas, layouts e componentes usando a sintaxe `@[[variavel#nome-da-variavel]]@`. Isso permite centralizar conteúdo que aparece em múltiplos lugares.

## 🏗️ Funcionalidades Principais

### 📝 **Gerenciamento de Variáveis**
- **Criar variáveis**: Definir novos valores reutilizáveis
- **Editar variáveis**: Modificar valores existentes
- **Categorizar**: Organizar variáveis por categoria
- **Buscar**: Localizar variáveis rapidamente

### 🌐 **Multi-idioma**
- **Por idioma**: Valores diferentes por idioma
- **Fallback**: Valor padrão se idioma não disponível
- **Sincronização**: Manter traduções alinhadas

### 🔗 **Tipos de Variáveis**
- **Texto**: Strings simples
- **HTML**: Conteúdo formatado
- **JSON**: Dados estruturados
- **Configuração**: Valores de sistema

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `variaveis`
```sql
CREATE TABLE variaveis (
    id_variaveis INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    valor TEXT,                          -- Valor da variável
    tipo VARCHAR(50) DEFAULT 'texto',    -- texto, html, json, config
    categoria VARCHAR(100),              -- Categoria organizacional
    idioma VARCHAR(10) DEFAULT 'pt-br',  -- Idioma do valor
    descricao TEXT,                      -- Descrição de uso
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/variaveis/
├── variaveis.php                # Controlador principal
├── variaveis.js                 # Funcionalidade client-side
├── variaveis.json               # Configuração do módulo
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   └── modal-variavel/
    │   └── pages/
    │       ├── variaveis/
    │       ├── variaveis-adicionar/
    │       └── variaveis-editar/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Sintaxe de Variáveis

### Uso Básico
```html
<!-- Em qualquer página, layout ou componente -->
<p>@[[variavel#nome-da-empresa]]@</p>
<p>Email: @[[variavel#email-contato]]@</p>
```

### Variáveis do Sistema
```html
<!-- Variáveis automáticas -->
@[[sistema#versao]]@          <!-- Versão do Conn2Flow -->
@[[sistema#ano-atual]]@       <!-- Ano atual -->
@[[sistema#data-atual]]@      <!-- Data atual formatada -->
@[[usuario#nome]]@            <!-- Nome do usuário logado -->
@[[pagina#titulo]]@           <!-- Título da página atual -->
```

### Variáveis com Fallback
```html
<!-- Se variável não existir, usa valor padrão -->
@[[variavel#saudacao|Olá, visitante!]]@
```

## 🎨 Interface do Usuário

### Lista de Variáveis
- Tabela com busca e filtros
- Agrupamento por categoria
- Preview do valor
- Ações rápidas (copiar sintaxe, editar)

### Formulário de Edição
- **Nome/ID**: Identificador único
- **Valor**: Editor apropriado ao tipo
- **Tipo**: Texto, HTML, JSON, Config
- **Categoria**: Dropdown ou texto livre
- **Idioma**: Seleção de idioma
- **Descrição**: Explicação de uso

## 🔄 Processamento de Variáveis

### Fluxo de Substituição
```php
function processarVariaveis($conteudo, $idioma = 'pt-br') {
    // Pattern para encontrar variáveis
    $pattern = '/@\[\[variavel#([a-zA-Z0-9-_]+)(?:\|([^\]]*))?\]\]@/';
    
    return preg_replace_callback($pattern, function($matches) use ($idioma) {
        $id = $matches[1];
        $fallback = $matches[2] ?? '';
        
        // Buscar variável
        $variavel = buscar('variaveis', [
            'id' => $id,
            'idioma' => $idioma,
            'status' => 'A'
        ]);
        
        return $variavel ? $variavel['valor'] : $fallback;
    }, $conteudo);
}
```

## 📊 Categorias Comuns

### Contato
```
empresa-nome
empresa-endereco
empresa-telefone
empresa-email
empresa-cnpj
```

### Redes Sociais
```
url-facebook
url-instagram
url-linkedin
url-youtube
url-twitter
```

### SEO
```
meta-description-padrao
meta-keywords-padrao
og-image-padrao
```

### Textos do Site
```
slogan
descricao-empresa
copyright
mensagem-cookies
```

## 💡 Boas Práticas

### Nomenclatura
- Use kebab-case: `nome-da-variavel`
- Prefixe por categoria: `contato-email`, `social-facebook`
- Seja descritivo e consistente

### Organização
- Agrupe por uso/categoria
- Documente propósito de cada variável
- Mantenha valores atualizados

### Uso
- Prefira variáveis a texto hardcoded
- Use para conteúdo que muda frequentemente
- Considere multi-idioma desde o início

## ⚠️ Notas Importantes
- Variáveis são processadas em tempo de renderização
- Cache pode afetar atualização imediata
- Evite variáveis com valores muito grandes

## 🔗 Módulos Relacionados
- `admin-componentes`: Componentes que usam variáveis
- `admin-layouts`: Layouts que usam variáveis
- `admin-paginas`: Páginas que usam variáveis
