# Parsedown (embutido no CLI)

- **Origem:** https://github.com/erusev/parsedown, tag `1.7.4`, arquivo `Parsedown.php` (licença MIT em `LICENSE.txt`).
- **Uso:** somente pelo `c2f docs:build` (`cli/src/Support/Docs/MarkdownRenderer.php`), em tempo de build. Não é distribuído nas instalações do Gestor e não usa Composer.
- **Patch local (req-178):** `blockSetextHeader()` e `blockTable()` receberam `?array $Block = null` no lugar de `array $Block = null`, para o PHP 8.4+ não emitir *deprecation* de parâmetro implicitamente anulável. Nenhuma outra linha foi alterada.
- **Para atualizar:** baixe a nova tag, reaplique o patch se ainda for necessário e rode `vendor/bin/phpunit --filter DocsBuildReq178Test`.
