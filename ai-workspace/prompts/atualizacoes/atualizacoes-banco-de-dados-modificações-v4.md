# Prompt Interactive Programming - Atualização dos Projetos Não Atualizar Banco de Dados Marcados como de Projeto

## 🎯 Contexto Inicial

**Problema Identificado:**
Durante a atualização normal do sistema, todos os registros atualizados pelo deploy de projetos via API OAuth (implementado na v2.4.0), o atualizador do banco de dados está sobrescrevendo registros no banco de dados que foram modificados pelo deploy do projeto. Isso ocorre porque as atualizações normais do sistema (atualizações de versão) não distinguem entre registros modificados atualizados via deploy de projeto.

**Solução Proposta:**
Implementar um sistema de marcação similar ao `user_modified`, mas para projetos. Quando um registro é atualizado via deploy de projeto, ele será marcado com o ID do projeto, impedindo que atualizações normais do sistema o sobrescrevam.

**Tabelas Afetadas:**
- `componentes` ✅ (possui user_modified)
- `layouts` ✅ (possui user_modified)
- `paginas` ✅ (possui user_modified)
- `variaveis` ✅ (possui user_modified)
- `templates` ✅ (possui user_modified - migração 20251030160430_create_templates_table.php)

**Campo a Ser Adicionado:**
- `project` (VARCHAR(255) NULL) - Armazena o ID do projeto que fez a última atualização.

**Formato do PROJECT_TARGET:**
- String identificadora do projeto (ex: "digitalfluxus", "meu-projeto")
- Obtido de `devEnvironment.projectTarget` no environment.json
- Usado como chave para acessar configurações específicas do projeto

### 🏗️ Arquitetura Proposta

**Fluxo de Deploy de Projeto (COM --project):**
1. Script `deploy-projeto.sh` identifica `PROJECT_TARGET`
2. Envia ZIP + header `X-Project-ID: $PROJECT_TARGET` para API
3. API executa atualização com `--project=$PROJECT_TARGET`
4. Atualização sobrescreve dados normalmente e marca `project = PROJECT_TARGET`
5. Registros ficam protegidos contra atualizações normais futuras

**Fluxo de Atualização Normal (SEM --project):**
1. Atualização normal do sistema é executada
2. Registros com `project IS NOT NULL` são pulados (não atualizados)
3. Registros com `user_modified = 1` são preservados (lógica existente)
4. Apenas registros sem marcação são atualizados normalmente

**Lógica de Proteção:**
- **Deploy com --project**: Sempre sobrescreve e marca com project ID
- **Update normal**: Pula registros com `project IS NOT NULL` (exceto se `user_modified = 1`)
- **user_modified = 1**: Sempre priorizado (usuário tem controle total, sobrescreve qualquer proteção)

**Cenários de Uso:**
- Deploy de projeto: Atualiza tudo e marca com project ID
- Update normal: Respeita marcações de projeto (não sobrescreve)
- Usuário modifica: `user_modified=1` permite sobrescrever proteção de projeto

## 📝 Orientações para o Agente

### 🎯 Objetivos do Projeto:
1. **Criar Migração de Banco**: Adicionar campo `project` nas tabelas especificadas
2. **Atualizar API de Deploy**: Modificar endpoint `/_api/project/update` para marcar registros com project ID
3. **Atualizar Script de Deploy**: Modificar `deploy-projeto.sh` para passar project target
4. **Testar Integração**: Verificar que atualizações normais respeitam a marcação de projeto

### 📋 Etapas de Implementação:

#### Etapa 1: Criar Migração de Banco de Dados
- Criar nova migração em `gestor/db/migrations/`
- Adicionar campo `project` VARCHAR(255) NULL nas tabelas:
  - componentes ✅ (já possui user_modified)
  - layouts ✅ (já possui user_modified)
  - paginas ✅ (já possui user_modified)
  - variaveis ✅ (já possui user_modified)
  - templates ✅ (já possui user_modified - migração 20251030160430_create_templates_table.php)
- Executar migração e verificar estrutura das tabelas

#### Etapa 2: Atualizar API de Deploy de Projetos
- Modificar `gestor/controladores/api/api.php` endpoint `/_api/project/update`
- Durante processamento do ZIP e atualização do banco, marcar registros com project ID
- Usar o `PROJECT_TARGET` passado via header HTTP `X-Project-ID` ou parâmetro no corpo da requisição
- Implementar lógica para definir `project = ?` durante INSERT/UPDATE dos registros

#### Etapa 3: Atualizar Script de Deploy
- Modificar `ai-workspace/scripts/projects/deploy-projeto.sh`
- Adicionar header `X-Project-ID: $PROJECT_TARGET` na requisição curl para a API
- Verificar se a API recebe e processa corretamente o project ID

