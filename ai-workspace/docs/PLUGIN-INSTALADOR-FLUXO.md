# Fluxo Instalador de Plugins (Fase 1)

## Objetivo
Gerenciar instalação/atualização de plugins a partir de artefato ZIP local (origem `upload`) substituindo completamente os arquivos do plugin (sem limpeza de registros antigos no banco neste momento) e sincronizando recursos declarados.

## Escopo Fase 1
- Origens suportadas: `upload` (ZIP local). GitHub e outros: FUTURO.
- Sem remoção de registros de banco. Apenas inserção/atualização (adoption de órfãos) e substituição física de arquivos.
- Backup automático da versão anterior antes de sobrescrever.
- Cálculo de checksums (SHA-256) para recursos HTML/CSS quando ausentes.
- Suporte a Data.json + módulos (module-id.json).

## Localizações
- Código principal: `gestor/bibliotecas/plugins-installer.php`
- Orquestração CLI: `gestor/controladores/plugins/atualizacao-plugin.php`
- Logs: `gestor/logs/plugins/installer.log`
- Backups: `gestor/plugins/_backups/<slug>-YYYYMMDD-HHMMSS.zip`

## Pipeline
1. Validação de parâmetros / origem.
2. Cópia do ZIP para staging e extração em `temp/plugins/<slug>`.
3. (Se existir instalação anterior) backup ZIP da pasta final existente.
4. Remoção da pasta final antiga e cópia dos novos arquivos.
5. Localização do `Data.json` (busca recursiva em `db/data`).
6. Cópia do `Data.json` para diretório do plugin.
7. Parse e sincronização granular de recursos (layouts, pages, components, variables).
8. Sincronização de módulos (procura `modules/*/module-id.json`).
9. Persistência / atualização de metadados na tabela `plugins`.
10. Logging final e código de saída.

## Recursos e Checksums
Para layouts, páginas e componentes:
- Caminhos tentados para cada ID:
  - `<tipo>/<id>.html` e `<tipo>/<id>.css`
  - `<tipo>/<id>/index.html` e `<tipo>/<id>/index.css`
- Se encontrados, gera-se:
```
checksum: {
  html: <sha256 html ou null>,
  css: <sha256 css ou null>,
  combined: sha256(html_hash + ':' + css_hash)
}
```
- Se já houver `checksum` no Data.json, permanece.

## Adoção (Orphan Adoption)
Na inserção:
- Se registro existente para a mesma chave natural (ignorando inicialmente `plugin` e às vezes `module`), e estiver sem `plugin`, o registro é "adotado" (atualiza-se `plugin` + campos).
- Evita duplicações em reprocessamentos.

## Decisões Importantes
- NÃO remover registros de banco nesta fase (limpeza futura: Fase 2).
- Backup sempre antes de substituir, garantindo rollback manual simples.
- Estrutura preparada para adicionar remoção de recursos obsoletos posteriormente (marcando diffs com base em checksum).

## Logs Principais
Exemplos:
- `[ok] backup criado em <path>`
- `[ok] sync granular plugin=<slug> inserts=X updates=Y skipped=Z`
- `[ok] sync módulos plugin=<slug> modules=N inserts=X updates=Y skipped=Z`

## Possíveis Próximas Fases
1. Limpeza de registros órfãos / duplicados antigos.
2. Enforcing UNIQUE constraints após saneamento.
3. Suporte a origem GitHub (público/privado). 
4. Estratégia de remoção ou arquivamento de recursos não mais presentes no pacote.
5. Relatórios detalhados de diff (similar aos scripts de atualização de sistema).

## Referências Internas
- Scripts de atualização: `gestor/controladores/atualizacoes/atualizacoes-sistema.php` e `.../atualizacoes-banco-de-dados.php` serviram como inspiração para fallback/adoption.

## Testes
Script utilitário: `gestor/tests/plugin-counts.php` para contagem de recursos por plugin.
Fluxo usado:
```
php ai-workspace/scripts/build-test-plugin.php
bash docker/utils/sincroniza-gestor.sh checksum
php gestor/controladores/plugins/atualizacao-plugin.php --id=test-plugin --origem_tipo=upload --arquivo=gestor/tests/build/test-plugin.zip --debug
php gestor/tests/plugin-counts.php --id=test-plugin
```

## Notas
- Caso o ZIP não contenha `Data.json`, instalação continua mas sem sincronização de recursos.
- Backups são acumulativos; política de retenção poderá ser aplicada futuramente.
