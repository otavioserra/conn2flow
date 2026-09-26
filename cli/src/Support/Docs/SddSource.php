<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Support\Docs;

/** Fonte pública e conservadora do SDD do Core. Nunca lê SDD de projetos. */
final class SddSource
{
    private const FOLDERS = ['process', 'decisions', 'implementation', 'human-requests', 'validation'];
    /** @var list<string> */
    private array $privateNames = ['transformamp', 'snapphoton', 'lumix', 'linkedin', 'conn2flow-nexus', 'conn2flow-app'];

    /** @return array{docs: array<string, array{meta: array<string, mixed>, body: string}>, warnings: list<string>} */
    public function collect(string $coreRoot): array
    {
        $root = rtrim(str_replace('\\', '/', $coreRoot), '/') . '/sdd';
        $docs = [];
        $warnings = [];
        if (!is_dir($root)) {
            return ['docs' => [], 'warnings' => ['sdd/: fonte não encontrada.']];
        }
        $environmentFile = dirname($root) . '/dev-environment/data/environment.json';
        $environment = is_file($environmentFile) ? json_decode((string)file_get_contents($environmentFile), true) : null;
        foreach (array_keys((array)($environment['devProjects'] ?? [])) as $projectId) {
            if ($projectId !== 'conn2flow-site') {
                $this->privateNames[] = (string)$projectId;
            }
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'md') {
                continue;
            }
            $relative = substr(str_replace('\\', '/', $file->getPathname()), strlen($root) + 1);
            $parts = explode('/', $relative);
            if (count($parts) > 1 && !in_array($parts[0], self::FOLDERS, true)) {
                continue;
            }
            if (count($parts) > 2 || preg_match('~(?:^|/)(?:archive|backlog|MEMORIA[^/]*)/|(?:^|/)MEMORIA[^/]*\.md$~i', $relative)) {
                continue;
            }
            $body = (string)file_get_contents($file->getPathname());
            $filtered = $this->filter($body);
            if ($filtered === null) {
                $warnings[] = "sdd/{$relative}: excluído pelo filtro de conteúdo sensível.";
                continue;
            }
            if ($filtered !== $body) {
                $warnings[] = "sdd/{$relative}: dados sensíveis substituídos.";
            }
            preg_match('/^#\s+(.+)$/m', $filtered, $heading);
            $title = trim($heading[1] ?? pathinfo($relative, PATHINFO_FILENAME));
            $paragraphs = preg_split('/\R\s*\R/', preg_replace('/^#\s+.+\R?/', '', $filtered, 1) ?? $filtered);
            $description = '';
            foreach ($paragraphs as $paragraph) {
                $candidate = trim(preg_replace('/\s+/', ' ', $paragraph) ?? $paragraph);
                if ($candidate !== '' && !preg_match('/^(?:#|\||-|\*|>|```|<!--)/', $candidate)) {
                    $description = mb_substr($candidate, 0, 180);
                    break;
                }
            }
            preg_match('/(?:^|[-_])(\d{1,4})(?:[-_.]|$)/', basename($relative), $number);
            $rel = 'sdd/' . $relative;
            $docs[$rel] = ['meta' => [
                'title' => $title,
                'description' => $description,
                'section' => 'sdd',
                'order' => isset($number[1]) ? (int)$number[1] : 100,
            ], 'body' => $filtered];
        }
        ksort($docs, SORT_STRING);
        return ['docs' => $docs, 'warnings' => $warnings];
    }

    /** Retorna null quando não é seguro distinguir o segredo do texto público. */
    public function filter(string $body): ?string
    {
        if (preg_match('/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----|\b(?:password|senha|secret|api[_-]?key|access[_-]?token)\s*[:=]\s*[\x27\"]?[^\s\x27\"`]{8,}|\bBearer\s+[A-Za-z0-9._~+\/-]{12,}|\b(?:gh[pousr]_[A-Za-z0-9]{20,}|sk-[A-Za-z0-9_-]{20,}|AKIA[A-Z0-9]{16})\b|\beyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\b/i', $body)) {
            return null;
        }
        $patterns = [
            '~\b[A-Z]:[/\\\\]Users[/\\\\][^\s)`\]>]+~i',
            '~file:///?[A-Z]:[/\\\\]Users[/\\\\][^\s)`\]>]+~i',
            '~/(?:home|root|var/www|opt)/[^\s)`\]>]+~i',
            '~\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b~i',
            '~\b(?:\d{1,3}\.){3}\d{1,3}\b~',
            '~\b(?:ssh\s+(?:-\S+\s+)*|Host\s+)[a-z][a-z0-9._-]+\b~i',
            '~\b(?:token|client_secret|authorization)\s*[:=]\s*[A-Za-z0-9._/-]{8,}\b~i',
            '~\b(?:password|senha|secret|api[_-]?key|access[_-]?token|host|hostname|server)\s*[:=]\s*[\x27\"]?[^\s\x27\"`]{1,}~i',
            '~\b(?:https?://)?(?:[a-z0-9-]+\.)*(?:lab|staging|vps|hestia)[a-z0-9.-]*\.[a-z]{2,}(?:/[^\s)`]*)?~i',
        ];
        foreach ($patterns as $pattern) {
            $body = preg_replace($pattern, '[REDACTED]', $body);
            if ($body === null) {
                return null;
            }
        }
        foreach (array_unique($this->privateNames) as $name) {
            $body = preg_replace('~\b' . preg_quote($name, '~') . '\b~i', '[REDACTED]', $body);
            if ($body === null) {
                return null;
            }
        }
        $body = preg_replace_callback(
            '~(?:(?:https?://)?[a-z0-9][a-z0-9.-]*\.(?:com|net|org|dev|io|cloud|br|local))(?:/[^\s)`\]>]*)?~i',
            static function (array $match): string {
                $host = strtolower((string)(parse_url(str_starts_with($match[0], 'http') ? $match[0] : 'https://' . $match[0], PHP_URL_HOST) ?: ''));
                foreach (['github.com', 'conn2flow.com', 'php.net', 'w3.org', 'getcomposer.org', 'nodejs.org', 'packagist.org'] as $public) {
                    if ($host === $public || str_ends_with($host, '.' . $public)) {
                        return $match[0];
                    }
                }
                return '[REDACTED]';
            },
            $body
        );
        if ($body === null) {
            return null;
        }
        $body = preg_replace('~/(?:home|root|var/www|opt)/[^\s)`\]>]+~i', '[REDACTED]', $body);
        return $body;
    }
}
