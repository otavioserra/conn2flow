# Módulo: admin-arquivos

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `admin-arquivos` |
| **Nome** | Administração de Arquivos |
| **Versão** | `1.1.0` |
| **Categoria** | Módulo Administrativo |
| **Complexidade** | 🔴 Alta |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html` |

## 🎯 Propósito

O módulo **admin-arquivos** é o **sistema central de gerenciamento de arquivos e mídias** do Conn2Flow. Responsável por toda a funcionalidade de upload, organização, categorização e gerenciamento de arquivos digitais (imagens, vídeos, documentos, etc.).

## 🏗️ Funcionalidades Principais

### 📤 **Sistema de Upload**
- **Upload múltiplo**: Suporte a vários arquivos simultaneamente
- **Validação de tamanho**: Limite máximo de 10MB por arquivo
- **Tipos suportados**: Imagens, vídeos, áudios, documentos
- **Geração automática de thumbnails**: Para imagens e vídeos
- **Organização por data**: Estrutura automática `YYYY/MM/`
- **Nomes únicos**: Sistema de identificadores únicos para evitar conflitos

### 🗂️ **Organização e Categorização**
- **Categorias múltiplas**: Um arquivo pode pertencer a várias categorias
- **Filtros avançados**: Por data, categoria, tipo, nome
- **Ordenação flexível**: Alfabética, por data (crescente/decrescente)
- **Paginação**: 100 arquivos por página
- **Busca rápida**: Sistema de pesquisa em tempo real

### 🖼️ **Visualização e Gestão**
- **Preview inteligente**: Miniaturas automáticas para diferentes tipos
- **Informações detalhadas**: Nome, data, tipo, tamanho
- **Ações rápidas**: Copiar URL, selecionar, editar, excluir
- **Modal responsivo**: Interface adaptável para diferentes dispositivos
- **Suporte iframe**: Integração em popups e modais

### 🔗 **Integração com Sistema**
- **CKEditor**: Integração nativa com editor de texto
- **Seletor de arquivos**: Modal para seleção em outros módulos
- **API REST**: Endpoints para operações programáticas
- **Links permanentes**: URLs estáveis para arquivos

## 🗄️ Estrutura de Banco de Dados

### Tabela Principal: `arquivos`
```sql
CREATE TABLE arquivos (
    id_arquivos INT AUTO_INCREMENT PRIMARY KEY,
    id_usuarios INT NOT NULL,                 -- Usuário que fez upload
    nome VARCHAR(255) NOT NULL,               -- Nome original do arquivo
    id VARCHAR(255) UNIQUE NOT NULL,          -- Identificador único
    tipo VARCHAR(100),                        -- MIME type
    caminho VARCHAR(500),                     -- Caminho do arquivo
    caminho_mini VARCHAR(500),                -- Caminho da miniatura
    status CHAR(1) DEFAULT 'A',               -- Status (A=Ativo, D=Deletado)
    versao INT DEFAULT 1,                     -- Controle de versão
    data_criacao DATETIME DEFAULT NOW(),      -- Data de upload
    data_modificacao DATETIME DEFAULT NOW(),   -- Última modificação
    INDEX idx_status (status),
    INDEX idx_usuario (id_usuarios),
    INDEX idx_tipo (tipo),
    FOREIGN KEY (id_usuarios) REFERENCES usuarios(id_usuarios)
);
```

### Tabela de Relacionamento: `arquivos_categorias`
```sql
CREATE TABLE arquivos_categorias (
    id_arquivos INT NOT NULL,
    id_categorias INT NOT NULL,
    PRIMARY KEY (id_arquivos, id_categorias),
    FOREIGN KEY (id_arquivos) REFERENCES arquivos(id_arquivos),
    FOREIGN KEY (id_categorias) REFERENCES categorias(id_categorias)
);
```

## 📁 Estrutura de Arquivos

### Organização Física
```
contents/files/
├── YYYY/                    # Ano
│   └── MM/                  # Mês
│       ├── arquivo.ext      # Arquivo original
│       └── mini/            # Miniaturas
│           └── arquivo.ext  # Thumbnail
```

### Códigos Principais

#### 🔧 **Funções PHP Core**

##### `admin_arquivos_lista($params)`
Função principal para listagem de arquivos com suporte a filtros, paginação e categorização.

**Parâmetros:**
- `pagina` (string): Template onde será renderizada a lista

**Funcionalidades:**
- Aplicação de filtros (data, categoria, ordenação)
- Paginação com 100 itens por página
- Agrupamento por categorias
- Suporte AJAX para carregamento dinâmico
- Geração de URLs e miniaturas

##### `admin_arquivos_ajax_upload_file()`
Processamento AJAX de upload de arquivos.

**Funcionalidades:**
- Validação de tamanho (máx. 10MB)
- Geração de identificador único
- Criação de estrutura de diretórios
- Processamento de miniaturas
- Inserção no banco de dados
- Retorno JSON com status

##### `admin_arquivos_criar_dir_herdando_permissao($dir)`
Criação de diretórios com herança de permissões do diretório pai.

#### 🖥️ **JavaScript Core**

##### Sistema de Upload jQuery File Upload
```javascript
$('#fileupload').fileupload({
    url: 'admin-arquivos/?ajax=upload_file',
    dataType: 'json',
    maxFileSize: 10000000, // 10MB
    acceptFileTypes: /(\.|\/)(gif|jpe?g|png|mp4|pdf|doc|docx)$/i
});
```

##### Sistema de Filtros
```javascript
// Filtros por data, categoria e ordenação
var filtros = {
    dataDe: $('#rangestart').calendar('get date'),
    dataAte: $('#rangeend').calendar('get date'),
    categorias: $('#categories').dropdown('get value'),
    order: $('#order').dropdown('get value')
};
```

##### Paginação AJAX
```javascript
function carregarMaisArquivos() {
    $.post('admin-arquivos/?ajax=lista_mais_resultados', {
        pagina: listaPaginaAtual + 1,
        filtros: JSON.stringify(filtros)
    }, function(data) {
        $('#files-list-cont').append(data.pagina);
        listaPaginaAtual++;
    });
}
```

## 🎨 Interface de Usuário

### 📱 **Layout Responsivo**
- **Desktop**: Grid de 4 colunas com thumbnails grandes
- **Tablet**: Grid de 3 colunas com thumbnails médios  
- **Mobile**: Lista vertical com thumbnails pequenos

### 🔽 **Área de Upload (Drag & Drop)**
```html
<div class="upload-zone">
    <div class="dz-message">
        <i class="cloud upload icon"></i>
        <p>Arraste arquivos aqui ou clique para selecionar</p>
    </div>
