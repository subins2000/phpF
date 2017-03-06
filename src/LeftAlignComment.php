<?php

namespace Fmt;

final class LeftAlignComment extends FormatterPass
{
    const NON_INDENTABLE_COMMENT = "/*\x2 COMMENT \x3*/";

    public function candidate($source, $foundTokens)
    {
        if (
            isset($foundTokens[T_COMMENT]) ||
            isset($foundTokens[T_DOC_COMMENT])
        ) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';
        $touchedNonIndentableComment = false;

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            if (self::NON_INDENTABLE_COMMENT === $text) {
                $touchedNonIndentableComment = true;
                continue;
            }
            switch ($id) {
                case T_COMMENT:
                case T_DOC_COMMENT:
                    if ($touchedNonIndentableComment) {
                        $touchedNonIndentableComment = false;
                        $lines = explode($this->newLine, $text);
                        $lines = array_map(function ($v) {
                            $v = ltrim($v);
                            if ('*' === substr($v, 0, 1)) {
                                $v = ' '.$v;
                            }

                            return $v;
                        }, $lines);
                        $this->appendCode(implode($this->newLine, $lines));
                        break;
                    }
                    $this->appendCode($text);
                    break;

                case T_WHITESPACE:
                    list(, $nextText) = $this->inspectToken(1);
                    if (self::NON_INDENTABLE_COMMENT === $nextText && substr_count($text, "\n") >= 2) {
                        $text = substr($text, 0, strrpos($text, "\n") + 1);
                        $this->appendCode($text);
                        break;
                    } elseif (self::NON_INDENTABLE_COMMENT === $nextText && substr_count($text, "\n") === 1) {
                        $text = substr($text, 0, strrpos($text, "\n") + 1);
                        $this->appendCode($text);
                        break;
                    }
                    $this->appendCode($text);
                    break;

                default:
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }
}
