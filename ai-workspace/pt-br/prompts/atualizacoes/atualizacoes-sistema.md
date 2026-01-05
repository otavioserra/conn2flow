# Prompt Interactive Programming - Atualizações do Sistema (Controller: atualizacoes-sistema.php)

> Atualizado para MODELO SIMPLIFICADO: Overwrite total + verificação SHA256 do pacote ZIP. Seções antigas de diff / conflitos foram deprecadas e serão substituídas abaixo.

## 🤖 Agente de IA - Responsabilidades
- **Desenvolvimento**: Criar, evoluir e manter o script controlador de atualização do sistema (`atualizacoes-sistema.php`).
- **Refinamento Contínuo**: Ajustar este prompt conforme feedback / novas necessidades surgirem.
- **GIT**: Usar exclusivamente os scripts internos para versionamento. Use o para release: `bash ./ai-workspace/git/scripts/release.sh ${input:tipo} \"${input:tagMsg}\" \"${input:commitMsg}\"`; use só para commit: `bash ./ai-workspace/git/scripts/commit.sh \"${input:commitMsg}\"`.
- **Docker**: Executar e validar rotinas dentro do container de aplicação. Use para enviar os dados atualizados para o ambiente de testes: `bash docker/utils/sincroniza-gestor.sh checksum`. Depois use `docker exec conn2flow-app bash -c \"php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-sistema.php\"` para testar.
- **Qualidade**: Garantir logs claros, idempotência quando possível e segurança (não remover / sobrescrever arquivos críticos inadvertidamente).

## 🎯 Contexto Atual (Modelo Simplificado)
Objetivo: Atualizar o Conn2Flow de maneira direta, segura e reproduzível, privilegiando simplicidade sobre granularidade.

Fluxo principal:
1. Descobrir tag mais recente (ou usar `--tag`).
2. Baixar `gestor.zip`.
3. Baixar `gestor.zip.sha256` e validar SHA256 (a menos que `--no-verify`).
4. Extrair conteúdo completo em staging temporário.
5. (Opcional `--backup`) Criar snapshot FULL (exclui dirs ignorados) antes de qualquer alteração.
6. Overwrite TOTAL dos arquivos (remoção de órfãos) preservando `contents/`, `logs/`, `backups/`, `temp/`.
7. Merge aditivo de `.env` (apenas acrescenta novas chaves, loga deprecadas).
8. Executar atualização de banco (exceto modos que desativam).
9. Exportar plano JSON com estatísticas agregadas + checksum + merge .env.
10. Limpeza opcional do staging (`--clean-temp`).

O modelo anterior baseado em diff e conflitos foi REMOVIDO.

Este arquivo (PROMPT) é a ESPECIFICAÇÃO VIVA. O agente deve mantê-lo coerente com a implementação real.

## 🧪 Ambiente de Testes
- Config Docker: `docker/dados/docker-compose.yml`
- Instalação montada (host): `docker/dados/sites/localhost/conn2flow-gestor`
- Instalação no container: `/var/www/sites/localhost/conn2flow-gestor/`
- Instalador web para referência: `http://localhost/instalador/`
- Ferramentas:
    - Sincronização código gestor: `docker/utils/sincroniza-gestor.sh checksum`
    - Comandos úteis: `docker/utils/comandos-docker.md`
- Exemplo execução dentro do container:
    - `docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-sistema.php --debug"`
- Logs de atualização deverão ir para: `gestor/logs/atualizacoes/` (prefixo: `atualizacoes-sistema-YYYYmmdd.log`).

## 🗃️ Repositório GIT
- Commit / tag sempre via: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`
- Nunca rodar git manual (push/pull) diretamente.
- Mensagens de commit do módulo de atualização devem iniciar com prefixo: `update-system:`

## ⚙️ Configurações da Implementação
Variáveis / caminhos padrão (derivados em runtime):

| Conceito | Valor / Regra |
|----------|---------------|
| Base do gestor | `realpath(dirname(__FILE__) . '/../../') . DIRECTORY_SEPARATOR` |
| Pasta staging temporária | `<base>/temp/atualizacoes/<timestamp>/` |
| Arquivo zip destino | `gestor.zip` dentro da pasta staging |
| Log principal | `<base>/logs/atualizacoes/atualizacoes-sistema.log` (com rotação por data) |
| Pasta backups (arquivos) | `<base>/backups/atualizacoes/arquivos/<timestamp>/` |
| Pasta backups (db futuro) | `<base>/backups/atualizacoes/db/` |
| Template .env novo | `<staging>/autenticacoes.exemplo/dominio/.env` |
| .env atual produtivo | `<base>/autenticacoes/<env-dir>/.env` |

Regras `.env` (merge):
1. NÃO sobrescrever valores existentes do usuário.
2. Adicionar ao final variáveis novas mantendo comentário (linhas iniciadas com `#` associadas imediatamente acima da key).
3. Preservar ordem original do `.env` atual.
4. Se uma chave existe no template mas ausente no atual → adicionar com valor do template + comentário `# added-by-update YYYY-mm-dd`.
5. Detectar chaves removidas no template apenas LOGANDO (não remover do arquivo do usuário).

