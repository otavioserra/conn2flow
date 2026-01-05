# Gestor Desenvolvimento - Antigo 8 (Setembro 2025)

## Objetivo Focado Desta Sessão
Adaptar rigidamente o script core `atualizacoes-banco-de-dados.php` para um equivalente de plugin (`atualizacao-plugin-banco-de-dados.php`), sem "inovar": apenas ajustar caminhos, suportar `--plugin=<slug>` e operar sobre `db/data` do plugin, mantendo toda a lógica original (migracoes, checksum, dry-run, orphans, reverse export, backup, filtros, etc.).

## Escopo Realizado
- Criação/adaptação quase linha a linha do script de atualização para plugins.
- Inclusão de resolução dinâmica segura de diretório do plugin.
- Suporte obrigatório a `--plugin` e reutilização dos mesmos flags do core (`--dry-run`, `--debug`, `--skip-migrate`, `--tables`, `--force-all`, `--backup`, `--reverse`, `--log-diff`, `--orphans-mode`).
- Ajuste de logs (arquivo nomeado `atualizacoes-plugin-bd-<slug>`).
- Exportação de órfãos isolada por plugin (`plugins/<slug>/db/orphans/bd/`).
- Inclusão opcional do diretório de migrações do plugin (`plugins/<slug>/db/migrations`) ao array de paths Phinx sem remover o core.
- Proteção contra slug inválido (barras) e log de debug de path.
- Tolerância a ausência de `.env` (apenas warning) – conexão via `config.php`.
- Salvaguarda: pulo de arquivos *Data.json cujas tabelas não existem no banco (evita erro `Base table ... doesn't exist`).

## Arquivos / Diretórios Envolvidos
- `gestor/controladores/plugins/atualizacao-plugin-banco-de-dados.php` (novo/adaptado) – principal entrega.
- `gestor/plugins/example-plugin/db/data/PaginasData.json` – fixture simples para validação.
- Sincronização Docker via script existente (`docker/utils/sincroniza-gestor.sh checksum`).

## Problemas Encontrados & Soluções
| Problema | Causa | Solução |
|---------|-------|---------|
| Script não encontrava plugin | `BASE_PATH_DB` relativo sem recalcular em runtime dentro do container | Recalcular `BASE_PATH_DB` em `main()` com `realpath(__DIR__.'/../../')` |
| Erro de diretório plugin não encontrado | Caminho relativo resolvendo vazio em log (`base=`) | Ajuste de recalculo + log de debug antes da validação |
| Falha por ausência de `.env` | Ambiente de testes não sincronizado | Tornar checagem não-fatal: apenas aviso e prosseguir |
| Exceção tabela inexistente (ex: ExampleData.json) | Data.json excede esquema padrão | Inserir verificação `SHOW TABLES LIKE` antes de sincronizar |
| Migrações plugin potencialmente conflitando | Necessidade de incluir pasta sem romper core | Anexar diretório a `paths['migrations']` (array merge) |

## Execução de Testes (Container)
1. Sincronização: `bash docker/utils/sincroniza-gestor.sh checksum` (task fornecida).  
2. Dry-run:  
   `docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/plugins/atualizacao-plugin-banco-de-dados.php --plugin=example-plugin --dry-run --debug --skip-migrate"`
3. Resultado obtido (dry-run):
```
📝 Relatório Final Atualização BD
══════════════════════════════════════════════════
📦 componentes => +0 ~6 =0
📦 layouts => +0 ~2 =0
📦 paginas => +1 ~0 =0
📦 variaveis => +0 ~0 =6
Σ TOTAL => +1 ~8 =6
```
4. Logs armazenados em `logs/atualizacoes/atualizacoes-plugin-bd-example-plugin.log` (contendo linha DEBUG inicial antes da correção e execuções subsequentes).

## Decisões Deliberadas
- NÃO criar tabela dedicada de checksum para plugins (requisito era copiar lógica – manter uso de `manager_updates`).
- NÃO alterar assinatura de funções internas (garante diffs mínimos com core). 
- NÃO remover código de features não necessárias agora (reverse export, backup) para preservar paridade.

## Riscos / Limitações
- `manager_updates` continua acumulando entradas também para plugins (pode exigir filtro futuro se volume crescer).
- Migrações de plugin são apenas adicionadas ao path; colisões de nomes/timestamps malformados ainda precisam de curadoria (exemplo existente com timestamp curto gerará erro Phinx se `--skip-migrate` não usado).
- Log de debug `DEBUG_PLUGIN_CANDIDATE` permanece (pode ser removido em limpeza posterior).

## Próximos Passos Sugeridos
1. (Opcional) Executar sem `--dry-run` para aplicar atualizações reais do plugin.  
2. Sanitizar migrações do plugin garantindo timestamps válidos `YYYYMMDDHHMMSS`.  
3. Adicionar flag futura `--no-manager-log` se quiser evitar registrar em `manager_updates` ao rodar só plugins.  
4. Remover log de debug e consolidar documentação curta em `ai-workspace/docs/PLUGIN-INSTALADOR-FLUXO.md` (linkando script).  
5. Criar teste automatizado simples (PHP) validando: inserção inicial vs. segunda execução sem mudanças (idempotência).  

## Checklist de Entrega (Sessão)
- [x] Adaptação linha a linha preservando funções core
- [x] Suporte obrigatório `--plugin=<slug>`
- [x] Resolução de diretórios (dados, migrações, órfãos) plugin
- [x] Dry-run validado no container
- [x] Pulo de tabelas inexistentes evitando exceptions
- [x] Registro em log separado por slug

## Diferenças Principais vs. Core (Intencionais)
| Aspecto | Core | Plugin Adaptado |
|---------|------|-----------------|
| Diretório data | `gestor/db/data/` | `gestor/plugins/<slug>/db/data/` |
| Orphans | `gestor/db/orphans/bd/` | `gestor/plugins/<slug>/db/orphans/bd/` |
| Migrações extra | Apenas core | Core + plugin (append) |
| .env ausente | Fatal | Warning (continua) |
| Path base dinamização | Assumido na carga inicial | Recalculado em `main()` |

## Commits de Contexto Relevantes (antes desta sessão)
Referência a modernizações recentes (preview system, framework CSS, atualização de update engine) que motivam manter scripts de atualização claros e isolados.

## Conclusão
A sessão cumpriu estritamente o objetivo de criar a variante de atualização de banco para plugins, mantendo paridade lógica com o script principal e introduzindo apenas adaptações mínimas necessárias para escopo de plugin. O fluxo agora permite sincronizações independentes de dados de plugins com segurança (dry-run, checksum, backups, filtragem) dentro do mesmo ecossistema de atualização já existente.

_Pronto para repassar ao próximo agente. Este documento funciona como ponto de restauração contextual (Antigo 8)._ 
