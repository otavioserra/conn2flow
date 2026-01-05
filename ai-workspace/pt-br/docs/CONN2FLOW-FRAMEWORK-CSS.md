# CONN2FLOW - Suporte a Múltiplos Frameworks CSS (Fase 1)

## Objetivo
Persistir e propagar o campo `framework_css` para layouts, páginas e componentes, habilitando evolução futura de renderização condicionada (TailwindCSS vs FomanticUI).

## Escopo Fase 1
- Inclusão do campo em: Data JSON (`LayoutsData.json`, `PaginasData.json`, `ComponentesData.json`) e tabelas `layouts`, `paginas`, `componentes`.
- Fallback automático para `fomantic-ui` quando ausente.
- Nenhuma alteração em runtime de carregamento de assets ainda.

## Migrações
Arquivos criados:
```
20250827110000_alter_layouts_add_framework_css.php
20250827110010_alter_paginas_add_framework_css.php
20250827110020_alter_componentes_add_framework_css.php
```
Cada um adiciona `framework_css VARCHAR(50) NULL` (comentado, reversível no down).

## Script de Atualização
Arquivo: `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`
- Adicionada constante `DEFAULT_FRAMEWORK_CSS = 'fomantic-ui'`.
- Função utilitária `getFrameworkCss($src)` retorna valor ou fallback.
- Campo incorporado nas coleções de layouts, páginas e componentes (globais, módulos, plugins).

## Exemplos de Origem
Página (Tailwind):
```json
{ "id": "dashboard", "name": "Dashboard", "framework_css": "tailwindcss" }
```
Componente (fallback Fomantic):
```json
{ "id": "botao-salvar" }
```

## Data JSON (Exemplo Simplificado)
```json
{
  "id": "dashboard",
  "language": "pt-br",
  "framework_css": "tailwindcss",
  "html": "...",
  "css": null,
  "versao": 1
}
```

## Regras Atuais
| Item | Regra |
|------|-------|
| Valores suportados | `fomantic-ui`, `tailwindcss` (outros passam sem validação por enquanto) |
| Fallback | Ausente ou vazio => `fomantic-ui` |
| Persistência | Sempre escrito nos Data JSON após atualização |
| Banco | Coluna nullable; null tratado como fallback em nível de aplicação |

## Próximas Etapas (Fase 2)
1. Injeção condicional de assets (inicialmente por página). 
2. Build integrado de Tailwind (watch + purge). 
3. Otimização de payload (carregar somente framework necessário no contexto). 
4. Cache segmentado por framework para renderizações.
5. Whitelist/validação de frameworks e métricas de adoção.

## Considerações de Compatibilidade
- Inclusão do campo não altera lógica de unicidade, versionamento ou checksums.
- Deploys antigos ignoram o campo sem quebrar; novas versões enriquecem Data JSON.

## Rollback
Executar `phinx rollback` para remover colunas (migrações possuem down). Data JSON manterá campo; consumidores antigos simplesmente ignoram chaves extras.

## Commit Sugerido
```
feat(css): adiciona suporte inicial a framework_css (TailwindCSS fase 1)

- Migrations adicionam coluna framework_css em layouts/paginas/componentes
- Atualização de coleta de recursos propaga campo com fallback 'fomantic-ui'
- Planejamento atualizado + docs CONN2FLOW-FRAMEWORK-CSS
- Sem mudanças de runtime de assets (planejado fase 2)
```

## Tag Sugerida
`v1.15.0-frameworkcss-fase1` (ou incluir no próximo release acumulado).

---
Data: 2025-08-27
Autor: Otavio Serra