Conjunto mínimo de chaves críticas a monitorar (para alerta se ausentes): `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `OPENSSL_PASSWORD`, `USUARIO_HASH_PASSWORD`, `URL_RAIZ`.

Modos de execução:
- FULL (default) → arquivos + merge .env + banco.
- --only-files → apenas atualização de arquivos + merge .env.
- --only-db → apenas banco.
- --no-db → igual FULL porém sem banco (deprecia `--only-files`).

Flags / Opções CLI suportadas (versão simplificada):
```
--tag=GESTOR_TAG        # Usa release específico (ex: gestor-v1.2.3)
--version=GESTOR_TAG    # Alias de --tag
--domain=DOMINIO        # Domínio / nome do ambiente (pasta autenticacoes/<dominio>)
--env-dir=DOMINIO       # Alias
--download-only         # Baixa (e verifica) + extrai sem aplicar overwrite
--skip-download         # Usa zip já existente em staging (debug)
--dry-run               # Simula (não copia/remove). Stats ainda não simuladas.
--backup                # Snapshot FULL antes do overwrite
--only-files            # Só arquivos + merge .env
--only-db               # Só banco
--no-db                 # Igual a full sem etapa de banco
--no-verify             # Desativa verificação SHA256 do gestor.zip
--force-all             # Encaminha ao script de banco
--tables=lista          # Encaminha ao script de banco
--log-diff              # Encaminha ao script de banco
--debug                 # Aumenta verbosidade
--clean-temp            # Remove staging ao final (mesmo dry-run)
--logs-retention-days=N # Mantém apenas N dias de logs/plan JSON (default 14, 0 desativa)
--help                  # Exibe ajuda
```

Erros fatais interrompem execução com código != 0. (Checkpoint JSON ainda FUTURO.)

### Diretórios Ignorados na Atualização de Arquivos
No overwrite TOTAL estes diretórios são preservados (sem remoção ou sobrescrita):
```
logs/
backups/
temp/
contents/   # uploads e arquivos enviados pelo usuário (pasta dedicada)
```
Justificativa: `contents/` contém uploads; `logs/` e `backups/` mantêm histórico; `temp/` pode conter processos em andamento.

### Dependências de Extensões PHP (mínimas)
Ambiente precisa ter as extensões habilitadas antes de rodar o controlador:
```
curl      # download de releases via GitHub API / assets
zip       # manipulação de gestor.zip (ZipArchive)
json      # (já habilitada) para plano / logs estruturados
mbstring  # (futuro) internacionalização avançada
openssl   # (já usada em outras partes do sistema)
```
Sem `curl` ou `zip` o script abortará em etapas iniciais. Docker base já deve conter.

## 📖 Bibliotecas / Helpers Atuais
- `logAtualizacao()` / `logErroCtx()`
- `parseArgs()` / `help()` / `validarOpts()`
- `descobrirUltimaTagGestor()` / `downloadRelease()`
- `downloadZipChecksum()` / `verifyZipSha256()`
- `extrairZipGestor()`
- `coletarArquivos()` / `aplicarOverwriteTotal()` / `backupTotal()`
- `mergeEnv()` (aditivo)
- `executarAtualizacaoBanco()`
- `exportarPlanoJson()` / `renderRelatorioFinal()`
- Hooks: `hookBeforeFiles()`, `hookAfterDb()`, `hookAfterAll()`

## 📝 Orientações para o Agente
1. Manter este arquivo sincronizado com mudanças de parâmetros e fluxo.
2. Adicionar novas opções CLI somente após atualizar secção de FLAGS.
3. Garantir comentários DocBlock em TODAS as funções públicas/internas descrevendo:
    - Objetivo
    - Parâmetros
    - Retorno
    - Exceções
4. Toda escrita em disco deve validar permissões e relatar erros significativos.
5. Nunca excluir diretórios sensíveis: `autenticacoes/`, `logs/`, `backups/`, `vendor/`.
6. Atualização de arquivos deve tratar conflitos:
    - Se arquivo modificado localmente (heurística: checksum diferente + presença de marcador `// LOCAL-EDIT`), salvar cópia `.bak` e registrar conflito.
