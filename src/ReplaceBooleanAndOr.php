<?php

namespace Fmt;

final class ReplaceBooleanAndOr extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_LOGICAL_AND]) || isset($foundTokens[T_LOGICAL_OR])) {
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

            if (T_LOGICAL_AND == $id) {
                $text = '&&';
            } elseif (T_LOGICAL_OR == $id) {
                $text = '||';
            }

            $this->appendCode($text);
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Convert from "and"/"or" to "&&"/"||". Danger! This pass leads to behavior change.';
    }

    public function getExample()
    {
        return <<<'EOT'
if ($a and $b or $c) {...}

if ($a && $b || $c) {...}
EOT;
    }
}
