<?php

namespace Fmt;

final class SpaceBetweenMethods extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_FUNCTION])) {
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
                case T_FUNCTION:
                    $this->appendCode($text);
                    $this->printUntil(ST_CURLY_OPEN);
                    $this->printCurlyBlock();
                    if (!$this->rightTokenIs([ST_CURLY_CLOSE, ST_SEMI_COLON, ST_COMMA, ST_PARENTHESES_CLOSE])) {
                        $this->appendCode($this->getCrlf());
                    }
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
        return 'Put space between methods.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
class A {
    function b(){

    }
    function c(){

    }
}
?>
to
<?php
class A {
    function b(){

    }

    function c(){

    }

}
?>
EOT;
    }
}
