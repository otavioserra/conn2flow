---
title: "plugins-installer.php library"
label: "Plugin installer"
description: "Installs and updates a plugin: fetches the package (upload, public or private GitHub, local folder), checks SHA-256 and the manifest, moves it to plugins/<slug>/, runs migrations and syncs the data."
section: reference
order: 330
sources:
  - gestor/bibliotecas/plugins-installer.php
  - gestor/controladores/plugins/atualizacao-plugin.php
  - gestor/modulos/admin-plugins/admin-plugins.php
verified_at: a1a87ec9
---

# `plugins-installer.php` library

The plugin installation engine. It is called by the `admin-plugins` module (plugins screen) and by the `gestor/controladores/plugins/atualizacao-plugin.php` command-line script. Exit codes and states are in [plugins-consts.php](plugins-consts.md).

When included, it also loads `banco.php`, `plugins-consts.php` and the `admin-plugins` module controller.

## `plugin_process(array $params): int`

```php
$code = plugin_process([
    'slug' => 'my-plugin',
    'origem_tipo' => 'github_privado',     // upload | github_publico | github_privado | local_path
    'owner' => 'company', 'repo' => 'my-plugin', 'ref' => 'main',
    'cred_ref' => 'COMPANY',               // reads PLUGIN_TOKEN_COMPANY from the .env
]);
echo plg_exit_code_label($code);
```

In order:

1. **Fetch the package** into `sys_get_temp_dir()/plg_<slug>.zip`:
   - `upload`: copies `arquivo`;
   - `github_publico` / `github_privado`: downloads the zip of `owner/repo@ref`, or a direct `download_url` (release asset); the private one uses a token (`cred_ref`);
   - `local_path`: zips a local folder or copies a ready zip.
2. **SHA-256** (only when there is a `sha256_url`): downloads `<package>.sha256` and compares it with the zip hash. The file must contain **only the hash**; the `sha256sum` format (`hash  name`) is rejected. Without `sha256_url`, there is no integrity check.
3. **Unchanged checksum:** if the zip SHA-256 equals the one already recorded for the plugin, it stops here successfully (use `reprocessar` to force).
4. **Extract** into `gestor/temp/plugins/<slug>/` and locate `manifest.json` (at the root or in `plugin/`).
5. **Validate the manifest**: `id`, `name` and `version` required; `version` as `x.y.z`; `id` with lowercase letters, digits, `-` and `_`.
6. **Move** to `gestor/plugins/<slug>/` (backing up the previous one).
7. **Migrations**: the plugin's Phinx `db/migrations/`, with the core configuration.
8. **Data**: the plugin's `db/data/*Data.json` go to the database through the plugin updater (`controladores/plugins/atualizacao-plugin-banco-de-dados.php`), with the resources of the plugin's modules.
9. **Metadata** in the `plugins` table (version, checksum, origin, `status_execucao`) and a log in `gestor/logs/plugins/`.

Options: `dry_run` (neither moves nor writes to the database), `no_migrations`, `only_migrations`, `only_resources`, `no_resources`, `reprocessar`, `referencia`.

## GitHub credentials

`plugin_credentials_lookup($cred_ref)`: a value starting with `ghp_`, `github_pat_`, `gho_`, `ghu_` or `ghs_` is used as the token itself; anything else becomes the `PLUGIN_TOKEN_<VALUE>` variable of the `.env`.

> [!WARNING]
> `cred_ref` is stored in the `plugins` table (`origem_credencial_ref`). If you pass the token instead of a reference, **the token ends up in the database**. Always prefer the `.env` reference.

## Security

