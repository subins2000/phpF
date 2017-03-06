<?php

namespace Fmt;

final class EliminateDuplicatedEmptyLines extends FormatterPass
{
    const EMPTY_LINE = "\x2 EMPTYLINE \x3";

    public function candidate($source, $foundTokens)
    {
        return true;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case T_WHITESPACE:
                case T_COMMENT:
                case T_OPEN_TAG:
                    if ($this->hasLn($text) || (T_COMMENT == $id && '//' == substr($text, 0, 2))) {
                        $text = str_replace($this->newLine, self::EMPTY_LINE.$this->newLine, $text);
                    }

                    $this->appendCode($text);
                    break;
                default:
                    $this->appendCode($text);
                    break;
            }
        }

        $ret = $this->code;
        $count = 0;
        do {
            $ret = str_replace(
                self::EMPTY_LINE.$this->newLine.self::EMPTY_LINE.$this->newLine.self::EMPTY_LINE.$this->newLine,
                self::EMPTY_LINE.$this->newLine.self::EMPTY_LINE.$this->newLine,
                $ret,
                $count
            );
        } while ($count > 0);
        $ret = str_replace(self::EMPTY_LINE, '', $ret);

        list($id) = $this->getToken(end($this->tkns));
        if (T_WHITESPACE === $id) {
            $ret = rtrim($ret).$this->newLine;
        }

        return $ret;
    }
}
