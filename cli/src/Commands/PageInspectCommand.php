<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class PageInspectCommand implements CommandInterface
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'page:inspect';
    }

    public function getDescription(): string
    {
        return 'Inspect a page headlessly via Playwright: computed styles, console errors, screenshots.';
    }

    public function getAliases(): array
    {
        return ['inspect'];
    }

    public function getHelp(): string
    {
        return <<<HELP
Usage: c2f page:inspect <url_or_route> [options]

Options:
  --project=ID              Project ID (resolves base URL from environment.json)
  --selector="sel1,sel2"    CSS selectors to inspect in the mounted DOM
  --computed="prop1,prop2"  CSS properties to extract (e.g. display,opacity)
  --screenshot[=PATH]       Capture screenshot (default: temp/inspect-screenshot.png)
  --cookies=PATH            Cookie jar path (default: temp/agent-cookies.txt if exists)

Output: JSON with status, url, consoleErrors, elements, screenshotPath
HELP;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $urlOrRoute = $input->getArgument(0);
        $projectId = $input->getOption('project');
        $selectors = $input->getOption('selector', '');
        $computed = $input->getOption('computed', '');
        $screenshot = $input->hasOption('screenshot');
        $screenshotPath = is_string($input->getOption('screenshot')) && $input->getOption('screenshot') !== true
            ? $input->getOption('screenshot')
            : 'temp/inspect-screenshot.png';
        $cookiesPath = $input->getOption('cookies');

        // Resolve URL
        $baseUrl = '';
        if ($projectId) {
            $envJsonPath = $this->rootPath . DIRECTORY_SEPARATOR . 'dev-environment' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'environment.json';
            if (is_file($envJsonPath)) {
                $envJson = json_decode(file_get_contents($envJsonPath), true);
                $baseUrl = $envJson['devProjects'][$projectId]['url'] ?? $envJson['devEnvironment']['accessURL'] ?? '';
            }
        }

        $url = $urlOrRoute;
        if ($url && !str_starts_with($url, 'http')) {
            $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
        }

        if (!$url) {
            $output->error('URL is required. Provide a URL argument or use --project=ID with a route.');
            return 1;
        }

        // Resolve cookie jar path
        if (!$cookiesPath) {
            $defaultCookies = $this->rootPath . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'agent-cookies.txt';
            if (is_file($defaultCookies)) {
                $cookiesPath = $defaultCookies;
            }
        } elseif (!str_starts_with($cookiesPath, '/') && !str_starts_with($cookiesPath, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:/', $cookiesPath)) {
            $cookiesPath = $this->rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cookiesPath);
        }

        // Resolve screenshot path
        if ($screenshot) {
            if (!str_starts_with($screenshotPath, '/') && !str_starts_with($screenshotPath, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:/', $screenshotPath)) {
                $screenshotPath = $this->rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $screenshotPath);
            }
            $screenshotDir = dirname($screenshotPath);
            if (!is_dir($screenshotDir)) {
                mkdir($screenshotDir, 0755, true);
            }
        }

        // Build config for the Node script
        $config = [
            'url' => $url,
            'selectors' => $selectors ? array_map('trim', explode(',', $selectors)) : [],
            'computedProperties' => $computed ? array_map('trim', explode(',', $computed)) : [],
            'screenshot' => $screenshot,
            'screenshotPath' => $screenshot ? $screenshotPath : null,
            'cookiesPath' => $cookiesPath,
        ];

        $configB64 = base64_encode(json_encode($config));

        // Locate the Node.js inspect script
        $scriptPath = $this->rootPath . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'page-inspect.js';

        if (!is_file($scriptPath)) {
            $output->error("Inspect script not found: {$scriptPath}");
            $output->writeln('Ensure cli/scripts/page-inspect.js exists.');
            return 1;
        }

        // Check Node.js availability
        $nodeCheck = shell_exec('node --version 2>&1');
        if (!$nodeCheck || !str_starts_with(trim($nodeCheck), 'v')) {
            $output->error('Node.js is required but was not found. Install Node.js first.');
            return 1;
        }

        $output->info("Inspecting: {$url}");
        if ($cookiesPath) {
            $output->info("Cookies: {$cookiesPath}");
        }

        // Execute Node script
        $cmd = 'node ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($configB64);

        $process = proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $this->rootPath);

        if (!is_resource($process)) {
            $output->error('Failed to spawn Node.js process.');
            return 1;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $output->error('Page inspection failed.');
            if ($stderr) {
                $output->writeln($stderr);
            }
            return 1;
        }

        // Output JSON result
        $output->writeln($stdout);

        return 0;
    }
}
