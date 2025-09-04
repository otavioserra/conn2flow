# Projeto: Sistema de Instalação / Atualização de Plugins (Fase 1)

## 🎯 Contexto Inicial
Este documento consolida o planejamento para evoluir o módulo `admin-plugins` (atualmente um CRUD simples de nomes) em um **gerenciador completo de instalação e atualização de plugins**.

### Branches
1. `main`: Desenvolvimento do Sistema. Ambiente atual já existente.
2. `plugin-development`: Desenvolvimento de Plugin. Ambiente que iremos criar onde terá skeleton de desenvolvimento de plugin. Todo novo plugin irá clonar este ambiente para criar um novo plugin.
**Informação Importante**: haverá dois ambientes diferentes, pois o primeiro é focado no sistema principal, enquanto o segundo é dedicado ao desenvolvimento de plugins.

### Arquitetura dos Scripts
1. Usar a arquitetura existente de atualização do sistema como base para a criação do fluxo de plugins:
**Desenvolvimento do Sistema**:
- `atualizacao-dados-recursos.php` (gera data sources / *Data.json* de layouts, páginas, componentes, variáveis) - Path: `gestor/controladores/agents/arquitetura/`. Este script é consumido pelo GitHub Actions quando faz um lançamento de uma versão: `.github\workflows\release-gestor.yml`:
```yml
- name: Generate Resources Updates
      run: |
        cd gestor/controladores/agents/arquitetura
        php atualizacao-dados-recursos.php
        echo "Resource updates generated successfully"
```
**Ambiente de Testes**:
- Tem um ambiente completo de testes feitos em docker. Paths: `docker`, `docker\dados\docker-compose.yml` e `docker\dados\Dockerfile`.
- `ai-workspace\scripts\atualizacoes\build-local-gestor.sh` - script de simulação de release similar ao `release-gestor.yml`, usado para gerar o zip e o hash e enviar para o ambiente de testes local do docker. Ou seja, ele cria o artefato para este ambiente.
**Atualização do Sistema**:
- `atualizacoes-banco-de-dados.php` (sincroniza *Data.json* → banco) - Path: `gestor/controladores/atualizacoes/`
- `atualizacoes-sistema.php` (orquestra fluxo de atualização do core) - Path: `gestor/controladores/atualizacoes/`
- Ambos os scripts acima são consumidos pelo módulo de atualização do sistema: `gestor\modulos\admin-atualizacoes\admin-atualizacoes.php`, bem como pode ser rodado via CLI.
**GIT**
- O sistema tem scripts prontos para automação de tarefas comuns, como commit e release. Para release ele usa o `ai-workspace\git\scripts\release.sh` e para commit o `ai-workspace\git\scripts\commit.sh`. No caso de release, o script sh ainda executa internamente o `ai-workspace\scripts\version.php` que automaticamente aumenta a versão do sistema.

2. Criar scripts específicos para o fluxo de plugins:
**Desenvolvimento de Plugin** - localizado na branch `plugin-development`:
- `update-data-resources-plugin.php` (gera data sources / *Data.json* de layouts, páginas, componentes, variáveis de um plugin) - Path: `utils/controllers/agents/`. Este script será consumido pelo GitHub Actions quando faz um lançamento de uma versão de um plugin qualquer: `.github\workflows\release-gestor-plugin.yml` (deverá ser criado como base no `release-gestor.yml`):
```yml
- name: Generate Plugin Resources Updates
      run: |
        cd utils/controllers/agents/
        php update-data-resources-plugin.php
        echo "Plugin resource updates generated successfully"
```
**Ambiente de Testes**:
- Tem um ambiente completo de testes feitos em docker. Sendo assim, para fazer os testes, subentende que o docker com o `docker-compose.yml` e o `Dockerfile` estão configurados corretamente previamente pelo ambiente de testes do sistema principal. Não precisa fazer nada aqui, apenas garantir que o ambiente está em funcionamento.
- `ai-workspace\scripts\updates\build-local-gestor-plugin.sh` - criar um script de simulação de release similar ao `release-gestor-plugin.yml`, usado para gerar o zip e o hash e enviar para o ambiente de testes local do docker. Ou seja, ele cria o artefato para este ambiente. Usar a mesma pasta da do gestor.
**Atualização de Plugin** - localizado na branch `main` junto do sistema principal:
- `atualizacao-plugin.php` (gerencia a instalação/atualização de um plugin para arquivos e banco de dados) - Path: `gestor/controladores/plugins/`
- O script acima será consumido pelo módulo de atualização de plugins: `gestor\modulos\admin-plugins\admin-plugins.php`, bem como pode ser rodado via CLI.
**GIT**
- O gestor de plugins deverá ter scripts prontos para automação de tarefas comuns, como commit e release. Para release ele usará o `ai-workspace\git\scripts\release.sh` e para commit o `ai-workspace\git\scripts\commit.sh`. No caso de release, o script sh deverá executar um script similar `ai-workspace\git\scripts\version.php` que automaticamente aumenta a versão do plugin. Ou seja, vai mudar a versão do manifest neste caso