7. `dry-run` deve produzir plano detalhado (JSON) sem aplicar.
8. Logging multilíngue: manter chaves futuras em arquivos JSON (planejado) – por ora usar `__t()` se disponível; caso não, fallback a texto pt-br e marcar `TODO:i18n`.
9. Preparar pontos de extensão (funções vazias `hookBeforeFiles()`, `hookAfterDb()`).
10. Retornar código de saída (CLI) consistente: 0=sucesso, 1=erro genérico, 2=erro download, 3=erro extração, 4=erro merge env, 5=erro banco.

## 🧭 Fluxo (Pseudo-code Overwrite Total)
```
parseArgs(argv)
if help -> print + exit 0
validarOpts()
log start
if !only-db:
    staging = prepararStaging()
    tag = opts.tag || descobrirUltimaTagGestor()
    zip = skip-download ? staging/gestor.zip : downloadRelease(tag, staging)
    if verificação ativa: checksumFile = downloadZipChecksum(tag); verifyZipSha256(zip, checksumFile)
    extrairZipGestor(zip, staging)
    if backup && !dry: backupTotal(base, backupDir, excludes)
    stats = aplicarOverwriteTotal(staging, base, excludes, dry)
    mergeEnv(.envAtual, .envTemplate, context, dry)
    exportarPlanoJson({stats}, context)
if !only-files && !no-db: executarAtualizacaoBanco()
hookAfterAll()
renderRelatorioFinal()
if clean-temp: removerStaging()
exit 0
catch Download -> EXIT_DOWNLOAD
catch Extraction -> EXIT_EXTRACTION
catch EnvMerge -> EXIT_ENV_MERGE
catch Database -> EXIT_DB_ERROR
catch Integrity -> EXIT_INTEGRITY
catch Generic -> EXIT_GENERIC
```

### Estrutura do Arquivo `atualizacoes-sistema.php`
Ordem sugerida:
1. `declare(strict_types=1);`
2. Header comentário (propósito, uso CLI, exemplos)
3. Constantes básicas / paths
4. Require libs mínimas
5. Funções utilitárias (log, io, checksum, etc.)
6. Funções domínio (download, extração, overwrite, merge .env, backup, banco)
7. Hooks vazios
8. Função `main()` e dispatch CLI

### Plano / Estatísticas
O plano JSON atual inclui: `stats.removed`, `stats.copied`, `stats.total_new`, `env_merge.added`, `env_merge.deprecated`, `checksum`.

### Merge .env (Pseudo Atual)
```
curLines = file(.envAtual)
tplLines = file(.envTemplate)
curMap = parseEnvLines(curLines)  # KEY => {value,index}
tplMap = parseEnvLines(tplLines)
added = keys(tplMap) - keys(curMap)
deprecated = keys(curMap) - keys(tplMap)
if !dry-run and added:
    append "\n# added-by-update YYYY-mm-dd" + cada KEY=valor do template
registrar em contexto env_merge.added / deprecated
log resumo
```

### Execução Banco
Reuse `atualizacoes-banco-de-dados.php` definindo:
```
$GLOBALS['CLI_OPTS'] = [
  'env-dir' => <env>,
  'debug' => opts.debug,
  'force-all' => opts.forceAll,
  'tables' => opts.tables,
  'log-diff' => opts.logDiff
];
require 'atualizacoes-banco-de-dados.php';
```

### Internacionalização
Incluir (futuro) arquivo `controladores/atualizacoes/lang/pt-br.json` e `en.json` com chaves:
```
{
  "update_start": "Iniciando atualização do sistema",
  "download_release": "Baixando release {tag}",
  "merge_env_added": "Variável adicionada ao .env: {key}",
  "merge_env_summary": "Merge .env concluído. Novas: {added} | Ausentes no template: {deprecated}",
  "db_update_start": "Atualizando banco de dados",
  "db_update_done": "Banco de dados atualizado",
  "files_plan": "Plano de arquivos - adicionar:{add} atualizar:{upd} conflitos:{conflicts}",
  "update_finished": "Atualização concluída com sucesso"
}
```

## 🤔 Decisões Atuais
| Tema | Decisão | Status |
|------|---------|--------|
| Integridade | SHA256 do ZIP obrigatório (arquivo gestor.zip.sha256) | Implementado |
| Modelo Arquivos | Overwrite total + remoção de órfãos | Implementado |
| Conflitos | Não tratado (delegado a arquitetura de plugins) | N/A |
| Backup | Snapshot full opcional (--backup) | Implementado |
| Rollback | Restaurar snapshot (futuro) | Planejado |
| Plano JSON | Estatísticas agregadas + checksum + env merge | Implementado |
| Dry-run Stats | Simular counts sem operações | Pendente |
| Retenção Logs | Poda automática de logs/planos antigos (--logs-retention-days) | Implementado |
| Limpeza Temp Antigo | Remoção automática de temp/atualizacoes/ >24h | Implementado |
| Remoção gestor/db pós update | Remove pasta db após aplicar dados | Implementado |
| Execução Web / Jobs | Painel administrativo / fila | Futuro |

