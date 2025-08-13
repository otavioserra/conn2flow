# Prompt: Correção de Bug - Conn2Flow

## 🐛 Contexto do Bug
```
Você está corrigindo um bug no sistema Conn2Flow.

IMPORTANTE: Leia primeiro:
- `ai-workspace/docs/CONN2FLOW-SISTEMA-CONHECIMENTO.md`
- `ai-workspace/docs/CONN2FLOW-[AREA]-DETALHADO.md` (área do bug)
```

## 📋 Informações do Bug

### Identificação
- **ID/Ticket:** [NUMERO_TICKET]
- **Título:** [TITULO_BUG]
- **Severidade:** [CRITICA/ALTA/MEDIA/BAIXA]
- **Área Afetada:** [MODULO/COMPONENTE]

### Descrição do Problema
[Descrever detalhadamente o comportamento incorreto]

### Reprodução do Bug
1. [Passo 1 para reproduzir]
2. [Passo 2 para reproduzir]
3. [Passo 3 para reproduzir]
4. **Resultado:** [O que acontece de errado]

### Comportamento Esperado
[Descrever como deveria funcionar corretamente]

### Informações do Ambiente
- **PHP:** [versão]
- **MySQL:** [versão]
- **Servidor:** [Apache/Nginx]
- **SO:** [Windows/Linux]
- **Navegador:** [se aplicável]

## 🔍 Investigação Inicial

### Logs de Erro
```
[Colar logs relevantes aqui]
```

### Arquivos Suspeitos
- `[arquivo1.php]` - [motivo da suspeita]
- `[arquivo2.php]` - [motivo da suspeita]

### Código Relacionado
[Apontar funções, classes ou módulos que podem estar envolvidos]

## 📁 Arquivos Relevantes

### Para Análise
- `gestor/bibliotecas/[biblioteca].php`
- `gestor/modulos/[modulo]/[arquivo].php`
- `gestor/controladores/[controller].php`
- `gestor/config.php`

### Logs e Debug
- `gestor-instalador/installer.log`
- `gestor/logs/` (se existir)
- Console do navegador (F12)

## 🔧 Comandos Úteis
```
- "Busque por [termo_erro] em todo o projeto"
- "Analise a função [nome_funcao] em [arquivo]"
- "Mostre arquivos modificados recentemente"
- "Execute script ai-workspace/scripts/check-installation.php"
```

## 🛠️ Plano de Correção

### Fase 1: Diagnóstico
- [ ] Reproduzir o bug localmente
- [ ] Analisar logs de erro
- [ ] Identificar root cause
- [ ] Mapear código afetado

### Fase 2: Solução
- [ ] Desenvolver correção
- [ ] Testar correção isoladamente
- [ ] Verificar efeitos colaterais
- [ ] Validar em diferentes cenários

### Fase 3: Validação
- [ ] Testar cenário original
- [ ] Testar casos edge
- [ ] Verificar regressões
- [ ] Validar performance

### Fase 4: Documentação
- [ ] Documentar causa raiz
- [ ] Atualizar código com comentários
- [ ] Atualizar documentação se necessário
- [ ] Preparar release notes

## ⚠️ Considerações Especiais

### Compatibilidade
- [ ] Não quebrar funcionalidades existentes
- [ ] Manter compatibilidade com versões PHP suportadas
- [ ] Verificar impacto em módulos relacionados

### Segurança
- [ ] Não introduzir vulnerabilidades
- [ ] Manter sanitização adequada
- [ ] Preservar validações existentes

### Performance
- [ ] Não degradar performance
- [ ] Otimizar se possível
- [ ] Considerar impacto em queries de banco

---
**Data:** $(date)
**Desenvolvedor:** Otavio Serra
**Tipo:** Correção de Bug
**Projeto:** Conn2Flow v1.4+
