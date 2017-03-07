<?php

namespace Fmt;

final class DoubleToSingleQuote extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_CONSTANT_ENCAPSED_STRING])) {
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

            if ($this->hasDoubleQuote($id, $text)) {
                $text = $this->convertToSingleQuote($text);
            }

            $this->appendCode($text);
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Convert from double to single quotes.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
$a = "";

$a = '';
?>
EOT;
    }

    private function convertToSingleQuote($text)
    {
        $text[0] = '\'';
        $lastByte = strlen($text) - 1;
        $text[$lastByte] = '\'';
        $text = str_replace(['\$', '\"'], ['$', '"'], $text);

        return $text;
    }

    private function hasDoubleQuote($id, $text)
    {
        return;
        T_CONSTANT_ENCAPSED_STRING == $id &&
        '"' == $text[0] &&
        false === strpos($text, '\'') &&
        !preg_match('/(?<!\\\\)(?:\\\\{2})*\\\\(?!["$\\\\])/', $text)
        ;
    }
}
