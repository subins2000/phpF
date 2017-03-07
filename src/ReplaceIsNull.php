<?php

namespace Fmt;

final class ReplaceIsNull extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        return true;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';
        $this->useCache = true;
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            $this->cache = [];

            if (T_STRING == $id && 'is_null' == strtolower($text) && !$this->leftUsefulTokenIs([T_OBJECT_OPERATOR, T_DOUBLE_COLON])) {
                $this->appendCode('null');
                $this->printAndStopAt(ST_PARENTHESES_OPEN);
                $this->appendCode('===');
                $this->printAndStopAt(ST_PARENTHESES_CLOSE);
                continue;
            }

            $this->appendCode($text);
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Replace is_null($a) with null === $a.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
is_null($a);
?>
to
<?php
null === $a;
?>
EOT;
    }
}
