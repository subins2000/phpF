<?php

namespace Fmt;

final class AlignPHPCode2 extends AdditionalPass
{
    const PLACEHOLDER_STRING = "\x2 CONSTANT_STRING_%d \x3";

    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_INLINE_HTML])) {
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
                case T_OPEN_TAG:
                    list(, $prevText) = $this->getToken($this->leftToken());

                    $prevSpace = substr(strrchr($prevText, $this->newLine), 1);
                    $skipPadLeft = false;
                    if (rtrim($prevSpace) == $prevSpace) {
                        $skipPadLeft = true;
                    }
                    $prevSpace = preg_replace('/[^\s\t]/', ' ', $prevSpace);

                    $placeholders = [];
                    $strings = [];
                    $stack = $text;
                    while (list($index, $token) = each($this->tkns)) {
                        list($id, $text) = $this->getToken($token);
                        $this->ptr = $index;

                        if (T_CONSTANT_ENCAPSED_STRING == $id || T_ENCAPSED_AND_WHITESPACE == $id) {
                            $strings[] = $text;
                            $text = sprintf(self::PLACEHOLDER_STRING, $this->ptr);
                            $placeholders[] = $text;
                        }
                        $stack .= $text;

                        if (T_CLOSE_TAG == $id) {
                            break;
                        }
                    }

                    $tmp = explode($this->newLine, $stack);
                    $lastLine = sizeof($tmp) - 2;
                    foreach ($tmp as $idx => $line) {
                        $before = $prevSpace;
                        if ('' === trim($line)) {
                            continue;
                        }

                        if ($skipPadLeft) {
                            $before = '';
                            $skipPadLeft = false;
                        }

                        $tmp[$idx] = $before.$line;
                    }

                    $stack = implode($this->newLine, $tmp);
                    $stack = str_replace($placeholders, $strings, $stack);

                    $this->code = rtrim($this->code, " \t");
                    $this->appendCode($stack);
                    break;

                default:
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Align PHP code within opening and closing php block.';
    }

    public function getExample()
    {
        return <<<'EOT'
<div>
    <?php
    echo $a;
    ?>
</div>
EOT;
    }
}
