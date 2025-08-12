### Feature: Correção na Migração de Variáveis para Recursos

**Descrição:**

Esta atualização corrige dois problemas identificados no script de migração de variáveis (`recurso-variaveis-criar-origem.php`):

1.  **Tratamento de Módulos Inexistentes:** Variáveis associadas a módulos que não existem mais nas pastas `gestor/modulos` ou `gestor-plugins` (como `_sistema`) agora são corretamente classificadas como globais. Para manter a rastreabilidade, o nome do módulo original foi adicionado ao objeto da variável no arquivo `variables.json` global.
2.  **Caminho de Criação de Arquivo:** Corrigido o problema que causava a criação de um arquivo `variables.json` na raiz de `gestor/resources` em vez de no diretório de idioma correto (ex: `gestor/resources/pt-br/`).

**Mudanças:**

-   **`gestor/controladores/agents/arquitetura/recurso-variaveis-criar-origem.php`**:
    -   Modificada a função `formatarVars()` para incluir uma lógica que verifica a existência do módulo. Se o módulo não for encontrado, a variável é adicionada ao grupo `globais` com um campo adicional `modulo` para referência.
    -   Ajustes na função `guardarVarsNosResources()` para garantir que os arquivos de variáveis globais sejam sempre salvos dentro da pasta de idioma correspondente.

**Como Testar:**

1.  Execute o script PHP: `php gestor/controladores/agents/arquitetura/recurso-variaveis-criar-origem.php`.
2.  Verifique o relatório de saída para confirmar que as contagens de variáveis globais, de módulos e de plugins estão corretas.
3.  Inspecione o arquivo `gestor/resources/pt-br/variables.json` e confirme que as variáveis de módulos inexistentes (ex: `_sistema`) estão presentes e contêm o campo `"modulo": "_sistema"`.
4.  Confirme que nenhum arquivo `variables.json` foi criado diretamente em `gestor/resources`.
