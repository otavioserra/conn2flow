# 📝 Variáveis - Manual do Usuário

## O que são Variáveis?

**Variáveis** são pedaços reutilizáveis de conteúdo que você pode usar em todo o seu site. Em vez de digitar a mesma informação em vários lugares (como o nome da sua empresa ou telefone), você define uma vez como variável e referencia em todos os lugares. Quando você atualiza a variável, todos os lugares usando-a atualizam automaticamente!

---

## 🎯 Primeiros Passos

### Acessando Variáveis
1. No Dashboard, encontre o card **Variáveis**
2. Clique para abrir o módulo
3. Você verá todas as variáveis existentes

---

## 🏗️ Entendendo Variáveis

### Como as Variáveis Funcionam
```
Defina uma vez:
┌─────────────────────────────┐
│  Variável: telefone-empresa │
│  Valor: (11) 1234-5678      │
└─────────────────────────────┘

Use em qualquer lugar com:
@[[variavel#telefone-empresa]]@

Resultado:
(11) 1234-5678
```

---

## 📋 Lista de Variáveis

### O que Você Verá
- **Nome/ID** - Identificador da variável
- **Valor** - Valor atual (prévia)
- **Categoria** - Agrupamento
- **Tipo** - Texto, HTML, etc.
- **Ações** - Editar, excluir

### Filtrando
- **Busca** - Encontre por nome ou valor
- **Categoria** - Filtre por grupo
- **Tipo** - Filtre por tipo de variável

---

## ➕ Criando uma Nova Variável

### Passo a Passo
1. Clique em **"Adicionar Variável"**
2. Preencha os detalhes:
   - **Nome/ID** - Identificador único (minúsculas, hífens)
   - **Valor** - O conteúdo
   - **Tipo** - Texto, HTML, JSON, etc.
   - **Categoria** - Agrupamento (opcional)
   - **Descrição** - Para que serve (opcional)
3. Clique em **"Salvar"**

### Dicas de Nomenclatura
- Use minúsculas com hífens: `nome-empresa`
- Seja descritivo: `email-contato` não `email1`
- Use prefixos: `social-facebook`, `social-twitter`

---

## 🔧 Tipos de Variáveis

| Tipo | Melhor Para | Exemplo |
|------|-------------|---------|
| **Texto** | Strings simples | Nome da empresa, telefone |
| **HTML** | Conteúdo formatado | Endereço com quebras de linha |
| **JSON** | Dados estruturados | Configurações |
| **Número** | Valores numéricos | Preços, contagens |

---

## 📦 Usando Variáveis

### Uso Básico
```html
<!-- Em qualquer página, layout ou componente -->
<p>Entre em contato em @[[variavel#email-empresa]]@</p>
<p>Ligue: @[[variavel#telefone-empresa]]@</p>
```

### Em Diferentes Contextos

**No Texto:**
```html
<p>© 2024 @[[variavel#nome-empresa]]@. Todos os direitos reservados.</p>
```

**Em Atributos:**
```html
<a href="mailto:@[[variavel#email-contato]]@">Envie um Email</a>
<img src="@[[variavel#url-logo]]@" alt="Logo">
```

**Em JavaScript:**
```html
<script>
    var nomeEmpresa = "@[[variavel#nome-empresa]]@";
</script>
```

---

## 🌐 Variáveis do Sistema

Algumas variáveis são fornecidas automaticamente:

| Variável | Descrição |
|----------|-----------|
| `@[[sistema#versao]]@` | Versão do Conn2Flow |
| `@[[sistema#ano-atual]]@` | Ano atual |
| `@[[sistema#data-atual]]@` | Data de hoje |
| `@[[usuario#nome]]@` | Nome do usuário logado |
| `@[[pagina#titulo]]@` | Título da página atual |

---

## 📂 Categorias Comuns de Variáveis

### Informações de Contato
```
contato-email
contato-telefone
contato-endereco
contato-horario
```

### Informações da Empresa
```
empresa-nome
empresa-slogan
empresa-cnpj
empresa-registro
```

### Redes Sociais
```
social-facebook
social-instagram
social-linkedin
social-twitter
social-youtube
```

### SEO Padrão
```
seo-titulo-padrao
seo-descricao-padrao
seo-og-image
```

---

## ✏️ Editando Variáveis

### Como Editar
1. Encontre a variável na lista
2. Clique em **Editar** (ícone de lápis)
3. Altere o valor
4. Clique em **Salvar**

> 💡 **Alterações entram em vigor imediatamente** em todas as páginas usando a variável!

---

## 🌍 Variáveis Multi-idioma

### Criando Versões por Idioma
1. Crie uma variável para cada idioma
2. Use prefixo ou sufixo de idioma:
   - `mensagem-boas-vindas-en`
   - `mensagem-boas-vindas-pt`
3. Ou use o seletor de idioma ao editar

### Detecção Automática de Idioma
O sistema pode usar automaticamente a versão correta do idioma baseada no idioma da página atual.

---

## ❓ Perguntas Frequentes

### P: Minha variável não está aparecendo
**R:** Verifique:
1. O ID está escrito corretamente? (sensível a maiúsculas)
2. A sintaxe está correta? `@[[variavel#id]]@`
3. A variável está salva e ativa?

### P: Posso usar HTML em uma variável de texto?
**R:** É melhor usar o tipo HTML se você precisar de formatação. O tipo texto pode escapar caracteres HTML.

### P: Como excluo uma variável?
**R:** Primeiro verifique onde está sendo usada! Excluir uma variável usada em páginas deixará o placeholder visível.

### P: Existem limites?
**R:** Nomes de variáveis devem ter menos de 255 caracteres. Valores podem ser muito maiores.

---

## 💡 Melhores Práticas

### Organização
1. **Nomenclatura consistente** - Use prefixos para agrupar variáveis relacionadas
2. **Documente uso** - Preencha o campo de descrição
3. **Categorize** - Agrupe variáveis logicamente
4. **Revise regularmente** - Remova variáveis não usadas

### Conteúdo
1. **Mantenha valores simples** - Conteúdo complexo = componentes
2. **Sem dados sensíveis** - Não armazene senhas ou segredos
3. **Atualize com cuidado** - Lembre-se que alterações afetam todos os usos
4. **Backup de valores** - Antes de grandes alterações

---

## 🆘 Precisa de Ajuda?

- Confira **Componentes** para conteúdo reutilizável mais complexo
- Confira **Layouts** e **Páginas** para ver uso de variáveis
- Entre em contato com seu administrador do sistema
- Visite nossa documentação em [conn2flow.com/docs](https://conn2flow.com/docs)
