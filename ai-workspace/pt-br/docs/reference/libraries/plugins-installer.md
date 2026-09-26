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
  Normaliza um slug de plugin para formato seguro.
  Parâmetros:
  - `$slug`: Slug original do plugin.
  Retorno: Slug normalizado (ex: "My-Plugin_123" → "my-plugin_123").
- `plugin_base_root(): string` — [linha 37](../../../../../gestor/bibliotecas/plugins-installer.php#L37)
  Retorna o caminho raiz do diretório do gestor.
  Retorno: Caminho absoluto para o diretório raiz.
- `plugin_staging_path(string $slug): string` — [linha 45](../../../../../gestor/bibliotecas/plugins-installer.php#L45)
  Retorna o caminho de staging temporário para um plugin.
  Parâmetros:
  - `$slug`: Slug do plugin.
  Retorno: Caminho completo para temp/plugins/{slug}.
- `plugin_final_path(string $slug): string` — [linha 53](../../../../../gestor/bibliotecas/plugins-installer.php#L53)
  Retorna o caminho final de instalação de um plugin.
  Parâmetros:
  - `$slug`: Slug do plugin.
  Retorno: Caminho completo para plugins/{slug}.
- `plugin_datajson_dest_dir(string $slug): string` — [linha 61](../../../../../gestor/bibliotecas/plugins-installer.php#L61)
  Retorna o diretório de destino para arquivos *Data.json do plugin.
  Parâmetros:
  - `$slug`: Slug do plugin.
  Retorno: Caminho completo para db/data/plugins/{slug}.
- `plugin_safe_mkdir(string $path): void` — [linha 69](../../../../../gestor/bibliotecas/plugins-installer.php#L69)
  Cria um diretório recursivamente se não existir.
  Parâmetros:
  - `$path`: Caminho do diretório a criar.
- `plugin_remove_dir(string $dir): void` — [linha 77](../../../../../gestor/bibliotecas/plugins-installer.php#L77)
  Remove um diretório recursivamente incluindo todo seu conteúdo.
  Parâmetros:
  - `$dir`: Caminho do diretório a remover.
- `plugin_compute_checksum(string $file): ?string` — [linha 85](../../../../../gestor/bibliotecas/plugins-installer.php#L85)
  Calcula o checksum SHA-256 de um arquivo.
  Parâmetros:
  - `$file`: Caminho do arquivo.
  Retorno: Hash SHA-256 ou null se o arquivo não existir.
- `plugin_is_cli_context(): bool` — [linha 95](../../../../../gestor/bibliotecas/plugins-installer.php#L95)
  Detecta se o script está sendo executado via CLI ou web.
  Retorno: True se CLI, false se web.
- `tabelaFromDataFile(string $file): string` — [linha 110](../../../../../gestor/bibliotecas/plugins-installer.php#L110)
  Converte nome de arquivo *Data.json para nome de tabela snake_case.
  Parâmetros:
  - `$file`: Nome do arquivo *Data.json.
  Retorno: Nome da tabela em snake_case.
- `plugin_read_json(string $path, array &$errors): ?array` — [linha 134](../../../../../gestor/bibliotecas/plugins-installer.php#L134)
  Lê e decodifica um arquivo JSON.
  Parâmetros:
  - `$path`: Caminho do arquivo JSON.
  - `$errors`: Array de erros (passado por referência).
  Retorno: Dados decodificados ou null em caso de erro.
- `plugin_validate_manifest(array $manifest, array &$errors): bool` — [linha 154](../../../../../gestor/bibliotecas/plugins-installer.php#L154)
  Valida campos obrigatórios e formato do manifest.json do plugin.
  Parâmetros:
  - `$manifest`: Dados do manifest.json decodificados.
  - `$errors`: Array de erros (passado por referência).
  Retorno: True se válido, false caso contrário.
- `plugin_credentials_lookup(?string $credRef): ?string` — [linha 173](../../../../../gestor/bibliotecas/plugins-installer.php#L173)
  Busca credenciais de token para acesso a repositórios privados.
  Parâmetros:
  - `$credRef`: Referência ou token direto.
  Retorno: Token encontrado ou null.
- `plugin_http_download(string $url, string $dest, array $headers, array &$log): bool` — [linha 206](../../../../../gestor/bibliotecas/plugins-installer.php#L206)
  Realiza download HTTP de arquivo com suporte a cURL e fallback para streams.
  Parâmetros:
  - `$url`: URL para download.
  - `$dest`: Caminho do arquivo de destino.
  - `$headers`: Headers HTTP a enviar (formato: ['Header-Name' => 'value']).
  - `$log`: Array de log (passado por referência).
  Retorno: True se sucesso, false em caso de erro.
- `plugin_github_zip_url(string $owner, string $repo, string $ref): string` — [linha 276](../../../../../gestor/bibliotecas/plugins-installer.php#L276)
  Gera URL para download do zipball de um repositório GitHub.
  Parâmetros:
  - `$owner`: Proprietário do repositório (usuário ou organização).
  - `$repo`: Nome do repositório.
  - `$ref`: Referência (branch, tag ou commit SHA).
  Retorno: URL completa para download do zipball.
- `plugin_download_github_public(string $owner, string $repo, string $ref, string $destZip, array &$log): bool` — [linha 296](../../../../../gestor/bibliotecas/plugins-installer.php#L296)
  Baixa plugin de repositório público do GitHub.
  Parâmetros:
  - `$owner`: Proprietário do repositório.
  - `$repo`: Nome do repositório.
  - `$ref`: Referência (branch/tag/commit).
  - `$destZip`: Caminho do arquivo ZIP de destino.
  - `$log`: Array de log (passado por referência).
  Retorno: True se sucesso, false em caso de erro.
- `plugin_download_github_private(string $owner, string $repo, string $ref, string $destZip, string $token, array &$log): bool` — [linha 355](../../../../../gestor/bibliotecas/plugins-installer.php#L355)
  Baixa plugin de repositório privado do GitHub usando token de autenticação.
  Parâmetros:
  - `$owner`: Proprietário do repositório.
  - `$repo`: Nome do repositório.
  - `$ref`: Referência (branch/tag/commit).
  - `$destZip`: Caminho do arquivo ZIP de destino.
  - `$token`: Token de autenticação do GitHub.
  - `$log`: Array de log (passado por referência).
  Retorno: True se sucesso, false em caso de erro.
- `plugin_copy_local_path(string $sourcePath, string $destZip, array &$log): bool` — [linha 379](../../../../../gestor/bibliotecas/plugins-installer.php#L379)
  Copia plugin de caminho local (diretório ou arquivo ZIP).
  Parâmetros:
  - `$sourcePath`: Caminho local do plugin (diretório ou arquivo ZIP).
  - `$destZip`: Caminho do arquivo ZIP de destino.
  - `$log`: Array de log (passado por referência).
  Retorno: True se sucesso, false em caso de erro.
- `plugin_extract_zip(string $zipFile, string $destDir, array &$log): bool` — [linha 419](../../../../../gestor/bibliotecas/plugins-installer.php#L419)
  Extrai arquivo ZIP para diretório de destino.
  Parâmetros:
  - `$zipFile`: Caminho do arquivo ZIP.
  - `$destDir`: Diretório de destino para extração.
  - `$log`: Array de log (passado por referência).
  Retorno: True se sucesso, false em caso de erro.
- `plugin_locate_manifest(string $staging, array &$log): ?string` — [linha 457](../../../../../gestor/bibliotecas/plugins-installer.php#L457)
  Localiza o arquivo manifest.json dentro do diretório de staging.
  Parâmetros:
  - `$staging`: Diretório de staging extraído.
  - `$log`: Array de log (passado por referência).
  Retorno: Caminho completo do manifest.json ou null se não encontrado.
- `plugin_move_to_final(string $staging, string $final, array &$log): bool` — [linha 490](../../../../../gestor/bibliotecas/plugins-installer.php#L490)
  Move diretório do plugin de staging para localização final.
  Parâmetros:
  - `$staging`: Diretório de staging.
  - `$final`: Diretório final de instalação.
  - `$log`: Array de log (passado por referência).
  Retorno: True se sucesso, false em caso de erro.
- `plugin_persist_metadata(string $slug, array $manifest, ?string $checksum, string $origemTipo, array $opcoes, array &$log): void` — [linha 555](../../../../../gestor/bibliotecas/plugins-installer.php#L555)
  Persiste metadados do plugin na tabela plugins do banco de dados.
  Parâmetros:
  - `$slug`: ID único do plugin.
  - `$manifest`: Dados do manifest.json.
  - `$checksum`: Hash SHA-256 do pacote.
  - `$origemTipo`: Tipo de origem (github_public, github_private, local).
  - `$opcoes`: Opções de instalação (referencia, ref, cred_ref).
  - `$log`: Array de log (passado por referência).
- `plugin_backup_existing(string $finalPath, array &$log): void` — [linha 610](../../../../../gestor/bibliotecas/plugins-installer.php#L610)
  Cria backup em ZIP da instalação existente do plugin.
  Parâmetros:
  - `$finalPath`: Caminho do plugin instalado.
  - `$log`: Array de log (passado por referência).
- `plugin_sync_datajson(string $staging, string $slug, array &$log): void` — [linha 638](../../../../../gestor/bibliotecas/plugins-installer.php#L638)
  Sincroniza arquivos Data.json do plugin com o banco de dados.
  Parâmetros:
  - `$staging`: Diretório de staging do plugin
  - `$slug`: Identificador único do plugin
  - `$log`: Referência ao array de log (passado por referência)
- `plugin_sync_datajson_multi(array $filesMap, string $slug, array &$log, string $finalBase): void` — [linha 705](../../../../../gestor/bibliotecas/plugins-installer.php#L705)
  Sincroniza dados a partir de arquivos *Data.json detectados dinamicamente.
  Parâmetros:
  - `$filesMap`: Mapeamento de nome da tabela para caminho do arquivo Data.json
  - `$slug`: Identificador único do plugin
  - `$log`: Referência ao array de log (passado por referência)
  - `$finalBase`: Caminho base do plugin instalado
- `plugin_run_migrations(string $slug, string $finalPath, array &$log, array $opts = []): void` — [linha 748](../../../../../gestor/bibliotecas/plugins-installer.php#L748)
  Executa migrações de banco de dados específicas do plugin usando Phinx.
  Parâmetros:
  - `$slug`: Identificador único do plugin
  - `$finalPath`: Caminho final do plugin instalado
  - `$log`: Referência ao array de log (passado por referência)
  - `$opts`: Opções de execução:
- `plugin_sync_resources_granular(array $resources, string $pluginId, ?string $baseDir, array &$log): void` — [linha 794](../../../../../gestor/bibliotecas/plugins-installer.php#L794)
  Sincroniza recursos (layouts, pages, components, variables) de forma granular.
  Parâmetros:
  - `$resources`: Array de recursos organizados por idioma
  - `$pluginId`: Identificador único do plugin
  - `$baseDir`: Diretório base para buscar arquivos HTML/CSS (opcional)
  - `$log`: Referência ao array de log (passado por referência)
- `plugin_enrich_resource_checksums(?string $baseDir, string $tipo, string $id, array &$item, array &$log): void` — [linha 848](../../../../../gestor/bibliotecas/plugins-installer.php#L848)
  Enriquece recursos com checksums calculados a partir de arquivos HTML e CSS.
  Parâmetros:
  - `$baseDir`: Diretório base para buscar arquivos
  - `$tipo`: Tipo de recurso: 'layouts', 'pages', ou 'components'
  - `$id`: Identificador do recurso
  - `$item`: Referência ao array do item (modificado com checksums)
  - `$log`: Referência ao array de log (passado por referência)
- `plugin_guess_resource_files(string $baseDir, string $tipo, string $id): ?array` — [linha 873](../../../../../gestor/bibliotecas/plugins-installer.php#L873)
  Tenta localizar automaticamente arquivos HTML e CSS de um recurso.
  Parâmetros:
  - `$baseDir`: Diretório base do plugin
  - `$tipo`: Tipo de recurso: 'layouts', 'pages', ou 'components'
  - `$id`: Identificador do recurso
  Retorno: Array com chaves 'html' e/ou 'css', ou null se não encontrado
- `plugin_sync_modules_resources(string $staging, string $pluginId, array &$log): void` — [linha 905](../../../../../gestor/bibliotecas/plugins-installer.php#L905)
  Sincroniza recursos de módulos a partir de arquivos module-id.json.
  Parâmetros:
  - `$staging`: Diretório de staging do plugin
  - `$pluginId`: Identificador único do plugin
  - `$log`: Referência ao array de log (passado por referência)
- `plugin_db_fetch_one(string $sql): array|null` — [linha 995](../../../../../gestor/bibliotecas/plugins-installer.php#L995)
  Busca e retorna um único registro do banco de dados.
  Parâmetros:
  - `$sql`: Consulta SQL completa
  Retorno: Array associativo com os dados ou null se não encontrado
- `plugin_delegate_database_operations(string $pluginSlug, array $dataFiles, array &$log): bool` — [linha 1011](../../../../../gestor/bibliotecas/plugins-installer.php#L1011)
  Delega operações de banco de dados para o sistema robusto de atualizações.
  Parâmetros:
  - `$pluginSlug`: Slug normalizado do plugin
  - `$dataFiles`: Lista de arquivos Data.json para processar (vazio = todos)
  - `$log`: Array de log passado por referência para registrar mensagens
  Retorno: True se a delegação foi bem-sucedida, false em caso de erro
- `plugin_upsert_generic(string $tableName, string $pluginSlug, array $row, array &$tot, array &$log): void` — [linha 1080](../../../../../gestor/bibliotecas/plugins-installer.php#L1080)
  Função genérica de upsert que delega para o sistema robusto de banco de dados.
  Parâmetros:
  - `$tableName`: Nome da tabela
  - `$pluginSlug`: Identificador único do plugin
  - `$row`: Dados da linha a ser inserida/atualizada
  - `$tot`: Referência ao array de totalizadores (passado por referência)
  - `$log`: Referência ao array de log (passado por referência)
- `plugin_upsert_layout(string $plugin, string $lang, string $id, array $src, array &$tot): void` — [linha 1103](../../../../../gestor/bibliotecas/plugins-installer.php#L1103)
  Insere ou atualiza um layout no banco de dados.
  Parâmetros:
  - `$plugin`: Identificador do plugin
  - `$lang`: Código do idioma (ex: 'pt-br')
  - `$id`: Identificador único do layout
  - `$src`: Dados source com chaves: name/nome, checksum, version
  - `$tot`: Referência ao array de totalizadores (passado por referência)
- `plugin_upsert_page(string $plugin, string $lang, string $id, array $src, array &$tot): void` — [linha 1164](../../../../../gestor/bibliotecas/plugins-installer.php#L1164)
  Insere ou atualiza uma página no banco de dados.
  Parâmetros:
  - `$plugin`: Identificador do plugin
  - `$lang`: Código do idioma (ex: 'pt-br')
  - `$id`: Identificador único da página
  - `$src`: Dados source com chaves: name/nome, path/caminho, module/modulo, checksum, version
  - `$tot`: Referência ao array de totalizadores (passado por referência)
- `plugin_upsert_component(string $plugin, string $lang, string $id, array $src, array &$tot): void` — [linha 1232](../../../../../gestor/bibliotecas/plugins-installer.php#L1232)
  Insere ou atualiza um componente no banco de dados.
  Parâmetros:
  - `$plugin`: Identificador do plugin
  - `$lang`: Código do idioma (ex: 'pt-br')
  - `$id`: Identificador único do componente
  - `$src`: Dados source com chaves: name/nome, module/modulo, checksum, version
  - `$tot`: Referência ao array de totalizadores (passado por referência)
- `plugin_upsert_variable(string $plugin, string $lang, string $id, array $src, array &$tot): void` — [linha 1295](../../../../../gestor/bibliotecas/plugins-installer.php#L1295)
  Insere ou atualiza uma variável no banco de dados.
  Parâmetros:
  - `$plugin`: Identificador do plugin
  - `$lang`: Código do idioma (ex: 'pt-br')
  - `$id`: Identificador único da variável
  - `$src`: Dados source com chaves: value/valor, type/tipo, group/grupo, module/modulo
  - `$tot`: Referência ao array de totalizadores (passado por referência)
- `plugin_upsert_module(string $plugin, string $id, array $src, array &$tot): void` — [linha 1346](../../../../../gestor/bibliotecas/plugins-installer.php#L1346)
  Insere ou atualiza um módulo no banco de dados.
  Parâmetros:
  - `$plugin`: Identificador do plugin
  - `$id`: Identificador único do módulo
  - `$src`: Dados source com chaves: name/nome, modulo_grupo_id, titulo, icone, icone2,
  - `$tot`: Referência ao array de totalizadores (passado por referência)
- `plugin_checksum_changed(string $slug, ?string $newChecksum): bool` — [linha 1402](../../../../../gestor/bibliotecas/plugins-installer.php#L1402)
  Verifica se o checksum do pacote mudou desde a última instalação.
  Parâmetros:
  - `$slug`: Identificador único do plugin
  - `$newChecksum`: Novo checksum calculado (SHA-256)
  Retorno: True se mudou ou se não há checksum anterior, false se inalterado
- `plugin_mark_status(string $slug, string $status): void` — [linha 1424](../../../../../gestor/bibliotecas/plugins-installer.php#L1424)
  Marca o status de execução do plugin no banco de dados.
  Parâmetros:
  - `$slug`: Identificador único do plugin
  - `$status`: Status a ser registrado (ex: 'instalando', 'erro', 'concluído')
- `plugin_log_block(array $logLines, string $slug): void` — [linha 1440](../../../../../gestor/bibliotecas/plugins-installer.php#L1440)
  Registra mensagens de log em arquivo com timestamp.
  Parâmetros:
  - `$logLines`: Array de mensagens de log
  - `$slug`: Identificador único do plugin
- `plugin_cleanup_after_install(string $finalPath, array &$log): void` — [linha 1458](../../../../../gestor/bibliotecas/plugins-installer.php#L1458)
  Limpa pasta DB e corrige permissões após instalação do plugin.
  Parâmetros:
  - `$finalPath`: Caminho do plugin instalado
  - `$log`: Referência ao array de log (passado por referência)
- `plugin_fix_temp_file_permissions(string $filePath, array &$log): void` — [linha 1479](../../../../../gestor/bibliotecas/plugins-installer.php#L1479)
  Corrige permissões de arquivo temporário via chown.
  Parâmetros:
  - `$filePath`: Caminho completo do arquivo
  - `$log`: Referência ao array de log (passado por referência)
- `plugin_process(array $params): int` — [linha 1539](../../../../../gestor/bibliotecas/plugins-installer.php#L1539)
  Função principal do instalador de plugins - orquestra todo o processo.
  Parâmetros:
  - `$params`: Parâmetros da instalação:
  Retorno: Código de saída:

<!-- c2f:extract:end -->
