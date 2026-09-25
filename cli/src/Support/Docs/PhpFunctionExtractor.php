<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Support\Docs;

/**
 * Lê as funções de topo de um arquivo PHP pelo tokenizer (req-177, X1 do FEAT-014).
 *
 * Extrai o que é neutro de idioma — nome, parâmetros (tipo, referência, variádico, default),
 * tipo de retorno e linha —, completando tipos ausentes na assinatura com `@param`/`@return`
 * do docblock. A prosa do docblock NÃO entra: o código do Core a escreve em português e o
 * bloco é o mesmo nas docs pt-br e en.
 */
final class PhpFunctionExtractor
{
    /**
     * @return list<array{name: string, line: int, params: list<array{name: string, type: string, default: ?string, byRef: bool, variadic: bool}>, return: string}>
     */
    public static function extract(string $code): array
    {
        $tokens = token_get_all($code);
        $count = count($tokens);
        $functions = [];
        $lastDoc = null;

        // Corpos de função e de classe são pulados inteiros; o que sobra de `function <nome>`
        // é função global — inclusive as declaradas dentro de `if (!function_exists(...)) {`,
        // padrão de `arquivo.php`.
        for ($i = 0; $i < $count; $i++) {
            $t = $tokens[$i];
            if (!is_array($t)) {
                if ($t !== '{' && $t !== '}') {
                    $lastDoc = null;
                }
                continue;
            }
            if ($t[0] === T_DOC_COMMENT) {
                $lastDoc = $t[1];
                continue;
            }
            if (in_array($t[0], [T_CLASS, T_TRAIT, T_INTERFACE, T_ENUM], true) && !self::isClassConstant($tokens, $i)) {
                $i = self::skipBlock($tokens, $i);
                $lastDoc = null;
                continue;
            }
            if ($t[0] !== T_FUNCTION) {
                if (!in_array($t[0], [T_WHITESPACE, T_COMMENT], true)) {
                    $lastDoc = null;
                }
                continue;
            }

            // `function` de topo: próximo T_STRING é o nome (closures não têm nome e são ignoradas).
            $j = $i + 1;
            while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                $j++;
            }
            if ($j < $count && $tokens[$j] === '&') {
                $j++;
                while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                    $j++;
                }
            }
            if (!isset($tokens[$j]) || !is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING) {
                continue;
            }
            $name = $tokens[$j][1];
            $line = $tokens[$i][2];

            [$params, $return, $end] = self::signature($tokens, $j + 1);
            $docTypes = self::docTypes($lastDoc);
            foreach ($params as &$p) {
                if ($p['type'] === '' && isset($docTypes['params'][$p['name']])) {
                    $p['type'] = $docTypes['params'][$p['name']];
                }
            }
            unset($p);
            if ($return === '' && $docTypes['return'] !== '') {
                $return = $docTypes['return'];
            }

