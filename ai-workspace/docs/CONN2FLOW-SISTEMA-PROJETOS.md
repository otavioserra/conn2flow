# Conn2Flow - Sistema de Projetos (Project Deployment System)

## Visão Geral
Sistema completo para deploy automatizado de projetos via API OAuth 2.0, com processamento inline seguro e renovação automática de tokens.

## Arquitetura
- **API Endpoint**: `/api/project/update` - Recebe uploads ZIP via multipart/form-data
- **Autenticação**: OAuth 2.0 com renovação automática de tokens
- **Processamento**: Execução inline (sem shell_exec) para segurança em produção
- **Deploy**: Extração direta na raiz do sistema (`$_GESTOR['ROOT_PATH']`)
- **Banco**: Atualização inline via `atualizacoes-banco-de-dados.php`

## Componentes Principais

### 1. API Endpoint (`gestor/controladores/api/api.php`)
```php
function api_project_update(){
    // Processamento completo:
    // - Validação OAuth
    // - Upload ZIP
    // - Extração direta na raiz
    // - Execução inline do banco
    // - Limpeza temporária
}
```

### 2. Scripts de Automação

#### `compactar-projeto.sh` - Compressão e Upload
- Compacta projeto em ZIP
- Faz upload via API com OAuth
- Renovação automática de token em caso de 401
- URLs dinâmicas baseadas em environment.json

#### `renovar-token.sh` - Renovação OAuth
- Valida token atual
- Renova automaticamente via refresh_token
- Atualiza environment.json
- Tratamento de erros e logs

#### `teste-integracao.sh` - Testes Completos
- Validação de configuração
- Teste de estrutura de diretórios
- Atualização de recursos
- Compactação e upload
- Renovação de token
- Conectividade da API

### 3. Configuração (`environment.json`)
```json
{
  "api_url": "https://api.conn2flow.com",
  "oauth": {
    "client_id": "...",
    "client_secret": "...",
    "access_token": "...",
    "refresh_token": "..."
  },
  "project": {
    "id": "conn2flow-gestor",
    "version": "1.0.0"
  }
}
```

## Fluxo de Funcionamento

### 1. Preparação
```bash
# Configurar environment.json com credenciais OAuth
# Validar estrutura do projeto
```

### 2. Deploy Automatizado
```bash
# Executar compactação e upload
./ai-workspace/scripts/projects/compactar-projeto.sh
```

### 3. Processamento na API
- ✅ Validação do token OAuth
- ✅ Upload do arquivo ZIP
- ✅ Extração direta na raiz do sistema
- ✅ Execução inline da atualização do banco
- ✅ Limpeza de arquivos temporários

## Segurança Implementada

### Execução Inline
- **Antes**: `shell_exec("php atualizacoes-banco-de-dados.php")`
- **Agora**: Include direto e execução inline
- **Motivo**: Servidores de produção desabilitam shell_exec

### Renovação Automática de Tokens
- Detecção automática de erro 401
- Renovação transparente via refresh_token
- Retry automático da operação
- Sem interrupção no fluxo

### Deploy Direto na Raiz
- **Antes**: Extração em subdiretório com validação de projeto
- **Agora**: Extração direta em `$_GESTOR['ROOT_PATH']`
- **Motivo**: Arquitetura simplificada e direta

## Scripts de Suporte

### Atualização de Recursos
- `atualizacao-dados-recursos.php` - Script principal
- `atualizacao-dados-recursos.sh` - Automação com parâmetros dinâmicos
- Suporte a `--project-path` para caminhos customizados

## Testes de Integração
Sistema validado com 6 testes principais:
1. ✅ Configuração do environment.json
2. ✅ Estrutura de diretórios do projeto
3. ✅ Atualização de recursos
4. ✅ Compactação do projeto
5. ✅ Renovação de token OAuth
6. ✅ Conectividade da API

## Logs e Monitoramento
- Logs detalhados em `/logs/atualizacoes/`
- Persistência de execuções em `atualizacoes_execucoes`
- Estatísticas de deploy (arquivos copiados/removidos)
- Tratamento completo de erros

## Uso em Produção

### Pré-requisitos
- Credenciais OAuth válidas em `environment.json`
- Permissões de escrita na raiz do sistema
- PHP com funções de arquivo habilitadas

### Comando de Deploy
```bash
cd /path/to/project
./ai-workspace/scripts/projects/compactar-projeto.sh
```

### Verificação
```bash
# Verificar logs
tail -f /logs/atualizacoes/$(date +%Y%m%d).log

# Verificar status da API
curl -H "Authorization: Bearer <token>" https://api.conn2flow.com/api/project/status
```

## Manutenção
- Tokens OAuth são renovados automaticamente
- Limpeza automática de temporários (>24h)
- Retenção de logs configurável (padrão 14 dias)
- Backup opcional antes do deploy

## Troubleshooting

### Erro 401 Unauthorized
- Verificar se token OAuth expirou
- Sistema tenta renovação automática
- Se persistir, verificar credenciais em environment.json

### Erro de Permissões
- Garantir que diretório raiz tem permissões www-data:www-data
- Verificar se PHP pode escrever arquivos

### Falha na Extração ZIP
- Verificar se arquivo ZIP não está corrompido
- Verificar espaço em disco disponível
- Logs detalhados em `/logs/atualizacoes/`

---
Sistema implementado e testado com sucesso
Última atualização: 2025-01-27