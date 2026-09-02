# BL-003 — Contenção de path traversal no servidor de arquivos estáticos

- **Tipo**: Security
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: MÉDIA/ALTA (depende do rewrite do servidor)
- **Origem**: análise de segurança 2026-07-31 (código real)
- **Componentes**: `controladores/arquivo-estatico/arquivo-estatico.php`, `gestor.php` (roteador)

## Contexto observado

O caminho físico do arquivo servido é montado por concatenação direta do caminho da URL:

```php
$file = $_GESTOR['assets-path'].$_GESTOR['caminho-total'];   // e também contents-path
if(file_exists($file)) arquivo_estatico_enviar($file, $ext);
```
([arquivo-estatico.php:206,212,221](../../../gestor/controladores/arquivo-estatico/arquivo-estatico.php))

`caminho-total` vem de `$_REQUEST['_gestor-caminho']` e é apenas `strtolower`+`explode('/')`, **sem rejeição de `..`** nem verificação de que o caminho resolvido permanece dentro de `assets/` ou `contents/` ([gestor.php:2482-2504](../../../gestor/gestor.php)). A seleção de "arquivo estático" depende só de existir uma extensão (`pathinfo`).

O `.htaccess`/rewrite provavelmente restringe a entrada na prática, mas o código de aplicação **não** valida contenção — defesa em profundidade ausente. Se o rewrite mudar, for contornado, ou `_gestor-caminho` puder ser injetado por query string, um caminho com `../` de extensão conhecida poderia escapar da raiz e ler arquivos como `.env`/chaves.

## Proposta de melhoria (a validar)

1. Rejeitar qualquer `caminho-total` contendo `..`, bytes nulos ou barras invertidas antes de qualquer uso.
2. Após montar `$file`, aplicar `realpath()` e confirmar `str_starts_with($real, realpath(base))` para cada base permitida (`assets`, `contents`, `modulos`); servir só se contido.
3. Testes unitários com payloads de traversal (`../`, encoding, unicode) garantindo 404 seguro.

## Critérios de aceite (rascunho)

- Requisições com traversal retornam 404 sem tocar arquivos fora das bases.
- Contenção validada por `realpath` para todas as bases servidas.

> Item de backlog — não autorizado para implementação até promoção para `sdd/human-requests/`.
