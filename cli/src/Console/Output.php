<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Console;

use Conn2Flow\Cli\Contracts\OutputInterface;

final class Output implements OutputInterface
{
    private bool $hasAnsi;

    public function __construct()
    {
        $this->hasAnsi = $this->detectAnsiSupport();
    }

    private function detectAnsiSupport(): bool
    {
        if (getenv('NO_COLOR') !== false) {
            return false;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            return (getenv('ANSICON') !== false
                || getenv('ConEmuANSI') === 'ON'
                || getenv('WT_SESSION') !== false
                || (function_exists('sapi_windows_vt100_support') && sapi_windows_vt100_support(STDOUT)));
        }

        return function_exists('posix_isatty') ? posix_isatty(STDOUT) : true;
    }

    private function colorize(string $text, string $ansiCode): string
    {
        if (!$this->hasAnsi) {
            return $text;
        }
        return "\033[{$ansiCode}m{$text}\033[0m";
    }

    public function write(string $message): void
    {
        fwrite(STDOUT, $message);
    }

    public function writeln(string $message = ''): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }

    public function success(string $message): void
    {
        $tag = $this->colorize(' ✔ SUCCESS ', '30;42;1');
        $this->writeln("{$tag} " . $this->colorize($message, '32'));
    }

    public function info(string $message): void
    {
        $tag = $this->colorize(' ℹ INFO ', '30;46;1');
        $this->writeln("{$tag} " . $this->colorize($message, '36'));
    }

    public function warning(string $message): void
    {
        $tag = $this->colorize(' ⚠ WARNING ', '30;43;1');
        $this->writeln("{$tag} " . $this->colorize($message, '33'));
    }

    public function error(string $message): void
    {
        $tag = $this->colorize(' ✖ ERROR ', '37;41;1');
        $this->writeln("{$tag} " . $this->colorize($message, '31;1'));
    }

    public function title(string $title): void
    {
        $len = mb_strlen($title) + 4;
        $bar = str_repeat('═', $len);
        $this->writeln('');
        $this->writeln($this->colorize("╔{$bar}╗", '36;1'));
        $this->writeln($this->colorize("║  {$title}  ║", '36;1'));
        $this->writeln($this->colorize("╚{$bar}╝", '36;1'));
        $this->writeln('');
    }

    public function section(string $section): void
    {
        $this->writeln('');
        $this->writeln($this->colorize("▶ {$section}", '35;1'));
        $this->writeln($this->colorize(str_repeat('─', mb_strlen($section) + 3), '90'));
    }

    public function table(array $headers, array $rows): void
    {
        if (empty($headers) && empty($rows)) {
            return;
        }

        $colWidths = [];
        foreach ($headers as $colIdx => $header) {
            $colWidths[$colIdx] = mb_strlen($header);
        }

        foreach ($rows as $row) {
            foreach ($row as $colIdx => $val) {
                $valStr = (string)$val;
                $colWidths[$colIdx] = max($colWidths[$colIdx] ?? 0, mb_strlen($valStr));
            }
        }

        // Top border
        $top = '┌';
        $mid = '├';
        $bot = '└';
        $totalCols = count($colWidths);
        $idx = 0;
        foreach ($colWidths as $w) {
            $top .= str_repeat('─', $w + 2) . ($idx < $totalCols - 1 ? '┬' : '┐');
            $mid .= str_repeat('─', $w + 2) . ($idx < $totalCols - 1 ? '┼' : '┤');
            $bot .= str_repeat('─', $w + 2) . ($idx < $totalCols - 1 ? '┴' : '┘');
            $idx++;
        }

        $this->writeln($this->colorize($top, '90'));

        // Header Row
        $headerLine = '│';
        foreach ($headers as $colIdx => $h) {
            $headerLine .= ' ' . str_pad($h, $colWidths[$colIdx]) . ' │';
        }
        $this->writeln($this->colorize($headerLine, '37;1'));
        $this->writeln($this->colorize($mid, '90'));

        // Data Rows
        foreach ($rows as $row) {
            $rowLine = '│';
            foreach ($colWidths as $colIdx => $w) {
                $cell = (string)($row[$colIdx] ?? '');
                $rowLine .= ' ' . str_pad($cell, $w) . ' │';
            }
            $this->writeln($rowLine);
        }

        $this->writeln($this->colorize($bot, '90'));
        $this->writeln('');
    }
}
