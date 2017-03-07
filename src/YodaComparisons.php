<?php

namespace Fmt;

final class YodaComparisons extends AdditionalPass
{
    const CHAIN_FUNC = 'CHAIN_FUNC';

    const CHAIN_LITERAL = 'CHAIN_LITERAL';

    const CHAIN_STRING = 'CHAIN_STRING';

    const CHAIN_VARIABLE = 'CHAIN_VARIABLE';

    const PARENTHESES_BLOCK = 'PARENTHESES_BLOCK';

    public function candidate($source, $foundTokens)
    {
        if (
            isset($foundTokens[T_IS_EQUAL]) ||
            isset($foundTokens[T_IS_IDENTICAL]) ||
            isset($foundTokens[T_IS_NOT_EQUAL]) ||
            isset($foundTokens[T_IS_NOT_IDENTICAL])
        ) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        return $this->yodise($source);
    }

    public function getDescription()
    {
        return 'Execute Yoda Comparisons.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
if($a == 1){

}
?>
to
<?php
if(1 == $a){

}
?>
EOT;
    }

    protected function yodise($source)
    {
        $tkns = $this->aggregateVariables($source);
        while (list($ptr, $token) = each($tkns)) {
            if (is_null($token)) {
                continue;
            }
            list($id) = $this->getToken($token);
            switch ($id) {
                case T_IS_EQUAL:
                case T_IS_IDENTICAL:
                case T_IS_NOT_EQUAL:
                case T_IS_NOT_IDENTICAL:
                    list($left, $right) = $this->siblings($tkns, $ptr);
                    list($leftId) = $tkns[$left];
                    list($rightId) = $tkns[$right];
                    if ($leftId == $rightId) {
                        continue;
                    }

                    $leftPureVariable = $this->isPureVariable($leftId);
                    for ($leftmost = $left; $leftmost >= 0; --$leftmost) {
                        list($leftScanId) = $this->getToken($tkns[$leftmost]);
                        if ($this->isLowerPrecedence($leftScanId)) {
                            ++$leftmost;
                            break;
                        }
                        $leftPureVariable &= $this->isPureVariable($leftScanId);
                    }

                    $rightPureVariable = $this->isPureVariable($rightId);
                    for ($rightmost = $right; $rightmost < sizeof($tkns) - 1; ++$rightmost) {
                        list($rightScanId) = $this->getToken($tkns[$rightmost]);
                        if ($this->isLowerPrecedence($rightScanId)) {
                            --$rightmost;
                            break;
                        }
                        $rightPureVariable &= $this->isPureVariable($rightScanId);
                    }

                    if ($leftPureVariable && !$rightPureVariable) {
                        $origLeftTokens = $leftTokens = implode('', array_map(function ($token) {
                            return isset($token[1]) ? $token[1] : $token;
                        }, array_slice($tkns, $leftmost, $left - $leftmost + 1)));
                        $origRightTokens = $rightTokens = implode('', array_map(function ($token) {
                            return isset($token[1]) ? $token[1] : $token;
                        }, array_slice($tkns, $right, $rightmost - $right + 1)));

                        $leftTokens = (substr($origRightTokens, 0, 1) == ' ' ? ' ' : '').trim($leftTokens).(substr($origRightTokens, -1, 1) == ' ' ? ' ' : '');
                        $rightTokens = (substr($origLeftTokens, 0, 1) == ' ' ? ' ' : '').trim($rightTokens).(substr($origLeftTokens, -1, 1) == ' ' ? ' ' : '');

                        $tkns[$leftmost] = ['REPLACED', $rightTokens];
                        $tkns[$right] = ['REPLACED', $leftTokens];

                        if ($leftmost != $left) {
                            for ($i = $leftmost + 1; $i <= $left; ++$i) {
                                $tkns[$i] = null;
                            }
                        }
                        if ($rightmost != $right) {
                            for ($i = $right + 1; $i <= $rightmost; ++$i) {
                                $tkns[$i] = null;
                            }
                        }
                    }
            }
        }

        return $this->render($tkns);
    }

