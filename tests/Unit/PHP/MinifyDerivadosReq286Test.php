<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-286 r2: o painel serve o `.min.js` fora do ambiente de desenvolvimento. Ninguém regenerava os derivados e o
 * pipeline da release não roda `assets:minify`: a produção ficou com o `admin-atualizacoes.min.js` de antes da
 * REQ-285 (sem decisão em lote, sem reabrir a aba de choques) e com um `admin-categorias.min.js` chamando o Fomantic
 * que a req-223 tirou da tela. Este teste roda no `composer test`, que o CI da release executa antes de empacotar.
 *
 * Mesma descoberta e mesmo hash do `cli/src/Commands/AssetsMinifyCommand.php` (sha1 sem diferença de CRLF).
 * Para corrigir: `php cli/c2f.php assets:minify`.
 */
final class MinifyDerivadosReq286Test extends TestCase
{
    private const IGNORAR = ['/vendor/', '/datatables/', '/jQuery-Mask-Plugin', '/jQuery-File-Upload', '/node_modules/'];

    public function testDerivadosEmDiaComAsFontes(): void
    {
        $raiz = str_replace('\\', '/', CONN2FLOW_ROOT);
        $manifesto = json_decode((string)file_get_contents($raiz . '/gestor/assets/minify-manifest.json'), true) ?: [];
        $desatualizados = [];
        foreach (['gestor/assets', 'gestor/modulos'] as $base) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz . '/' . $base, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                $caminho = str_replace('\\', '/', $f->getPathname());
                if (!$f->isFile() || strtolower($f->getExtension()) !== 'js' || substr($caminho, -7) === '.min.js') continue;
                foreach (self::IGNORAR as $t) if (strpos($caminho, $t) !== false) continue 2;
                $rel = ltrim(substr($caminho, strlen($raiz)), '/');
                $hash = sha1(str_replace("\r\n", "\n", (string)file_get_contents($caminho)));
                $min = preg_replace('/\.js$/', '.min.js', $caminho);
                if (!is_file($min) || ($manifesto[$rel]['sha1'] ?? null) !== $hash) $desatualizados[] = $rel;
            }
        }
        $this->assertSame([], $desatualizados, 'Derivados .min.js desatualizados: rode `php cli/c2f.php assets:minify` e commite os .min.js e o minify-manifest.json.');
    }

    public function testFonteVaziaTemDerivadoVazio(): void
    {
        $this->assertSame('', trim((string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/admin-categorias/admin-categorias.js')));
        $this->assertSame('', (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/admin-categorias/admin-categorias.min.js'), 'Sem o código Fomantic removido na req-223.');
        $cmd = (string)file_get_contents(CONN2FLOW_ROOT . '/cli/src/Commands/AssetsMinifyCommand.php');
        $this->assertStringContainsString('$hash = sha1(str_replace("\r\n", "\n", $conteudo));', $cmd);
    }
}
