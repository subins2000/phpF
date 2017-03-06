<?php

namespace Fmt;

final class PSR2IndentWithSpace extends FormatterPass
{
    private $size = 4;

    public function __construct($size = null)
    {
        if ($size > 0) {
            $this->size = $size;
        }
    }

    public function candidate($source, $foundTokens)
    {
        return true;
    }

    public function format($source)
    {
        $spaces = str_repeat(' ', (int) $this->size);
        $this->tkns = token_get_all($source);
        $this->code = '';
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case T_COMMENT:
                case T_DOC_COMMENT:
                case T_WHITESPACE:
                    $this->appendCode(str_replace($this->indentChar, $spaces, $text));
                    break;
                default:
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }
}
