---
title: "Crie um módulo administrativo"
description: "Scaffold do módulo, revisão do código gerado e sincronização dos recursos."
section: guides
sources:
  - cli/src/Commands/ModuleCreateCommand.php
  - gestor/modulos/menus/menus.json
  - gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php
verified_at: e5b61f8e
---

# Crie um módulo administrativo

Na raiz do Core, consulte `php cli/c2f.php module:create --help` e execute `php cli/c2f.php module:create meu-modulo [--table=minha_tabela]`. O comando normaliza o identificador para minúsculas e kebab case; o nome da tabela padrão usa sublinhado. Se `gestor/modulos/<id>/` já existir, retorna erro sem sobrescrever.

O scaffold grava `<id>.php`, `<id>.json`, `<id>.js` e recursos pt-br/en (páginas e variáveis). Antes de publicar, revise o controller e as operações geradas, inclusive permissão, validação, CSRF, histórico e nomes da tabela. O scaffold não substitui a revisão funcional. Use [menus](../reference/modules/menus.md) como exemplo de módulo real e [recursos](../concepts/resources.md) para entender a compilação.

Defina as páginas e os vínculos em `resources/<idioma>/` e no JSON do módulo. Depois, no fluxo de desenvolvimento do Core, rode o pipeline local `php cli/c2f.php manager:update-all`. O runtime lê páginas e estilos do banco após a atualização. Revise os órfãos reportados pelo compilador antes de considerar o módulo disponível.
