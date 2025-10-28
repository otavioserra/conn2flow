# Documentação das Bibliotecas Conn2Flow

> 📚 Documentação completa das 26 bibliotecas PHP do sistema Conn2Flow

## Visão Geral

Este diretório contém a documentação detalhada de todas as bibliotecas (libraries) do sistema Conn2Flow localizadas em `gestor/bibliotecas/`. As bibliotecas fornecem funcionalidades essenciais para o funcionamento do CMS, desde operações de banco de dados até gerenciamento de usuários e integrações com IA.

## Bibliotecas Disponíveis

### 📊 Bibliotecas Core do Sistema

| Biblioteca | Funções | Descrição | Documentação |
|-----------|---------|-----------|--------------|
| **banco.php** | 45 | Operações de banco de dados MySQL/MySQLi | [📖 Docs](./BIBLIOTECA-BANCO.md) |
| **gestor.php** | 24 | Funções principais do CMS | [📖 Docs](./BIBLIOTECA-GESTOR.md) |
| **autenticacao.php** | 18 | Autenticação e segurança | [📖 Docs](./BIBLIOTECA-AUTENTICACAO.md) |
| **configuracao.php** | 4 | Gerenciamento de configurações | [📖 Docs](./BIBLIOTECA-CONFIGURACAO.md) |

### 🎨 Bibliotecas de Interface e Apresentação

| Biblioteca | Funções | Descrição | Documentação |
|-----------|---------|-----------|--------------|
| **interface.php** | 52 | Componentes de interface do usuário | [📖 Docs](./BIBLIOTECA-INTERFACE.md) |
| **html.php** | 8 | Geração de HTML | [📖 Docs](./BIBLIOTECA-HTML.md) |
| **widgets.php** | 4 | Componentes de widgets | [📖 Docs](./BIBLIOTECA-WIDGETS.md) |
| **formulario.php** | 5 | Geração e validação de formulários | [📖 Docs](./BIBLIOTECA-FORMULARIO.md) |

### 📄 Bibliotecas de Conteúdo e Dados

| Biblioteca | Funções | Descrição | Documentação |
|-----------|---------|-----------|--------------|
| **pagina.php** | 7 | Gerenciamento de páginas | [📖 Docs](./BIBLIOTECA-PAGINA.md) |
| **modelo.php** | 10 | Templates e modelos | [📖 Docs](./BIBLIOTECA-MODELO.md) |
| **formato.php** | 12 | Formatação de dados | [📖 Docs](./BIBLIOTECA-FORMATO.md) |
| **variaveis.php** | 3 | Gerenciamento de variáveis | [📖 Docs](./BIBLIOTECA-VARIAVEIS.md) |

### 👤 Bibliotecas de Usuários e Comunicação

| Biblioteca | Funções | Descrição | Documentação |
|-----------|---------|-----------|--------------|
| **usuario.php** | 6 | Gerenciamento de usuários | [📖 Docs](./BIBLIOTECA-USUARIO.md) |
| **comunicacao.php** | 2 | Comunicação e mensagens | [📖 Docs](./BIBLIOTECA-COMUNICACAO.md) |
| **log.php** | 5 | Sistema de logs | [📖 Docs](./BIBLIOTECA-LOG.md) |

### 🔌 Bibliotecas de Plugins e Extensões

| Biblioteca | Funções | Descrição | Documentação |
|-----------|---------|-----------|--------------|
| **plugins-installer.php** | 43 | Sistema de instalação de plugins | [📖 Docs](./BIBLIOTECA-PLUGINS-INSTALLER.md) |
| **plugins.php** | 1 | Utilitários de plugins | [📖 Docs](./BIBLIOTECA-PLUGINS.md) |
| **plugins-consts.php** | 0 | Constantes de plugins | [📖 Docs](./BIBLIOTECA-PLUGINS-CONSTS.md) |

### 🤖 Bibliotecas de Integração

| Biblioteca | Funções | Descrição | Documentação |
|-----------|---------|-----------|--------------|
| **ia.php** | 9 | Integração com IA (Gemini API) | [📖 Docs](./BIBLIOTECA-IA.md) |
| **pdf.php** | 1 | Geração de PDFs | [📖 Docs](./BIBLIOTECA-PDF.md) |
| **ftp.php** | 4 | Operações FTP | [📖 Docs](./BIBLIOTECA-FTP.md) |

