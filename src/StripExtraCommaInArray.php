<?php

namespace Fmt;

final class StripExtraCommaInArray extends AdditionalPass
{
    const EMPTY_ARRAY = 'ST_EMPTY_ARRAY';

    const ST_SHORT_ARRAY_OPEN = 'SHORT_ARRAY_OPEN';

    public function candidate($source, $foundTokens)
    {
        return true;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);

        $contextStack = [];
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case ST_BRACKET_OPEN:
                    $found = ST_BRACKET_OPEN;
                    if ($this->isShortArray()) {
                        $found = self::ST_SHORT_ARRAY_OPEN;
                    }
                    $contextStack[] = $found;
                    break;
                case ST_BRACKET_CLOSE:
                    if (isset($contextStack[0]) && !$this->leftTokenIs(ST_BRACKET_OPEN)) {
                        if (self::ST_SHORT_ARRAY_OPEN == end($contextStack) && $this->leftUsefulTokenIs(ST_COMMA)) {
                            $prevTokenIdx = $this->leftUsefulTokenIdx();
                            $this->tkns[$prevTokenIdx] = null;
                        }
                        array_pop($contextStack);
                    }
                    break;
                case T_STRING:
                    if ($this->rightTokenIs(ST_PARENTHESES_OPEN)) {
                        $contextStack[] = T_STRING;
                    }
                    break;
                case T_ARRAY:
                    if ($this->rightTokenIs(ST_PARENTHESES_OPEN)) {
                        $contextStack[] = T_ARRAY;
                    }
                    break;
                case ST_PARENTHESES_OPEN:
                    if (isset($contextStack[0]) && T_ARRAY == end($contextStack) && $this->rightTokenIs(ST_PARENTHESES_CLOSE)) {
                        $contextStack[sizeof($contextStack) - 1] = self::EMPTY_ARRAY;
                    } elseif (!$this->leftTokenIs([T_ARRAY, T_STRING])) {
                        $contextStack[] = ST_PARENTHESES_OPEN;
                    }
                    break;
                case ST_PARENTHESES_CLOSE:
                    if (isset($contextStack[0])) {
                        if (T_ARRAY == end($contextStack) && $this->leftUsefulTokenIs(ST_COMMA)) {
                            $prevTokenIdx = $this->leftUsefulTokenIdx();
                            $this->tkns[$prevTokenIdx] = null;
                        }
                        array_pop($contextStack);
                    }
                    break;
            }
            $this->tkns[$this->ptr] = [$id, $text];
        }

        return $this->renderLight();
    }

    public function getDescription()
    {
        return 'Remove trailing commas within array blocks';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
// From
$a = [$a, $b, ];
$b = array($b, $c, );

// To
$a = [$a, $b];
$b = array($b, $c);
?>
EOT;
    }
}
