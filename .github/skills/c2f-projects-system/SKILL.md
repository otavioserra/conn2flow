---
name: c2f-projects-system
description: Use ao trabalhar com a arquitetura de projetos Conn2Flow: deploy automatizado via OAuth API (/api/project/update), compactação ZIP, renovação de tokens, environment.json e execução inline.
user-invocable: false
---

# Sistema de Projetos e Deploy (`CONN2FLOW-SISTEMA-PROJETOS.md`)

Consulte e aplique as seguintes convenções ao gerenciar a arquitetura de projetos e o pipeline de deploy no Conn2Flow:

## 1. Arquitetura do Sistema de Deploy

- **Endpoint de API**: `/api/project/update` (Recebe pacotes ZIP via `multipart/form-data`).
- **Autenticação**: OAuth 2.0 com renovação automática de tokens via `refresh_token`.
- **Execução Inline Segura**: O deploy extrai os arquivos diretamente na raiz (`$_GESTOR['ROOT_PATH']`) e executa `atualizacoes-banco-de-dados.php` por `include` direto (evitando `shell_exec` desabilitado em produção).
- **Logs de Auditoria**: Registrados em `/logs/atualizacoes/YYYYMMDD.log` e tabela `atualizacoes_execucoes`.

---

## 2. Configuração de Credenciais (`environment.json`)

Arquivo de credenciais e ambiente na raiz do projeto:
```json
{
  "api_url": "https://api.conn2flow.com",
  "oauth": {
    "client_id": "SEU_CLIENT_ID",
    "client_secret": "SEU_CLIENT_SECRET",
    "access_token": "SEU_ACCESS_TOKEN",
    "refresh_token": "SEU_REFRESH_TOKEN"
  },
  "project": {
    "id": "conn2flow-gestor",
    "version": "1.0.0"
  }
}
```

---

## 3. Scripts Principais de Automação de Projetos

* **`compactar-projeto.sh`**: Compacta o projeto em ZIP e faz upload para a API do servidor. Se receber HTTP 401, renova o token automaticamente e reenvia.
* **`renovar-token.sh`**: Valida o `access_token` e renova via OAuth utilizando o `refresh_token`, atualizando o `environment.json`.
* **`teste-integracao.sh`**: Executa a suíte de 6 testes de integração (configuração, estrutura, atualização de recursos, compactação, OAuth e conectividade da API).

---

## 4. Pipeline de Operação

1. **Atualização Local de Recursos**: Execute a compilação local de recursos antes de empacotar.
2. **Execução do Deploy**:
   ```bash
   cd /caminho/do/projeto
   ./ai-workspace/scripts/projects/compactar-projeto.sh
   ```
3. **Fluxo Automático do Servidor**:
   - Valida OAuth ➔ Extrai ZIP na raiz ➔ Executa Upsert do Banco inline ➔ Limpa temporários ➔ Grava log.
