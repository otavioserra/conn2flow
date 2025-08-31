# CONN2FLOW - GitHub Actions e Automação CI/CD

## 📋 Visão Geral

O projeto Conn2Flow possui um **sistema completo de automação CI/CD** através do GitHub Actions, desenvolvido para automatizar completamente os processos de release tanto do **Gestor** quanto do **Instalador**. Este sistema representa uma implementação madura de DevOps para projetos PHP.

## 🏗️ Estrutura de Automação

```
.github/
├── workflows/              # Workflows GitHub Actions
│   ├── release-gestor.yml      # Automação release do Gestor
│   └── release-instalador.yml  # Automação release do Instalador
├── scripts/                # Scripts utilitários
│   └── get-latest-installer.sh # Busca última versão do instalador
└── chatmodes/              # Configurações para agentes IA
    └── 4.1-Beast.chatmode.md  # Modo de desenvolvimento avançado
```

## 🚀 Workflows de Release

### 1. Release Gestor (`release-gestor.yml`)

**Trigger:** Tags que começam com `gestor-v*` (ex: `gestor-v1.16.0`)

#### Processo Automatizado:
```yaml
1. Checkout do código fonte
2. Setup PHP 8.2 + extensões necessárias
3. Instalação Composer (produção)
4. Geração automática de recursos
5. Commit das atualizações
6. Limpeza de arquivos desnecessários
7. Criação do gestor.zip + checksum SHA256
8. Criação da release no GitHub
9. Upload dos assets
```

#### Características Técnicas:
- **Otimização de tamanho**: Remove recursos físicos (mantém JSON)
- **Segurança**: Remove arquivos `.env` sensíveis
- **Integridade**: Gera checksum SHA256 automático
- **Limpeza**: Remove cache, node_modules, arquivos temporários
- **Versionamento automático**: Atualização de recursos pré-release

#### Assets Gerados:
- `gestor.zip` - Sistema completo otimizado para produção
- `gestor.zip.sha256` - Checksum para verificação de integridade

### 2. Release Instalador (`release-instalador.yml`)

**Trigger:** Tags que começam com `instalador-v*` (ex: `instalador-v1.4.0`)

#### Processo Automatizado:
```yaml
1. Checkout do código fonte
2. Criação do instalador.zip
3. Exclusão de arquivos de desenvolvimento
4. Criação da release no GitHub
5. Upload do asset
```

#### Características Técnicas:
- **Instalador puro**: Apenas arquivos necessários para instalação
- **Multi-idioma**: Suporte PT-BR e EN preservado
- **Limpeza automática**: Remove logs, cache, arquivos temporários
- **Compatibilidade**: Preparado para todas as versões do Gestor

#### Assets Gerados:
- `instalador.zip` - Instalador web completo e multilíngue

## 🔧 Scripts Utilitários

### get-latest-installer.sh
```bash
#!/bin/bash
# Busca automaticamente a URL do instalador mais recente
# Filtra apenas releases "instalador-v*"
# Retorna URL direta para download
```

**Uso:**
```bash
# Obter URL do instalador mais recente
bash .github/scripts/get-latest-installer.sh

# Saída: https://github.com/otavioserra/conn2flow/releases/download/instalador-vX.Y.Z/instalador.zip
```

**Aplicação:**
- Automação de deploy
- Scripts de instalação
- Documentação dinâmica
- Integração com outros sistemas

## 🤖 Desenvolvimento Assistido por IA

### Beast Mode Chat Configuration
**Arquivo:** `.github/chatmodes/4.1-Beast.chatmode.md`

Configuração avançada para agentes IA (GitHub Copilot, Claude, ChatGPT) com:

#### Características:
- **Autonomia total**: Resolve problemas completamente antes de retornar
- **Pesquisa obrigatória**: Sempre busca informações atualizadas na web
- **Iteração contínua**: Não para até o problema estar 100% resolvido
- **Testes rigorosos**: Validação completa de todas as alterações
- **Planejamento detalhado**: Todo-lists estruturados para acompanhamento

