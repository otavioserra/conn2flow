# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto segue [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [Unreleased]

### Added
- Documentação técnica detalhada em `ai-workspace/docs/`
- Histórico completo de mudanças em `CONN2FLOW-CHANGELOG-HISTORY.md`
- CHANGELOG.md padrão da indústria seguindo Keep a Changelog

## [2.0.19] - 2025-09-15

### Added
- **Sistema de Logging Unificado de Plugins**: Unificação completa dos logs de operações de banco de dados de plugins com prefixo `[db-internal]` para identificação clara
- **Componente de Exibição de Versão**: Novo componente elegante para exibir versão do gestor no layout administrativo usando Semantic UI
- **Correções Críticas na Instalação de Plugins**: Resolução de conflitos de função e compatibilidade web/CLI para instalação robusta

### Changed
- **Refatoração do Sistema de Logs**: Substituição de 25+ chamadas `log_disco()` por `log_unificado()` em scripts de atualização de plugins
- **Melhoria na Arquitetura de Plugins**: Detecção automática de logger externo e prefixação inteligente de logs

### Fixed
- **Correção de Conflitos de Função**: Resolução de "Cannot redeclare function" em contexto web durante instalação de plugins
- **Compatibilidade Web/CLI**: Adição de declarações globais adequadas para execução web de scripts de instalação
- **Namespace Conflicts**: Correção de conflitos de nomes em scripts de atualização de banco de dados de plugins

### Security
- **Rastreabilidade Aprimorada**: Logs unificados facilitam auditoria e debugging de operações de plugins

### Added
- **Sistema de Plugins Aprimorado**: Correções críticas e novas funcionalidades para plugins
- **Arquitetura de Plugins V2**: Detecção dinâmica de Data.json e rastreio completo de origem
- **Templates de Desenvolvimento**: Padronização e automação completa para criação de plugins
- **Sistema de Rastreio de Dados**: Injeção automática de slug em tabelas com coluna plugin
- **Resolução Dinâmica de Ambiente**: Environment.json dinâmico em todos os scripts de automação
- **Estrutura de Plugins Refatorada**: Nova arquitetura para desenvolvimento de plugins Conn2Flow
- **Documentação Abrangente**: Sistema completo de documentação para módulos e plugins
- **Limpeza Ampla do Sistema**: Desabilitação de ferramentas legadas e simplificação da estrutura

### Changed
- **Migração para IDs Textuais**: Campos de referência de módulos convertidos para formato textual
- **Scripts de Automação Padronizados**: Resolução dinâmica do environment.json em todos os scripts
- **Arquitetura de Plugins Modernizada**: Estrutura V2 com detecção automática e templates

### Fixed
- **Correções Críticas em Plugins**: Sistema de plugins com detecção dinâmica e correções de origem
- **Timezone Corrigido**: Ajuste para America/Sao_Paulo no ambiente Docker
- **Compatibilidade de Scripts**: Todos os scripts agora funcionam em qualquer repositório de plugin

### Security
- **Rastreabilidade Completa**: Sistema de origem de dados para futura desinstalação limpa de plugins

## [1.16.0] - 2025-09-02

### Added
- Sistema de Preview TailwindCSS/FomanticUI com CodeMirror
- Modal responsivo para preview de recursos CSS
- Suporte a múltiplos frameworks CSS (TailwindCSS e FomanticUI)
- Editor de código com syntax highlighting
- Sistema de exportação de recursos CSS aprimorado

### Changed
- Melhorias na interface de preview
- Otimização do sistema de modals

### Fixed
- Correções na renderização de preview CSS
- Melhorias na compatibilidade com diferentes frameworks

## [1.15.0] - 2025-08-31

### Added
- Sistema de arquitetura de recursos aprimorado
- Melhorias no processo de exportação
- Validação automática de recursos

### Changed
- Otimização da estrutura de dados de recursos
- Melhoria na performance de exportação

### Fixed
- Correções críticas no sistema de exportação
- Resolução de bugs na validação de recursos

## [1.14.0] - 2025-08-30

### Added
- Sistema de limpeza e otimização HTML/CSS
- Melhorias na estrutura de componentes
- Validação automática de estrutura HTML

### Changed
- Reorganização da arquitetura de layout
- Otimização do processo de limpeza

### Fixed
- Correções na estrutura HTML/CSS
- Melhorias na validação de componentes

## [1.13.0] - 2025-08-29

### Added
- Sistema de páginas e componentes aprimorado
- Melhorias no gerenciamento de layouts
- Validação de estrutura de páginas

### Changed
- Otimização do sistema de componentes
- Melhoria na organização de layouts

### Fixed
- Correções no sistema de páginas
- Resolução de problemas de estrutura

## [1.12.0] - 2025-08-28

### Added
- Melhorias no sistema de instalação
- Aprimoramento do instalador automático
- Validação de ambiente pós-instalação

### Changed
- Otimização do processo de instalação
- Melhoria na detecção de dependências

### Fixed
- Correções críticas no instalador
- Resolução de problemas de compatibilidade

## [1.11.0] - 2025-08-27

