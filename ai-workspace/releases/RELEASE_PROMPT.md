# RELEASE: Conn2Flow Instalador - Correção de Dependência de Bibliotecas (Julho 2025)

## RESUMO DAS ALTERAÇÕES


### 1. Tratamento de Erros e Log
- O instalador agora exibe mensagens de erro detalhadas e mostra o log dos últimos erros diretamente na interface web.
- Backend envia as últimas linhas do arquivo `installer.log` junto com a resposta de erro em JSON.
- Frontend exibe o log, permite copiar o conteúdo e reiniciar o processo após falha.

### 2. Internacionalização
- Novas chaves de tradução adicionadas nos arquivos `pt-br.json` e `en-us.json` para mensagens de progresso, erro, log e botões.
- Mensagens de erro e progresso agora aparecem no idioma selecionado pelo usuário.

### 3. Interface de Instalação
- Modal de carregamento aprimorado, com feedback visual para progresso e erros.
- Exibição do log de erro e botão para copiar log diretamente na interface.
- Botão "Tentar Novamente" para reiniciar o processo após falha.

### 4. Sincronização e Validação de Campos
- Sincronização automática do campo host do banco de dados com o domínio do site.
- Validações aprimoradas nos campos do formulário, com exibição de mensagens específicas para cada erro.

### 5. Backend
- Função `read_last_lines` implementada para ler as últimas linhas do arquivo de log e enviar ao frontend em caso de erro.
- Função `send_json_error` agora aceita o conteúdo do log como parâmetro e inclui no JSON de resposta.
- Em modo de desenvolvimento, o instalador mostra detalhes do erro (arquivo, linha) junto com o log.

### 6. Seeder de Páginas
- Pequenas correções de sintaxe e formatação nos arquivos de seed de páginas (`PaginasSeeder.php`).

### 7. Dashboard
- Ajuste na função `dashboard_remover_pagina_instalacao_sucesso` para simplificar a chamada do método de remoção da página de sucesso após a instalação.

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

# RELEASE: Conn2Flow Gestor - Refatorações e Correções Gerais (Julho 2025)

## RESUMO DAS ALTERAÇÕES

### 1. Dashboard
- Refatoração da função `dashboard_remover_pagina_instalacao_sucesso` para simplificar e otimizar a remoção da página de sucesso após a instalação.
- Ajuste nas chamadas de banco de dados para evitar duplicidade e garantir consistência.
- Melhoria na integração dos componentes do dashboard e inclusão de toasts informativos.

### 2. Seeds de Páginas
- Correções de sintaxe e formatação nos arquivos de seed (`PaginasSeeder.php`), evitando duplicidade e garantindo consistência dos dados iniciais.

### 3. Performance e Organização
- Pequenas melhorias de performance e organização de código em funções do dashboard e módulos relacionados.

## ARQUIVOS MODIFICADOS

- `gestor/modulos/dashboard/dashboard.php` (refatoração e correções)
- `gestor/db/seeds/PaginasSeeder.php` (correções de sintaxe)

## INSTRUÇÕES PARA O AGENTE GIT

1. Executar o script de release para o gestor com o tipo `patch` e as mensagens de commit apropriadas. Exemplo:
   `./ai-workspace/scripts/release.sh patch "refactor(dashboard): Simplifica remoção de página de sucesso e corrige seeds" "refactor(dashboard): Otimiza função de remoção, corrige seeds e melhora integração de componentes."`
2. O script irá incrementar a versão do gestor conforme padrão e criar a tag correspondente.
3. Gerar um novo release zipado do `gestor` com todas as melhorias e correções.
4. Publicar a nova versão do gestor.

**Versão:** 1.8.7
**Data:** Julho 2025
**Criticidade:** Média (Refatoração, correções e melhorias de performance)
**Compatibilidade:** Total
