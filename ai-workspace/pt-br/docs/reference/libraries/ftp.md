---
title: "Biblioteca ftp.php"
label: "FTP"
description: "Conexão FTP/FTPS guardada em $_GESTOR e envio ou download de um arquivo por vez. Sem chamadores no core."
section: reference
order: 340
sources:
  - gestor/bibliotecas/ftp.php
verified_at: 8768245a
---

# Biblioteca `ftp.php`

Camada fina sobre a extensão `ftp` do PHP, com **uma conexão por requisição** guardada em `$_GESTOR['ftp-conexao']`. Nenhum módulo do core a usa; o deploy de projetos é feito pelo CLI (SSH ou API), não por esta biblioteca.

```php
gestor_incluir_biblioteca('ftp');

if (ftp_conectar(['host' => 'ftp.exemplo.com', 'usuario' => $u, 'senha' => $s, 'secure' => true])) {
    ftp_colocar_arquivo(['local' => '/tmp/relatorio.csv', 'remoto' => 'relatorios/relatorio.csv']);
    ftp_pegar_arquivo(['remoto' => 'entrada/lista.txt', 'local' => '/tmp/lista.txt']);
    ftp_fechar_conexao();
} else {
    error_log($_GESTOR['ftp-erro']);
}
```

- `ftp_conectar()` usa `ftp_ssl_connect()` quando `secure === true` e a função existe; senão, FTP **sem criptografia**, em silêncio. Liga o modo passivo, a menos que `$_GESTOR['ftp-conexao-nao-passiva']` esteja definido. Em falha, grava a mensagem em `$_GESTOR['ftp-erro']`.
- `ftp_colocar_arquivo()` e `ftp_pegar_arquivo()` usam `FTP_BINARY` por padrão (`modoFTP` troca) e devolvem `true`/`false`; sem `local` ou `remoto`, devolvem `null`.
- Sem a extensão `ftp` instalada, a primeira chamada termina em erro fatal.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/ftp.php` por `c2f docs:extract` — 4 funções. Não edite dentro deste bloco.

- `ftp_conectar(array|false $params = false): bool` — [linha 41](../../../../../gestor/bibliotecas/ftp.php#L41)
  Estabelece conexão com servidor FTP.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['usuario']`: Usuário da conta FTP (obrigatório).
  - `$params['senha']`: Senha da conta FTP (obrigatório).
  - `$params['host']`: Host/servidor FTP (obrigatório).
  - `$params['secure']`: Se true, tenta conexão via FTP SSL (opcional).
  Retorno: True se conexão estabelecida com sucesso, false caso contrário.
- `ftp_fechar_conexao(array|false $params = false): bool|void` — [linha 90](../../../../../gestor/bibliotecas/ftp.php#L90)
  Fecha a conexão FTP ativa.
  Parâmetros:
  - `$params`: Parâmetros da função (não utilizado atualmente).
  Retorno: False se não houver conexão ativa, void após fechar.
- `ftp_colocar_arquivo(array|false $params = false): bool|void` — [linha 125](../../../../../gestor/bibliotecas/ftp.php#L125)
  Envia um arquivo para o servidor FTP.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['remoto']`: Caminho/nome do arquivo no servidor remoto (obrigatório).
  - `$params['local']`: Caminho/nome do arquivo local (obrigatório).
  - `$params['modoFTP']`: Modo de transferência: FTP_ASCII ou FTP_BINARY (opcional, padrão: FTP_BINARY).
  Retorno: True se upload bem-sucedido, false caso contrário.
- `ftp_pegar_arquivo(array|false $params = false): bool|void` — [linha 169](../../../../../gestor/bibliotecas/ftp.php#L169)
  Baixa um arquivo do servidor FTP.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['remoto']`: Caminho/nome do arquivo no servidor remoto (obrigatório).
  - `$params['local']`: Caminho/nome onde salvar o arquivo localmente (obrigatório).
  - `$params['modoFTP']`: Modo de transferência: FTP_ASCII ou FTP_BINARY (opcional, padrão: FTP_BINARY).
  Retorno: True se download bem-sucedido, false caso contrário.

<!-- c2f:extract:end -->
