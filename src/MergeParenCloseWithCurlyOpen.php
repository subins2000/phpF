<?php

namespace Fmt;

final class MergeParenCloseWithCurlyOpen extends FormatterPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[ST_CURLY_OPEN]) || isset($foundTokens[T_ELSE]) || isset($foundTokens[T_ELSEIF])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';

        $touchedElseStringParenClose = false;
        $touchedCurlyClose = false;

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case T_STRING:
                case ST_PARENTHESES_CLOSE:
                    $touchedElseStringParenClose = true;
                    $this->appendCode($text);
                    break;

                case ST_CURLY_CLOSE:
                    $touchedCurlyClose = true;
                    $this->appendCode($text);
                    break;

                case ST_CURLY_OPEN:
                    if ($touchedElseStringParenClose) {
                        $touchedElseStringParenClose = false;
                        $this->code = rtrim($this->code);
                    }
                    $this->appendCode($text);
                    break;

                case T_ELSE:
                    $touchedElseStringParenClose = true;
                case T_ELSEIF:
                    if ($touchedCurlyClose) {
                        $this->code = rtrim($this->code);
                        $touchedCurlyClose = false;
                    }
                    $this->appendCode($text);
                    break;

                case T_WHITESPACE:
                    $this->appendCode($text);
                    break;

                default:
                    $touchedElseStringParenClose = false;
                    $touchedCurlyClose = false;
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }
}
