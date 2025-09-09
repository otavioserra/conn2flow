# Fluxo Instalador de Plugins (Fase 1)

## Objetivo
Gerenciar instalação/atualização de plugins a partir de artefato ZIP local (origem `upload`), GitHub público/privado ou caminho local, substituindo completamente os arquivos do plugin e sincronizando recursos declarados dinamicamente.

## Escopo Fase 1
- Origens suportadas: `upload` (ZIP local), `github_publico`, `github_privado`, `local_path`.
- Detecção automática de todos os arquivos `*Data.json` no diretório `db/data/` do plugin.
- Suporte a qualquer tabela via arquivos `*Data.json` (não limitado a lista hardcoded).
- Backup automático da versão anterior antes de sobrescrever.
- Cálculo de checksums (SHA-256) para recursos HTML/CSS quando ausentes.
- Suporte a Data.json + módulos (module-id.json).
- Limpeza automática da pasta `db/` após processamento.
- Correção automática de permissões de arquivos.

## Localizações
- Código principal: `gestor/bibliotecas/plugins-installer.php`
- Orquestração CLI: `gestor/controladores/plugins/atualizacao-plugin.php`
- Logs: `gestor/logs/plugins/installer.log`
- Backups: `gestor/plugins/_backups/<slug>-YYYYMMDD-HHMMSS.zip`
- Staging: `gestor/temp/plugins/<slug>/`

## Pipeline Atualizado
1. Validação de parâmetros / origem.
2. Download/cópia do pacote para staging (`temp/plugins/<slug>/`).
3. Extração do ZIP em staging.
4. Validação de manifest.json e estrutura.
5. Backup da instalação anterior (se existir).
6. Movimentação de arquivos para diretório final (`plugins/<slug>/`).
7. Execução de migrações (se habilitadas).
8. **Detecção automática de todos os `*Data.json`** no diretório `db/data/`.
9. Sincronização granular de recursos para cada arquivo detectado.
10. Sincronização de módulos (procura `modules/*/module-id.json`).
11. **Limpeza da pasta `db/` do plugin instalado**.
12. **Correção de permissões (chown recursivo)**.
13. Persistência / atualização de metadados na tabela `plugins`.
14. Logging final e código de saída.

## Detecção Dinâmica de Data.json
- **Antes**: Lista hardcoded de arquivos específicos.
- **Agora**: Uso de `glob('*Data.json')` para detectar automaticamente todos os arquivos.
- **Conversão**: Função `tabelaFromDataFile()` converte nome do arquivo para nome da tabela.
- **Exemplos**:
  - `ModulosData.json` → tabela `modulos`
  - `ExampleTableData.json` → tabela `example_table`
  - `HostsConfiguracoesData.json` → tabela `hosts_configuracoes`

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

## Limpeza Pós-Instalação
Após processamento completo:
- **Remoção da pasta `db/`**: Evita lixo no plugin instalado, pois os Data.json já foram processados.
- **Correção de permissões**: `chown -R` recursivo usando dono/grupo da pasta pai (`plugins/`).

## Adoção (Orphan Adoption)
Na inserção:
- Se registro existente para a mesma chave natural (ignorando inicialmente `plugin` e às vezes `module`), e estiver sem `plugin`, o registro é "adotado" (atualiza-se `plugin` + campos).
- Evita duplicações em reprocessamentos.

## Decisões Importantes
- NÃO remover registros de banco nesta fase (limpeza futura: Fase 2).
- Backup sempre antes de substituir, garantindo rollback manual simples.
- Detecção dinâmica permite plugins atualizarem qualquer tabela.
- Limpeza automática evita acúmulo de arquivos desnecessários.
- Correção de permissões garante consistência no ambiente.

## Logs Principais
Exemplos:
- `[ok] backup criado em <path>`
- `[ok] Detectado modo multi-arquivos de dados (layouts, paginas, componentes, variaveis, modulos)`
- `[ok] sync módulos plugin=<slug> modules=1 inserts=0 updates=1 skipped=0`
- `[ok] multi-data sincronizado plugin=<slug> inserts=3 updates=12 skipped=0`
- `[ok] pasta db/ removida do plugin instalado`
- `[ok] permissões corrigidas para www-data:www-data`

## Possíveis Próximas Fases
1. Limpeza de registros órfãos / duplicados antigos.
2. Enforcing UNIQUE constraints após saneamento.
3. Estratégia de remoção ou arquivamento de recursos não mais presentes no pacote.
4. Relatórios detalhados de diff (similar aos scripts de atualização de sistema).
5. Suporte a dependências entre plugins.

## Referências Internas
- Scripts de atualização: `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php` serviram como inspiração.
- Função `tabelaFromDataFile()` reutilizada dos scripts de atualização do sistema.

## Testes
Script utilitário: `gestor/tests/plugin-counts.php` para contagem de recursos por plugin.
Fluxo usado:
```
# Teste completo com detecção dinâmica:
php gestor/controladores/plugins/atualizacao-plugin.php --id=test-plugin --origem_tipo=local_path --local_path=/var/www/sites/localhost/conn2flow-gestor/plugins/test-plugin
```

## Notas
- Caso o ZIP não contenha `Data.json`, instalação continua mas sem sincronização de recursos.
- Backups são acumulativos; política de retenção poderá ser aplicada futuramente.
- Sistema agora suporta instalação de plugins que atualizam qualquer tabela do banco via `*Data.json`.
- Limpeza automática da pasta `db/` evita arquivos desnecessários no plugin instalado.
- Correção de permissões garante que arquivos tenham o dono/grupo correto do ambiente.