### Objetivo Geral (MVP Fase 1)
Implementar fluxo mínimo de instalação/atualização de plugins:
1. Skeleton em `plugin-development`
2. Workflow release (gera ZIP + sha256 + Data.json plugin)
3. Instalação (upload / GitHub público / path local dev)
4. Atualização incremental (mesmo pipeline)
5. Registro + versionamento (manifest + checksum)
6. Reuso de rotinas existentes sem refatorações profundas
7. Persistência de metadados mínimos

Itens avançados movidos para documento `modificar-plugins-v2.md`.

## 🧩 Escopo Fase 1 (Fechado)
- Adicionar metadados mínimos em `plugins` (modelo definido) incluindo suporte a GitHub privado.
- Pipeline de origem (Fase 1):
    1. Upload manual `.zip`
    2. GitHub público (release/tag ou branch ZIP)
    3. GitHub privado (PAT via referência segura)
    4. Caminho local (dev) opcional
- Extração, validação e registro de pacote.
- Padronização mínima de estrutura (manifest + pastas esperadas).
- Consumo de Data.json gerado no release (instalador não gera).
- Sincronização seletiva com banco (recursos do plugin).
- Logging básico por arquivo + status de execução.
- Interface: listar / instalar / atualizar / reprocessar / detalhes (remoção somente Fase 2 como soft delete).
- Controle de versão Git no skeleton de plugin (scripts commit/release/version). 

## 🚫 Fora do Escopo (Fase 1)
- Dependências entre plugins (graph resolution) - Abordagem para o futuro.
- Rollback automático de plugin - Abordagem para o futuro - Para simplificar devido à complexidade do processo, vamos apenas descompactar os dados e copiar os mesmos para a pasta de plugins do sistema, atualizar o banco de dados e manter um log das alterações.
- Assinatura criptográfica / verificação GPG - Usar a mesma estratégia do `atualizacoes-sistema.php` que usa um arquivo de HASH. No caso do gestor principal usa: `gestor.zip` e o `gestor.zip.sha256`.
- Sandbox / isolamento de execução do código PHP do plugin - Abordagem para o futuro.

## 🗃️ Modelo de Dados (Fase 1 – Mínimo)
Adicionar em `plugins` (ou alterar se já existir):
- origem_tipo (upload|github_publico|github_privado|local_path)
- origem_referencia (ex: owner/repo, caminho local, identificador interno)
- origem_branch_tag (nullable)
- origem_credencial_ref (nullable) (alias para buscar token fora do banco – substitui origem_token_ref anterior)
- versao_instalada (nullable)
- checksum_pacote (nullable)
- manifest_json (text nullable)
- status_execucao (idle|instalando|atualizando|erro|ok) default idle
- data_instalacao (datetime nullable)
- data_ultima_atualizacao (datetime nullable)

Adiado (V2+): log_ultima_execucao, ultima_verificacao.
Slug = campo id existente.

Tokens: não serão persistidos; resolução via variável de ambiente (ex: PLUGIN_TOKEN_<REF>) ou config segura PHP.