Installing a plugin means running its code on the server: only install packages from trusted sources, and with `sha256_url` whenever possible. Also, the normalization of names containing `\` (zips made on Windows) moves files without checking that the target stays inside the staging folder.

## Support functions

Paths (`plugin_base_root()`, `plugin_staging_path()`, `plugin_final_path()`, `plugin_datajson_dest_dir()`), files (`plugin_safe_mkdir()`, `plugin_remove_dir()`, `plugin_read_json()`, `plugin_compute_checksum()`, `plugin_fix_temp_file_permissions()`, `plugin_cleanup_after_install()`), download (`plugin_http_download()`, `plugin_github_zip_url()`, `plugin_download_github_public()`, `plugin_download_github_private()`, `plugin_copy_local_path()`, `plugin_extract_zip()`, `plugin_locate_manifest()`), installation (`plugin_move_to_final()`, `plugin_backup_existing()`, `plugin_persist_metadata()`, `plugin_mark_status()`, `plugin_checksum_changed()`, `plugin_log_block()`, `plugin_normalize_slug()`, `plugin_validate_manifest()`, `plugin_is_cli_context()`) and data (`plugin_run_migrations()`, `plugin_sync_datajson()`, `plugin_sync_datajson_multi()`, `plugin_sync_resources_granular()`, `plugin_sync_modules_resources()`, `plugin_enrich_resource_checksums()`, `plugin_guess_resource_files()`, `plugin_delegate_database_operations()`, `plugin_db_fetch_one()`, `plugin_upsert_generic()`, `plugin_upsert_layout()`, `plugin_upsert_page()`, `plugin_upsert_component()`, `plugin_upsert_variable()`, `plugin_upsert_module()`, `tabelaFromDataFile()`).

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/plugins-installer.php` by `c2f docs:extract` — 44 functions. Do not edit inside this block.

