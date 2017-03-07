<?php

namespace Fmt;

final class StripNewlineAfterCurlyOpen extends AdditionalPass
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
                    list(, $text) = $this->printAndStopAt(T_WHITESPACE);
                    if ($this->hasLn($text)) {
                        $text = substr(strrchr($text, 10), 0);
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

    public function getDescription()
    {
        return 'Strip empty lines after opening curly brace.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
// From
for ($a = 0; $a < 10; $a++){

    if($a){

        // do something
    }
}
// To
for ($a = 0; $a < 10; $a++){
    if($a){
        // do something
    }
}
?>
EOT;
    }
}
