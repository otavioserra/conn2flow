
# RELEASE: Conn2Flow Instalador - Hotfix JS: Função runInstallation Global (Julho 2025)

## RESUMO DAS ALTERAÇÕES

### 1. Correção Crítica no Instalador
- Corrigido erro fatal no frontend do instalador: `Uncaught ReferenceError: runInstallation is not defined`.
- A função `runInstallation` foi movida para o escopo global do arquivo `installer.js`, garantindo que o submit do formulário de instalação funcione corretamente.
- Corrigidos problemas de fechamento de chaves e duplicidade de linhas no final do arquivo JS, eliminando erros de sintaxe e garantindo compilação limpa.

### 2. Teste e Validação
- Instalação testada após o ajuste: o processo segue normalmente, sem erro JS, e o modal de progresso/carregamento funciona como esperado.
- O instalador está pronto para ser publicado e testado em ambiente de homologação.

## ARQUIVOS MODIFICADOS

- `gestor-instalador/assets/js/installer.js` (função global, correção de sintaxe)

## INSTRUÇÕES PARA O AGENTE DE PUBLICAÇÃO

1. Gerar uma nova tag do instalador com o hotfix JS.
2. Zipar o diretório `gestor-instalador` para release.
3. Publicar a nova versão para testes.

**Versão sugerida:** 1.0.25
**Data:** Julho 2025
**Criticidade:** Alta (bloqueio de instalação)
**Compatibilidade:** Total

### 8. Correção de Dependência de Bibliotecas
- Corrigido bug fatal na etapa `createAdminAutoLogin` (`PHP Fatal error: Call to undefined function gestor_incluir_biblioteca()`).
- Adicionados `require_once` para todas as bibliotecas essenciais do gestor na ordem correta dentro da função, garantindo que todas as funções estejam disponíveis.

## ARQUIVOS MODIFICADOS

- `gestor-instalador/index.php` (tratamento de erro e log)
- `gestor-instalador/views/installer.php` (interface de erro e log)
- `gestor-instalador/assets/js/installer.js` (tratamento de erro, log, internacionalização)
- `gestor-instalador/lang/pt-br.json` e `gestor-instalador/lang/en-us.json` (novas traduções)
- `gestor/db/seeds/PaginasSeeder.php` (correções de sintaxe)
- `gestor/modulos/dashboard/dashboard.php` (remoção da página de sucesso)
- `gestor-instalador/src/Installer.php` (função `createAdminAutoLogin`)

## INSTRUÇÕES PARA O AGENTE GIT

1.  Executar o script de release para o instalador com o tipo `patch` e as mensagens de commit apropriadas. Exemplo:
    `./ai-workspace/scripts/release-instalador.sh patch "fix(install): Corrige dependências de bibliotecas" "fix(install): Adiciona require_once para bibliotecas ausentes em createAdminAutoLogin para resolver erro fatal."`
2.  O script irá incrementar a versão para **1.0.23** e criar a tag `instalador-v1.0.23`.
3.  Gerar um novo release zipado do `gestor-instalador` com a correção.
4.  Publicar a nova versão do instalador.

**Versão:** 1.0.24
**Data:** Julho 2025
**Criticidade:** Alta (Melhorias de UX, diagnóstico e correção de bug fatal)
**Compatibilidade:** Total

---


# RELEASE: Conn2Flow Gestor

## RESUMO DAS ALTERAÇÕES

Nenhuma alteração realizada no gestor nesta release. Todas as correções e melhorias foram aplicadas exclusivamente ao instalador (`gestor-instalador`).
