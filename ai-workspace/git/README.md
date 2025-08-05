# 🚀 Releases e Versões - Conn2Flow

Esta pasta contém histórico de releases, notas de versão e prompts relacionados a deploys.

## 📋 Arquivos de Release

### 🎯 Principais
- **`RELEASE_PROMPT.md`** - Template/prompt para releases principais


### 📝 Notas de Versão *(planejadas)*
- **`v1.4.0-release-notes.md`** - Correção crítica do instalador
- **`v1.5.0-release-notes.md`** - Próxima versão
- **`changelog.md`** - Histórico completo de mudanças

### 🗂️ Commit Prompt
- **`COMMIT_PROMPT.md`** - Template para registrar e comitar alterações profundas ou de manutenção, sem necessidade de release formal. Use este arquivo para orientar o agente sobre como salvar o histórico do ciclo de trabalho, mensagem de commit, branch e contexto.

## 🎯 Estrutura de Release

### 1. Preparação
- Documentar todas as mudanças
- Testar em ambiente de desenvolvimento
- Validar compatibilidade

### 2. Documentação
- Criar/atualizar release notes
- Atualizar documentação técnica
- Preparar guias de migração se necessário

### 3. Deploy
- Gerar pacotes (gestor.zip, etc.)
- Atualizar versões
- Fazer deploy em produção

### 4. Comunicação
- Notificar usuários sobre mudanças
- Atualizar documentação pública
- Responder dúvidas/problemas

## 📊 Template de Release Notes

```markdown
# Conn2Flow v[VERSAO] - [TITULO]

## 📋 Resumo
[Descrição breve das principais mudanças]

## ✨ Novas Funcionalidades
- [Funcionalidade 1]
- [Funcionalidade 2]

## 🐛 Correções de Bugs
- [Bug 1 corrigido]
- [Bug 2 corrigido]

## 🔧 Melhorias
- [Melhoria 1]
- [Melhoria 2]

## ⚠️ Breaking Changes
- [Mudança que quebra compatibilidade]

## 📱 Compatibilidade
- PHP: 7.4+ / 8.0+ / 8.1+ / 8.2+
- MySQL: 5.7+ / 8.0+
```

---
**Última atualização:** 5 de agosto, 2025
**Estrutura:** ai-workspace/releases/
