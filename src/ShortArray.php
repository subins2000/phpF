<?php

namespace Fmt;

final class ShortArray extends AdditionalPass
{
    const FOUND_ARRAY = 'array';

    const FOUND_PARENTHESES = 'paren';

    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_ARRAY])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';
        $foundParen = [];
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case T_ARRAY:
                    if ($this->rightTokenIs([ST_PARENTHESES_OPEN])) {
                        $foundParen[] = self::FOUND_ARRAY;
                        $this->printAndStopAt(ST_PARENTHESES_OPEN);
                        $this->appendCode(ST_BRACKET_OPEN);
                        break;
                    }
                case ST_PARENTHESES_OPEN:
                    $foundParen[] = self::FOUND_PARENTHESES;
                    $this->appendCode($text);
                    break;

                case ST_PARENTHESES_CLOSE:
                    $popToken = array_pop($foundParen);
                    if (self::FOUND_ARRAY == $popToken) {
                        $this->appendCode(ST_BRACKET_CLOSE);
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
        return 'Convert old array into new array. (array() -> [])';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
echo array();
?>
to
<?php
echo [];
?>
EOT;
    }
}
