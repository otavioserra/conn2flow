# BATCH-031 - Estruturação de Framework de Testes Unitários e E2E

## Escopo do Lote
Este lote implementa e configura um ambiente completo de testes automatizados para o Conn2Flow, instalando e estruturando o PHPUnit (para testes de backend em PHP), o Vitest (para testes de scripts frontend em JS) e o Playwright (para validação funcional E2E no navegador). O lote também cria suítes de testes piloto e integra a execução automática no pipeline de CI/CD.

---

## Checklist de Implementação

### 1. Estruturação Física e Configuração de Ambiente
- [ ] Criar a pasta raiz `tests/` com a seguinte subestrutura:
  - `tests/Unit/PHP/` (testes de unidade de bibliotecas core e helpers PHP)
  - `tests/Unit/JS/` (testes de unidade de controladores/widgets JS)
  - `tests/Integration/` (testes de banco de dados, fluxo de migrações e rotas)
  - `tests/E2E/` (testes end-to-end de fluxos completos de painel e site publicado)
- [ ] Adicionar e configurar o arquivo `phpunit.xml` na raiz do projeto, definindo a suíte de testes do PHP, bootstrapping de dependências (carregamento de `gestor/config.php` e Common/PDO stubs) e diretórios de cobertura.
- [ ] Configurar o arquivo `vitest.config.js` na raiz do projeto para o ambiente de testes de JS, definindo stubs do DOM (utilizando `happy-dom` ou `jsdom`) para simular o comportamento de componentes jQuery e Fomantic UI.

### 2. Framework de Testes do Backend (PHPUnit)
- [ ] Integrar o PHPUnit nas dependências do Composer como pacote de desenvolvimento (`composer require --dev phpunit/phpunit`).
- [ ] Criar um banco de dados de testes SQLite em memória ou banco MySQL dedicado a testes configurado no bootstrap.
- [ ] Escrever testes piloto de unidade:
  - [ ] Validar a helper de inclusão de recursos `gestor_pagina_recursos_incluir()`.
  - [ ] Validar sanitizações, manipulação de URLs e criptografia básica das bibliotecas comuns.
- [ ] Escrever testes piloto de integração:
  - [ ] Validar roteamento de widgets dinâmicos (`gestor_pagina_widgets_ajax()`).
  - [ ] Validar execução de migrações em um banco limpo de testes.

### 3. Framework de Testes do Frontend (Vitest)
- [ ] Inicializar o ambiente NPM na raiz para testes (caso necessário) ou configurar dependências locais de dev para rodar os testes JS.
- [ ] Configurar mocks globais para as variáveis do core `$_GESTOR` e bibliotecas externas (jQuery, Fomantic UI modules).
- [ ] Escrever testes piloto de unidade JS:
  - [ ] Testar rotinas de debounce, detecção de modificação de templates e formatação de campos dinâmicos em `publisher-highlights.js`.
  - [ ] Testar a lógica de remontagem e ordenação dinâmica em `publisher-index.widget.js`.

### 4. Framework de Testes E2E (Playwright)
- [ ] Instalar o Playwright (`npm init playwright@latest -- --yes` direcionado à pasta `tests/E2E`).
- [ ] Configurar o `playwright.config.js` para rodar contra um servidor local de desenvolvimento (utilizando o ambiente do Docker local de desenvolvimento como base).
- [ ] Desenvolver scripts E2E de validação crítica:
  - [ ] Fluxo completo de Login Administrativo -> Acesso ao Dashboard -> Modificação de Perfil.
  - [ ] Fluxo de Criação de Destaques -> Associação de Publicador -> Renderização no Site Público.
  - [ ] Fluxo de busca, ordenação e clique em "Carregar Mais" no Publicador Índice.

### 5. Integração Contínua (CI/CD)
- [ ] Criar tarefas e scripts facilitadores de execução local (ex: `composer test`, `npm run test:ui`).
- [ ] Adicionar um novo workflow do GitHub Actions `.github/workflows/run-tests.yml` para rodar a suíte completa de testes unitários em cada Pull Request ou push na branch `main`.

---

## Validação Esperada
O lote será considerado completo após aprovação na checklist de validação de `BATCH-031`.
