<?php

namespace Fmt;

final class WordWrap extends AdditionalPass
{
    const ALIGNABLE_WORDWRAP = "\x2 WORDWRAP \x3";

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
        $detectedTab = false;
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;

            $originalText = $text;
            if (T_WHITESPACE == $id) {
                if (!$detectedTab && false !== strpos($text, "\t")) {
                    $detectedTab = true;
                }
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
                $this->appendCode($this->newLine.self::ALIGNABLE_WORDWRAP);
            }

            $this->appendCode($originalText);
        }

        if (false === strpos($this->code, self::ALIGNABLE_WORDWRAP)) {
            return $this->code;
        }

        $lines = explode($this->newLine, $this->code);
        foreach ($lines as $idx => $line) {
            if (false !== strpos($line, self::ALIGNABLE_WORDWRAP)) {
                $line = str_replace(self::ALIGNABLE_WORDWRAP, '', $line);
                $line = str_pad($line, self::$length, ' ', STR_PAD_LEFT);
                if ($detectedTab) {
                    $line = preg_replace('/\G {'.self::$tabSizeInSpace.'}/', "\t", $line);
                }
                $lines[$idx] = $line;
            }
        }

        return implode($this->newLine, $lines);
    }

    public function getDescription()
    {
        return 'Word wrap at 80 columns.';
    }

    public function getExample()
    {
        return '';
    }
}
