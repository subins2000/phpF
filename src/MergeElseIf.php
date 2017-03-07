<?php

namespace Fmt;

final class MergeElseIf extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_ELSE]) || isset($foundTokens[T_ELSEIF])) {
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
                case T_IF:
                    if ($this->leftTokenIs([T_ELSE]) && !$this->leftTokenIs([T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO])) {
                        $this->rtrimAndAppendCode($text);
                        break;
                    }
                    $this->appendCode($text);
                    break;
                case T_ELSEIF:
                    $this->appendCode('elseif');
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
        return 'Merge if with else.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
if($a){

} else if($b) {

}

if($a){

} elseif($b) {

}
?>
EOT;
    }
}
