<?php

namespace Fmt;

final class NormalizeIsNotEquals extends FormatterPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_IS_NOT_EQUAL])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;

            if (T_IS_NOT_EQUAL == $id) {
                $text = str_replace('<>', '!=', $text).$this->getSpace();
            }
            $this->appendCode($text);
        }

        return $this->code;
    }
}