## ✅ Progresso da Implementação (Consolidado)
- [x] Esqueleto inicial / parser / logging
- [x] Descoberta de tag + download ZIP
- [x] Verificação SHA256 automática
- [x] Extração staging
- [x] Overwrite total + remoção órfãos
- [x] Backup full opcional
- [x] Merge .env aditivo
- [x] Integração script banco
- [x] Plano JSON (stats + checksum + env)
- [x] Dry-run básico (sem stats simuladas)
- [x] Códigos saída / tratamento exceções
- [x] Limpeza staging opcional
- [x] Atualização workflow release (gera gestor.zip.sha256)
- [x] Atualização desta especificação
- [ ] Testes manuais end-to-end nova release
- [ ] README / docs externas
- [ ] Dry-run com contagem simulada
- [ ] Planejamento rollback

### Subprogresso Testes Manuais (container)
- [x] Download + checksum verificado
- [x] Overwrite total com backup full
- [x] Merge .env adicionando chave
- [ ] Execução full (com banco)
- [ ] Execução com --no-verify (pular integridade)
- [ ] Execução dry-run (validar não modificação)

## ☑️ Processo Pós-Implementação
- [ ] Dry-run (confere plano + sem alterações reais)
- [ ] Execução full com nova tag release real
- [ ] Merge .env (chave simulada) validado
- [ ] Atualização banco (flags) validada
- [ ] Execução com --no-verify (aviso)
- [ ] Commit via script

## ♻️ Alterações Recentes
1. Remoção diff/conflitos.
2. Introdução checksum SHA256.
3. Overwrite total + remoção órfãos.
4. Backup full snapshot.
5. Plano JSON simplificado.
6. Documentação atualizada.
7. Limpeza automática de diretórios temporários antigos (>24h).
8. Remoção automática de gestor/db após atualização de banco concluída.
9. Nova flag --logs-retention-days para poda de logs/planos antigos.
10. Normalização coluna de linguagem (language vs linguagem_codigo) no script de banco.
11. Remoção de artefatos gestor.zip/gestor-local.zip antes de mover para produção.

## ✅ Progresso da Implementação das Alterações e Correções
- [x] Itens 1–6 aplicados
- [ ] Dry-run stats simuladas
- [ ] Rollback automático

## ☑️ Processo Pós Alterações e Correções
- [ ] Executar novamente com cenários reais
- [ ] Commit com mensagem estruturada

---
**Data:** 25/08/2025
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow v1.14.0
**Módulo:** Sistema de Atualizações

## 🆕 Novidades v1.14.0

### Adicionado
- Flag `--logs-retention-days=N` (default 14) para poda automática de:
    - `logs/atualizacoes/atualizacoes-sistema-YYYYMMDD.log`
    - `logs/atualizacoes/atualizacoes-bd-YYYYMMDD.log` e variantes (`atualizacoes-banco-`).
    - Arquivos `plan-YYYYmmdd-HHMMSS.json`.
- Poda automática de diretórios antigos em `temp/atualizacoes/` (>24h) preservando staging atual.
- Normalização dinâmica de coluna de linguagem no script de banco (`language` ou `linguagem_codigo`).
- Prevenção de duplicação de registros multilíngues via índices fallback.

### Alterado
- Deploy remove artefatos `gestor.zip` / `gestor-local.zip` do staging antes de mover.
- Pasta `gestor/db/` removida após aplicação dos dados para reduzir divergências e superfície.
- Ajuda CLI atualizada com nova flag de retenção de logs.

### Corrigido
- Erros "Unknown column 'language'" em tabelas onde só existia `linguagem_codigo` (detecção + normalização unificada).
- Crescimento indefinido de logs/planos sem política de retenção.
- Artefato zip podendo permanecer na raiz após atualização local.

### Removido
- Dependência de manter `gestor/db/` persistente entre releases (agora transitório).

### Notas
1. Ajuste retenção: `--logs-retention-days=30` amplia histórico; `0` desativa poda.
2. Para auditoria prolongada de scripts JSON originais, copiar `gestor/db/` antes ou implementar futura flag de preservação.
3. Mecanismo de normalização não altera schema, apenas mapeia em tempo de execução.

### Próximos Passos (Planejados)
- Simulação de stats em `--dry-run`.
- Rollback usando snapshot full.
- Flag opcional para preservar pasta `db` após update.

> Este prompt está sincronizado com o modelo simplificado atual. Qualquer evolução (rollback, web UI, fila) deve atualizar aqui primeiro.