</div>
```

### 🗃️ **Lista de Arquivos**
```html
<div class="files-grid">
    <div class="file-card" data-file-id="123">
        <div class="thumbnail">
            <img src="files/2024/08/mini/imagem.jpg" alt="Imagem">
        </div>
        <div class="file-info">
            <h4>imagem.jpg</h4>
            <span class="file-date">31/08/2024 15:30</span>
            <span class="file-type">image/jpeg</span>
        </div>
        <div class="file-actions">
            <button class="btn-copy" data-url="files/2024/08/imagem.jpg">
                <i class="copy icon"></i> Copiar URL
            </button>
            <button class="btn-select">
                <i class="check icon"></i> Selecionar
            </button>
        </div>
    </div>
</div>
```

### 🎛️ **Filtros Avançados**
```html
<div class="filters-panel">
    <div class="ui form">
        <div class="fields">
            <div class="field">
                <label>Período</label>
                <div class="ui calendar" id="rangestart">
                    <input type="text" placeholder="Data inicial">
                </div>
            </div>
            <div class="field">
                <div class="ui calendar" id="rangeend">
                    <input type="text" placeholder="Data final">
                </div>
            </div>
            <div class="field">
                <label>Categorias</label>
                <select id="categories" class="ui multiple dropdown">
                    <option value="">Todas as categorias</option>
                </select>
            </div>
            <div class="field">
                <label>Ordenação</label>
                <select id="order" class="ui dropdown">
                    <option value="alphabetical-asc">A-Z</option>
                    <option value="alphabetical-desc">Z-A</option>
                    <option value="order-date-asc">Mais antigos</option>
                    <option value="order-date-desc">Mais recentes</option>
                </select>
            </div>
        </div>
        <div class="buttons">
            <button class="ui button filterButton">Filtrar</button>
            <button class="ui button clearButton">Limpar</button>
        </div>
    </div>
