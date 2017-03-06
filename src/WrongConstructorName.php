<?php

namespace Fmt;

final class WrongConstructorName extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_NAMESPACE]) || isset($foundTokens[T_CLASS])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';
        $touchedNamespace = false;
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case T_NAMESPACE:
                    if (!$this->rightUsefulTokenIs(T_NS_SEPARATOR)) {
                        $touchedNamespace = true;
                    }
                    $this->appendCode($text);
                    break;
                case T_CLASS:
                    $this->appendCode($text);
                    if ($this->leftUsefulTokenIs([T_DOUBLE_COLON])) {
                        break;
                    }
                    if ($touchedNamespace) {
                        break;
                    }
                    $classLocalName = '';
                    while (list($index, $token) = each($this->tkns)) {
                        list($id, $text) = $this->getToken($token);
                        $this->ptr = $index;
                        $this->appendCode($text);
                        if (T_STRING == $id) {
                            $classLocalName = strtolower($text);
                        }
                        if (T_EXTENDS == $id || T_IMPLEMENTS == $id || ST_CURLY_OPEN == $id) {
                            break;
                        }
                    }
                    $count = 1;
                    while (list($index, $token) = each($this->tkns)) {
                        list($id, $text) = $this->getToken($token);
                        $this->ptr = $index;

                        if (T_STRING == $id && $this->leftUsefulTokenIs([T_FUNCTION]) && strtolower($text) == $classLocalName) {
                            $text = '__construct';
                        }
                        $this->appendCode($text);

                        if (ST_CURLY_OPEN == $id) {
                            ++$count;
                        }
                        if (ST_CURLY_CLOSE == $id) {
                            --$count;
                        }
                        if (0 == $count) {
                            break;
                        }
                    }
                    break;
                default:
                    $this->appendCode($text);
            }
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Update old constructor names into new ones. http://php.net/manual/en/language.oop5.decon.php';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
class A {
    function A(){

    }
}
?>
to
<?php
class A {
    function __construct(){

    }
}
?>
EOT;
    }
}
