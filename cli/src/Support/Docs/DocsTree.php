<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Support\Docs;

/**
 * Árvore de documentação do Core (req-177 §2.1): `ai-workspace/<idioma>/docs/`.
 *
 * Docs "novas" são as da taxonomia (`guides/`, `concepts/`, `reference/`, `whats-new/` e o
 * `index.md` da raiz); todo o resto é legado aguardando migração.
 */
final class DocsTree
{
    public const LANGS = ['pt-br', 'en'];
    public const SECTIONS = ['guides', 'concepts', 'reference', 'whats-new'];

    private string $rootPath;
    private string $source;

    /**
     * @param string $rootPath raiz do repositório (onde vivem `gestor/` e `.git`)
     * @param string|null $source diretório que contém `<idioma>/docs/` (padrão: `<root>/ai-workspace`)
     */
    public function __construct(string $rootPath, ?string $source = null)
    {
        $this->rootPath = rtrim(str_replace('\\', '/', $rootPath), '/');
        $this->source = rtrim(str_replace('\\', '/', $source ?? $this->rootPath . '/ai-workspace'), '/');
    }

    public function rootPath(): string
    {
        return $this->rootPath;
    }

    public function docsDir(string $lang): string
    {
        return $this->source . '/' . $lang . '/docs';
    }

    /** Caminho da doc relativo à raiz do repositório (para links de fonte). */
    public function repoRelative(string $lang, string $rel): string
    {
        $abs = $this->docsDir($lang) . '/' . $rel;

        return str_starts_with($abs, $this->rootPath . '/') ? substr($abs, strlen($this->rootPath) + 1) : $abs;
    }

    public static function isNew(string $rel): bool
    {
        if ($rel === 'index.md') {
            return true;
        }

        return in_array(explode('/', $rel)[0], self::SECTIONS, true);
    }

    /** Seção esperada para um caminho relativo (`home` para o índice raiz). */
    public static function expectedSection(string $rel): string
    {
        return $rel === 'index.md' ? 'home' : explode('/', $rel)[0];
    }

    /**
     * Todos os `.md` do idioma, relativos a `docs/`, em ordem estável.
     *
     * @return list<string>
     */
    public function files(string $lang): array
    {
        $dir = $this->docsDir($lang);
        if (!is_dir($dir)) {
            return [];
        }
        $out = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isFile() && strtolower($file->getExtension()) === 'md') {
                $out[] = substr(str_replace('\\', '/', $file->getPathname()), strlen($dir) + 1);
            }
        }
        sort($out, SORT_STRING);

        return $out;
    }

    /** @return list<string> */
    public function newDocs(string $lang): array
    {
        return array_values(array_filter($this->files($lang), [self::class, 'isNew']));
    }

    public function read(string $lang, string $rel): string
    {
        return (string)file_get_contents($this->docsDir($lang) . '/' . $rel);
    }

    public function exists(string $lang, string $rel): bool
    {
        return is_file($this->docsDir($lang) . '/' . $rel);
    }
}