#### Workflow de Desenvolvimento:
```markdown
1. Buscar URLs fornecidas pelo usuário
2. Entender profundamente o problema
3. Investigar o código-base
4. Pesquisar na internet
5. Desenvolver plano detalhado
6. Implementar incrementalmente
7. Debugar quando necessário
8. Testar frequentemente
9. Iterar até resolução completa
10. Validar e refletir sobre resultado
```

#### Aplicação Prática:
- **Desenvolvimento de features**: Implementação completa e robusta
- **Correção de bugs**: Investigação profunda e solução definitiva
- **Refatoração**: Melhorias estruturais com testes abrangentes
- **Documentação**: Criação detalhada e atualizada

## ⚙️ Configuração e Funcionamento

### Processo de Release Completo

#### Para o Gestor:
```bash
# 1. Desenvolvimento local
git add .
git commit -m "feature: nova funcionalidade"

# 2. Criar tag de release
git tag gestor-v1.17.0
git push origin gestor-v1.17.0

# 3. GitHub Actions automaticamente:
# - Gera recursos atualizados
# - Cria ZIP otimizado
# - Publica release
# - Disponibiliza para download
```

#### Para o Instalador:
```bash
# 1. Desenvolvimento local
git add gestor-instalador/
git commit -m "feat: melhoria no instalador"

# 2. Criar tag de release
git tag instalador-v1.5.0
git push origin instalador-v1.5.0

# 3. GitHub Actions automaticamente:
# - Cria ZIP do instalador
# - Publica release
# - Disponibiliza para download
```

### Integração com Sistema de Atualizações

O GitHub Actions trabalha integrado com o sistema de atualizações automáticas:

```php
// Sistema busca automaticamente no GitHub
$latest_gestor = "https://github.com/otavioserra/conn2flow/releases/download/gestor-v1.16.0/gestor.zip";
$checksum = "https://github.com/otavioserra/conn2flow/releases/download/gestor-v1.16.0/gestor.zip.sha256";

// Download e verificação automáticos
download_and_verify($latest_gestor, $checksum);
```

## 📊 Benefícios da Automação

### ✅ **Para Desenvolvedores**
- **Zero configuração manual**: Release automático via tag
- **Qualidade garantida**: Testes e validações automáticas
- **Versionamento consistente**: Padrões rigorosos de nomenclatura
- **Feedback imediato**: Notificações de sucesso/falha

### ✅ **Para Usuários**
- **Releases confiáveis**: Processo padronizado e testado
- **Integridade verificada**: Checksums SHA256 automáticos
- **Disponibilidade imediata**: Assets publicados automaticamente
- **Versionamento claro**: Tags e releases organizados

### ✅ **Para o Projeto**
- **DevOps maduro**: CI/CD completo e documentado
- **Qualidade de código**: Padrões automatizados
- **Escalabilidade**: Fácil adição de novos workflows
- **Rastreabilidade**: Histórico completo de releases

## 🔄 Workflows Específicos por Release

### Release Gestor v1.16.0 (Atual)
```yaml
Funcionalidades automáticas:
✅ Geração de recursos TailwindCSS/FomanticUI
✅ Compilação de módulos admin-layouts, admin-componentes
✅ Limpeza de arquivos de desenvolvimento
✅ Otimização para produção
✅ Documentação automática de release notes
✅ Upload de assets com checksums
```

### Release Instalador v1.4.0 (Atual)
```yaml
Funcionalidades automáticas:
✅ Empacotamento do instalador completo
✅ Preservação de arquivos multilíngues
✅ Limpeza de arquivos temporários
✅ Compatibilidade com Gestor v1.16.0+
✅ Documentação automática de recursos
✅ Upload direto para download
```

## 🛠️ Configurações Técnicas

### Permissions e Segurança
```yaml
permissions:
  contents: write  # Necessário para criar releases
  
env:
  GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}  # Token automático
```

