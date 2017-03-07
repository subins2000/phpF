<?php

namespace Fmt;

final class AlignGroupDoubleArrow extends AlignDoubleArrow
{
    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';

        $levelCounter = 0;
        $levelEntranceCounter = [];
        $contextCounter = [];
        $maxContextCounter = [];

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case ST_COMMA:
                    if (!$this->hasLnAfter() && !$this->hasLnRightToken()) {
                        if (!isset($levelEntranceCounter[$levelCounter])) {
                            $levelEntranceCounter[$levelCounter] = 0;
                        }
                        if (!isset($contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]])) {
                            $contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
                            $maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
                        }
                        ++$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]];
                        $maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = max($maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]], $contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]);
                    } elseif ($contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] > 1) {
                        $contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 1;
                    }
                    $this->appendCode($text);
                    break;

                case T_DOUBLE_ARROW:
                    $this->appendCode(
                        sprintf(
                            self::ALIGNABLE_EQUAL,
                            $levelCounter,
                            $levelEntranceCounter[$levelCounter],
                            $contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]
                        ).$text
                    );
                    break;

                case T_WHITESPACE:
                    if ($this->hasLn($text) && substr_count($text, $this->newLine) >= 2) {
                        ++$levelCounter;
                        if (!isset($levelEntranceCounter[$levelCounter])) {
                            $levelEntranceCounter[$levelCounter] = 0;
                        }
                        ++$levelEntranceCounter[$levelCounter];
                        if (!isset($contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]])) {
                            $contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
                            $maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
                        }
                        ++$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]];
                        $maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = max($maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]], $contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]);
                    }
                    $this->appendCode($text);
                    break;

                case ST_PARENTHESES_OPEN:
                case ST_BRACKET_OPEN:
                    ++$levelCounter;
                    if (!isset($levelEntranceCounter[$levelCounter])) {
                        $levelEntranceCounter[$levelCounter] = 0;
                    }
                    ++$levelEntranceCounter[$levelCounter];
                    if (!isset($contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]])) {
                        $contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
                        $maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
                    }
                    ++$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]];
                    $maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = max($maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]], $contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]);

                    $this->appendCode($text);
                    break;

                case ST_PARENTHESES_CLOSE:
                case ST_BRACKET_CLOSE:
                    --$levelCounter;
                    $this->appendCode($text);
                    break;

                default:
                    $this->appendCode($text);
                    break;
            }
        }
        $this->align($maxContextCounter);

        return $this->code;
    }

    public function getDescription()
    {
        return 'Vertically align T_DOUBLE_ARROW (=>) by line groups.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
$a = [
    1 => 1,
    22 => 22,

    333 => 333,
    4444 => 4444,
];

$a = [
    1  => 1,
    22 => 22,

    333  => 333,
    4444 => 4444,
];
?>
EOT;
    }
}
