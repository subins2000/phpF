<?php

namespace Fmt;

class ClassToSelf extends AdditionalPass
{
    const PLACEHOLDER = 'self';

    public function candidate($source, $foundTokens)
    {
        if (
            isset($foundTokens[T_CLASS]) ||
            isset($foundTokens[T_INTERFACE]) ||
            isset($foundTokens[T_TRAIT])
        ) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';
        $tknsLen = sizeof($this->tkns);

        $touchedDoubleColon = false;
        for ($ptr = 0; $ptr < $tknsLen; ++$ptr) {
            $token = $this->tkns[$ptr];
            list($id) = $this->getToken($token);

            if (T_DOUBLE_COLON == $id) {
                $touchedDoubleColon = true;
            }
            if ($touchedDoubleColon && T_CLASS == $id) {
                $touchedDoubleColon = false;
                break;
            }
            if (
                T_CLASS == $id ||
                T_INTERFACE == $id ||
                T_TRAIT == $id
            ) {
                $this->refWalkUsefulUntil($this->tkns, $ptr, T_STRING);
                list(, $name) = $this->getToken($this->tkns[$ptr]);

                $this->refWalkUsefulUntil($this->tkns, $ptr, ST_CURLY_OPEN);
                $start = $ptr;
                $this->refWalkCurlyBlock($this->tkns, $ptr);
                $end = ++$ptr;

                $this->convertToPlaceholder($name, $start, $end);
                break;
            }
        }

        return $this->render();
    }

    public function getDescription()
    {
        return '"self" is preferred within class, trait or interface.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
// From
class A {
    const constant = 1;
    function b(){
        A::constant;
    }
}

// To
class A {
    const constant = 1;
    function b(){
        self::constant;
    }
}
?>
EOT;
    }

    private function convertToPlaceholder($name, $start, $end)
    {
        for ($i = $start; $i < $end; ++$i) {
            list($id, $text) = $this->getToken($this->tkns[$i]);

            if (T_FUNCTION == $id && $this->rightTokenSubsetIsAtIdx($this->tkns, $i, [ST_REFERENCE, ST_PARENTHESES_OPEN])) {
                $this->refWalkUsefulUntil($this->tkns, $i, ST_CURLY_OPEN);
                $this->refWalkCurlyBlock($this->tkns, $i);
                continue;
            }

            if (
                !(T_STRING == $id && strtolower($text) == strtolower($name)) ||
                $this->leftTokenSubsetIsAtIdx($this->tkns, $i, T_NS_SEPARATOR) ||
                $this->rightTokenSubsetIsAtIdx($this->tkns, $i, T_NS_SEPARATOR)
            ) {
                continue;
            }

            if (
                $this->leftTokenSubsetIsAtIdx($this->tkns, $i, [T_INSTANCEOF, T_NEW]) ||
                $this->rightTokenSubsetIsAtIdx($this->tkns, $i, T_DOUBLE_COLON)
            ) {
                $this->tkns[$i] = [T_STRING, self::PLACEHOLDER];
            }
        }
    }
}
