<?php

namespace Fmt;

final class AlignConstVisibilityEquals extends AdditionalPass
{
    const ALIGNABLE_EQUAL = "\x2 EQUAL%d \x3";

    const OPEN_TAG = "<?php /*\x2 EQUAL OPEN TAG\x3*/";

    public function candidate($source, $foundTokens)
    {
        return true;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';

        $parenCount = 0;
        $bracketCount = 0;
        $contextCounter = 0;
        $touchedVisibilityConst = false;

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case T_PUBLIC:
                case T_PRIVATE:
                case T_PROTECTED:
                case T_CONST:
                    $touchedVisibilityConst = true;
                    $this->appendCode($text);
                    break;

                case ST_SEMI_COLON:
                    $touchedVisibilityConst = false;
                    $this->appendCode($text);
                    break;

                case T_FUNCTION:
                    ++$contextCounter;
                    $this->appendCode($text);
                    break;

                case ST_CURLY_OPEN:
                    $this->appendCode($text);
                    $block = $this->walkAndAccumulateCurlyBlock($this->tkns);
                    $aligner = new self();
                    $this->appendCode(
                        str_replace(self::OPEN_TAG, '', $aligner->format(self::OPEN_TAG.$block))
                    );
                    break;

                case ST_PARENTHESES_OPEN:
                    ++$parenCount;
                    $this->appendCode($text);
                    break;
                case ST_PARENTHESES_CLOSE:
                    --$parenCount;
                    $this->appendCode($text);
                    break;
                case ST_BRACKET_OPEN:
                    ++$bracketCount;
                    $this->appendCode($text);
                    break;
                case ST_BRACKET_CLOSE:
                    --$bracketCount;
                    $this->appendCode($text);
                    break;
                case ST_EQUAL:
                    if ($touchedVisibilityConst && !$parenCount && !$bracketCount) {
                        $this->appendCode(sprintf(self::ALIGNABLE_EQUAL, $contextCounter).$text);
                        break;
                    }

                default:
                    $this->appendCode($text);
                    break;
            }
        }

        $this->alignPlaceholders(self::ALIGNABLE_EQUAL, $contextCounter);

        return $this->code;
    }

    public function getDescription()
    {
        return 'Vertically align "=" of visibility and const blocks.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php

class A {
    public $a = 1;
    public $bb = 22;
    public $ccc = 333;
}

class A {
    public $a   = 1;
    public $bb  = 22;
    public $ccc = 333;
}
?>
EOT;
    }
}
