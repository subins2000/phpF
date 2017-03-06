<?php

namespace Fmt;

final class AddMissingParentheses extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_NEW])) {
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
                case T_NEW:
                    $this->appendCode($text);
                    list($foundId, $foundText, $touchedLn) = $this->printAndStopAt([
                        ST_PARENTHESES_OPEN,
                        ST_PARENTHESES_CLOSE,
                        T_COMMENT,
                        T_DOC_COMMENT,
                        ST_SEMI_COLON,
                        ST_COMMA,
                        ST_BRACKET_CLOSE,
                    ]);
                    if (ST_PARENTHESES_OPEN == $foundId) {
                        $this->appendCode($foundText);
                        break;
                    }
                    $this->rtrimAndAppendCode('()');
                    if ($touchedLn) {
                        $this->appendCode($this->newLine);
                    }
                    $this->appendCode($foundText);
                    break;
                default:
                    $this->appendCode($text);
            }
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Add extra parentheses in new instantiations.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
$a = new SomeClass;

$a = new SomeClass();
?>
EOT;
    }
}