    private function aggregateVariables($source)
    {
        $tkns = token_get_all($source);
        while (list($ptr, $token) = each($tkns)) {
            list($id, $text) = $this->getToken($token);

            if (ST_PARENTHESES_OPEN == $id) {
                $initialPtr = $ptr;
                $tmp = $this->scanAndReplace($tkns, $ptr, ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE, 'yodise', [T_IS_EQUAL, T_IS_IDENTICAL, T_IS_NOT_EQUAL, T_IS_NOT_IDENTICAL]);
                $tkns[$initialPtr] = [self::PARENTHESES_BLOCK, $tmp];
                continue;
            }
            if (ST_QUOTE == $id) {
                $stack = $text;
                $initialPtr = $ptr;
                while (list($ptr, $token) = each($tkns)) {
                    list($id, $text) = $this->getToken($token);
                    $stack .= $text;
                    $tkns[$ptr] = null;
                    if (ST_QUOTE == $id) {
                        break;
                    }
                }

                $tkns[$initialPtr] = [self::CHAIN_STRING, $stack];
                continue;
            }

            if (T_STRING == $id || T_VARIABLE == $id || T_NS_SEPARATOR == $id) {
                $initialIndex = $ptr;
                $stack = $text;
                $touchedVariable = false;
                if (T_VARIABLE == $id) {
                    $touchedVariable = true;
                }
                if (!$this->rightTokenSubsetIsAtIdx(
                    $tkns,
                    $ptr,
                    [T_STRING, T_VARIABLE, T_NS_SEPARATOR, T_OBJECT_OPERATOR, T_DOUBLE_COLON, ST_CURLY_OPEN, ST_PARENTHESES_OPEN, ST_BRACKET_OPEN, T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES]
                )) {
                    continue;
                }
                while (list($ptr, $token) = each($tkns)) {
                    list($id, $text) = $this->getToken($token);
                    $tkns[$ptr] = null;
                    if (ST_CURLY_OPEN == $id) {
                        $text = $this->scanAndReplaceCurly($tkns, $ptr, ST_CURLY_OPEN, 'yodise', [T_IS_EQUAL, T_IS_IDENTICAL, T_IS_NOT_EQUAL, T_IS_NOT_IDENTICAL]);
                    } elseif (T_CURLY_OPEN == $id) {
                        $text = $this->scanAndReplaceCurly($tkns, $ptr, ST_CURLY_OPEN, 'yodise', [T_IS_EQUAL, T_IS_IDENTICAL, T_IS_NOT_EQUAL, T_IS_NOT_IDENTICAL]);
                    } elseif (T_DOLLAR_OPEN_CURLY_BRACES == $id) {
                        $text = $this->scanAndReplaceCurly($tkns, $ptr, T_DOLLAR.ST_CURLY_OPEN, 'yodise', [T_IS_EQUAL, T_IS_IDENTICAL, T_IS_NOT_EQUAL, T_IS_NOT_IDENTICAL]);
                    } elseif (ST_BRACKET_OPEN == $id) {
                        $text = $this->scanAndReplace($tkns, $ptr, ST_BRACKET_OPEN, ST_BRACKET_CLOSE, 'yodise', [T_IS_EQUAL, T_IS_IDENTICAL, T_IS_NOT_EQUAL, T_IS_NOT_IDENTICAL]);
                    } elseif (ST_PARENTHESES_OPEN == $id) {
                        $text = $this->scanAndReplace($tkns, $ptr, ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE, 'yodise', [T_IS_EQUAL, T_IS_IDENTICAL, T_IS_NOT_EQUAL, T_IS_NOT_IDENTICAL]);
                    }

                    $stack .= $text;

                    if (!$touchedVariable && T_VARIABLE == $id) {
                        $touchedVariable = true;
                    }

                    if (
                        !$this->rightTokenSubsetIsAtIdx(
                            $tkns,
                            $ptr,
                            [T_STRING, T_VARIABLE, T_NS_SEPARATOR, T_OBJECT_OPERATOR, T_DOUBLE_COLON, ST_CURLY_OPEN, ST_PARENTHESES_OPEN, ST_BRACKET_OPEN, T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES]
                        )
                    ) {
                        break;
                    }
                }
                $chain = [self::CHAIN_LITERAL, $stack];
                if (substr(trim($stack), -1, 1) == ST_PARENTHESES_CLOSE) {
                    $chain = [self::CHAIN_FUNC, $stack];
                } elseif ($touchedVariable) {
                    $chain = [self::CHAIN_VARIABLE, $stack];
                }
                $tkns[$initialIndex] = $chain;
            }
        }
        $tkns = array_values(array_filter($tkns));

        return $tkns;
    }

    private function isLowerPrecedence($id)
    {
        switch ($id) {
            case ST_REFERENCE:
            case ST_BITWISE_XOR:
            case ST_BITWISE_OR:
            case T_BOOLEAN_AND:
            case T_BOOLEAN_OR:
            case ST_QUESTION:
            case ST_COLON:
            case ST_EQUAL:
            case T_PLUS_EQUAL:
            case T_MINUS_EQUAL:
            case T_MUL_EQUAL:
            case T_POW_EQUAL:
            case T_DIV_EQUAL:
            case T_CONCAT_EQUAL:
            case T_MOD_EQUAL:
            case T_AND_EQUAL:
            case T_OR_EQUAL:
            case T_XOR_EQUAL:
            case T_SL_EQUAL:
            case T_SR_EQUAL:
            case T_DOUBLE_ARROW:
            case T_LOGICAL_AND:
            case T_LOGICAL_XOR:
            case T_LOGICAL_OR:
            case ST_COMMA:
            case ST_SEMI_COLON:
            case T_RETURN:
            case T_THROW:
            case T_GOTO:
            case T_CASE:
            case T_COMMENT:
            case T_DOC_COMMENT:
            case T_OPEN_TAG:
                return true;
        }

        return false;
    }

    private function isPureVariable($id)
    {
        return self::CHAIN_VARIABLE == $id || T_VARIABLE == $id || T_INC == $id || T_DEC == $id || ST_EXCLAMATION == $id || T_COMMENT == $id || T_DOC_COMMENT == $id || T_WHITESPACE == $id;
    }
}
