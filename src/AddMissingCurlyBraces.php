<?php

namespace Fmt;

final class AddMissingCurlyBraces extends FormatterPass
{
    public function candidate($source, $foundTokens)
    {
        if (
            isset($foundTokens[T_ELSE]) ||
            isset($foundTokens[T_WHILE]) ||
            isset($foundTokens[T_FOR]) ||
            isset($foundTokens[T_FOREACH]) ||
            isset($foundTokens[T_ELSEIF]) ||
            isset($foundTokens[T_IF])
        ) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';
        $touchedCurlyClose = false;
        $hasCurlyOnLeft = false;
        for ($index = sizeof($this->tkns) - 1; 0 <= $index; --$index) {
            $token = $this->tkns[$index];
            list($id) = $this->getToken($token);
            $this->ptr = $index;

            $hasCurlyOnLeft = false;

            switch ($id) {
                case T_ELSE:
                    if ($this->rightTokenSubsetIsAtIdx($this->tkns, $this->ptr, [ST_CURLY_OPEN, ST_COLON, T_IF], $this->ignoreFutileTokens)) {
                        break;
                    }
                    $this->insertCurlyBraces();
                    break;

                case ST_CURLY_CLOSE:
                    $touchedCurlyClose = true;
                    break;

                case T_WHILE:
                    if ($touchedCurlyClose) {
                        $touchedCurlyClose = false;
                        $hasCurlyOnLeft = true;
                    }

                case T_FOR:
                case T_FOREACH:
                case T_ELSEIF:
                case T_IF:
                    $this->refWalkUsefulUntil($this->tkns, $this->ptr, ST_PARENTHESES_OPEN);
                    $this->refWalkBlock($this->tkns, $this->ptr, ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE);
                    if (
                        ($hasCurlyOnLeft && $this->rightTokenSubsetIsAtIdx($this->tkns, $this->ptr, [ST_SEMI_COLON], $this->ignoreFutileTokens)) ||
                        $this->rightTokenSubsetIsAtIdx($this->tkns, $this->ptr, [ST_CURLY_OPEN, ST_COLON, ST_SEMI_COLON], $this->ignoreFutileTokens)
                    ) {
                        break;
                    }
                    $this->insertCurlyBraces();
                    break;
            }
        }

        return $this->render($this->tkns);
    }

    private function addSemicolon()
    {
        if (T_CLOSE_TAG == $this->tkns[$this->ptr][0]) {
            return $this->refInsert($this->tkns, $this->ptr, [ST_SEMI_COLON, ST_SEMI_COLON]);
        }
        ++$this->ptr;
    }

    private function insertCurlyBraces()
    {
        $this->refSkipIfTokenIsAny($this->tkns, $this->ptr, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT]);
        $this->refInsert($this->tkns, $this->ptr, [ST_CURLY_OPEN, ST_CURLY_OPEN]);
        $this->refInsert($this->tkns, $this->ptr, [T_WHITESPACE, $this->newLine]);
        $this->refSkipBlocks($this->tkns, $this->ptr);
        $this->addSemicolon();
        $this->refInsert($this->tkns, $this->ptr, [T_WHITESPACE, $this->newLine]);
        $this->refInsert($this->tkns, $this->ptr, [ST_CURLY_CLOSE, ST_CURLY_CLOSE]);
        $this->refInsert($this->tkns, $this->ptr, [T_WHITESPACE, $this->newLine]);
    }
}
