<?php

namespace Fmt;

final class NoSpaceAfterPHPDocBlocks extends FormatterPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_DOC_COMMENT])) {
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
                case T_WHITESPACE:
                    if ($this->hasLn($text) && $this->leftTokenIs(T_DOC_COMMENT)) {
                        $text = substr(strrchr($text, 10), 0);
                        $this->appendCode($text);
                        break;
                    }
                default:
                    $this->appendCode($text);
            }
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Remove empty lines after PHPDoc blocks.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
/**
 * @param int $myInt
 */

function a($myInt){
}

/**
 * @param int $myInt
 */
function a($myInt){
}
?>
EOT;
    }
}
