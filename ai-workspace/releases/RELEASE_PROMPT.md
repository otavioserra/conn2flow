# RELEASE: Conn2Flow Instalador - Correção de Dependência de Bibliotecas (Julho 2025)

## RESUMO DAS ALTERAÇÕES

- Corrigido um **bug fatal** que ocorria durante a etapa de instalação `createAdminAutoLogin` com o erro: `PHP Fatal error: Call to undefined function gestor_incluir_biblioteca()`.
- **Causa Raiz:** A função `createAdminAutoLogin()` no `Installer.php` tentava utilizar a biblioteca `usuario.php` do gestor, mas as dependências dessa biblioteca (`banco.php`, `gestor.php`, `ip.php`) não estavam sendo carregadas no escopo do instalador.
- **Solução:** Foram adicionados `require_once` para todas as bibliotecas essenciais do gestor na ordem correta dentro da função `createAdminAutoLogin()`, garantindo que todas as funções estejam disponíveis durante a execução.

## ARQUIVOS MODIFICADOS

- `gestor-instalador/src/Installer.php` (função `createAdminAutoLogin`)

## INSTRUÇÕES PARA O AGENTE GIT

1.  Executar o script de release para o instalador com o tipo `patch` e as mensagens de commit apropriadas. Exemplo:
    `./ai-workspace/scripts/release-instalador.sh patch "fix(install): Corrige dependências de bibliotecas" "fix(install): Adiciona require_once para bibliotecas ausentes em createAdminAutoLogin para resolver erro fatal."`
2.  O script irá incrementar a versão para **1.0.23** e criar a tag `instalador-v1.0.23`.
3.  Gerar um novo release zipado do `gestor-instalador` com a correção.
4.  Publicar a nova versão do instalador.

**Versão:** 1.0.23
**Data:** Julho 2025
**Criticidade:** Crítica (Impedia a conclusão da instalação)
**Compatibilidade:** Total
