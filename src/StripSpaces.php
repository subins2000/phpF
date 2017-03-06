<?php

namespace Fmt;

final class StripSpaces extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_WHITESPACE]) || isset($foundTokens[T_COMMENT])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;

            if (T_WHITESPACE == $id || T_COMMENT == $id) {
                continue;
            }

            $this->appendCode($text);
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Remove all empty spaces';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
// From
$a = [$a, $b];
$b = array($b, $c);

// To
$a=[$a,$b];$b=array($b,$c);
?>
EOT;
    }
}