            $functions[] = ['name' => $name, 'line' => $line, 'params' => $params, 'return' => $return];
            $lastDoc = null;
            $i = $end;
        }

        return $functions;
    }

    /**
     * @param list<mixed> $tokens
     * @return array{0: list<array{name: string, type: string, default: ?string, byRef: bool, variadic: bool}>, 1: string, 2: int}
     */
    private static function signature(array $tokens, int $start): array
    {
        $count = count($tokens);
        $i = $start;
        while ($i < $count && $tokens[$i] !== '(') {
            $i++;
        }
        $i++;
        $level = 0;
        $params = [];
        $buf = [];
        for (; $i < $count; $i++) {
            $t = $tokens[$i];
            $text = is_array($t) ? $t[1] : $t;
            if (in_array($text, ['(', '['], true)) {
                $level++;
            } elseif (in_array($text, [')', ']'], true)) {
                if ($level === 0) {
                    break;
                }
                $level--;
            }
            if ($text === ',' && $level === 0) {
                $params[] = self::param($buf);
                $buf = [];
                continue;
            }
            $buf[] = $t;
        }
        if (self::hasContent($buf)) {
            $params[] = self::param($buf);
        }

        // Retorno: `: tipo` entre `)` e `{`/`;`.
        $return = '';
        $i++;
        $collect = false;
        for (; $i < $count; $i++) {
            $t = $tokens[$i];
            if ($t === '{' || $t === ';') {
                break;
            }
            if ($t === ':') {
                $collect = true;
                continue;
            }
            if ($collect && !(is_array($t) && $t[0] === T_WHITESPACE)) {
                $return .= is_array($t) ? $t[1] : $t;
            }
        }

        // Pula o corpo inteiro para não confundir funções internas com as de topo.
        $end = $i;
        if (isset($tokens[$i]) && $tokens[$i] === '{') {
            $depth = 0;
            for (; $i < $count; $i++) {
                $t = $tokens[$i];
                if ($t === '{' || (is_array($t) && in_array($t[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
                    $depth++;
                } elseif ($t === '}') {
                    $depth--;
                    if ($depth === 0) {
                        break;
                    }
                }
            }
            $end = $i;
        }

        return [array_values(array_filter($params)), $return, $end];
    }

    /** `Foo::class` não abre bloco. @param list<mixed> $tokens */
    private static function isClassConstant(array $tokens, int $i): bool
    {
        for ($k = $i - 1; $k >= 0; $k--) {
            if (is_array($tokens[$k]) && in_array($tokens[$k][0], [T_WHITESPACE, T_COMMENT], true)) {
                continue;
            }

            return is_array($tokens[$k]) && $tokens[$k][0] === T_DOUBLE_COLON;
        }

        return false;
    }

    /** Avança até o `}` que fecha o primeiro `{` a partir de $i. @param list<mixed> $tokens */
    private static function skipBlock(array $tokens, int $i): int
    {
        $count = count($tokens);
        $depth = 0;
        for (; $i < $count; $i++) {
            $t = $tokens[$i];
            if ($t === '{' || (is_array($t) && in_array($t[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
                $depth++;
            } elseif ($t === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            } elseif ($t === ';' && $depth === 0) {
                return $i;
            }
        }

        return $count - 1;
    }

    /**
     * @param list<mixed> $buf
     * @return array{name: string, type: string, default: ?string, byRef: bool, variadic: bool}|null
     */
    private static function param(array $buf): ?array
    {
        $type = '';
        $name = '';
        $default = null;
        $byRef = false;
        $variadic = false;
        $inDefault = false;
        foreach ($buf as $t) {
            $text = is_array($t) ? $t[1] : $t;
            $id = is_array($t) ? $t[0] : null;
            if ($inDefault) {
                $default .= $text;
                continue;
            }
            if ($id === T_VARIABLE) {
                $name = substr($text, 1);
            } elseif ($text === '=') {
                $inDefault = true;
                $default = '';
            } elseif ($id === T_ELLIPSIS) {
                $variadic = true;
            } elseif ($text === '&' || $id === T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG || $id === T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG) {
                $byRef = true;
            } elseif ($id !== T_WHITESPACE && $id !== T_COMMENT && $name === '') {
                $type .= $text;
            }
        }
        if ($name === '') {
            return null;
        }

        return [
            'name' => $name,
            'type' => $type,
            'default' => $default === null ? null : preg_replace('/\s+/', ' ', trim($default)),
            'byRef' => $byRef,
            'variadic' => $variadic,
        ];
    }

    /** @param list<mixed> $buf */
    private static function hasContent(array $buf): bool
    {
        foreach ($buf as $t) {
            if (!(is_array($t) && $t[0] === T_WHITESPACE)) {
                return true;
            }
        }

        return false;
    }

    /** @return array{params: array<string, string>, return: string} */
    private static function docTypes(?string $doc): array
    {
        $out = ['params' => [], 'return' => ''];
        if ($doc === null) {
            return $out;
        }
        // `\w++(?!\[)` ignora linhas como `@param string $params['codigo']`, que documentam
        // chaves do array e não o parâmetro em si.
        if (preg_match_all('/@param\s+([^\s$]+)\s+&?(?:\.\.\.)?\$(\w++)(?!\[)/', $doc, $m, PREG_SET_ORDER)) {
            foreach ($m as $p) {
                $out['params'][$p[2]] = $p[1];
            }
        }
        if (preg_match('/@return\s+([^\s]+)/', $doc, $r)) {
            $out['return'] = $r[1];
        }

        return $out;
    }

    /**
     * Assinatura legível: `nome(tipo $a, $b = 'x'): retorno`.
     *
     * @param array{name: string, params: list<array{name: string, type: string, default: ?string, byRef: bool, variadic: bool}>, return: string} $fn
     */
    public static function signatureText(array $fn): string
    {
        $parts = [];
        foreach ($fn['params'] as $p) {
            $s = ($p['type'] !== '' ? $p['type'] . ' ' : '')
                . ($p['byRef'] ? '&' : '')
                . ($p['variadic'] ? '...' : '')
                . '$' . $p['name'];
            if ($p['default'] !== null) {
                $s .= ' = ' . $p['default'];
            }
            $parts[] = $s;
        }

        return $fn['name'] . '(' . implode(', ', $parts) . ')' . ($fn['return'] !== '' ? ': ' . $fn['return'] : '');
    }
}
