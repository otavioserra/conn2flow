---
title: "Biblioteca plugins-installer.php"
label: "Instalador de plugins"
description: "Instala e atualiza um plugin: obtém o pacote (upload, GitHub público ou privado, pasta local), confere SHA-256 e manifesto, move para plugins/<slug>/, roda migrações e sincroniza os dados."
section: reference
order: 330
sources:
  - gestor/bibliotecas/plugins-installer.php
  - gestor/controladores/plugins/atualizacao-plugin.php
  - gestor/modulos/admin-plugins/admin-plugins.php
verified_at: a1a87ec9
---

# Biblioteca `plugins-installer.php`

O motor de instalação de plugins. Quem o chama é o módulo `admin-plugins` (tela de plugins) e o script de linha de comando `gestor/controladores/plugins/atualizacao-plugin.php`. Os códigos de saída e estados estão em [plugins-consts.php](plugins-consts.md).

Ao ser incluída, carrega também `banco.php`, `plugins-consts.php` e o controlador do módulo `admin-plugins`.

## `plugin_process(array $params): int`

```php
$codigo = plugin_process([
    'slug' => 'meu-plugin',
    'origem_tipo' => 'github_privado',     // upload | github_publico | github_privado | local_path
    'owner' => 'empresa', 'repo' => 'meu-plugin', 'ref' => 'main',
    'cred_ref' => 'EMPRESA',               // lê PLUGIN_TOKEN_EMPRESA do .env
]);
echo plg_exit_code_label($codigo);
```

Na ordem:

1. **Obter o pacote** em `sys_get_temp_dir()/plg_<slug>.zip`:
   - `upload`: copia `arquivo`;
   - `github_publico` / `github_privado`: baixa o zip de `owner/repo@ref`, ou uma `download_url` direta (asset de release); o privado usa um token (`cred_ref`);
   - `local_path`: compacta uma pasta local ou copia um zip pronto.
2. **SHA-256** (só quando há `sha256_url`): baixa `<pacote>.sha256` e compara com o hash do zip. O arquivo precisa conter **só o hash**; o formato do `sha256sum` (`hash  nome`) é recusado. Sem `sha256_url`, não há verificação de integridade.
3. **Checksum inalterado:** se o SHA-256 do zip é o mesmo já registrado para o plugin, para aqui com sucesso (use `reprocessar` para forçar).
4. **Extrair** em `gestor/temp/plugins/<slug>/` e localizar `manifest.json` (na raiz ou em `plugin/`).
5. **Validar o manifesto**: `id`, `name` e `version` obrigatórios; `version` no formato `x.y.z`; `id` com minúsculas, dígitos, `-` e `_`.
6. **Mover** para `gestor/plugins/<slug>/` (com backup do anterior).
7. **Migrações** Phinx de `db/migrations/` do plugin, com a configuração do core.
8. **Dados**: os `db/data/*Data.json` do plugin vão para o banco pelo atualizador de plugins (`controladores/plugins/atualizacao-plugin-banco-de-dados.php`), com os recursos dos módulos do plugin.
9. **Metadados** na tabela `plugins` (versão, checksum, origem, `status_execucao`) e log em `gestor/logs/plugins/`.

Opções: `dry_run` (não move nem grava no banco), `no_migrations`, `only_migrations`, `only_resources`, `no_resources`, `reprocessar`, `referencia`.

## Credenciais do GitHub

`plugin_credentials_lookup($cred_ref)`: um valor que começa com `ghp_`, `github_pat_`, `gho_`, `ghu_` ou `ghs_` é usado como o próprio token; qualquer outro vira a variável `PLUGIN_TOKEN_<VALOR>` do `.env`.

> [!WARNING]
> O `cred_ref` é gravado na tabela `plugins` (`origem_credencial_ref`). Se você passar o token em vez de uma referência, **o token fica no banco**. Prefira sempre a referência ao `.env`.

## Segurança

