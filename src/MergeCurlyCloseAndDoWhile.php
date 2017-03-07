<?php

namespace Fmt;

final class MergeCurlyCloseAndDoWhile extends FormatterPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_WHILE], $foundTokens[T_DO])) {
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
            switch ($id) {
                case T_WHILE:
                    $str = $text;
                    while (list($index, $token) = each($this->tkns)) {
                        list($id, $text) = $this->getToken($token);
                        $this->ptr = $index;
                        $str .= $text;
                        if (
                            ST_CURLY_OPEN == $id ||
                            ST_COLON == $id ||
                            (ST_SEMI_COLON == $id && (ST_SEMI_COLON == $ptId || ST_CURLY_OPEN == $ptId || T_COMMENT == $ptId || T_DOC_COMMENT == $ptId))
                        ) {
                            $this->appendCode($str);
                            break;
                        } elseif (ST_SEMI_COLON == $id && !(ST_SEMI_COLON == $ptId || ST_CURLY_OPEN == $ptId || T_COMMENT == $ptId || T_DOC_COMMENT == $ptId)) {
                            $this->rtrimAndAppendCode($str);
                            break;
                        }
                    }
                    break;

                case T_WHITESPACE:
                    $this->appendCode($text);
                    break;

                default:
                    $ptId = $id;
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }
}
