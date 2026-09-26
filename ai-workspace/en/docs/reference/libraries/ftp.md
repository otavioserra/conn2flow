---
title: "ftp.php library"
description: "An FTP/FTPS connection kept in $_GESTOR and uploading or downloading one file at a time. No callers in the core."
section: reference
order: 340
sources:
  - gestor/bibliotecas/ftp.php
verified_at: 8768245a
---

# `ftp.php` library

A thin layer over PHP's `ftp` extension, with **one connection per request** kept in `$_GESTOR['ftp-conexao']`. No core module uses it; project deploys are done by the CLI (SSH or API), not by this library.

```php
gestor_incluir_biblioteca('ftp');

if (ftp_conectar(['host' => 'ftp.example.com', 'usuario' => $u, 'senha' => $s, 'secure' => true])) {
    ftp_colocar_arquivo(['local' => '/tmp/report.csv', 'remoto' => 'reports/report.csv']);
    ftp_pegar_arquivo(['remoto' => 'inbox/list.txt', 'local' => '/tmp/list.txt']);
    ftp_fechar_conexao();
} else {
    error_log($_GESTOR['ftp-erro']);
}
```

- `ftp_conectar()` uses `ftp_ssl_connect()` when `secure === true` and the function exists; otherwise, **unencrypted** FTP, silently. It turns on passive mode unless `$_GESTOR['ftp-conexao-nao-passiva']` is set. On failure, it stores the message in `$_GESTOR['ftp-erro']`.
- `ftp_colocar_arquivo()` and `ftp_pegar_arquivo()` use `FTP_BINARY` by default (`modoFTP` changes it) and return `true`/`false`; without `local` or `remoto`, they return `null`.
- Without the `ftp` extension installed, the first call ends in a fatal error.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/ftp.php` by `c2f docs:extract` — 4 functions. Do not edit inside this block.

- `ftp_conectar(array|false $params = false): bool` — [line 41](../../../../../gestor/bibliotecas/ftp.php#L41)
- `ftp_fechar_conexao(array|false $params = false): bool|void` — [line 90](../../../../../gestor/bibliotecas/ftp.php#L90)
- `ftp_colocar_arquivo(array|false $params = false): bool|void` — [line 125](../../../../../gestor/bibliotecas/ftp.php#L125)
- `ftp_pegar_arquivo(array|false $params = false): bool|void` — [line 169](../../../../../gestor/bibliotecas/ftp.php#L169)

<!-- c2f:extract:end -->