Instalar um plugin é executar o código dele no servidor: só instale pacotes de origem confiável, e com `sha256_url` sempre que possível. Além disso, a normalização de nomes com `\` (zips feitos no Windows) move arquivos sem conferir se o destino continua dentro da pasta de staging.

## Funções de apoio

Caminhos (`plugin_base_root()`, `plugin_staging_path()`, `plugin_final_path()`, `plugin_datajson_dest_dir()`), arquivos (`plugin_safe_mkdir()`, `plugin_remove_dir()`, `plugin_read_json()`, `plugin_compute_checksum()`, `plugin_fix_temp_file_permissions()`, `plugin_cleanup_after_install()`), download (`plugin_http_download()`, `plugin_github_zip_url()`, `plugin_download_github_public()`, `plugin_download_github_private()`, `plugin_copy_local_path()`, `plugin_extract_zip()`, `plugin_locate_manifest()`), instalação (`plugin_move_to_final()`, `plugin_backup_existing()`, `plugin_persist_metadata()`, `plugin_mark_status()`, `plugin_checksum_changed()`, `plugin_log_block()`, `plugin_normalize_slug()`, `plugin_validate_manifest()`, `plugin_is_cli_context()`) e dados (`plugin_run_migrations()`, `plugin_sync_datajson()`, `plugin_sync_datajson_multi()`, `plugin_sync_resources_granular()`, `plugin_sync_modules_resources()`, `plugin_enrich_resource_checksums()`, `plugin_guess_resource_files()`, `plugin_delegate_database_operations()`, `plugin_db_fetch_one()`, `plugin_upsert_generic()`, `plugin_upsert_layout()`, `plugin_upsert_page()`, `plugin_upsert_component()`, `plugin_upsert_variable()`, `plugin_upsert_module()`, `tabelaFromDataFile()`).

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/plugins-installer.php` por `c2f docs:extract` — 44 funções. Não edite dentro deste bloco.

