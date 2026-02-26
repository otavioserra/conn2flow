# 📝 Componente Formulário - Formulário de Contato/Cadastro

**Versão:** 1.0.0
**Data:** 2025-10-28
**Autor:** Sistema Conn2Flow
**Tags:** componente, formulario, contato, cadastro, form

## 📋 Descrição
Cria um componente de formulário completo e funcional para contato, cadastro ou coleta de dados.

## 🎯 Objetivo
Gerar um componente de formulário HTML acessível e estilizado com validação visual e layout responsivo.

## 📝 Parâmetros de Entrada

### Obrigatórios:
- **Tipo do Formulário**: contato, cadastro, login, newsletter, pesquisa

### Opcionais:
- **Campos**: Lista de campos personalizados
- **Estilo Visual**: moderno, minimalista, em card, flutuante
- **Validação**: Se inclui validação visual de campos
- **Botão Submit**: Texto do botão de envio

## 🏗️ Estrutura do Componente

### Formulário de Contato
```
┌─────────────────────────────────────┐
│  📝 Título do Formulário            │
│  Subtítulo ou instrução             │
│                                     │
│  ┌─────────────┐ ┌───────────────┐  │
│  │ Nome         │ │ Email         │  │
│  └─────────────┘ └───────────────┘  │
│  ┌─────────────────────────────────┐│
│  │ Assunto                         ││
│  └─────────────────────────────────┘│
│  ┌─────────────────────────────────┐│
│  │ Mensagem                        ││
│  │                                  ││
│  │                                  ││
│  └─────────────────────────────────┘│
│                                     │
│  [Enviar Mensagem]                  │
└─────────────────────────────────────┘
```

### Formulário de Login
```
┌─────────────────────────────────────┐
│  🔐 Entrar                         │
│                                     │
│  ┌─────────────────────────────────┐│
│  │ Email                           ││
│  └─────────────────────────────────┘│
│  ┌─────────────────────────────────┐│
│  │ Senha                           ││
│  └─────────────────────────────────┘│
│  □ Lembrar-me      Esqueci a senha │
│                                     │
│  [Entrar]                           │
│                                     │
│  Não tem conta? Cadastre-se         │
└─────────────────────────────────────┘
```

## 🎨 Estilo Esperado
- Labels flutuantes ou acima dos campos
- Estados de foco, erro e sucesso nos inputs
- Botão de submit estilizado e prominente
- Layout responsivo (campos lado a lado em desktop, empilhados em mobile)
- Acessibilidade: labels associados, aria-attributes
- Se precisar de scripts de validação, incluir no bloco ```html-extra-head ``` ou inline