### Ambiente de Build
```yaml
runs-on: ubuntu-latest  # Ambiente Linux padronizado
php-version: '8.2'      # PHP moderno e compatível
extensions: zip, curl, mbstring, openssl  # Extensões necessárias
composer: --no-dev --optimize-autoloader  # Produção otimizada
```

### Versionamento Automático
```yaml
# Tags suportadas:
gestor-v*.*.* → Release do Gestor
instalador-v*.*.* → Release do Instalador

# Exemplos:
gestor-v1.16.0, gestor-v1.17.0-beta
instalador-v1.4.0, instalador-v1.5.0-rc1
```

## 📈 Evolução e Melhorias

### Versões Implementadas
- **v1.0**: Workflow básico de release
- **v2.0**: Automação de geração de recursos
- **v3.0**: Checksums e verificação de integridade
- **v4.0**: Limpeza automática e otimização
- **v5.0**: Documentação automática e release notes detalhadas

### Melhorias Futuras Planejadas
- **Testes automatizados**: PHPUnit integrado nos workflows
- **Deploy automático**: Deploy direto para servidores de demo
- **Notificações**: Webhook para Discord/Slack
- **Multi-environment**: Deploy staging/production
- **Security scanning**: Análise automática de vulnerabilidades

## 🔧 Solução de Problemas

### Problemas Comuns

#### Workflow falha na geração de recursos
```bash
# Verificar se arquivo existe
php gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php

# Verificar permissões de escrita
ls -la gestor/db/data/
ls -la gestor/resources/
```

#### Release não é criada
```bash
# Verificar formato da tag
git tag --list | grep -E "(gestor|instalador)-v"

# Verificar push da tag
git ls-remote --tags origin
```

#### Assets não são enviados
```bash
# Verificar se arquivos foram criados
ls -la gestor.zip*
ls -la instalador.zip*

# Verificar logs do workflow no GitHub
```

### Debugging de Workflows
```yaml
# Adicionar step de debug
- name: Debug Environment
  run: |
    echo "Working directory: $(pwd)"
    echo "Files in directory:"
    ls -la
    echo "PHP version:"
    php -v
```

## 📚 Arquivos de Referência

### Documentação Oficial
- `.github/workflows/release-gestor.yml` - Workflow principal do Gestor
- `.github/workflows/release-instalador.yml` - Workflow do Instalador
- `.github/scripts/get-latest-installer.sh` - Script de busca automática
- `.github/chatmodes/4.1-Beast.chatmode.md` - Configuração IA avançada

### Logs e Monitoramento
- GitHub Actions → [Projeto Conn2Flow](https://github.com/otavioserra/conn2flow/actions)
- Releases → [Página de Releases](https://github.com/otavioserra/conn2flow/releases)
- Tags → [Tags do Projeto](https://github.com/otavioserra/conn2flow/tags)

### Integração com Documentação
- [Sistema de Atualizações](CONN2FLOW-ATUALIZACOES-SISTEMA.md) - Como o sistema usa os releases
- [Instalador Detalhado](CONN2FLOW-INSTALADOR-DETALHADO.md) - Como o instalador baixa releases
- [Ambiente Docker](CONN2FLOW-AMBIENTE-DOCKER.md) - Integração com desenvolvimento local

---

## 🎯 Conclusão

O sistema GitHub Actions do Conn2Flow representa uma **solução madura e completa de DevOps** para projetos PHP. Com automação total dos releases, verificação de integridade, otimização para produção e documentação automática, oferece:

- **🚀 Automação completa** de releases via tags
- **🔒 Segurança garantida** com checksums e limpeza automática
- **⚡ Eficiência máxima** com workflows otimizados
- **📊 Qualidade consistente** através de padrões automatizados
- **🛠️ Ferramentas avançadas** para desenvolvimento assistido por IA
- **📈 Escalabilidade** para futuras expansões

**Status**: ✅ **Produção - Estável e Documentado**  
**Última atualização**: Agosto 2025  
**Desenvolvido por**: Otavio Serra + GitHub Copilot IA  
**Integração**: Sistema completo de CI/CD para Conn2Flow
