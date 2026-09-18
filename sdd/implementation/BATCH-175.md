# BATCH-175: `ForcarAtualizacaoTest` idempotente

Execução da [req-170](../human-requests/req-170.md).

---

## Atividades e Checklist

### 1. [x] Recarga explícita do contrato
* `schemaMetadata(bool $recarregar = false)` limpa o `static` quando chamada com `true`.
* Sem argumento, o comportamento é idêntico ao anterior — produção e pipeline não mudam.

### 2. [x] Cada teste com o próprio contrato
* Helper `escreverContrato(array $forcar = [])` grava o `schema-metadata.json` do cenário e recarrega.
* O teste de bypass passa suas entradas forçadas; o teste de deploy de projeto passa nenhuma.

### 3. [x] Validação
* Três execuções seguidas do arquivo isolado e duas da suíte completa, sem apagar o cache.

---

## Execução (2026-09-18)

A causa era mais específica do que "cache estático atrapalha o teste": **só o primeiro teste escrevia
o `schema-metadata.json`**, e o segundo dependia do cache que o primeiro tinha deixado preenchido.
Enquanto o PHPUnit executava na ordem de declaração, isso passava despercebido. Na execução seguinte
a uma falha, o `.phpunit.result.cache` coloca os testes que falharam à frente — o segundo teste passa
a rodar primeiro, encontra o diretório temporário ainda sem contrato, lê metadados vazios e quebra,
levando o primeiro junto.

O acoplamento foi removido na origem: cada teste grava o contrato de que precisa e força a releitura.
A recarga explícita em `schemaMetadata()` é o mínimo necessário para que isso seja possível sem
transformar o cache em leitura de disco a cada chamada.

### Validação

- `php -l` limpo em `atualizacoes-banco-de-dados.php` e no arquivo de teste.
- `ForcarAtualizacaoTest` isolado: três execuções consecutivas, `OK (3 tests, 19 assertions)` nas três.
- Suíte completa executada **duas vezes seguidas**, sem apagar `.phpunit.result.cache`: 1181 testes,
  7828 asserções, 0 falhas nas duas.

### Observação

A validação da req-169 havia sido dada como verde depois de apagar `.phpunit.result.cache` à mão. O
resultado era real, mas o procedimento escondia o problema. Com esta correção, a suíte pode ser
executada repetidamente sem esse cuidado.
