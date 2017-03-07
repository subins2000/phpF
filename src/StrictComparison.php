<?php

namespace Fmt;

final class StrictComparison extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_IS_EQUAL]) || isset($foundTokens[T_IS_NOT_EQUAL])) {
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

            if (T_IS_EQUAL == $id) {
                $text = '===';
            } elseif (T_IS_NOT_EQUAL == $id) {
                $text = '!==';
            }

            $this->appendCode($text);
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'All comparisons are converted to strict. Danger! This pass leads to behavior change.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
// From
if($a == $b){}
if($a != $b){}

// To
if($a === $b){}
if($a !== $b){}
?>
EOT;
    }
}
