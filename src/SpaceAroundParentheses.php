<?php

namespace Fmt;

final class SpaceAroundParentheses extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[ST_PARENTHESES_OPEN]) || isset($foundTokens[ST_PARENTHESES_CLOSE])) {
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
                case ST_PARENTHESES_OPEN:
                    list($prevId) = $this->inspectToken(-1);
                    list($nextId) = $this->inspectToken(+1);

                    $this->appendCode(
                        $this->getSpace(
                            (
                                $this->leftTokenIs(
                                    [
                                        ST_PARENTHESES_OPEN,
                                    ]
                                )
                                && $prevId != T_WHITESPACE
                                && $prevId != T_FUNCTION
                            )
                        )
                        .$text.
                        $this->getSpace(!$this->rightTokenIs([
                            T_WHITESPACE, ST_PARENTHESES_CLOSE,
                        ]))
                    );
                    break;
                case ST_PARENTHESES_CLOSE:
                    list($prevId) = $this->inspectToken(-1);
                    list($nextId) = $this->inspectToken(+1);

                    $this->appendCode(
                        $this->getSpace(
                            (
                                !$this->leftTokenIs(
                                    [
                                        ST_PARENTHESES_OPEN,
                                    ]
                                )
                                &&
                                $prevId != T_WHITESPACE
                            )
                        )
                        .$text
                    );
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
        return 'Add spaces inside parentheses.';
    }

    public function getExample()
    {
        echo '
<?php
// From:
if (true) foo(); foo( $a );

// To:
if ( true ) foo(); foo( $a );
';
    }
}
