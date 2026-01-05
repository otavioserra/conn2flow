# Projeto: Seletor de Linguagens e Detecção Automática

## 🎯 Contexto Inicial
O sistema `conn2flow` já possui suporte a rotas com identificação de linguagem (ex: `/en/pagina/`), onde o backend detecta o código da linguagem na URL, define a variável de ambiente e ajusta o caminho da requisição.
O objetivo agora é implementar a interface para o usuário final (frontend) e controles administrativos (backend) para gerenciar essa funcionalidade.

## 📝 Especificações Técnicas

### 1. Configuração (Admin Environment)
**Objetivo:** Permitir ativar/desativar o widget e a detecção automática via painel.

*   **Arquivos:**
    *   `gestor/modulos/admin-environment/resources/pt-br/pages/admin-environment/admin-environment.html`
    *   `gestor/modulos/admin-environment/admin-environment.js`
    *   `gestor/modulos/admin-environment/admin-environment.php`
*   **Novas Variáveis de Ambiente (.env):**
    *   `LANGUAGE_WIDGET_ACTIVE` (true/false)
    *   `LANGUAGE_AUTO_DETECT` (true/false)
*   **Implementação:**
    *   Adicionar checkboxes na aba "Configurações de Linguagem" do HTML.
    *   Atualizar o JS para enviar esses novos campos no AJAX de salvar.
    *   Atualizar o PHP para ler/gravar essas variáveis no arquivo `.env`.

### 2. Sinalização Backend (Gestor Core)
**Objetivo:** Informar ao frontend se o widget deve ser renderizado e passar dados de contexto.

*   **Arquivo:** `gestor/gestor.php`
*   **Lógica:**
    *   Verificar se `$_ENV['LANGUAGE_WIDGET_ACTIVE']` é `true`.
    *   Se sim, popular `$_GESTOR['javascript-vars']['languages']` com um array contendo:
        *   `active`: bool (estado do widget)
        *   `auto_detect`: bool (estado da detecção)
        *   `current`: string (código da linguagem atual, ex: 'pt-br')
        *   `list`: array (lista de linguagens disponíveis em `$_GESTOR['languages']`)
        *   `default`: string (linguagem padrão)

### 3. Frontend - Widget e Lógica (Global JS)
**Objetivo:** Renderizar o seletor e gerenciar a detecção/troca de idioma.

*   **Arquivo:** `gestor/assets/global/global.js`
*   **Lógica Principal:**
    *   Verificar existência de `gestor.languages`.
*   **Funcionalidade A: Widget de Seleção**
    *   Criar dinamicamente um elemento HTML (ex: botão flutuante ou item de menu) se `gestor.languages.active` for true.
    *   Ao clicar, mostrar opções baseadas em `gestor.languages.list`.
    *   **Ação de Troca:** Ao selecionar um idioma:
        1.  Gerar nova URL.
        2.  Lógica de URL:
            *   Se a URL atual já tem código de língua (ex: `/en/...`), substituir pelo novo.
            *   Se não tem, adicionar o novo código logo após a raiz da instalação.
        3.  Salvar cookie `language_code`.
        4.  Redirecionar `window.location.href`.
*   **Funcionalidade B: Detecção Automática**
    *   Verificar se o cookie `language_code` **NÃO** existe.
    *   Se não existe:
        1.  Ler `navigator.language` ou `navigator.userLanguage`.
        2.  Verificar se a linguagem detectada existe na lista permitida.
        3.  Gravar cookie `language_code` com a linguagem detectada (ou a padrão se não suportada).
        4.  Se `gestor.languages.auto_detect` for `true` E a linguagem detectada for diferente da atual da URL:
            *   Redirecionar para a URL com a linguagem correta.

## ✅ Progresso da Implementação

### Fase 1: Configuração (Admin Environment)
- [ ] 1.1 - Adicionar campos HTML no `admin-environment.html` (Widget Active, Auto Detect).
- [ ] 1.2 - Atualizar `admin-environment.js` para capturar e enviar novos dados.
- [ ] 1.3 - Atualizar `admin-environment.php` para processar leitura/escrita no .env.

### Fase 2: Backend (Gestor)
- [ ] 2.1 - Implementar lógica em `gestor.php` para injetar `languages` em `javascript-vars`.

### Fase 3: Frontend (Global JS)
- [ ] 3.1 - Implementar detecção automática e criação de cookie.
- [ ] 3.2 - Implementar construção da interface do Widget (HTML/CSS via JS).
- [ ] 3.3 - Implementar lógica de troca de URL (Redirecionamento).

---
**Data:** 25/11/2025
**Desenvolvedor:** GitHub Copilot
**Projeto:** Conn2Flow v2.5.x