- `plugin_normalize_slug(string $slug): string` — [linha 30](../../../../../gestor/bibliotecas/plugins-installer.php#L30)
- `plugin_base_root(): string` — [linha 37](../../../../../gestor/bibliotecas/plugins-installer.php#L37)
- `plugin_staging_path(string $slug): string` — [linha 45](../../../../../gestor/bibliotecas/plugins-installer.php#L45)
- `plugin_final_path(string $slug): string` — [linha 53](../../../../../gestor/bibliotecas/plugins-installer.php#L53)
- `plugin_datajson_dest_dir(string $slug): string` — [linha 61](../../../../../gestor/bibliotecas/plugins-installer.php#L61)
- `plugin_safe_mkdir(string $path): void` — [linha 69](../../../../../gestor/bibliotecas/plugins-installer.php#L69)
- `plugin_remove_dir(string $dir): void` — [linha 77](../../../../../gestor/bibliotecas/plugins-installer.php#L77)
- `plugin_compute_checksum(string $file): ?string` — [linha 85](../../../../../gestor/bibliotecas/plugins-installer.php#L85)
- `plugin_is_cli_context(): bool` — [linha 95](../../../../../gestor/bibliotecas/plugins-installer.php#L95)
- `tabelaFromDataFile(string $file): string` — [linha 110](../../../../../gestor/bibliotecas/plugins-installer.php#L110)
- `plugin_read_json(string $path, array &$errors): ?array` — [linha 134](../../../../../gestor/bibliotecas/plugins-installer.php#L134)
- `plugin_validate_manifest(array $manifest, array &$errors): bool` — [linha 154](../../../../../gestor/bibliotecas/plugins-installer.php#L154)
- `plugin_credentials_lookup(?string $credRef): ?string` — [linha 173](../../../../../gestor/bibliotecas/plugins-installer.php#L173)
- `plugin_http_download(string $url, string $dest, array $headers, array &$log): bool` — [linha 206](../../../../../gestor/bibliotecas/plugins-installer.php#L206)
- `plugin_github_zip_url(string $owner, string $repo, string $ref): string` — [linha 276](../../../../../gestor/bibliotecas/plugins-installer.php#L276)
- `plugin_download_github_public(string $owner, string $repo, string $ref, string $destZip, array &$log): bool` — [linha 296](../../../../../gestor/bibliotecas/plugins-installer.php#L296)
- `plugin_download_github_private(string $owner, string $repo, string $ref, string $destZip, string $token, array &$log): bool` — [linha 355](../../../../../gestor/bibliotecas/plugins-installer.php#L355)
- `plugin_copy_local_path(string $sourcePath, string $destZip, array &$log): bool` — [linha 379](../../../../../gestor/bibliotecas/plugins-installer.php#L379)
- `plugin_extract_zip(string $zipFile, string $destDir, array &$log): bool` — [linha 419](../../../../../gestor/bibliotecas/plugins-installer.php#L419)
- `plugin_locate_manifest(string $staging, array &$log): ?string` — [linha 457](../../../../../gestor/bibliotecas/plugins-installer.php#L457)
- `plugin_move_to_final(string $staging, string $final, array &$log): bool` — [linha 490](../../../../../gestor/bibliotecas/plugins-installer.php#L490)
- `plugin_persist_metadata(string $slug, array $manifest, ?string $checksum, string $origemTipo, array $opcoes, array &$log): void` — [linha 555](../../../../../gestor/bibliotecas/plugins-installer.php#L555)
- `plugin_backup_existing(string $finalPath, array &$log): void` — [linha 610](../../../../../gestor/bibliotecas/plugins-installer.php#L610)
- `plugin_sync_datajson(string $staging, string $slug, array &$log): void` — [linha 638](../../../../../gestor/bibliotecas/plugins-installer.php#L638)
- `plugin_sync_datajson_multi(array $filesMap, string $slug, array &$log, string $finalBase): void` — [linha 705](../../../../../gestor/bibliotecas/plugins-installer.php#L705)
- `plugin_run_migrations(string $slug, string $finalPath, array &$log, array $opts = []): void` — [linha 748](../../../../../gestor/bibliotecas/plugins-installer.php#L748)
- `plugin_sync_resources_granular(array $resources, string $pluginId, ?string $baseDir, array &$log): void` — [linha 794](../../../../../gestor/bibliotecas/plugins-installer.php#L794)
- `plugin_enrich_resource_checksums(?string $baseDir, string $tipo, string $id, array &$item, array &$log): void` — [linha 848](../../../../../gestor/bibliotecas/plugins-installer.php#L848)
- `plugin_guess_resource_files(string $baseDir, string $tipo, string $id): ?array` — [linha 873](../../../../../gestor/bibliotecas/plugins-installer.php#L873)
- `plugin_sync_modules_resources(string $staging, string $pluginId, array &$log): void` — [linha 905](../../../../../gestor/bibliotecas/plugins-installer.php#L905)
- `plugin_db_fetch_one(string $sql): array|null` — [linha 995](../../../../../gestor/bibliotecas/plugins-installer.php#L995)
- `plugin_delegate_database_operations(string $pluginSlug, array $dataFiles, array &$log): bool` — [linha 1011](../../../../../gestor/bibliotecas/plugins-installer.php#L1011)
- `plugin_upsert_generic(string $tableName, string $pluginSlug, array $row, array &$tot, array &$log): void` — [linha 1080](../../../../../gestor/bibliotecas/plugins-installer.php#L1080)
- `plugin_upsert_layout(string $plugin, string $lang, string $id, array $src, array &$tot): void` — [linha 1103](../../../../../gestor/bibliotecas/plugins-installer.php#L1103)
- `plugin_upsert_page(string $plugin, string $lang, string $id, array $src, array &$tot): void` — [linha 1164](../../../../../gestor/bibliotecas/plugins-installer.php#L1164)
- `plugin_upsert_component(string $plugin, string $lang, string $id, array $src, array &$tot): void` — [linha 1232](../../../../../gestor/bibliotecas/plugins-installer.php#L1232)
- `plugin_upsert_variable(string $plugin, string $lang, string $id, array $src, array &$tot): void` — [linha 1295](../../../../../gestor/bibliotecas/plugins-installer.php#L1295)
- `plugin_upsert_module(string $plugin, string $id, array $src, array &$tot): void` — [linha 1346](../../../../../gestor/bibliotecas/plugins-installer.php#L1346)
- `plugin_checksum_changed(string $slug, ?string $newChecksum): bool` — [linha 1402](../../../../../gestor/bibliotecas/plugins-installer.php#L1402)
- `plugin_mark_status(string $slug, string $status): void` — [linha 1424](../../../../../gestor/bibliotecas/plugins-installer.php#L1424)
- `plugin_log_block(array $logLines, string $slug): void` — [linha 1440](../../../../../gestor/bibliotecas/plugins-installer.php#L1440)
- `plugin_cleanup_after_install(string $finalPath, array &$log): void` — [linha 1458](../../../../../gestor/bibliotecas/plugins-installer.php#L1458)
- `plugin_fix_temp_file_permissions(string $filePath, array &$log): void` — [linha 1479](../../../../../gestor/bibliotecas/plugins-installer.php#L1479)
- `plugin_process(array $params): int` — [linha 1539](../../../../../gestor/bibliotecas/plugins-installer.php#L1539)

<!-- c2f:extract:end -->
