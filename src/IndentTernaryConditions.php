<?php

namespace Fmt;

final class IndentTernaryConditions extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[ST_QUESTION])) {
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
                case ST_COLON:
                case ST_QUESTION:
                    if ($this->hasLnBefore()) {
                        $this->appendCode($this->getIndent(+1));
                    }
                    $this->appendCode($text);
                    break;
                default:
                    $this->appendCode($text);
            }
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Applies indentation to ternary conditions.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
$a = ($b)
? $c
: $d
;
?>
to
<?php
$a = ($b)
    ? $c
    : $d
;
?>
EOT;
    }
}
