# RELEASE: Conn2Flow Instalador - Correção de Erro 503 (Julho 2025)

## RESUMO DAS ALTERAÇÕES

- Corrigido um **bug crítico de erro 503** que ocorria durante a etapa de instalação `createAdminAutoLogin`.
- **Causa Raiz:** O script `config.php` do `gestor` era chamado pelo instalador sem o contexto adequado, resultando na falha ao localizar o arquivo `.env` recém-criado (o caminho para `autenticacoes/localhost/` era montado incorretamente).
- **Solução:** A função `setupGestorEnvironment()` no `Installer.php` foi atualizada para definir a variável global `$_INDEX['sistemas-dir']` com o caminho correto da instalação do `gestor` *antes* de incluir o `config.php`. Isso garante que o ambiente do gestor seja simulado corretamente e que o arquivo `.env` seja encontrado.

## ARQUIVOS MODIFICADOS

- `gestor-instalador/src/Installer.php` (função `setupGestorEnvironment`)

## INSTRUÇÕES PARA O AGENTE GIT

1. Gerar um novo release zipado do `gestor-instalador` com a correção acima.
2. Publicar a nova versão do instalador.
3. Atualizar a documentação técnica (`CONN2FLOW-INSTALADOR-DETALHADO.md`) para refletir a correção e a importância da variável `$_INDEX['sistemas-dir']`.

**Versão:** 1.0.22
**Data:** Julho 2025
**Criticidade:** Crítica (Impedia a conclusão da instalação)
**Compatibilidade:** Total
