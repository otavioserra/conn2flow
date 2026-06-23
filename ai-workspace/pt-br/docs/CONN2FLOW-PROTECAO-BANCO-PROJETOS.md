# Conn2Flow - Proteção de Banco de Dados Baseada em Projetos

## 📋 Índice
- [🎯 Contexto e Problema](#🎯-contexto-e-problema)
- [🏗️ Arquitetura da Solução](#🏗️-arquitetura-da-solução)
- [💾 Mudanças no Banco de Dados](#💾-mudanças-no-banco-de-dados)
- [🔧 Implementação Técnica](#🔧-implementação-técnica)
- [📦 API e Scripts](#📦-api-e-scripts)
- [🧪 Testes e Validação](#🧪-testes-e-validação)
- [📖 Referências](#📖-referências)

---

## 🎯 Contexto e Problema

### Problema Identificado
Durante a atualização normal do sistema Conn2Flow, todos os registros modificados pelo deploy de projetos via API OAuth estavam sendo sobrescritos. Isso ocorria porque as atualizações normais do sistema (atualizações de versão) não distinguiam entre registros modificados por deploy de projeto versus modificações normais.

### Solução Implementada
Sistema de marcação similar ao `user_modified`, mas específico para projetos. Quando um registro é atualizado via deploy de projeto, ele é marcado com o ID do projeto, impedindo que atualizações normais do sistema o sobrescrevam.

### Tabelas Afetadas
- `componentes` ✅ (possui user_modified)
- `layouts` ✅ (possui user_modified)
- `paginas` ✅ (possui user_modified)
- `variaveis` ✅ (possui user_modified)
- `templates` ✅ (possui user_modified)

### Campo Adicionado
- `project` (VARCHAR(255) NULL) - Armazena o ID do projeto que fez a última atualização

---

## 🏗️ Arquitetura da Solução

### Fluxo de Deploy de Projeto
1. Script `deploy-projeto.sh` identifica `PROJECT_TARGET` do environment.json
2. Envia ZIP + header `X-Project-ID: $PROJECT_TARGET` para API
3. API executa atualização com `--project=$PROJECT_TARGET`
4. Atualização sobrescreve dados e marca `project = PROJECT_TARGET`
5. Registros ficam protegidos contra atualizações normais futuras

### Fluxo de Atualização Normal
1. Atualização normal do sistema é executada
2. Registros com `project IS NOT NULL` são pulados (não atualizados)
3. Registros com `user_modified = 1` são preservados (lógica existente)
4. Apenas registros sem marcação são atualizados

### Lógica de Proteção
- **Deploy com --project**: Sempre sobrescreve e marca com project ID
- **Update normal**: Pula registros com `project IS NOT NULL` (exceto se `user_modified = 1`)
- **user_modified = 1**: Sempre priorizado (usuário tem controle total)

### Cenários de Uso
- **Deploy de projeto**: Atualiza tudo e marca com project ID
- **Update normal**: Respeita marcações de projeto
- **Usuário modifica**: `user_modified=1` sobrescreve proteção de projeto

---

## 💾 Mudanças no Banco de Dados

### Migração Criada
**Arquivo**: `gestor/db/migrations/20251113120000_add_project_field_to_resource_tables.php`

```php
final class AddProjectFieldToResourceTables extends AbstractMigration
{
    public function change(): void
    {
        $tables = ['componentes', 'layouts', 'paginas', 'variaveis', 'templates'];
        foreach ($tables as $tableName) {
            $table = $this->table($tableName);
            $table->addColumn('project', 'string', ['limit' => 255, 'null' => true])
                  ->update();
        }
    }
}
```

### Estrutura das Tabelas
Todas as tabelas afetadas agora possuem:
- `project` VARCHAR(255) NULL - ID do projeto que fez a última atualização

---

## 🔧 Implementação Técnica

### Modificações no sincronizarTabela()
**Arquivo**: `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`

```php
// Verificação de projeto
$project = $GLOBALS['CLI_OPTS']['project'] ?? null;

// Proteção aplicada apenas nas 5 tabelas do $preserveMap
if (isset($preserveMap[$tabela])) {
    if (!$project && !empty($exist['project'])) {
        if (empty($exist['user_modified']) || (int)$exist['user_modified'] !== 1) {
            // Pular registro protegido por projeto
            continue;
        }
    }
}

// Marcação durante updates/inserts
if (isset($preserveMap[$tabela]) && $project) {
    $diff['project'] = $project; // Para updates
    $row['project'] = $project;  // Para inserts
}
```

### Variável $preserveMap
Define as tabelas que suportam proteção:
```php
$preserveMap = [
    'paginas'      => ['nome','layout_id','caminho','framework_css','sem_permissao','html','css'],
    'layouts'      => ['nome','framework_css','html','css'],
    'componentes'  => ['nome','modulo','framework_css','html','css'],
    'templates'    => ['nome','target','framework_css','html','css'],
    'variaveis'    => ['valor']
];
```

---

## 📦 API e Scripts

### API de Deploy de Projetos
**Arquivo**: `gestor/controladores/api/api.php`

```php
function api_project_update() {
    // Receber project ID do header
    $project_id = $_SERVER['HTTP_X_PROJECT_ID'] ?? null;
    
    // Passar para atualização
    api_executar_atualizacao_banco($project_path, $project_id);
}
```

### Script de Deploy
**Arquivo**: `ai-workspace/scripts/projects/deploy-projeto.sh`

```bash
# Enviar header com project target
curl -H "Authorization: Bearer $token" \
     -H "X-Project-ID: $project_target" \
     -F "project_zip=@$zip_file" \
     "$api_url"
```

### PROJECT_TARGET
- String do `devEnvironment.projectTarget` no environment.json
- Exemplos: "digitalfluxus", "project-test", "meu-projeto"

---

## 🧪 Testes e Validação

### Cenários Testados
1. **Deploy de Projeto**: Marca registros com project ID correto
2. **Update Normal**: Pula registros marcados por projeto
3. **user_modified**: Sobrescreve proteção de projeto
4. **Rollback**: Migração reversível

### Validação em Ambiente de Testes
- ✅ Deploy marca corretamente com string do projeto
- ✅ Updates normais respeitam marcações
- ✅ Prioridade do user_modified mantida
- ✅ Sem impacto em outras tabelas

### Validação em Produção
- Aguardando testes em ambiente de produção
- Verificar comportamento com dados reais

---

## 📖 Referências

### Arquivos Modificados
- `gestor/db/migrations/20251113120000_add_project_field_to_resource_tables.php`
- `gestor/controladores/api/api.php`
- `ai-workspace/scripts/projects/deploy-projeto.sh`
- `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`

### Documentação Relacionada
- [Sistema de Templates](CONN2FLOW-GESTOR-DETALHAMENTO.md#📝-sistema-de-templates)
- [Banco de Dados](CONN2FLOW-GESTOR-DETALHAMENTO.md#💾-banco-de-dados)
- [API do Sistema](CONN2FLOW-GESTOR-DETALHAMENTO.md#🌐-sistema-web)

### Version
- **Implementado em**: Conn2Flow v2.5.1
- **Data**: 13/11/2025
- **Desenvolvedor**: Otavio Serra

---

## 🔁 Exceção: Atualização Forçada (`forcar_atualizacao`) (BATCH-056)

A proteção baseada em `project` / `user_modified` pode ser **deliberadamente contornada** para registros específicos declarados em `forcar_atualizacao` (no `tabela.config` do módulo ou no `tables_config.json` global; consolidado em `schema-metadata.json`).

Para os registros que casam (por `pk` ou `natural_key`), o atualizador:
- **ignora** a checagem de `project` (registros de deploy de projeto são re-sincronizados);
- **ignora** a preservação de `user_modified` (aplica o payload completo do deploy);
- **redefine `user_modified = 0`** quando estava em `1`, realinhando o registro à base de código;
- **preserva** o valor de `project` (não altera nem limpa).

É o mecanismo recomendado para corrigir, num deploy, registros que ficaram divergentes por edição manual ou por deploy de projeto anterior.

---
*Esta documentação detalha a implementação da proteção de banco de dados baseada em projetos, garantindo que deploys de projeto não sejam sobrescritos por atualizações normais do sistema.*