<?php

namespace Fmt;

final class TightConcat extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[ST_CONCAT])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';
        $whitespaces = " \t";
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case ST_CONCAT:
                    if (!$this->leftUsefulTokenIs([T_LNUMBER, T_DNUMBER]) && !$this->hasLnBefore()) {
                        $this->code = rtrim($this->code, $whitespaces);
                    }
                    list($nextId, $nextText) = $this->inspectToken(+1);
                    if (T_WHITESPACE == $nextId && !$this->hasln($nextText) && !$this->rightUsefulTokenIs([T_LNUMBER, T_DNUMBER])) {
                        each($this->tkns);
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
        return 'Ensure string concatenation does not have spaces, except when close to numbers.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
// From
$a = 'a' . 'b';
$a = 'a' . 1 . 'b';
// To
$a = 'a'.'b';
$a = 'a'. 1 .'b';
?>
EOT;
    }
}