## 📦 Estrutura Esperada do Pacote do Plugin com a estrutura bem como o comentários do que é cada parte do skeleton.
Nota: Artefatos de release local ficarão na pasta `ai-workspace/scripts/build/`. Nome do arquivo do core: `gestor.zip`; nome do arquivo de plugin: `gestor-plugin.zip` + `gestor-plugin.zip.sha256`.
```
.github/                                            (configurações do GitHub Actions)
    workflows/                                      (workflows do GitHub Actions)
        release-gestor-plugin.yml                   (workflow para liberar o plugin gestor)
ai-workspace/                                       (ambiente do workspace da IA)
    git/                                            (pasta dados do Git gerado por IA)
        scripts/                                    (scripts de automação para Git gerado por IA)
            release.sh                              (script de release do plugin gestor)
            commit.sh                               (script de commit do plugin gestor)
            version.php                             (script que atualiza a versão do plugin gestor)
    scripts/                                        (scripts de automação gerados por IA)
        build/                                      (pasta que armazena os builds locais)
        updates/                                    (scripts de atualização gerados por IA)
            build-local-gestor-plugin.sh            (script de simulação de release gerado por IA)
utils/                                              (utilitários da criação do plugin)
    controllers/                                    (controladores do plugin)
        agents/                                     (controladores de agentes do plugin)
            update-data-resources-plugin.php        (gera data sources / *Data.json* de layouts, páginas, componentes, variáveis de um plugin)
plugin/                                             (raiz do plugin específico)
	manifest.json                                   (obrigatório)
    controllers/                                    (controladores do plugin)
        controller-id/                              (controlador específico. O plugin pode ter 0-n controladores)
            controller-id.php                       (php específico do controlador)
	modules/                                        (pasta com todos os módulos do plugin)
        module-id/                                  (módulo específico sempre segue esse padrão para conexão automática com o sistema. O plugin pode ter 0-n módulos)
            resources/                              (recursos do módulo específico - estrutura similar ao sistema principal)
                pt-br/                              (recursos do módulo específico na linguagem pt-br[pode ter en,es,etc. na mesma estrutura])
                    pages/                          (páginas do módulo específico)
                        page-id/                    (diretório que armazena HTML e CSS específica da página de id: `page-id`. 0-n páginas)
                            page-id.css             (CSS específico da página - Opcional)
                            page-id.html            (HTML específico da página - Opcional)
                    layouts/                        (layouts do módulo específico)
                        layout-id/                  (diretório que armazena HTML e CSS específica do layout de id: `layout-id`. 0-n layouts)
                            layout-id.css           (CSS específico do layout - Opcional)
                            layout-id.html          (HTML específico do layout - Opcional)
                    components/                     (componentes do módulo específico)
                        component-id/               (diretório que armazena HTML e CSS específica do componente de id: `component-id`. 0-n componentes)
                            component-id.css        (CSS específico do componente - Opcional)
                            component-id.html       (HTML específico do componente - Opcional)
            modulo-id.json                          (mapeamento das páginas, layouts e componentes do módulo específico, bem como variáveis e demais variáveis que serão consumidas no módulo)
            modulo-id.js                            (javascript específico do módulo)
            modulo-id.php                           (php específico do módulo, ele que é referenciado no gestor.php para ser executado)
    resources/                                      (recursos globais do plugin - estrutura similar ao sistema principal)
        pt-br/                                      (recursos globais específico na linguagem pt-br[pode ter en,es,etc. na mesma estrutura])
            pages/                                  (páginas globais segue mesmo padrão da dos módulos)
            layouts/                                (layouts globais segue mesmo padrão da dos módulos)
            components/                             (componentes globais segue mesmo padrão da dos módulos)
            components.json                         (componentes globais do plugin)
            layouts.json                            (layouts globais do plugin)
            pages.json                              (páginas globais do plugin)
            variables.json                          (variáveis globais do plugin)
        resources.map.php                           (mapeamento dos recursos globais)
    db/                                             (banco de dados do plugin)
        data/                                       (dados específicos do plugin no formato *Data.json* gerado pelo `atualizacao-dados-recursos-plugin.php` no desenvolvimento do plugin e armazenado aqui)
	    migrations/                                 (migrações específicas do plugin)
	assets/                                         (css/js/imagens)
	vendor/                                         (se isolado – avaliar política)
```

