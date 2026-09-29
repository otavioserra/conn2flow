---
name: c2f-projects-system
description: "LEIA ANTES de sincronizar, compilar ou fazer deploy de projetos (conn2flow-site, lumix, etc.). Se não ler: arquivos do projeto sobrescrevem o core indevidamente, dados de tenants se misturam ou o deploy falha."
user-invocable: false
---

# Sistema de Projetos e Deploy (`CONN2FLOW-SISTEMA-PROJETOS.md`)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Executar comandos de sincronização de projetos (`deploy-project-v2.sh`, `sync-project-to-core`, etc.) ou configurar `environment.json`.
- **SKIP APENAS SE**: Desenvolvimento isolado no Core do Conn2Flow sem projetos associados.
- **CONSEQUÊNCIA DE IGNORAR**: Sobrescrita acidental de código do Core por arquivos de projeto, inconsistência de caminhos no `environment.json` e falhas de deploy em produção.

---

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

---

## 5. Regras de Indexação do Sitemap (`sitemap.php`)

O gerador `sitemap.php` inclui automaticamente todas as páginas ativas que possuam `sem_permissao = 1` (`without_permission: true`), filtradas pela função `sitemap_caminho_nao_indexavel()`.

### 5.1 Rotas Excluídas Automaticamente do Sitemap

Ficam sumariamente **excluídas** do sitemap:
- **Desfechos transacionais**: `success`, `error`, `cancel`, `cancelled`, `confirmation`
- **Sufixos de etapa transacional**: `.../payment`, `.../checkout`, `.../download`
- **Rotas de fluxo de compra**: `cart`, `checkout`, `*-checkout`
- **Páginas semente**: Páginas de template que originam rotas dinâmicas filhas (devem ter `without_permission` desmarcado na semente)

### 5.2 Diretriz Normativa para Controle de Indexação

1. **Remoção de Página Pública do Sitemap**:
   - Desmarcar a opção `sem_permissao` no cadastro da página no Gestor, OU
   - Aplicar um dos sufixos ou caminhos padronizados não-indexáveis.
2. **Validação Obrigatória Pós-Deploy**:
   - Sempre auditar a saída pública do sitemap para certificar que rotas de checkout, erro ou confirmação não estão expostas para indexação:
   ```bash
   curl -s https://<dominio>/sitemap.xml | grep -E 'checkout|success|cancel|error'
   ```
   *(A saída deve ser vazia).*

> [!WARNING]
> Indexar páginas de checkout ou desfechos transacionais expõe telas de erro ou formulários incompletos a robôs de busca (SEO), gerando tráfego fantasma e potenciais erros 400/500 no monitoramento.

