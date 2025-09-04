# Sistema de Plugins – Planejamento Fase 2+

Documento complementar ao MVP (Fase 1). Não implementar antes da estabilização de pelo menos 1 plugin em ambiente de teste.

## Segurança Avançada
- Tabela `plugins_credenciais` (id, tipo, alias, secreto_encriptado, created_at, updated_at)
- Criptografia AES-256-GCM (key via ENV) ou libsodium
- Validação MIME (finfo) + limite de tamanho configurável
- Assinatura opcional (GPG) – pacote.zip + pacote.zip.sig
- Lista negra: `.env`, `composer.json` raiz, arquivos sensíveis

## Dados / Modelo
Campos adicionais em `plugins`:
- origem_token_ref
- log_ultima_execucao
- ultima_verificacao
- removido_em (soft delete)
 - descricao (opcional futura)
 - indice composto (origem_tipo, origem_referencia)

Tabela opcional `plugins_logs` para granularidade de auditoria.

## plugin em Recursos
Adicionar `plugin` (nullable) às tabelas: layouts, paginas, componentes, variaveis.
Motivos: desinstalação limpa, rollback, filtragem.
Índices recomendados: (plugin), (plugin, id).

## Refatoração Recursos Compartilhados
Criar `bibliotecas/recursos.php` com funções reutilizáveis (cálculo checksum, incremento de versão, exportação Data.json).
Providers: CoreResourceProvider / PluginResourceProvider.

## Atualizações Automáticas
- Cron/Agendador para verificar novas tags (GitHub API)
- Políticas: auto | notify | manual
- Registro de última checagem (`ultima_verificacao`).

## Rollback
- Armazenar ZIPs por versão em `backups/plugins/<slug>/<versao>.zip`
- Comando: `atualizacao-plugin.php --rollback --plugin=<slug> --versao=X`
- Verificar checksum antes de aplicar rollback.

## Dependências Entre Plugins
- Manifest: `dependencias: [{ id, min, max }]`
- Resolver grafo (topological sort) antes de batch install.
- Bloquear instalação se dependência ausente ou versão incompatível.

## Métricas / Telemetria
- Eventos: install_success, install_fail, update_nochange, update_changed, rollback_exec
- Persistir em tabela ou exportar JSON periódico.

## Desinstalação Completa
Fluxo:
1. Marcar plugin como removendo
2. Backup diretório
3. Remover registros com plugin
4. Excluir diretório
5. Registrar log final

## Scripts Pós-Instalação
- Executar `scripts_pos_instalacao` sequencialmente com timeout e stop-on-error configurável.

## Migrações Específicas de Plugins
- Diretório: `plugin/db/migrations/`
- Prefixo: `<slug>_<timestamp>_<descricao>.php`
- Tabela separada sugerida: `phinxlog_plugins` para organização.

## Assets Versionados
- Estrutura opcional: `assets/<versao>/...` para cache busting.

## CLI Extensões Futuras
- `--verify` integridade
- `--list` plugins instalados
- `--diff` manifest instalado vs novo pacote
- `--prune` limpeza de backups antigos

## Matriz de Cenários (F2+)
| Cenário | Resultado |
|---------|-----------|
| Atualização sem mudança | Pular sincronização |
| Downgrade bloqueado | Exigir --force |
| Dependência faltante | Abort |
| Rollback sucesso | Restaurar versão anterior |

## Riscos & Mitigações
- Colisão IDs → política de prefixos obrigatória
- Vazamento de tokens → criptografia + masking
- Corrupção parcial → staging atômico + checksums
 - Diretório alvo inconsistente → consolidar convenção (`gestor/plugins/`)

## Pendências Antes de Iniciar F2
1. Confirmar política de dependências
2. Escolher mecanismo de criptografia padrão
3. Decidir sobre adoção de assinatura GPG
4. Priorização: segurança vs rollback vs dependências

---
Gerado em 02/09/2025.