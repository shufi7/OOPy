<?php

namespace App\Services;

class FinalExamCodeAnswer
{
    public function matches(array $question, string $answer): bool
    {
        $expected = $question['answer'];
        if (trim($answer) === '') {
            return false;
        }
        // A blank within a method name accepts an identifier, not an expression.
        if (preg_match('/(?:def\s+|\.)(_{3,})/', $question['code'] ?? '')) {
            return trim($answer) === $expected;
        }
        $key = $this->normalize($expected);
        if ($key !== null) {
            return $this->normalize($answer) === $key;
        }

        // Preserve existing Admin keys outside this deliberately limited grammar.
        return trim($answer) === trim($expected);
    }

    public function normalize(string $source): ?array
    {
        if (preg_match('/[\r\n\x00]/', $source)) {
            return null;
        }
        $tokens = [];
        $rest = trim($source);
        while ($rest !== '') {
            if (! preg_match('/\A(?:[A-Za-z_][A-Za-z_0-9]*|[.@=(),+])/', $rest, $match)) {
                return null;
            }
            $tokens[] = $match[0];
            $rest = ltrim(substr($rest, strlen($match[0])), " \t");
        }
        $position = 0;
        $prefix = in_array($tokens[0] ?? null, ['return', '@'], true) ? $tokens[$position++] : null;
        $expression = $this->expression($tokens, $position);
        if ($expression === null) {
            return null;
        }
        if (($tokens[$position] ?? null) === '=' && $prefix === null) {
            if (! in_array($expression[0], ['name', 'attr'], true)) {
                return null;
            }
            $position++;
            $value = $this->expression($tokens, $position);
            if ($value === null) {
                return null;
            }
            $expression = ['assign', $expression, $value];
        }

        return $position === count($tokens) ? [$prefix, $expression] : null;
    }

    private function expression(array $tokens, int &$position): ?array
    {
        $left = $this->primary($tokens, $position);
        while ($left !== null && ($tokens[$position] ?? null) === '+') {
            $position++;
            $right = $this->primary($tokens, $position);
            if ($right === null) {
                return null;
            }
            $left = ['add', $left, $right];
        }

        return $left;
    }

    private function primary(array $tokens, int &$position): ?array
    {
        $token = $tokens[$position++] ?? null;
        if ($token === '(') {
            $node = $this->expression($tokens, $position);
            if ($node === null || ($tokens[$position++] ?? null) !== ')') {
                return null;
            }
        } elseif ($this->identifier($token)) {
            $node = ['name', $token];
        } else {
            return null;
        }
        while (in_array($tokens[$position] ?? null, ['.', '('], true)) {
            $operator = $tokens[$position++];
            if ($operator === '.') {
                $name = $tokens[$position++] ?? null;
                if (! $this->identifier($name)) {
                    return null;
                }
                $node = ['attr', $node, $name];
            } else {
                $arguments = [];
                while (($tokens[$position] ?? null) !== ')') {
                    $argument = $this->expression($tokens, $position);
                    if ($argument === null) {
                        return null;
                    }
                    $arguments[] = $argument;
                    if (($tokens[$position] ?? null) !== ',') {
                        break;
                    }
                    $position++;
                }
                if (($tokens[$position++] ?? null) !== ')') {
                    return null;
                }
                $node = ['call', $node, $arguments];
            }
        }

        return $node;
    }

    private function identifier(?string $token): bool
    {
        return $token !== null && preg_match('/\A[A-Za-z_][A-Za-z_0-9]*\z/', $token)
            && ! in_array($token, explode(' ', 'False None True and as assert async await break class continue def del elif else except finally for from global if import in is lambda nonlocal not or pass raise return try while with yield'), true);
    }
}
