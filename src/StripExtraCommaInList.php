<?php

namespace Fmt;

final class StripExtraCommaInList extends FormatterPass
{
    const EMPTY_LIST = 'ST_EMPTY_LIST';

    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_LIST])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);

        $contextStack = [];
        $touchedListArrayString = false;

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case T_STRING:
                case T_ARRAY:
                case T_LIST:
                    $touchedListArrayString = true;
                    if ($this->rightTokenIs(ST_PARENTHESES_OPEN)) {
                        $contextStack[] = $id;
                    }
                    break;

                case ST_PARENTHESES_OPEN:
                    if (isset($contextStack[0]) && T_LIST == end($contextStack) && $this->rightTokenIs(ST_PARENTHESES_CLOSE)) {
                        $contextStack[sizeof($contextStack) - 1] = self::EMPTY_LIST;
                    } elseif (!$touchedListArrayString) {
                        $contextStack[] = ST_PARENTHESES_OPEN;
                    }
                    break;

                case ST_PARENTHESES_CLOSE:
                    if (isset($contextStack[0])) {
                        if (T_LIST == end($contextStack) && $this->leftUsefulTokenIs(ST_COMMA)) {
                            $prevTokenIdx = $this->leftUsefulTokenIdx();
                            $this->tkns[$prevTokenIdx] = null;
                        }
                        array_pop($contextStack);
                    }
                    break;

                default:
                    $touchedListArrayString = false;
                    break;
            }
            $this->tkns[$this->ptr] = [$id, $text];
        }

        return $this->renderLight();
    }
}
