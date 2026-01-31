# 🎨 Layouts - Manual do Usuário

## O que são Layouts?

**Layouts** são os modelos mestres que definem a estrutura geral das suas páginas. Eles contêm os elementos comuns que aparecem em cada página - como cabeçalho, rodapé e navegação. O conteúdo real da página é inserido em uma área especial dentro do layout.

---

## 🎯 Primeiros Passos

### Acessando Layouts
1. No Dashboard, encontre o card **Layouts**
2. Clique para abrir o módulo
3. Você verá todos os layouts disponíveis

---

## 🏗️ Entendendo Layouts

### Como os Layouts Funcionam
```
Estrutura do Layout:
┌─────────────────────────────┐
│         CABEÇALHO           │  ← Comum a todas as páginas
├─────────────────────────────┤
│        NAVEGAÇÃO            │  ← Comum a todas as páginas
├─────────────────────────────┤
│                             │
│    @[[pagina#corpo]]@       │  ← Conteúdo da página vai aqui
│                             │
├─────────────────────────────┤
│          RODAPÉ             │  ← Comum a todas as páginas
└─────────────────────────────┘
```

A variável mágica `@[[pagina#corpo]]@` é onde o conteúdo único de cada página aparece.

---

## 📋 Lista de Layouts

### O que Você Verá
- **Nome do layout** - Identificador
- **Última modificação** - Quando foi alterado por último
- **Páginas usando** - Quantas páginas usam este layout
- **Ações** - Editar, duplicar, excluir

---

## ➕ Criando um Novo Layout

### Passo a Passo
1. Clique em **"Adicionar Layout"**
2. Preencha os detalhes:
   - **Nome** - Um nome descritivo
   - **ID** - Identificador único (gerado automaticamente do nome)
3. Digite a estrutura HTML no editor de código
4. Adicione CSS se necessário
5. Clique em **"Salvar"**

### Template Básico de Layout
```html
<!DOCTYPE html>
<html lang="@[[pagina#idioma]]@">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@[[pagina#titulo]]@</title>
    @[[pagina#head]]@
</head>
<body>
    <header>
        @[[componente#site-header]]@
    </header>
    
    <main>
        @[[pagina#corpo]]@
    </main>
    
    <footer>
        @[[componente#site-footer]]@
    </footer>
    
    @[[pagina#scripts]]@
</body>
</html>
```

---

## 🔧 Variáveis Essenciais

### Variáveis Obrigatórias
| Variável | Propósito |
|----------|-----------|
| `@[[pagina#corpo]]@` | **Obrigatória!** Onde o conteúdo da página aparece |
| `@[[pagina#titulo]]@` | Título da página |
| `@[[pagina#head]]@` | Conteúdo adicional do head |
| `@[[pagina#scripts]]@` | JavaScript no final da página |

### Variáveis Opcionais
| Variável | Propósito |
|----------|-----------|
| `@[[pagina#idioma]]@` | Idioma atual |
| `@[[usuario#nome]]@` | Nome do usuário logado |
| `@[[componente#nome]]@` | Incluir um componente |
| `@[[variavel#nome]]@` | Inserir valor de uma variável |

---

## ✏️ Editando Layouts

### O Editor de Código
- **Aba HTML** - Estrutura principal
- **Aba CSS** - Estilos específicos do layout
- Destaque de sintaxe para fácil edição
- Números de linha para referência

### Dicas para Edição
1. Sempre faça backup antes de grandes alterações
2. Teste as alterações em uma página de preview primeiro
3. Mantenha a variável `@[[pagina#corpo]]@` intacta
4. Use componentes para seções reutilizáveis

---

## 🎨 Frameworks CSS

O Conn2Flow suporta:
- **Fomantic-UI** - Framework de UI rico em recursos
- **TailwindCSS** - Framework utility-first

### Selecionando um Framework
1. Edite o layout
2. Escolha no dropdown **Framework CSS**
3. Salve o layout
4. Classes do framework estão agora disponíveis

---

## 📦 Usando Componentes

Em vez de repetir código, use componentes:

```html
<!-- Em vez de repetir código de navegação -->
<nav>
    <!-- muito código repetido -->
</nav>

<!-- Use um componente -->
@[[componente#main-navigation]]@
```

### Benefícios
- Altere uma vez, atualize em todos os lugares
- Código de layout mais limpo
- Manutenção mais fácil

---

## ❓ Perguntas Frequentes

### P: O conteúdo da minha página não está aparecendo
**R:** Certifique-se de que seu layout inclui `@[[pagina#corpo]]@` - é aqui que o conteúdo aparece.

### P: Posso ter cabeçalhos diferentes para páginas diferentes?
**R:** Sim! Crie múltiplos layouts com cabeçalhos diferentes, depois atribua as páginas ao layout apropriado.

### P: Como adiciono o Google Analytics?
**R:** Adicione o código de rastreamento antes de `</head>` no seu layout, ou use `@[[pagina#head]]@` para incluí-lo das páginas.

### P: Meu CSS não está funcionando
**R:** Verifique:
1. O CSS está na aba CSS (não HTML)?
2. Há erros de sintaxe?
3. Outro estilo está sobrescrevendo?

---

## ⚠️ Notas Importantes

1. **Não exclua layouts ativos** - Primeiro reatribua as páginas a outro layout
2. **Backup antes de editar** - Grandes alterações podem quebrar páginas
3. **Teste completamente** - Verifique todas as páginas usando o layout após alterações
4. **Responsivo para mobile** - Sempre teste em dispositivos móveis

---

## 💡 Melhores Práticas

1. **Mantenha layouts mínimos** - Coloque partes reutilizáveis em componentes
2. **Nomeie claramente** - "Layout Principal do Site" não "Layout 1"
3. **Documente** - Adicione comentários HTML explicando seções
4. **Controle de versão** - Anote o que você mudou e quando
5. **Mobile first** - Projete para mobile, aprimore para desktop

---

## 🆘 Precisa de Ajuda?

- Confira o módulo **Componentes** para elementos reutilizáveis
- Confira **Variáveis** para conteúdo dinâmico
- Entre em contato com seu administrador do sistema
- Visite nossa documentação em [conn2flow.com/docs](https://conn2flow.com/docs)
