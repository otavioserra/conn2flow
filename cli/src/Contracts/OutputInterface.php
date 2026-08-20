<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Contracts;

interface OutputInterface
{
    /**
     * Write raw message to stdout without a trailing newline.
     */
    public function write(string $message): void;

    /**
     * Write message to stdout with a trailing newline.
     */
    public function writeln(string $message = ''): void;

    /**
     * Output a success message (green with icon).
     */
    public function success(string $message): void;

    /**
     * Output an informational message (cyan with icon).
     */
    public function info(string $message): void;

    /**
     * Output a warning message (yellow with icon).
     */
    public function warning(string $message): void;

    /**
     * Output an error message (red with icon).
     */
    public function error(string $message): void;

    /**
     * Output a stylized header/title banner.
     */
    public function title(string $title): void;

    /**
     * Output a section separator or subtitle.
     */
    public function section(string $section): void;

    /**
     * Output a formatted ASCII table.
     *
     * @param array<string> $headers
     * @param array<array<string>> $rows
     */
    public function table(array $headers, array $rows): void;
}