#### Etapa 4: Atualizar Lógica de Atualização Normal
- Modificar `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`
- **Quando SEM --project**: Adicionar condição para pular registros com `project IS NOT NULL`
- **Quando COM --project**: Sobrescrever normalmente e definir `project = PROJECT_ID` em todos os registros atualizados/inseridos
- Manter prioridade do `user_modified = 1` (usuário sempre tem controle total)
- Adicionar logging para registros pulados devido a marcações de projeto
- Implementar lógica: 
  - Se `--project` definido: atualizar tudo e marcar com project ID
  - Se `--project` não definido: pular registros com `project IS NOT NULL` (exceto se `user_modified = 1`)

#### Etapa 5: Testes e Validação
- Testar deploy de projeto marcando registros corretamente
- Testar atualização normal respeitando marcações
- Verificar rollback e recovery em caso de falhas

### 🔧 Requisitos Técnicos:
- **PHP**: 8.1+
- **MySQL/MariaDB**: 5.7+
- **Migrations**: Usar Phinx para migrations
- **API**: OAuth 2.0 authentication
- **Scripts**: Bash para deploy automation

### 🔧 Detalhes de Implementação Técnica

**No `sincronizarTabela()` - `atualizacoes-banco-de-dados.php`:**
```php
// Verificar se é execução de projeto
$project = !empty($GLOBALS['CLI_OPTS']['project']) ?? null;

// Durante updates/inserts:
if ($project) {
    // Deploy de projeto: sempre sobrescrever e marcar
    $row['project'] = $project;
} else {
    // Update normal: pular registros marcados com projeto
    if (!empty($exist['project'])) {
        // Pular este registro se não for user_modified
        if (empty($exist['user_modified']) || (int)$exist['user_modified'] !== 1) {
            log_disco("SKIP_PROJECT_PROTECTED tabela=$tabela chave=$k project={$exist['project']}");
            continue;
        }
    }
}
```

**No `api_executar_atualizacao_banco()` - `api.php`:**
```php
// Receber PROJECT_TARGET via header ou parâmetro
$projectId = $_SERVER['HTTP_X_PROJECT_ID'] ?? null;

// Passar para o script de atualização
$cli['project'] = $projectId;
```

**No `deploy-projeto.sh`:**
```bash
# Adicionar header na requisição curl
curl -H "X-Project-ID: $PROJECT_TARGET" ...
```

## 🤔 Dúvidas e 📝 Sugestões

**Dúvidas Pendentes:**
- ✅ Tabela `templates` existe e possui `user_modified` (migração verificada)
- ✅ PROJECT_TARGET é uma string do environment.json (ex: "digitalfluxus")
- ✅ Lógica definida: --project = sobrescrever e marcar; sem --project = respeitar marcações
- Como passar o PROJECT_TARGET para a API? Via header HTTP `X-Project-ID` ou parâmetro CLI?
- Como implementar a marcação `project = PROJECT_ID` durante updates/inserts no script?
- Precisa de migration rollback para remover o campo se necessário?
- Como limpar marcações de projeto quando necessário (ex: reverter deploy)?

**Sugestões de Implementação:**
- Usar transactions no banco para garantir atomicidade das operações
- Adicionar logging detalhado para debug de conflitos
- Implementar comando CLI para limpar marcações de projeto se necessário
- Considerar adicionar campo `project_updated_at` TIMESTAMP para auditoria
- Testar thoroughly: deploy marca, update normal respeita, user_modified sobrescreve

## ✅ Progresso da Implementação
- [x] Análise completa do código atual (API, scripts, migrations) - Tabelas verificadas, PROJECT_TARGET identificado
- [x] Lógica de proteção definida: --project = sobrescrever/marcar; sem --project = respeitar marcações
- [x] Parâmetro --project já implementado no script atualizacoes-banco-de-dados.php
- [x] Criação da migração para adicionar campo `project`
- [x] Teste da migração em ambiente de desenvolvimento
- [x] Modificação da API para passar --project para o script de atualização
- [x] Atualização do script deploy-projeto.sh para enviar PROJECT_TARGET
- [x] Implementação da lógica condicional no sincronizarTabela() - Aplicada apenas nas 5 tabelas do $preserveMap
- [x] Testes de integração completos
- [x] Documentação das mudanças
- [ ] Validação em produção (staging first)

---
**Data:** 13/11/2025 (Atualizado com correções e lógica definida)
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow v2.5.1 - Project-Based Database Update Protection

**Status:** Projeto atualizado com as 11 correções aplicadas. Lógica definida: deploy com --project sobrescreve e marca; update normal respeita marcações. Pronto para análise e implementação.