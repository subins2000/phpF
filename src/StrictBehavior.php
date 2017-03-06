<?php

namespace Fmt;

final class StrictBehavior extends AdditionalPass
{
    private static $functions = [
        'array_keys' => 3,
        'array_search' => 3,
        'base64_decode' => 2,
        'in_array' => 3,
        'mb_detect_encoding' => 3,
    ];

    public function candidate($source, $foundTokens)
    {
        return true;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;

            if (T_STRING != $id) {
                $this->appendCode($text);
                continue;
            }

            $lcText = strtolower($text);
            $foundKeyword = &self::$functions[$lcText];
            if (!isset($foundKeyword)) {
                $this->appendCode($text);
                continue;
            }

            if ($this->leftUsefulTokenIs([T_DOUBLE_COLON, T_OBJECT_OPERATOR])) {
                $this->appendCode($text);
                continue;
            }

            if (!$this->rightUsefulTokenIs(ST_PARENTHESES_OPEN)) {
                $this->appendCode($text);
                continue;
            }

            $maxParams = $foundKeyword;

            $this->appendCode($text);
            $this->printUntil(ST_PARENTHESES_OPEN);
            $paramCount = $this->printAndStopAtEndOfParamBlock();

            if ($paramCount < $maxParams) {
                for (++$paramCount; $paramCount < $maxParams; ++$paramCount) {
                    $this->appendCode(', null');
                }
                $this->appendCode(', true');
            }
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Activate strict option in array_search, base64_decode, in_array, array_keys, mb_detect_encoding. Danger! This pass leads to behavior change.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
// From
array_search($needle, $haystack);
base64_decode($str);
in_array($needle, $haystack);

array_keys($arr);
mb_detect_encoding($arr);

array_keys($arr, [1]);
mb_detect_encoding($arr, 'UTF8');

// To
array_search($needle, $haystack, true);
base64_decode($str, true);
in_array($needle, $haystack, true);

array_keys($arr, null, true);
mb_detect_encoding($arr, null, true);

array_keys($arr, [1], true);
mb_detect_encoding($arr, 'UTF8', true);
?>
EOT;
    }
}