### modulo-id.json - metadados dos recursos de cada módulo, bem como as variáveis dentro do módulo que será consumido pelo `modulo-id.php`:
```json
{
    "versao": "1.0.0",
    "bibliotecas": [
        "biblioteca-id"
    ],
    "tabela": {
        "nome": "tabela",
        "id": "id",
        "id_numerico": "id_tabela",
        "status": "status",
        "versao": "versao",
        "data_criacao": "data_criacao",
        "data_modificacao": "data_modificacao"
    },
    "resources": {
        "pt-br": {
            "layouts": [
                {
                    "name": "Layout Name",
                    "id": "layout-id",
                    "version": "1.0",
                    "checksum": {
                        "html": "474e067290ce9318c978ab463c3ed895",
                        "css": "c3fd0dfa321e5a4f032ff574cc07a4fb",
                        "combined": "1406ab702ddefb4fd6ae89fbaabdbf18"
                    }
                }
            ],
            "pages": [
                {
                    "name": "Page Name",
                    "id": "page-id",
                    "layout": "layout-id",
                    "path": "path-page-id\/",
                    "type": "system",
                    "option": "option",
                    "root": true,
                    "version": "1.0",
                    "checksum": {
                        "html": "8f33d8113e655162a32f7a7213409e19",
                        "css": "da65a7d1abba118408353e14d6102779",
                        "combined": "ddb032331dd7e8da25416f3ac40a104a"
                    }
                }
            ],
            "components": [
                {
                    "name": "Component Name",
                    "id": "component-id",
                    "version": "1.0",
                    "checksum": {
                        "html": "7fb861d588aebb98b48ff04511e06943",
                        "css": "",
                        "combined": "7fb861d588aebb98b48ff04511e06943"
                    }
                }
            ],
            "variables": [
                {
                    "id": "variable-id",
                    "value": "Valor",
                    "type": "tipo"
                }
            ]
        }
    }
}
```

### resources.map.php - Mapa com os caminhos para os metadados dos recursos:
```php
<?php

/**********
	Description: resources mapping.
**********/

// ===== Variable definition.

$resources = [
	'languages' => [
        'pt-br' => [
            'name' => 'Português (Brasil)',
            'data' => [
                'layouts' => 'layouts.json',
                'pages' => 'pages.json',
                'components' => 'components.json',
                'variables' => 'variables.json',
            ],
            'version' => '1',
        ],
    ],
];

// ===== Return the variable.

return $resources;
```

### components.json - metadados de cada componente (HTML e CSS diretamente na pasta conforme orientação anterior):
```json
[
    {
        "name": "Component Name",
        "id": "component-id",
        "version": "1.0",
        "checksum": {
            "html": "7fb861d588aebb98b48ff04511e06943",
            "css": "",
            "combined": "7fb861d588aebb98b48ff04511e06943"
        }
    }
]
```

### layouts.json - metadados de cada layout (HTML e CSS diretamente na pasta conforme orientação anterior):
```json
[
    {
        "name": "Layout Name",
        "id": "layout-id",
        "version": "1.0",
        "checksum": {
            "html": "474e067290ce9318c978ab463c3ed895",
            "css": "c3fd0dfa321e5a4f032ff574cc07a4fb",
            "combined": "1406ab702ddefb4fd6ae89fbaabdbf18"
        }
    }
]
```

### pages.json - metadados de cada página (HTML e CSS diretamente na pasta conforme orientação anterior):
```json
[
    {
        "name": "Page Name",
        "id": "page-id",
        "layout": "layout-id",
        "path": "path-page-id/",
        "type": "system",
        "option": "option",
        "root": true,
        "version": "1.0",
        "checksum": {
            "html": "8f33d8113e655162a32f7a7213409e19",
            "css": "da65a7d1abba118408353e14d6102779",
            "combined": "ddb032331dd7e8da25416f3ac40a104a"
        }
    }
]
```

### variables.json - metadados de cada variável, bem como seus valores:
```json
[
    {
        "id": "variable-id",
        "value": "Valor",
        "type": "tipo"
    }
]
```

### Manifest (Campos – Fase 1)
```
{
	"id": "meu-plugin-x",           // slug único
	"nome": "Meu Plugin X",
	"versao": "1.2.0",
	"descricao": "Funções avançadas ...",
	"compatibilidade": { "min": "1.0.0", "max": "2.x" },
	"autor": "Org / Dev",
	"license": "MIT",
	"recursos": { "layouts": true, "pages": true, "components": true, "variables": true },
	"scripts_pos_instalacao": ["php scripts/setup.php"],
	"checksum_override": null
}
```

