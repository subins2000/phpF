<?php

namespace Fmt;

final class UpgradeToPreg extends AdditionalPass
{
    private static $conversionTable = [
        'ereg' => [
            'to' => 'preg_match',
            'modifier' => '',
        ],
        'ereg_replace' => [
            'to' => 'preg_replace',
            'modifier' => '',
        ],
        'eregi' => [
            'to' => 'preg_match',
            'modifier' => 'i',
        ],
        'eregi_replace' => [
            'to' => 'preg_replace',
            'modifier' => 'i',
        ],
        'split' => [
            'to' => 'preg_split',
            'modifier' => '',
        ],
        'spliti' => [
            'to' => 'preg_split',
            'modifier' => 'i',
        ],
    ];

    private static $delimiters = ['/', '#', '!'];

    public function candidate($source, $foundTokens)
    {
        return;
        false !== stripos($source, 'ereg') ||
        false !== stripos($source, 'split')
        ;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            $this->tkns[$this->ptr] = [$id, $text];

            if (T_STRING != $id) {
                continue;
            }

            if ($this->leftUsefulTokenIs([T_OBJECT_OPERATOR, T_DOUBLE_COLON])) {
                continue;
            }

            $lctext = strtolower($text);
            if (T_STRING == $id && !isset(self::$conversionTable[$lctext])) {
                continue;
            }

            $funcIdx = $this->ptr;

            $this->walkUntil(ST_PARENTHESES_OPEN);
            if (!$this->rightUsefulTokenIs(T_CONSTANT_ENCAPSED_STRING)) {
                continue;
            }
            $this->walkUntil(T_CONSTANT_ENCAPSED_STRING);

            $patternIdx = $this->ptr;

            list(, $countTokens) = $this->peekAndCountUntilAny($this->tkns, $this->ptr, [ST_COMMA, ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE]);
            unset($countTokens[T_CONSTANT_ENCAPSED_STRING], $countTokens[ST_COMMA], $countTokens[ST_PARENTHESES_CLOSE]);
            if (sizeof($countTokens) > 0) {
                continue;
            }

            list(, $pattern) = $this->getToken($this->tkns[$patternIdx]);
            $patternQuote = substr($pattern, 0, 1);
            $pattern = substr($pattern, 1, -1);
            $delim = $this->detectRegexDelim($pattern);
            $newPattern = $delim.addcslashes($pattern, $delim).$delim.'D'.self::$conversionTable[$lctext]['modifier'];

            if (false === @preg_match($newPattern, '')) {
                continue;
            }

            $this->tkns[$funcIdx][1] = self::$conversionTable[$lctext]['to'];
            $this->tkns[$patternIdx][1] = $patternQuote.$newPattern.$patternQuote;
        }

        return $this->render($this->tkns);
    }

    public function getDescription()
    {
        return 'Upgrade ereg_* calls to preg_*';
    }

    public function getExample()
    {
        return '<?php
// From:
$var = ereg("[A-Z]", $var);
$var = eregi_replace("[A-Z]", "", $var)
$var = spliti("[A-Z]", $var);
// To:
$var = preg_match("/[A-Z]/Di", $var);
$var = preg_replace("/[A-Z]/Di", "", $var);
$var = preg_split("/[A-Z]/Di", $var);
';
    }

    private function detectRegexDelim($pattern)
    {
        $delim = [];
        foreach (self::$delimiters as $k => $d) {
            if (false === strpos($pattern, $d)) {
                return $d;
            }

            $delim[$d] = [substr_count($pattern, $d), $k];
        }

        uasort($delim, function ($a, $b) {
            if ($a[0] === $b[0]) {
                if ($a[1] === $b[1]) {
                    return 0;
                } elseif ($a[1] < $b[1]) {
                    return -1;
                }

                return 1;
            }

            if ($a[0] < $b[0]) {
                return -1;
            }

            return 1;
        });

        return key($delim);
    }
}
