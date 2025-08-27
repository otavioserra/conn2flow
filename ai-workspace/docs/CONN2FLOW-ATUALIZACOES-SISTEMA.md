# Conn2Flow - Sistema de Atualizações (Fase 1)

## Objetivo
Fornecer mecanismo automatizado para atualizar o núcleo `gestor/` com:
- Download (ou uso local) de artefato `gestor.zip`
- Verificação opcional de integridade (SHA256)
- Wipe seletivo (mantendo diretórios protegidos)
- Deploy simplificado (move/rename + fallback copy)
- Merge aditivo de `.env` com detecção de variáveis novas e deprecadas
- Execução de script unificado de atualização de banco (`atualizacoes-banco-de-dados.php`)
- Exportação de plano JSON com estatísticas
- Persistência de execuções em tabela `atualizacoes_execucoes`
- Execução CLI ou Web (incremental via AJAX)
- Retenção/housekeeping de logs e temporários

## Diretórios e Arquivos Envolvidos
```
/gestor/controladores/atualizacoes/
  atualizacoes-sistema.php         # Orquestrador principal (CLI + Web)
  atualizacoes-banco-de-dados.php  # Script banco unificado (chamado inline ou via processo)
/logs/atualizacoes/                # Logs diários e planos JSON
/temp/atualizacoes/                # Staging + sessões web
/temp/atualizacoes/sessions/       # <sid>.json + <sid>.log (web incremental)
/backups/atualizacoes/             # (opcional) backups full (--backup)
```

## Fluxo CLI (Resumo)
1. Bootstrap (download/extract + possível reexecução se script atualizado)
2. Deploy (wipe + mover + merge .env)
3. Banco (opcional conforme flags)
4. Limpeza + relatório final + retenção

## Fluxo Web Incremental
Ações (param `action`):
- `start`: prepara staging + baixa/extrai ZIP + valida + cria sessão
- `deploy`: aplica wipe+deploy + merge .env + plano
- `db`: executa script banco (pula se `only-files` ou `no-db`)
- `finalize`: limpeza staging + finaliza persistência
- `status`: retorna estado + log agregado
- `cancel`: marca execução como cancelada

Estado: `temp/atualizacoes/sessions/<sid>.json`
```
{
  "sid": "...",
  "exec_id": 15,
  "step": "deploy_files_done",
  "opts": { ...flags... },
  "release_tag": "gestor-v1.14.0",
  "checksum": { expected, got },
  "staging_dir": "/var/www/.../temp/atualizacoes/20250827-.../",
  "staging_root": "...",
  "progress": { bootstrap, deploy_files, database, finalize },
  "stats": { removed, copied },
  "finished": false
}
```

## Diretórios Protegidos (não removidos no wipe)
```
contents/ logs/ backups/ temp/ autenticacoes/
```
Motivos:
- `autenticacoes/` contém instâncias específicas (.env, chaves, configs)
- `logs/`, `backups/`, `temp/` preservam histórico e staging
- `contents/` (futuro) preservará uploads/ativos dinâmicos

## Estatísticas
Registro em plano JSON e na tabela:
- `stats_removed`: entradas removidas (arquivos/pastas não protegidos)
- `stats_copied`: itens movidos (rename ou copy fallback)

Tabela `atualizacoes_execucoes` (campos relevantes):
```
session_id, modo, release_tag, checksum,
env_added, stats_removed, stats_copied,
status (running|success|error), exit_code,
plan_json_path, log_file_path, session_log_path,
started_at, finished_at, created_at, updated_at
```

## Flags Principais (CLI)
```
--tag=gestor-vX.Y.Z     # força tag específica
--local-artifact        # usa artefato local (docker / conn2flow-github)
--only-files | --only-db | --no-db
--dry-run               # não aplica deploy/banco
--backup                # backup full antes do wipe
--no-verify             # ignora checksum SHA256
--download-only         # gera staging/plano sem aplicar
--logs-retention-days=N # default 14 (0 desativa)
--debug                 # logs DEBUG
```

## Merge .env
- Lê template em `autenticacoes.exemplo/dominio/.env` (ordem tentativa: dominio, localhost, dominio literal)
- Adiciona variáveis novas ao final com bloco `# added-by-update YYYY-mm-dd`
- Lista variáveis deprecadas (apenas em log)

## Banco de Dados
Script unificado permite:
- Flags repassadas: `--force-all`, `--tables=...`, `--log-diff`, `--dry-run`
- Execução externa (CLI) ou inline (web)
- Após sucesso remove `gestor/db/` (migrations + seeds) reduzindo superfície

## Housekeeping
- Diretórios temporários >24h removidos
- Logs / planos além da retenção apagados
- Staging removido ao final (exceto `--keep-temp` / dry-run preserva por design)

## Persistência & Resiliência
- Início registra linha `running`
- Parciais atualizam stats/env_added
- Finaliza com `success` ou `error` (exit_code)
- Fallback carrega stats de último plano JSON se contexto perdeu referência

## Erros & Códigos de Saída
```
0 OK
1 Genérico
2 DownloadException
3 ExtractionException
4 EnvMergeException
5 DatabaseUpdateException
6 (reservado rollback)
7 IntegrityException (checksum)
```

## Segurança & Permissões
Problema diagnosticado: diretórios/artefatos com owner `root` impediram rename/unlink pressionando fallback.
Solução operacional: garantir ownership `www-data:www-data` para diretórios de instalação e artefatos.
Recomendação adicional no deploy: script/infra garantir `chown -R www-data:www-data` após cópia do artefato.

## Lições Aprendidas (Debug Sentinel)
- Root cause de não atualização era permissão, não lógica de mover.
- Instrumentação (sentinel hashes) removida após validação.
- Mantido apenas logging essencial (sem deep hash).

## Possíveis Evoluções (Fase Futuras)
- Rollback transacional (arquivos + dump diff banco)
- Validação pós-deploy por checksum manifest
- Atualizações incrementais (diff) para reduzir downtime
- API REST para orquestração remota
- Assinatura digital dos artefatos (além de SHA256)
- Lock de execução distribuída (cluster)

---
Documento mantido por GitHub Copilot IA
Última atualização: 2025-08-27
