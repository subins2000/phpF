<?php

namespace Fmt;

final class AlignTypehint extends AdditionalPass
{
    const ALIGNABLE_TYPEHINT = "\x2 TYPEHINT%d \x3";

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

        $contextCounter = 0;

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case T_FUNCTION:
                    $this->appendCode($text);
                    $this->printUntil(ST_PARENTHESES_OPEN);
                    do {
                        list($id, $text) = $this->printAndStopAt([T_VARIABLE, ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE]);
                        if (ST_PARENTHESES_OPEN == $id) {
                            $this->appendCode($text);
                            $this->printBlock(ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE);
                            continue;
                        }
                        if (ST_PARENTHESES_CLOSE == $id) {
                            $this->appendCode($text);
                            break;
                        }
                        $this->appendCode(sprintf(self::ALIGNABLE_TYPEHINT, $contextCounter).$text);
                    } while (true);
                    ++$contextCounter;
                    break;

                default:
                    $this->appendCode($text);
                    break;
            }
        }

        $this->alignPlaceholders(self::ALIGNABLE_TYPEHINT, $contextCounter);

        return $this->code;
    }

    public function getDescription()
    {
        return 'Vertically align function type hints.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
//From:
function a(
    TypeA $a,
    TypeBB $bb,
    TypeCCC $ccc = array(),
    TypeDDDD $dddd,
    TypeEEEEE $eeeee
){
    noop();
}


//To:
function a(
    TypeA     $a,
    TypeBB    $bb,
    TypeCCC   $ccc = array(),
    TypeDDDD  $dddd,
    TypeEEEEE $eeeee
){
    noop();
}


?>
EOT;
    }
}
