<?php

namespace Fmt;

class AutoPreincrement extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_INC]) || isset($foundTokens[T_DEC])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        for ($this->ptr = sizeof($this->tkns) - 1; $this->ptr >= 0; --$this->ptr) {
            $token = $this->tkns[$this->ptr];
            $tokenRef = &$this->tkns[$this->ptr];

            $id = $token[0];
            if (!(T_INC == $id || T_DEC == $id)) {
                continue;
            }

            if (
                !$this->leftUsefulTokenIs([
                    ST_BRACKET_CLOSE,
                    ST_CURLY_CLOSE,
                    T_STRING,
                    T_VARIABLE,
                ])
                ||
                !$this->rightUsefulTokenIs([
                    ST_SEMI_COLON,
                    ST_PARENTHESES_CLOSE,
                ])
            ) {
                continue;
            }

            $this->findVariableLeftEdge();

            if (
                $this->leftUsefulTokenIs([
                    ST_SEMI_COLON,
                    ST_CURLY_OPEN,
                    ST_CURLY_CLOSE,
                    T_OPEN_TAG,
                ])
            ) {
                $this->refInsert($this->tkns, $this->ptr, $token);
                $tokenRef = null;
            }
        }

        return $this->render();
    }

    public function getDescription()
    {
        return 'Automatically convert postincrement to preincrement.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
$a++;
$b--;
func($a++);

++$a;
--$b;
func($a++);
?>
EOT;
    }

    private function findVariableLeftEdge()
    {
        $this->skipBlocks();

        $leftIdx = $this->leftUsefulTokenIdx();
        $idLeftToken = $this->tkns[$leftIdx][0];

        if (ST_DOLLAR == $idLeftToken) {
            $this->ptr = $leftIdx;
            $leftIdx = $this->leftUsefulTokenIdx();
            $idLeftToken = $this->tkns[$leftIdx][0];
        }

        if (T_OBJECT_OPERATOR == $idLeftToken) {
            $this->findVariableLeftEdge();

            return;
        }

        if (T_DOUBLE_COLON == $idLeftToken) {
            if (!$this->leftUsefulTokenIs([T_STRING])) {
                $this->findVariableLeftEdge();

                return;
            }

            $this->refWalkBackUsefulUntil($this->tkns, $this->ptr, [T_NS_SEPARATOR, T_STRING]);
            $this->ptr = $this->rightUsefulTokenIdx();
        }

        return;
    }

    private function skipBlocks()
    {
        do {
            $this->ptr = $this->leftUsefulTokenIdx();
            $id = $this->tkns[$this->ptr][0];

            if (ST_BRACKET_CLOSE == $id) {
                $this->refWalkBlockReverse($this->tkns, $this->ptr, ST_BRACKET_OPEN, ST_BRACKET_CLOSE);
            } elseif (ST_CURLY_CLOSE == $id) {
                $this->refWalkCurlyBlockReverse($this->tkns, $this->ptr);
            } elseif (ST_PARENTHESES_CLOSE == $id) {
                $this->refWalkBlockReverse($this->tkns, $this->ptr, ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE);
            }

            $id = $this->tkns[$this->ptr][0];
        } while (!(ST_DOLLAR == $id || T_VARIABLE == $id));
    }
}
