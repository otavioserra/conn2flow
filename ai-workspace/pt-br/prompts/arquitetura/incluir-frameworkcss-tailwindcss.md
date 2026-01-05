# Prompt Interactive Programming - Inclusão do Campo framework_css (TailwindCSS)

## 🎯 Contexto Inicial
- Expandir suporte a frameworks CSS no sistema, permitindo marcar cada recurso (página, layout, componente) com o framework utilizado.
- Frameworks suportados: 'fomantic-ui' (padrão), 'tailwindcss' (novo), outros futuros.
- Origem dos dados: arquivos JSON dos recursos, scripts de atualização, tabelas do banco.

## 📝 Orientações para o Agente
1. Projetar todas as alterações necessárias para permitir múltiplos frameworks CSS por recurso.
2. Documentar exemplos, fallback e fluxo de atualização.
3. Registrar dúvidas e sugestões antes da implementação.

## 🤔 Dúvidas e 📝 Sugestões
- O campo `framework_css` será sempre string, valores permitidos inicialmente: 'fomantic-ui', 'tailwindcss'.
Sim sempre string.
- Algum recurso pode usar múltiplos frameworks simultaneamente? (Ex: array ou string separada por vírgula)
Num primeiro momento só um recurso por vez.
- O campo será obrigatório ou opcional? (Se ausente, sempre usar 'fomantic-ui')
Se ausente sempre usar o 'fomantic-ui'.
- Algum impacto na renderização/rotas dos módulos? (Ex: assets, helpers, classes JS)
A parte de renderização será na fase 2 da modificação. Por enquanto só focar na atualização dos arquivos JSON e na lógica do `gestor\controladores\agents\arquitetura\atualizacao-dados-recursos.php`.
- Alguma regra para herança de framework entre layouts/páginas/componentes?
Não, só referência por enquanto.

## ✅ Progresso da Implementação
- [x] 1. Especificação do campo `framework_css` para layouts, páginas e componentes
- [x] 2. Atualização dos arquivos de origem dos recursos (JSON) para aceitar o campo (campo opcional, fallback automático)
- [x] 3. Criação de 3 migrations para adicionar o campo nas tabelas `paginas`, `layouts`, `componentes`
- [x] 4. Atualização dos scripts de geração/atualização de recursos para respeitar o campo e usar padrão se ausente
- [x] 5. Documentação de exemplos de uso e fallback
- [x] 6. Registro de dúvidas/sugestões para validação

### Exemplos (fase 1)

Recurso página em origem (pages.json ou módulo):
```json
{
	"id": "dashboard",
	"name": "Dashboard",
	"framework_css": "tailwindcss",
	"status": "A"
}
```

Se ausente:
```json
{
	"id": "relatorios",
	"name": "Relatórios"
}
```
Na coleta resultará em `framework_css: "fomantic-ui"`.

Layout exemplo:
```json
{ "id": "principal", "framework_css": "tailwindcss" }
```

Componente exemplo sem campo (fallback):
```json
{ "id": "botao-salvar" }
```

### Notas
1. Campo propagado para Data JSON (`LayoutsData.json`, `PaginasData.json`, `ComponentesData.json`).
2. Migrações adicionam coluna nullable; aplicação trata null como `fomantic-ui`.
3. Próxima etapa: marcar documentação final e validar dúvidas remanescentes (itens 5 e 6).

### Dúvidas Finais / Validação
- Nenhuma dúvida pendente. Escopo confirmado: apenas persistência e fallback na fase 1.

### Sugestões Futuras (Fase 2 – Renderização / Assets)
1. Carregamento condicional de bundles (CSS/JS) conforme `framework_css` predominante da página (priorizar página > layout > componentes). 
2. Estratégia de pré-processamento para Tailwind (build on demand vs. build único). 
3. Flag de configuração global para definir framework padrão (hoje constante DEFAULT_FRAMEWORK_CSS). 
4. Validação opcional de valores aceitáveis (whitelist) para evitar propagação de valores incorretos.
5. Métrica de uso por framework para monitorar adoção.

### How-To Adicionar Novo Framework (Futuro)
1. Adicionar valor novo aos arquivos de origem (JSON) em `framework_css`.
2. (Opcional) Atualizar whitelist se implementada.
3. Implementar pipeline de build/asset (ex: gerar CSS minificado).
4. Ajustar roteamento de injeção de assets dinâmicos.

### Confirmação
Implementação da Fase 1 concluída sem impacto no versionamento de recursos existentes (exceto inclusão do novo campo nos Data JSON).

---
---
**Data:** 27/08/2025
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow v1.15.0