### Added
- Sistema de correções críticas
- Melhorias na estabilidade do sistema
- Validação automática de integridade

### Changed
- Otimização da performance geral
- Melhoria na gestão de erros

### Fixed
- Correções críticas de bugs
- Melhorias na estabilidade

## [1.10.0] - 2025-08-26

### Added
- Sistema de preview com modals responsivos
- Melhorias na interface de usuário
- Validação de responsividade

### Changed
- Otimização do sistema de preview
- Melhoria na experiência do usuário

### Fixed
- Correções na responsividade
- Melhorias na compatibilidade

## [1.9.0] - 2025-08-25

### Added
- Sistema de atualizações automáticas
- Melhorias no gerenciamento de versões
- Validação de atualizações

### Changed
- Otimização do processo de atualização
- Melhoria na gestão de versões

### Fixed
- Correções no sistema de atualizações
- Resolução de problemas de versionamento

## [1.8.5] - 2025-07-31

### Added
- Preservação de logs durante instalação
- Sistema de login automático aprimorado
- Reorganização de configurações cPanel

### Changed
- Melhoria na experiência de instalação
- Otimização da preservação de dados

### Fixed
- Correções críticas no sistema de logs
- Melhorias na estabilidade do login automático

## [1.8.4] - 2025-07-30

### Added
- Detecção automática de URL_RAIZ
- Sistema de recuperação inteligente
- Correções SQL automáticas

### Changed
- Melhoria na detecção de configurações
- Otimização do sistema de recuperação

### Fixed
- Correções críticas em SQL
- Melhorias na detecção de URLs

## [1.8.0] - 2025-07-25

### Added
- Sistema híbrido de migração com Phinx
- Execução automática de migrações e seeders
- Sistema de configuração por ambiente (.env)
- Instalador automático completo

### Changed
- Migração de sistema SQL para Phinx
- Melhoria na gestão de configurações

### Fixed
- Correções no sistema de migrações
- Melhorias na estabilidade do instalador

## [1.7.0] - 2025-07-20

### Added
- Sistema de releases automatizados
- GitHub Actions para deploy contínuo
- Workflows de automação
- Tags automáticas de versionamento

### Changed
- Modernização do sistema de releases
- Melhoria na documentação

### Fixed
- Correções nos workflows de release
- Melhorias na automatização

## [1.6.0] - 2025-07-15

### Added
- Sistema multilíngue híbrido completo
- Suporte a múltiplos idiomas
- Gestão dinâmica de traduções

### Changed
- Otimização do sistema multilíngue
- Melhoria na performance de traduções

### Fixed
- Correções no sistema de idiomas
- Melhorias na gestão de traduções

## [1.0.0] - 2025-02-01

### Added
- Versão inicial do sistema Conn2Flow
- Sistema de gestão de conteúdo
- Estrutura modular básica
- Sistema de autenticação
- Interface administrativa

### Security
- Implementação de autenticação segura
- Proteção contra vulnerabilidades básicas

---

## Tipos de Mudanças

- `Added` para novas funcionalidades
- `Changed` para mudanças em funcionalidades existentes
- `Deprecated` para funcionalidades que serão removidas em breve
- `Removed` para funcionalidades removidas
- `Fixed` para correções de bugs
- `Security` para melhorias de segurança

## Links de Comparação

[Unreleased]: https://github.com/conecta2me/conn2flow/compare/gestor-v2.0.19...HEAD
[2.0.19]: https://github.com/conecta2me/conn2flow/compare/gestor-v2.0.0...gestor-v2.0.19
[2.0.0]: https://github.com/conecta2me/conn2flow/compare/gestor-v1.16.0...gestor-v2.0.0
[1.16.0]: https://github.com/conecta2me/conn2flow/compare/v1.15.0...gestor-v1.16.0
[1.15.0]: https://github.com/conecta2me/conn2flow/compare/v1.14.0...v1.15.0
[1.14.0]: https://github.com/conecta2me/conn2flow/compare/v1.13.0...v1.14.0
[1.13.0]: https://github.com/conecta2me/conn2flow/compare/v1.12.0...v1.13.0
[1.12.0]: https://github.com/conecta2me/conn2flow/compare/v1.11.0...v1.12.0
[1.11.0]: https://github.com/conecta2me/conn2flow/compare/v1.10.0...v1.11.0
[1.10.0]: https://github.com/conecta2me/conn2flow/compare/v1.9.0...v1.10.0
[1.9.0]: https://github.com/conecta2me/conn2flow/compare/v1.8.5...v1.9.0
[1.8.5]: https://github.com/conecta2me/conn2flow/compare/v1.8.4...v1.8.5
[1.8.4]: https://github.com/conecta2me/conn2flow/compare/v1.8.0...v1.8.4
[1.8.0]: https://github.com/conecta2me/conn2flow/compare/v1.7.0...v1.8.0
[1.7.0]: https://github.com/conecta2me/conn2flow/compare/v1.6.0...v1.7.0
[1.6.0]: https://github.com/conecta2me/conn2flow/compare/v1.0.0...v1.6.0
[1.0.0]: https://github.com/conecta2me/conn2flow/releases/tag/v1.0.0