- `plugin_normalize_slug(string $slug): string` — [line 30](../../../../../gestor/bibliotecas/plugins-installer.php#L30)
  Normaliza um slug de plugin para formato seguro.
  Parameters:
  - `$slug`: Slug original do plugin.
  Returns: Slug normalizado (ex: "My-Plugin_123" → "my-plugin_123").
- `plugin_base_root(): string` — [line 37](../../../../../gestor/bibliotecas/plugins-installer.php#L37)
  Retorna o caminho raiz do diretório do gestor.
  Returns: Caminho absoluto para o diretório raiz.
- `plugin_staging_path(string $slug): string` — [line 45](../../../../../gestor/bibliotecas/plugins-installer.php#L45)
  Retorna o caminho de staging temporário para um plugin.
  Parameters:
  - `$slug`: Slug do plugin.
  Returns: Caminho completo para temp/plugins/{slug}.
- `plugin_final_path(string $slug): string` — [line 53](../../../../../gestor/bibliotecas/plugins-installer.php#L53)
  Retorna o caminho final de instalação de um plugin.
  Parameters:
  - `$slug`: Slug do plugin.
  Returns: Caminho completo para plugins/{slug}.
- `plugin_datajson_dest_dir(string $slug): string` — [line 61](../../../../../gestor/bibliotecas/plugins-installer.php#L61)
  Retorna o diretório de destino para arquivos *Data.json do plugin.
  Parameters:
  - `$slug`: Slug do plugin.
  Returns: Caminho completo para db/data/plugins/{slug}.
- `plugin_safe_mkdir(string $path): void` — [line 69](../../../../../gestor/bibliotecas/plugins-installer.php#L69)
  Cria um diretório recursivamente se não existir.
  Parameters:
  - `$path`: Caminho do diretório a criar.
- `plugin_remove_dir(string $dir): void` — [line 77](../../../../../gestor/bibliotecas/plugins-installer.php#L77)
  Remove um diretório recursivamente incluindo todo seu conteúdo.
  Parameters:
  - `$dir`: Caminho do diretório a remover.
- `plugin_compute_checksum(string $file): ?string` — [line 85](../../../../../gestor/bibliotecas/plugins-installer.php#L85)
  Calcula o checksum SHA-256 de um arquivo.
  Parameters:
  - `$file`: Caminho do arquivo.
  Returns: Hash SHA-256 ou null se o arquivo não existir.
- `plugin_is_cli_context(): bool` — [line 95](../../../../../gestor/bibliotecas/plugins-installer.php#L95)
  Detecta se o script está sendo executado via CLI ou web.
  Returns: True se CLI, false se web.
- `tabelaFromDataFile(string $file): string` — [line 110](../../../../../gestor/bibliotecas/plugins-installer.php#L110)
  Converte nome de arquivo *Data.json para nome de tabela snake_case.
  Parameters:
  - `$file`: Nome do arquivo *Data.json.
  Returns: Nome da tabela em snake_case.
- `plugin_read_json(string $path, array &$errors): ?array` — [line 134](../../../../../gestor/bibliotecas/plugins-installer.php#L134)
  Lê e decodifica um arquivo JSON.
  Parameters:
  - `$path`: Caminho do arquivo JSON.
  - `$errors`: Array de erros (passado por referência).
  Returns: Dados decodificados ou null em caso de erro.
- `plugin_validate_manifest(array $manifest, array &$errors): bool` — [line 154](../../../../../gestor/bibliotecas/plugins-installer.php#L154)
  Valida campos obrigatórios e formato do manifest.json do plugin.
  Parameters:
  - `$manifest`: Dados do manifest.json decodificados.
  - `$errors`: Array de erros (passado por referência).
  Returns: True se válido, false caso contrário.
- `plugin_credentials_lookup(?string $credRef): ?string` — [line 173](../../../../../gestor/bibliotecas/plugins-installer.php#L173)
  Busca credenciais de token para acesso a repositórios privados.
  Parameters:
  - `$credRef`: Referência ou token direto.
  Returns: Token encontrado ou null.
- `plugin_http_download(string $url, string $dest, array $headers, array &$log): bool` — [line 206](../../../../../gestor/bibliotecas/plugins-installer.php#L206)
  Realiza download HTTP de arquivo com suporte a cURL e fallback para streams.
  Parameters:
  - `$url`: URL para download.
  - `$dest`: Caminho do arquivo de destino.
  - `$headers`: Headers HTTP a enviar (formato: ['Header-Name' => 'value']).
  - `$log`: Array de log (passado por referência).
  Returns: True se sucesso, false em caso de erro.
- `plugin_github_zip_url(string $owner, string $repo, string $ref): string` — [line 276](../../../../../gestor/bibliotecas/plugins-installer.php#L276)
  Gera URL para download do zipball de um repositório GitHub.
  Parameters:
  - `$owner`: Proprietário do repositório (usuário ou organização).
  - `$repo`: Nome do repositório.
  - `$ref`: Referência (branch, tag ou commit SHA).
  Returns: URL completa para download do zipball.
- `plugin_download_github_public(string $owner, string $repo, string $ref, string $destZip, array &$log): bool` — [line 296](../../../../../gestor/bibliotecas/plugins-installer.php#L296)
  Baixa plugin de repositório público do GitHub.
  Parameters:
  - `$owner`: Proprietário do repositório.
  - `$repo`: Nome do repositório.
  - `$ref`: Referência (branch/tag/commit).
  - `$destZip`: Caminho do arquivo ZIP de destino.
  - `$log`: Array de log (passado por referência).
  Returns: True se sucesso, false em caso de erro.
- `plugin_download_github_private(string $owner, string $repo, string $ref, string $destZip, string $token, array &$log): bool` — [line 355](../../../../../gestor/bibliotecas/plugins-installer.php#L355)
  Baixa plugin de repositório privado do GitHub usando token de autenticação.
  Parameters:
  - `$owner`: Proprietário do repositório.
  - `$repo`: Nome do repositório.
  - `$ref`: Referência (branch/tag/commit).
  - `$destZip`: Caminho do arquivo ZIP de destino.
  - `$token`: Token de autenticação do GitHub.
  - `$log`: Array de log (passado por referência).
  Returns: True se sucesso, false em caso de erro.
- `plugin_copy_local_path(string $sourcePath, string $destZip, array &$log): bool` — [line 379](../../../../../gestor/bibliotecas/plugins-installer.php#L379)
  Copia plugin de caminho local (diretório ou arquivo ZIP).
  Parameters:
  - `$sourcePath`: Caminho local do plugin (diretório ou arquivo ZIP).
  - `$destZip`: Caminho do arquivo ZIP de destino.
  - `$log`: Array de log (passado por referência).
  Returns: True se sucesso, false em caso de erro.
- `plugin_extract_zip(string $zipFile, string $destDir, array &$log): bool` — [line 419](../../../../../gestor/bibliotecas/plugins-installer.php#L419)
  Extrai arquivo ZIP para diretório de destino.
  Parameters:
  - `$zipFile`: Caminho do arquivo ZIP.
  - `$destDir`: Diretório de destino para extração.
  - `$log`: Array de log (passado por referência).
  Returns: True se sucesso, false em caso de erro.
- `plugin_locate_manifest(string $staging, array &$log): ?string` — [line 457](../../../../../gestor/bibliotecas/plugins-installer.php#L457)
  Localiza o arquivo manifest.json dentro do diretório de staging.
  Parameters:
  - `$staging`: Diretório de staging extraído.
  - `$log`: Array de log (passado por referência).
  Returns: Caminho completo do manifest.json ou null se não encontrado.
- `plugin_move_to_final(string $staging, string $final, array &$log): bool` — [line 490](../../../../../gestor/bibliotecas/plugins-installer.php#L490)
  Move diretório do plugin de staging para localização final.
  Parameters:
  - `$staging`: Diretório de staging.
  - `$final`: Diretório final de instalação.
  - `$log`: Array de log (passado por referência).
  Returns: True se sucesso, false em caso de erro.
- `plugin_persist_metadata(string $slug, array $manifest, ?string $checksum, string $origemTipo, array $opcoes, array &$log): void` — [line 555](../../../../../gestor/bibliotecas/plugins-installer.php#L555)
  Persiste metadados do plugin na tabela plugins do banco de dados.
  Parameters:
  - `$slug`: ID único do plugin.
  - `$manifest`: Dados do manifest.json.
  - `$checksum`: Hash SHA-256 do pacote.
  - `$origemTipo`: Tipo de origem (github_public, github_private, local).
  - `$opcoes`: Opções de instalação (referencia, ref, cred_ref).
  - `$log`: Array de log (passado por referência).
- `plugin_backup_existing(string $finalPath, array &$log): void` — [line 610](../../../../../gestor/bibliotecas/plugins-installer.php#L610)
  Cria backup em ZIP da instalação existente do plugin.
  Parameters:
  - `$finalPath`: Caminho do plugin instalado.
  - `$log`: Array de log (passado por referência).
- `plugin_sync_datajson(string $staging, string $slug, array &$log): void` — [line 638](../../../../../gestor/bibliotecas/plugins-installer.php#L638)
  Sincroniza arquivos Data.json do plugin com o banco de dados.
  Parameters:
  - `$staging`: Diretório de staging do plugin
  - `$slug`: Identificador único do plugin
  - `$log`: Referência ao array de log (passado por referência)
- `plugin_sync_datajson_multi(array $filesMap, string $slug, array &$log, string $finalBase): void` — [line 705](../../../../../gestor/bibliotecas/plugins-installer.php#L705)
  Sincroniza dados a partir de arquivos *Data.json detectados dinamicamente.
  Parameters:
  - `$filesMap`: Mapeamento de nome da tabela para caminho do arquivo Data.json
  - `$slug`: Identificador único do plugin
  - `$log`: Referência ao array de log (passado por referência)
  - `$finalBase`: Caminho base do plugin instalado
- `plugin_run_migrations(string $slug, string $finalPath, array &$log, array $opts = []): void` — [line 748](../../../../../gestor/bibliotecas/plugins-installer.php#L748)
  Executa migrações de banco de dados específicas do plugin usando Phinx.
  Parameters:
  - `$slug`: Identificador único do plugin
  - `$finalPath`: Caminho final do plugin instalado
  - `$log`: Referência ao array de log (passado por referência)
  - `$opts`: Opções de execução:
- `plugin_sync_resources_granular(array $resources, string $pluginId, ?string $baseDir, array &$log): void` — [line 794](../../../../../gestor/bibliotecas/plugins-installer.php#L794)
  Sincroniza recursos (layouts, pages, components, variables) de forma granular.
  Parameters:
  - `$resources`: Array de recursos organizados por idioma
  - `$pluginId`: Identificador único do plugin
  - `$baseDir`: Diretório base para buscar arquivos HTML/CSS (opcional)
  - `$log`: Referência ao array de log (passado por referência)
- `plugin_enrich_resource_checksums(?string $baseDir, string $tipo, string $id, array &$item, array &$log): void` — [line 848](../../../../../gestor/bibliotecas/plugins-installer.php#L848)
  Enriquece recursos com checksums calculados a partir de arquivos HTML e CSS.
  Parameters:
  - `$baseDir`: Diretório base para buscar arquivos
  - `$tipo`: Tipo de recurso: 'layouts', 'pages', ou 'components'
  - `$id`: Identificador do recurso
  - `$item`: Referência ao array do item (modificado com checksums)
  - `$log`: Referência ao array de log (passado por referência)
- `plugin_guess_resource_files(string $baseDir, string $tipo, string $id): ?array` — [line 873](../../../../../gestor/bibliotecas/plugins-installer.php#L873)
  Tenta localizar automaticamente arquivos HTML e CSS de um recurso.
  Parameters:
  - `$baseDir`: Diretório base do plugin
  - `$tipo`: Tipo de recurso: 'layouts', 'pages', ou 'components'
  - `$id`: Identificador do recurso
  Returns: Array com chaves 'html' e/ou 'css', ou null se não encontrado
- `plugin_sync_modules_resources(string $staging, string $pluginId, array &$log): void` — [line 905](../../../../../gestor/bibliotecas/plugins-installer.php#L905)
  Sincroniza recursos de módulos a partir de arquivos module-id.json.
  Parameters:
  - `$staging`: Diretório de staging do plugin
  - `$pluginId`: Identificador único do plugin
  - `$log`: Referência ao array de log (passado por referência)
- `plugin_db_fetch_one(string $sql): array|null` — [line 995](../../../../../gestor/bibliotecas/plugins-installer.php#L995)
  Busca e retorna um único registro do banco de dados.
  Parameters:
  - `$sql`: Consulta SQL completa
  Returns: Array associativo com os dados ou null se não encontrado
- `plugin_delegate_database_operations(string $pluginSlug, array $dataFiles, array &$log): bool` — [line 1011](../../../../../gestor/bibliotecas/plugins-installer.php#L1011)
  Delega operações de banco de dados para o sistema robusto de atualizações.
  Parameters:
  - `$pluginSlug`: Slug normalizado do plugin
  - `$dataFiles`: Lista de arquivos Data.json para processar (vazio = todos)
  - `$log`: Array de log passado por referência para registrar mensagens
  Returns: True se a delegação foi bem-sucedida, false em caso de erro
- `plugin_upsert_generic(string $tableName, string $pluginSlug, array $row, array &$tot, array &$log): void` — [line 1080](../../../../../gestor/bibliotecas/plugins-installer.php#L1080)
  Função genérica de upsert que delega para o sistema robusto de banco de dados.
  Parameters:
  - `$tableName`: Nome da tabela
  - `$pluginSlug`: Identificador único do plugin
  - `$row`: Dados da linha a ser inserida/atualizada
  - `$tot`: Referência ao array de totalizadores (passado por referência)
  - `$log`: Referência ao array de log (passado por referência)
- `plugin_upsert_layout(string $plugin, string $lang, string $id, array $src, array &$tot): void` — [line 1103](../../../../../gestor/bibliotecas/plugins-installer.php#L1103)
  Insere ou atualiza um layout no banco de dados.
  Parameters:
  - `$plugin`: Identificador do plugin
  - `$lang`: Código do idioma (ex: 'pt-br')
  - `$id`: Identificador único do layout
  - `$src`: Dados source com chaves: name/nome, checksum, version
  - `$tot`: Referência ao array de totalizadores (passado por referência)
- `plugin_upsert_page(string $plugin, string $lang, string $id, array $src, array &$tot): void` — [line 1164](../../../../../gestor/bibliotecas/plugins-installer.php#L1164)
  Insere ou atualiza uma página no banco de dados.
  Parameters:
  - `$plugin`: Identificador do plugin
  - `$lang`: Código do idioma (ex: 'pt-br')
  - `$id`: Identificador único da página
  - `$src`: Dados source com chaves: name/nome, path/caminho, module/modulo, checksum, version
  - `$tot`: Referência ao array de totalizadores (passado por referência)
- `plugin_upsert_component(string $plugin, string $lang, string $id, array $src, array &$tot): void` — [line 1232](../../../../../gestor/bibliotecas/plugins-installer.php#L1232)
  Insere ou atualiza um componente no banco de dados.
  Parameters:
  - `$plugin`: Identificador do plugin
  - `$lang`: Código do idioma (ex: 'pt-br')
  - `$id`: Identificador único do componente
  - `$src`: Dados source com chaves: name/nome, module/modulo, checksum, version
  - `$tot`: Referência ao array de totalizadores (passado por referência)
- `plugin_upsert_variable(string $plugin, string $lang, string $id, array $src, array &$tot): void` — [line 1295](../../../../../gestor/bibliotecas/plugins-installer.php#L1295)
  Insere ou atualiza uma variável no banco de dados.
  Parameters:
  - `$plugin`: Identificador do plugin
  - `$lang`: Código do idioma (ex: 'pt-br')
  - `$id`: Identificador único da variável
  - `$src`: Dados source com chaves: value/valor, type/tipo, group/grupo, module/modulo
  - `$tot`: Referência ao array de totalizadores (passado por referência)
- `plugin_upsert_module(string $plugin, string $id, array $src, array &$tot): void` — [line 1346](../../../../../gestor/bibliotecas/plugins-installer.php#L1346)
  Insere ou atualiza um módulo no banco de dados.
  Parameters:
  - `$plugin`: Identificador do plugin
  - `$id`: Identificador único do módulo
  - `$src`: Dados source com chaves: name/nome, modulo_grupo_id, titulo, icone, icone2,
  - `$tot`: Referência ao array de totalizadores (passado por referência)
- `plugin_checksum_changed(string $slug, ?string $newChecksum): bool` — [line 1402](../../../../../gestor/bibliotecas/plugins-installer.php#L1402)
  Verifica se o checksum do pacote mudou desde a última instalação.
  Parameters:
  - `$slug`: Identificador único do plugin
  - `$newChecksum`: Novo checksum calculado (SHA-256)
  Returns: True se mudou ou se não há checksum anterior, false se inalterado
- `plugin_mark_status(string $slug, string $status): void` — [line 1424](../../../../../gestor/bibliotecas/plugins-installer.php#L1424)
  Marca o status de execução do plugin no banco de dados.
  Parameters:
  - `$slug`: Identificador único do plugin
  - `$status`: Status a ser registrado (ex: 'instalando', 'erro', 'concluído')
- `plugin_log_block(array $logLines, string $slug): void` — [line 1440](../../../../../gestor/bibliotecas/plugins-installer.php#L1440)
  Registra mensagens de log em arquivo com timestamp.
  Parameters:
  - `$logLines`: Array de mensagens de log
  - `$slug`: Identificador único do plugin
- `plugin_cleanup_after_install(string $finalPath, array &$log): void` — [line 1458](../../../../../gestor/bibliotecas/plugins-installer.php#L1458)
  Limpa pasta DB e corrige permissões após instalação do plugin.
  Parameters:
  - `$finalPath`: Caminho do plugin instalado
  - `$log`: Referência ao array de log (passado por referência)
- `plugin_fix_temp_file_permissions(string $filePath, array &$log): void` — [line 1479](../../../../../gestor/bibliotecas/plugins-installer.php#L1479)
  Corrige permissões de arquivo temporário via chown.
  Parameters:
  - `$filePath`: Caminho completo do arquivo
  - `$log`: Referência ao array de log (passado por referência)
- `plugin_process(array $params): int` — [line 1539](../../../../../gestor/bibliotecas/plugins-installer.php#L1539)
  Função principal do instalador de plugins - orquestra todo o processo.
  Parameters:
  - `$params`: Parâmetros da instalação:
  Returns: Código de saída:

<!-- c2f:extract:end -->