Validações:
- `id` obrigatório (slug-safe)
- `versao` semântica
- `compatibilidade` usada para alerta (não bloqueio na fase 1)

## 🔄 Pipeline (InstALAÇÃO / Atualização)
1. Selecionar plugin (novo / existente)
2. Definir origem (upload / github_publico / local_path)
3. Obter pacote (upload → temp, github → download ZIP, local → copiar/zipar)
4. Calcular checksum + validar com .sha256 se existir
5. Extrair para staging: `gestor/temp/plugins/<slug>/`
6. Validar manifest + estrutura mínima
7. Copiar diretório final (overwrite seguro) para destino de plugins.
    - Usar diretório de novo path `gestor/plugins/<slug>/`.
    - Recomendação inicial: reutilizar `gestor-plugins/` para evitar nova raiz. Não, porque a ideia é os plugins ficarem dentro da instalação do sistema. Que são os arquivos filhos do `gestor/`. Por isso o correto é usar `gestor/plugins/`.
8. Consumir Data.json do plugin (gerado no release) e sincronizar banco (inserir/atualizar)
9. Persistir metadados (versão, checksum, datas)
10. Limpar staging (salvo modo debug)
11. Registrar log final

### Fluxos por Origem
| Origem | Ação Download | Observações |
|--------|---------------|-------------|
| Upload | Recebe ZIP | Validação tamanho / extensão |
| GitHub Público | GET https://codeload.github.com/{owner}/{repo}/zip/{ref} | Ref = branch ou tag |
| GitHub Privado | GET autenticado (Authorization: token <PAT>) | Token via `origem_credencial_ref` (lookup externo) |
| Local Path | Copy/Zip interno | Uso dev / desativável em produção |

## ♻️ Reuso de Componentes (Status)
Geração de Data.json feita no pipeline de release do plugin. Instalador apenas consome arquivos provisionados.

## 🔐 Segurança (Escopo Mínimo)
- Extensão `.zip` + limite tamanho
- Normalização de path (bloqueio traversal)
- Sanitização slug/IDs
- Checksum SHA256
- Tokens privados: não persistir no banco; somente referência (`origem_credencial_ref`).
- Logs não exibem token (apenas referência simbólica).

## 🧪 Testes (Plano Inicial)
- Upload válido simples.
- Upload repetido sem alteração (checksum igual → pular sincronização de dados).
- GitHub público com branch diferente.
- GitHub privado com credencial válida.
- GitHub privado credencial ausente → erro controlado.
- Manifest inválido (sem `id`).
- Estrutura faltando `manifest.json`.
- Conflito de slug já instalado com outro `id` diferente → erro.

## 🗂️ Logs & Códigos de Saída (Proposta)
Prefixo: `PLG_`
- `PLG_EXIT_OK = 0`
- `PLG_EXIT_PARAMS_OR_FILE = 10`
- `PLG_EXIT_VALIDATE = 11`
- `PLG_EXIT_MOVE = 12`
- `PLG_EXIT_DOWNLOAD = 20`
- `PLG_EXIT_ZIP_INVALID = 21`

Atual (implementado no código): 0,10,11,12,20,21.

Status Implementação:
- Download GitHub público/privado: Implementado com cURL/stream fallback.
- Sincronização Data.json granular: fase 1 registra estatísticas e copia arquivo (upsert granular pendente Fase 1.1).
- UI admin-plugins: ações instalar/atualizar/reprocessar + exibição de manifest e metadados implementadas (baseline, sem validações avançadas).

Formato de log: `[DATA] [LEVEL] [PLUGIN:slug] Mensagem`

## 🧱 Estrutura de Código (Fase 1)
- gestor/controladores/plugins/atualizacao-plugin.php (CLI / orchestrator)
- gestor/bibliotecas/plugins-installer.php (helpers)
- gestor/temp/plugins/ (staging)
- gestor/db/data/plugins/<slug>/ (Data.json gerado em release do plugin)

## 🔌 Integração com Interface (`admin-plugins`)
Novas ações:
- `adicionar` → formulário configura origem + upload opcional.
- `instalar` (POST) → dispara pipeline.
- `atualizar` → reexecuta pipeline se checksum remoto mudou.
- `reprocessar` → força regeneração de recursos mesmo sem mudança de checksum.
- `detalhes` → mostra manifest + histórico.

