<?php

namespace Fmt;

final class NormalizeLnAndLtrimLines extends FormatterPass
{
    public function candidate($source, $foundTokens)
    {
        return true;
    }

    public function format($source)
    {
        $source = str_replace(["\r\n", "\n\r", "\r", "\n"], $this->newLine, $source);
        $source = preg_replace('/\h+$/mu', '', $source);

        $this->tkns = token_get_all($source);
        $this->code = '';
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case T_INLINE_HTML:
                    $this->appendCode($text);
                    break;
                case ST_QUOTE:
                    $this->appendCode($text);
                    $this->printUntilTheEndOfString();
                    break;
                case T_START_HEREDOC:
                    $this->appendCode($text);
                    $this->printUntil(T_END_HEREDOC);
                    break;

                case T_COMMENT:
                case T_DOC_COMMENT:
                    list($prevId, $prevText) = $this->inspectToken(-1);

                    if (T_WHITESPACE === $prevId && ("\n" === $prevText || "\n\n" == substr($prevText, -2, 2))) {
                        $this->appendCode(LeftAlignComment::NON_INDENTABLE_COMMENT);
                    }

                    $lines = explode($this->newLine, $text);
                    $newText = '';
                    foreach ($lines as $v) {
                        $v = ltrim($v);
                        if ('*' === substr($v, 0, 1)) {
                            $v = ' '.$v;
                        }
                        $newText .= $this->newLine.$v;
                    }

                    $this->appendCode(ltrim($newText));
                    break;

                case T_CONSTANT_ENCAPSED_STRING:
                    $this->appendCode($text);
                    break;
                default:
                    if ($this->hasLn($text)) {
                        $trailingNewLine = $this->substrCountTrailing($text, $this->newLine);
                        if ($trailingNewLine > 0) {
                            $text = trim($text).str_repeat($this->newLine, $trailingNewLine);
                        }
                    }
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }
}