</div>
```

## 🔌 Integração com Outros Módulos

### 📝 **CKEditor (Editor de Texto)**
O módulo se integra nativamente com o CKEditor para inserção de imagens:

```javascript
CKEDITOR.config.filebrowserBrowseUrl = 'admin-arquivos/?paginaIframe=sim';
CKEDITOR.config.filebrowserImageBrowseUrl = 'admin-arquivos/?paginaIframe=sim&tipo=imagem';
```

### 🏷️ **Módulo Categorias**
Integração bidirecional para categorização de arquivos:

```php
// Buscar categorias disponíveis
$categorias = banco_select_name(
    "nome, id_categorias",
    "categorias",
    "WHERE id_modulos='11' OR id_modulos IS NULL"
);

// Associar arquivo a categorias
foreach($_POST['categorias'] as $id_categoria) {
    banco_insert_name([
        ['id_arquivos', $id_arquivo],
        ['id_categorias', $id_categoria]
    ], 'arquivos_categorias');
}
```

### 👥 **Sistema de Usuários**
Controle de propriedade e permissões:

```php
// Verificar permissão de upload
if(!gestor_usuario_permissao('admin-arquivos', 'upload')) {
    return gestor_erro('Sem permissão para upload');
}

// Registrar proprietário do arquivo
$id_usuario = gestor_usuario()['id_usuarios'];
```

## ⚙️ Configurações e Parâmetros

### 📐 **Limites e Validações**
```php
// Configurações de upload
define('MAX_FILE_SIZE', 10000000);           // 10MB
define('ALLOWED_TYPES', [
    'image/jpeg', 'image/png', 'image/gif',   // Imagens
    'video/mp4', 'video/webm',                // Vídeos
    'audio/mp3', 'audio/wav',                 // Áudios
    'application/pdf',                        // Documentos
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);
```

### 🗂️ **Estrutura de Diretórios**
```php
// Configuração automática de pastas
$basedir = 'files';
$thumbnail = 'mini';
$caminho = $basedir . '/' . date('Y') . '/' . date('m') . '/';
$caminho_mini = $caminho . $thumbnail . '/';
```

### 📊 **Paginação**
```php
// Configuração de listagem
$max_dados_por_pagina = 100;
$total_paginas = ceil($total_arquivos / $max_dados_por_pagina);
```

## 🛡️ Segurança

### 🔒 **Validações de Upload**
- **Verificação de tipo MIME**: Apenas tipos permitidos
- **Validação de extensão**: Dupla verificação
- **Limite de tamanho**: Proteção contra uploads grandes
- **Sanitização de nomes**: Remoção de caracteres perigosos
- **Verificação de propriedade**: Usuário autenticado

### 🗂️ **Proteção de Diretórios**
```php
// Criação segura de diretórios
function admin_arquivos_criar_dir_herdando_permissao($dir) {
    if (!is_dir($dir)) {
        $pai = dirname($dir);
        $permissao_pai = is_dir($pai) ? fileperms($pai) & 0777 : 0755;
        mkdir($dir, $permissao_pai, true);
    }
}
```

### 🚫 **Prevenção de Ataques**
- **SQL Injection**: Uso de `banco_escape_field()`
- **XSS**: Sanitização de dados de entrada
- **Path Traversal**: Validação de caminhos
- **Upload de Scripts**: Verificação rigorosa de tipos

## 📈 Performance e Otimização

### ⚡ **Estratégias de Performance**
- **Lazy Loading**: Carregamento sob demanda de miniaturas
- **Paginação AJAX**: Evita recarregamento completo da página
- **Cache de Thumbnails**: Geração única e reutilização
- **Índices de Banco**: Otimização de consultas
- **Compressão de Imagens**: Redução automática de qualidade

### 🗃️ **Cache e Armazenamento**
```php
// Sistema de cache de miniaturas
if (!file_exists($caminho_arquivo_mini)) {
    $imagem_redimensionada = imagescale($imagem_original, 300, 300);
    imagejpeg($imagem_redimensionada, $caminho_arquivo_mini, 80);
}
```

## 🔧 APIs e Endpoints

### 📡 **Endpoints AJAX**
| Endpoint | Método | Função |
|----------|--------|--------|
| `?ajax=upload_file` | POST | Upload de arquivo único |
| `?ajax=lista_mais_resultados` | POST | Paginação de arquivos |
| `?ajax=deletar_arquivo` | POST | Exclusão de arquivo |
| `?ajax=atualizar_categorias` | POST | Atualização de categorias |

### 📥 **Exemplo de Uso da API**
```javascript
// Upload via AJAX
var formData = new FormData();
formData.append('files[]', file);

$.ajax({
    url: 'admin-arquivos/?ajax=upload_file',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(response) {
        if(response.status === 'Ok') {
            console.log('Upload realizado:', response.arquivo);
        }
    }
});
```

## 🧪 Testes e Validação

### ✅ **Casos de Teste**
- **Upload básico**: Imagem JPG de 2MB
- **Upload múltiplo**: 5 arquivos de tipos diferentes
- **Validação de tamanho**: Arquivo de 15MB (deve falhar)
- **Filtros**: Busca por categoria específica
- **Paginação**: Navegação entre páginas
- **Exclusão**: Remoção de arquivo e cleanup

### 🐛 **Problemas Conhecidos**
- **Timeout em uploads grandes**: Configurar `max_execution_time`
- **Permissões de diretório**: Verificar propriedade www-data
- **Cache de thumbnails**: Limpeza manual ocasionalmente necessária

## 📊 Métricas e Monitoramento

### 📈 **KPIs do Módulo**
- **Arquivos totais**: Quantidade de arquivos no sistema
- **Uploads por dia**: Taxa de crescimento do repositório
- **Tipos mais usados**: Estatísticas de formato
- **Erros de upload**: Taxa de falhas
- **Uso de storage**: Espaço em disco utilizado

### 📋 **Logs Importantes**
```php
// Log de uploads
error_log("Upload realizado: {$nome} por usuário {$id_usuario}");

// Log de erros
error_log("Erro no upload: {$erro} - arquivo: {$nome}");
```

## 🚀 Roadmap e Melhorias

### ✅ **Implementado (v1.1.0)**
- Sistema básico de upload
- Categorização múltipla
- Filtros avançados
- Interface responsiva
- Integração CKEditor

### 🚧 **Em Desenvolvimento (v1.2.0)**
- Editor de imagens integrado
- Suporte a vídeos HTML5
- Sincronização com cloud storage
- API REST completa
- Compressão automática

### 🔮 **Planejado (v2.0.0)**
- Reconhecimento automático de conteúdo (AI)
- Versionamento de arquivos
- Colaboração em tempo real
- CDN integrado
- Backup automático

## 🔗 Dependências

### 📚 **Bibliotecas JavaScript**
- **jQuery File Upload 10.31.0**: Sistema de upload
- **Semantic UI**: Interface de usuário
- **Calendar JS**: Seletores de data
- **Modal Manager**: Sistema de modais

### 🔧 **Bibliotecas PHP**
- **GD Extension**: Processamento de imagens
- **cURL**: Comunicação HTTP
- **ZIP Extension**: Compressão de arquivos
- **OpenSSL**: Segurança

### 🗄️ **Banco de Dados**
- **MySQL 5.7+**: Banco principal
- **Indices otimizados**: Performance de consultas
- **Foreign Keys**: Integridade referencial

## 📖 Conclusão

O módulo **admin-arquivos** é uma peça fundamental do Conn2Flow, oferecendo um sistema robusto e completo para gerenciamento de arquivos digitais. Com interface moderna, funcionalidades avançadas e integração profunda com o sistema, representa um dos módulos mais sofisticados da plataforma.

**Características principais:**
- ✅ **Interface intuitiva** com drag & drop
- ✅ **Performance otimizada** com lazy loading e cache
- ✅ **Segurança robusta** com validações múltiplas
- ✅ **Integração nativa** com editores e outros módulos
- ✅ **Escalabilidade** preparada para grandes volumes

**Status**: ✅ **Produção - Maduro e Estável**  
**Mantenedores**: Equipe Core Conn2Flow  
**Última atualização**: 31 de agosto, 2025
