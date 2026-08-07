<?php

declare(strict_types=1);

namespace C2F\Database\Dialect;

use InvalidArgumentException;

abstract class AbstractDatabaseDialect implements DatabaseDialect
{
    final public function quoteIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            throw new InvalidArgumentException('Database identifier cannot be empty.');
        }

        if ($identifier === '*') {
            return '*';
        }

        $parts = explode('.', $identifier);
        $quoted = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '*') {
                $quoted[] = '*';
                continue;
            }

            if ($this->isAlreadyQuoted($part)) {
                $quoted[] = $part;
                continue;
            }

            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_$]*$/D', $part)) {
                throw new InvalidArgumentException("Invalid database identifier: {$identifier}");
            }

            $quoted[] = $this->quotePart($part);
        }

        return implode('.', $quoted);
    }

    abstract protected function quotePart(string $identifier): string;

    abstract protected function isAlreadyQuoted(string $identifier): bool;
}