Campos formulário por origem:
- Comum: nome, slug (auto), descrição (opcional)
- GitHub: owner, repo, branch/tag
- Privado: idem + referência de credencial
- Upload: input file
- Local: path absoluto/relativo validado

## 🔄 Versionamento / Checksum
- Comparar `versao` do manifest + `checksum_pacote`.
- Se checksum igual → marcar como "sem alteração" e não regenerar recursos (exceto se `reprocessar`).

## 🧷 Estratégia de Geração de *Data.json*
Gerado no workflow de release do plugin (segregado por plugin). Instalador não gera, apenas lê e sincroniza.

## 🧩 Sincronização Banco (Fase 1)
- Inferir escopo via nomes/prefixos
- Inserir se não existe; atualizar se checksum diferente
- Sem remoção física automática

## 🚀 Roadmap (Resumo)
F1: MVP (este documento)
F2: GitHub privado + credenciais + plugin em recursos + re-check
F3: Dependências + rollback parcial + desinstalação
F4: Métricas / telemetria / assinaturas

## ✅ Decisões Consolidadas Fase 1
1. Prefixo obrigatório em IDs de recursos: `plg_<slug>_`.
2. Downgrade bloqueado (flag `--force` adiada F2).
3. Não armazenar ZIP instalado (backup Fase 2).
4. Overwrite completo do diretório do plugin (remoção + cópia nova).
5. Staging sempre limpo (modo debug preservar adiado F2).
6. Descrição apenas em `manifest_json` (sem coluna, reavaliar depois).
7. Índice (origem_tipo, origem_referencia) adiado (possível otimização futura).
8. Diretório final definido: `gestor/plugins/<slug>` (abstração de path para futura mudança se necessário).
9. Artefato plugin: `gestor-plugin.zip` + `gestor-plugin.zip.sha256` na mesma pasta do core.
10. GitHub privado incluído já na Fase 1 via `origem_credencial_ref`.
11. Tokens não persistidos; resolução apenas ambiente/config.
12. Scripts Git de versionamento presentes no skeleton do plugin.

## ❓ Pendências Residuais para Confirmar Antes da Implementação
- Nome final do campo de credencial: manter `origem_credencial_ref` (proposto) ✅?
- Fonte padrão credenciais: `.env` (PLUGIN_TOKEN_<REF>) + fallback config PHP ✅?
- Logs: mascarar origem (ex: `cred=github_privado:MEUREF`) sem token ✅?

Confirmação destes 3 pontos libera início da implementação.

## ✅ Progresso da Implementação (Checklist F1)
 - [x] Migração novos campos `plugins`
 - [ ] Branch orphan `plugin-development` (adiado para final conforme estratégia)
 - [x] Skeleton base plugin (estrutura inicial + manifest)
 - [x] Workflow release plugin e `build-local-gestor-plugin.sh`
 - [x] Plugin exemplo (example-plugin básico)
 - [x] Script update-data-resources-plugin.php (stub)
 - [x] Script atualizacao-plugin.php (stub orchestrator expandido)
 - [x] Upload ZIP (pipeline + UI campos)
 - [x] Download GitHub público
 - [x] Extração segura (implementada)
 - [x] Manifest validação (com erros básicos)
 - [x] Checksum cálculo/compare
 - [x] Copiar assets/módulos/resources (overwrite final directory)
 - [x] Sincronização banco (granular inicial layouts/pages/components/variables via plugin)
 - [x] Persistir metadados
 - [x] Logs & códigos saída (constantes centralizadas)
 - [x] Interface instalar
 - [x] Interface atualizar
 - [x] Interface detalhes (manifest + metadados + tail log)
 - [ ] Testes manuais
 - [ ] Documentação final

Nota: constantes de saída centralizadas em `gestor/bibliotecas/plugins-consts.php` e usadas no orchestrator/installer.

## 🛠️ Ações Imediatas (Aguardando GO)
1. Responder Dúvidas Abertas
2. Fechar campos migração
3. Confirmar overwrite + backup ZIP
4. Criar branch orphan
5. Implementar migração + skeleton

---
**Data:** 02/09/2025
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow (Plugins Manager Fase 1)
