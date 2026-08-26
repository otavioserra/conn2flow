<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Support;

use RuntimeException;

final class ProjectEnvironmentResolver
{
    private string $rootPath;

    /** @var array<string, mixed>|null */
    private ?array $environment = null;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, '/\\');
    }

    public function getEnvironmentJsonPath(): string
    {
        return $this->rootPath
            . DIRECTORY_SEPARATOR . 'dev-environment'
            . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR . 'environment.json';
    }

    /**
     * @return array{
     *   id: string,
     *   gestorPath: string,
     *   dockerPath: ?string,
     *   envFile: string,
     *   host: string,
     *   accessUrl: string,
     *   config: array<string, mixed>
     * }
     */
    public function resolve(string $projectId): array
    {
        $environment = $this->loadEnvironment();
        $projects = $environment['devProjects'] ?? null;

        if (!is_array($projects) || !isset($projects[$projectId]) || !is_array($projects[$projectId])) {
            throw new RuntimeException("Project '{$projectId}' not found in environment.json (devProjects).");
        }

        /** @var array<string, mixed> $project */
        $project = $projects[$projectId];
        $configuredPath = $this->firstConfiguredString($project, ['path_tests', 'target', 'path']);
        if ($configuredPath === null) {
            throw new RuntimeException(
                "Project '{$projectId}' has no path_tests, target, or path configured in environment.json."
            );
        }

        $gestorPath = $this->normalizeHostPath($configuredPath);
        if (!is_file($gestorPath . DIRECTORY_SEPARATOR . 'config.php')) {
            $nestedGestorPath = $gestorPath . DIRECTORY_SEPARATOR . 'gestor';
            if (is_file($nestedGestorPath . DIRECTORY_SEPARATOR . 'config.php')) {
                $gestorPath = $nestedGestorPath;
            }
        }

        $accessUrl = $this->stringValue($project['url'] ?? null) ?? 'http://localhost/';
        $host = parse_url($accessUrl, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            $host = 'localhost';
        }

        return [
            'id' => $projectId,
            'gestorPath' => $gestorPath,
            'dockerPath' => $this->resolveDockerPath($project, $gestorPath),
            'envFile' => $this->resolveEnvFile($gestorPath, $host),
            'host' => $host,
            'accessUrl' => $accessUrl,
            'config' => $project,
        ];
    }

    /** @return array<string, mixed> */
    private function loadEnvironment(): array
    {
        if ($this->environment !== null) {
            return $this->environment;
        }

        $path = $this->getEnvironmentJsonPath();
        if (!is_file($path)) {
            throw new RuntimeException("environment.json not found at: {$path}");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Unable to read environment.json at: {$path}");
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            throw new RuntimeException("Invalid environment.json at: {$path}");
        }

        $this->environment = $decoded;

        return $decoded;
    }

    /**
     * @param array<string, mixed> $values
     * @param list<string> $keys
     */
    private function firstConfiguredString(array $values, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $this->stringValue($values[$key] ?? null);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    private function normalizeHostPath(string $path): string
    {
        $path = trim($path);

        if (DIRECTORY_SEPARATOR === '\\' && preg_match('#^/([A-Za-z])(?:/(.*))?$#', $path, $matches)) {
            $path = strtoupper($matches[1]) . ':\\' . str_replace('/', '\\', $matches[2] ?? '');
        } elseif (!$this->isAbsolutePath($path)) {
            $path = $this->rootPath . DIRECTORY_SEPARATOR . $path;
        }

        $path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $realPath = realpath($path);

        return $realPath !== false ? $realPath : $path;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    /** @param array<string, mixed> $project */
    private function resolveDockerPath(array $project, string $gestorPath): ?string
    {
        $configured = $this->stringValue($project['dockerPath'] ?? null);
        if ($configured !== null) {
            return '/' . trim(str_replace('\\', '/', $configured), '/') . '/';
        }

        $sitesRoot = $this->normalizeHostPath(
            $this->rootPath . DIRECTORY_SEPARATOR . 'dev-environment' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'sites'
        );
        $normalizedRoot = strtolower(rtrim(str_replace('\\', '/', $sitesRoot), '/')) . '/';
        $normalizedGestor = str_replace('\\', '/', $gestorPath);

        if (!str_starts_with(strtolower($normalizedGestor . '/'), $normalizedRoot)) {
            return null;
        }

        $relativePath = ltrim(substr($normalizedGestor, strlen(rtrim($normalizedRoot, '/'))), '/');

        return '/var/www/sites/' . ($relativePath !== '' ? rtrim($relativePath, '/') . '/' : '');
    }

    private function resolveEnvFile(string $gestorPath, string $host): string
    {
        $authenticationEnv = $gestorPath
            . DIRECTORY_SEPARATOR . 'autenticacoes'
            . DIRECTORY_SEPARATOR . $host
            . DIRECTORY_SEPARATOR . '.env';
        if (is_file($authenticationEnv)) {
            return $authenticationEnv;
        }

        $rootEnv = $gestorPath . DIRECTORY_SEPARATOR . '.env';
        if (is_file($rootEnv)) {
            return $rootEnv;
        }

        return $authenticationEnv;
    }
}
