# 🏷️ Gerenciamento de Categorias - Manual do Usuário

## O que é o Gerenciamento de Categorias?

O módulo **Categorias** (Admin Categorias) ajuda você a organizar seu conteúdo em grupos lógicos. Categorias facilitam para os usuários encontrarem conteúdo relacionado e ajudam a estruturar a arquitetura de informação do seu site.

---

## 🎯 Primeiros Passos

### Acessando Categorias
1. No Dashboard, encontre o card **Categorias**
2. Clique para abrir o módulo
3. Você verá todas as categorias existentes

---

## 📋 Lista de Categorias

### O que Você Verá
Para cada categoria:
- **Nome** - Nome de exibição
- **Slug** - Identificador amigável para URL
- **Pai** - Categoria pai (se aninhada)
- **Contagem** - Número de itens usando
- **Ações** - Editar, excluir

### Filtrando
- **Busca** - Encontrar por nome
- **Pai** - Mostrar apenas subcategorias
- **Tipo** - Filtrar por tipo de conteúdo

---

## ➕ Criando Categorias

### Como Criar
1. Clique em **"Adicionar Categoria"**
2. Preencha os detalhes:
   - **Nome** - Nome de exibição (ex: "Tecnologia")
   - **Slug** - Identificador URL (ex: "tecnologia")
   - **Pai** - Categoria pai opcional
   - **Descrição** - Para que serve esta categoria
3. Clique em **"Salvar"**

### Dicas de Nomenclatura
- Mantenha nomes curtos e claros
- Use singular ou plural consistentemente
- Pense nas expectativas do usuário

---

## 📁 Hierarquia de Categorias

### Categorias Pai/Filho
Você pode criar categorias aninhadas:
```
Tecnologia (pai)
├── Software
├── Hardware
└── Tutoriais
    ├── Iniciante
    └── Avançado
```

### Criando Subcategorias
1. Crie ou edite uma categoria
2. Selecione uma categoria **Pai**
3. Salve - ela se torna uma subcategoria

---

## ✏️ Editando Categorias

### Como Editar
1. Encontre a categoria na lista
2. Clique em **Editar** (ícone de lápis)
3. Atualize as informações
4. Clique em **"Salvar"**

### Mudando Pai
1. Edite a categoria
2. Selecione novo pai (ou nenhum)
3. Salve as alterações

---

## 🗑️ Excluindo Categorias

### Como Excluir
1. Encontre a categoria
2. Clique em **Excluir** (ícone de lixeira)
3. Confirme a exclusão

### O que Acontece com o Conteúdo?
- Conteúdo NÃO é excluído
- Conteúdo fica sem categoria
- Reatribua conteúdo antes de excluir se necessário

> ⚠️ **Aviso:** Excluir categoria pai pode deixar subcategorias órfãs!

---

## 🔗 Usando Categorias

### No Conteúdo
Ao criar páginas ou posts:
1. Encontre a seção Categorias
2. Marque as categorias aplicáveis
3. O conteúdo será associado

### Na Navegação
Categorias podem ser usadas para:
- Criar seções de menu
- Filtrar exibições de conteúdo
- Construir páginas de arquivo

---

## ❓ Perguntas Frequentes

### P: Posso ter o mesmo nome de categoria duas vezes?
**R:** Sim, se tiverem pais diferentes. Slugs devem ser únicos.

### P: O que acontece se eu renomear uma categoria?
**R:** O nome de exibição muda mas as associações de conteúdo permanecem.

### P: Posso mesclar categorias?
**R:** Não diretamente. Reatribua o conteúdo para uma categoria e exclua a outra.

### P: Quantos níveis de aninhamento?
**R:** Tecnicamente ilimitado, mas 2-3 níveis é recomendado para usabilidade.

---

## 💡 Melhores Práticas

1. **Planeje a estrutura primeiro** - Esboce sua hierarquia
2. **Mantenha simples** - Muitas categorias confundem usuários
3. **Use nomenclatura consistente** - Siga uma convenção
4. **Revise regularmente** - Remova categorias não usadas
5. **Pense em SEO** - Categorias afetam estrutura de URL

---

## 🆘 Precisa de Ajuda?

- Confira **Páginas** para categorização de conteúdo
- Confira **Layouts** para exibições de categoria
- Confira **Componentes** para widgets de categoria
- Entre em contato com seu administrador do sistema
- Visite nossa documentação em [conn2flow.com/docs](https://conn2flow.com/docs)
