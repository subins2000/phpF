<?php

namespace Fmt;

final class NewLineBeforeReturn extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_RETURN])) {
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
                case T_RETURN:
                    if (!$this->leftUsefulTokenIs([ST_CURLY_OPEN, ST_COLON])) {
                        $this->rtrimAndAppendCode($this->newLine.$this->newLine.$text);
                        break;
                    }
                default:
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Add an empty line before T_RETURN.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
// From
function a(){
    $a = 1;
    return $a;
}

// To
function a(){
    $a = 1;

    return $a;
}
?>
EOT;
    }
}
