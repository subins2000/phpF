<?php

namespace Fmt;

final class EchoToPrint extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_ECHO])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        while (list($index, $token) = each($this->tkns)) {
            list($id) = $this->getToken($token);
            $this->ptr = $index;

            if (T_ECHO == $id) {
                $start = $index;
                $end = $this->walkUsefulRightUntil($this->tkns, $index, [ST_SEMI_COLON, T_CLOSE_TAG]);
                $convert = true;
                for ($i = $start; $i < $end; ++$i) {
                    $tkn = $this->tkns[$i];
                    if (ST_PARENTHESES_OPEN === $tkn[0]) {
                        $this->refWalkBlock($tkns, $ptr, ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE);
                    } elseif (ST_BRACKET_OPEN === $tkn[0]) {
                        $this->refWalkBlock($tkns, $ptr, ST_BRACKET_OPEN, ST_BRACKET_CLOSE);
                    } elseif (ST_COMMA === $tkn[0]) {
                        $convert = false;
                        break;
                    }
                }
                if ($convert) {
                    $this->tkns[$start] = [T_PRINT, 'print'];
                }
            }
        }

        return $this->render();
    }

    public function getDescription()
    {
        return 'Convert from T_ECHO to print.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
echo 1;

print 2;
?>
EOT;
    }
}
