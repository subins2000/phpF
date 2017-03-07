<?php

namespace Fmt;

final class SmartLnAfterCurlyOpen extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[ST_CURLY_OPEN])) {
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
                case ST_CURLY_OPEN:
                    $this->appendCode($text);
                    $curlyCount = 1;
                    $stack = '';
                    $foundLineBreak = false;
                    $hasLnAfter = $this->hasLnAfter();
                    while (list($index, $token) = each($this->tkns)) {
                        list($id, $text) = $this->getToken($token);
                        $this->ptr = $index;
                        $stack .= $text;
                        if (T_START_HEREDOC == $id) {
                            $stack .= $this->walkAndAccumulateUntil($this->tkns, T_END_HEREDOC);
                            continue;
                        }
                        if (ST_QUOTE == $id) {
                            $stack .= $this->walkAndAccumulateUntil($this->tkns, ST_QUOTE);
                            continue;
                        }
                        if (ST_CURLY_OPEN == $id) {
                            ++$curlyCount;
                        }
                        if (ST_CURLY_CLOSE == $id) {
                            --$curlyCount;
                        }
                        if (T_WHITESPACE === $id && $this->hasLn($text)) {
                            $foundLineBreak = true;
                            break;
                        }
                        if (0 == $curlyCount) {
                            break;
                        }
                    }
                    if ($foundLineBreak && !$hasLnAfter) {
                        $this->appendCode($this->newLine);
                    }
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
        return 'Add line break when implicit curly block is added.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
if($a) echo array();
?>
to
<?php
if($a) {
    echo array();
}
?>
EOT;
    }
}
