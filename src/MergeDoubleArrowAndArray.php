<?php

namespace Fmt;

final class MergeDoubleArrowAndArray extends FormatterPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_ARRAY], $foundTokens[T_DOUBLE_ARROW])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';
        $touchedDoubleArrow = false;
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;

            if (T_DOUBLE_ARROW == $id) {
                $touchedDoubleArrow = true;
                $this->appendCode($text);
                continue;
            }

            if ($touchedDoubleArrow) {
                if (
                    T_WHITESPACE == $id ||
                    T_DOC_COMMENT == $id ||
                    T_COMMENT == $id
                ) {
                    $this->appendCode($text);
                    continue;
                }
                if (T_ARRAY === $id) {
                    $this->rtrimAndAppendCode($text);
                    $touchedDoubleArrow = false;
                    continue;
                }
                $touchedDoubleArrow = false;
            }

            $this->appendCode($text);
        }

        return $this->code;
    }
}
