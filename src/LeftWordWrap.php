<?php

namespace Fmt;

final class LeftWordWrap extends AdditionalPass
{
    const PLACEHOLDER_WORDWRAP = "\x2 WORDWRAP \x3";

    private static $length = 80;

    private static $tabSizeInSpace = 8;

    public function candidate($source, $foundTokens)
    {
        return true;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';

        $currentLineLength = 0;
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;

            $originalText = $text;
            if (T_WHITESPACE == $id) {
                $text = str_replace(
                    $this->indentChar,
                    str_repeat(' ', self::$tabSizeInSpace),
                    $text
                );
            }
            $textLen = strlen($text);

            $currentLineLength += $textLen;

            if ($this->hasLn($text)) {
                $currentLineLength = $textLen - strrpos($text, $this->newLine);
            }

            if ($currentLineLength > self::$length) {
                $currentLineLength = $textLen - strrpos($text, $this->newLine);
                $this->code = str_replace(self::PLACEHOLDER_WORDWRAP, $this->newLine, $this->code);
            }

            if (T_OBJECT_OPERATOR == $id || T_WHITESPACE == $id) {
                $this->code = str_replace(self::PLACEHOLDER_WORDWRAP, '', $this->code);
                $this->appendCode(self::PLACEHOLDER_WORDWRAP);
            }
            $this->appendCode($originalText);
        }

        $this->code = str_replace(self::PLACEHOLDER_WORDWRAP, '', $this->code);

        return $this->code;
    }

    public function getDescription()
    {
        return 'Word wrap at 80 columns - left justify.';
    }

    public function getExample()
    {
        return '';
    }
}
