<?php

namespace Fmt;

final class ReturnNull extends AdditionalPass
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
        $this->useCache = true;
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            $this->cache = [];

            if (ST_PARENTHESES_OPEN == $id && $this->leftTokenIs([T_RETURN])) {
                $parenCount = 1;
                $touchedAnotherValidToken = false;
                $stack = $text;
                while (list($index, $token) = each($this->tkns)) {
                    list($id, $text) = $this->getToken($token);
                    $this->ptr = $index;
                    $this->cache = [];
                    if (ST_PARENTHESES_OPEN == $id) {
                        ++$parenCount;
                    }
                    if (ST_PARENTHESES_CLOSE == $id) {
                        --$parenCount;
                    }
                    $stack .= $text;
                    if (0 == $parenCount) {
                        break;
                    }
                    if (
                        !(
                            (T_STRING == $id && strtolower($text) == 'null') ||
                            ST_PARENTHESES_OPEN == $id ||
                            ST_PARENTHESES_CLOSE == $id
                        )
                    ) {
                        $touchedAnotherValidToken = true;
                    }
                }
                if ($touchedAnotherValidToken) {
                    $this->appendCode($stack);
                }
                continue;
            }
            if (T_STRING == $id && strtolower($text) == 'null') {
                list($prevId) = $this->getToken($this->leftUsefulToken());
                list($nextId) = $this->getToken($this->rightUsefulToken());
                if (T_RETURN == $prevId && ST_SEMI_COLON == $nextId) {
                    continue;
                }
            }

            $this->appendCode($text);
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Simplify empty returns.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
function a(){
    return null;
}
?>
to
<?php
function a(){
    return;
}
?>
EOT;
    }
}