### 🛠️ Bibliotecas Utilitárias

| Biblioteca | Funções | Descrição | Documentação |
|-----------|---------|-----------|--------------|
| **geral.php** | 1 | Funções gerais | [📖 Docs](./BIBLIOTECA-GERAL.md) |
| **arquivo.php** | 0 | Operações de arquivo | [📖 Docs](./BIBLIOTECA-ARQUIVO.md) |
| **host.php** | 3 | Utilitários de host | [📖 Docs](./BIBLIOTECA-HOST.md) |
| **ip.php** | 2 | Utilitários de IP | [📖 Docs](./BIBLIOTECA-IP.md) |
| **lang.php** | 0 | Utilitários de linguagem | [📖 Docs](./BIBLIOTECA-LANG.md) |

## Estatísticas

- **Total de Bibliotecas**: 26
- **Bibliotecas Documentadas**: 26 (100%) ✅
- **Total de Funções**: 269
- **Funções Documentadas**: 269 (100%) ✅
- **Documentação**: ~330 páginas
- **Exemplos**: 90+ exemplos práticos
- **Casos de Uso**: 60+ cenários reais
- **Versão do Sistema**: v2.3.0
- **STATUS**: COMPLETO 🎉

## Convenções de Nomenclatura

As funções nas bibliotecas seguem um padrão de nomenclatura consistente:

```php
[biblioteca]_[operacao]_[contexto]($params)
```

### Exemplos:
- `banco_select()` - Operação de select no banco
- `formato_data_hora()` - Formatação de data e hora
- `usuario_autenticar()` - Autenticação de usuário
- `interface_modal_abrir()` - Abertura de modal na interface

## Padrões de Parâmetros

### Array de Parâmetros
Muitas funções aceitam um array associativo de parâmetros:

```php
function exemplo_funcao($params = false){
    if($params) foreach($params as $var => $val) $$var = $val;
    
    // Parâmetros disponíveis:
    // - parametro1 (tipo) - Obrigatório/Opcional - Descrição
    // - parametro2 (tipo) - Obrigatório/Opcional - Descrição
}
```

### Variáveis Globais
As bibliotecas utilizam variáveis globais para estado e configuração:

```php
global $_GESTOR;  // Configurações do sistema
global $_BANCO;   // Configurações do banco de dados
global $_USUARIO; // Dados do usuário autenticado
```

## Como Usar Esta Documentação

1. **Encontre a Biblioteca**: Use a tabela acima para localizar a biblioteca que contém a funcionalidade desejada
2. **Consulte a Documentação**: Clique no link de documentação para ver detalhes completos
3. **Veja Exemplos**: Cada função documentada inclui exemplos práticos de uso
4. **Entenda Dependências**: Verifique as dependências entre bibliotecas na documentação específica

## Estrutura da Documentação de Cada Biblioteca

Cada arquivo de documentação segue esta estrutura:

1. **Visão Geral**: Propósito e escopo da biblioteca
2. **Dependências**: Outras bibliotecas necessárias
3. **Variáveis Globais**: Variáveis globais utilizadas
4. **Funções Auxiliares**: Funções internas (prefixo sem biblioteca)
5. **Funções Principais**: API pública da biblioteca
6. **Exemplos de Uso**: Casos de uso práticos
7. **Notas de Versão**: Histórico de mudanças

## Contribuindo

Para adicionar ou melhorar a documentação:

1. Analise o código-fonte em `gestor/bibliotecas/[nome].php`
2. Documente funções públicas com:
   - Assinatura completa
   - Parâmetros (nome, tipo, obrigatoriedade, descrição)
   - Valor de retorno
   - Exemplo de uso
   - Notas e observações relevantes
3. Mantenha consistência com o formato existente
4. Teste os exemplos fornecidos

## Recursos Relacionados

- [📚 Sistema de Conhecimento](../CONN2FLOW-SISTEMA-CONHECIMENTO.md)
- [🔧 Desenvolvimento de Módulos](../CONN2FLOW-MODULOS-DETALHADO.md)
- [🎨 Layouts e Componentes](../CONN2FLOW-LAYOUTS-PAGINAS-COMPONENTES.md)
- [🔌 Arquitetura de Plugins](../CONN2FLOW-PLUGIN-ARCHITECTURE.md)

## Licença

Esta documentação é parte do projeto Conn2Flow e está disponível sob a mesma licença open-source do sistema principal.

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
