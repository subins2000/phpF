<?php

namespace Fmt;

final class EncapsulateNamespaces extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_NAMESPACE])) {
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
                case T_NAMESPACE:
                    if ($this->rightUsefulTokenIs(T_NS_SEPARATOR)) {
                        break;
                    }
                    $this->appendCode($text);
                    list($foundId, $foundText) = $this->printAndStopAt([ST_CURLY_OPEN, ST_SEMI_COLON]);
                    if (ST_CURLY_OPEN == $foundId) {
                        $this->appendCode($foundText);
                        $this->printCurlyBlock();
                    } elseif (ST_SEMI_COLON == $foundId) {
                        $this->appendCode(ST_CURLY_OPEN);
                        list($foundId, $foundText) = $this->printAndStopAt([T_NAMESPACE, T_CLOSE_TAG]);
                        if (T_CLOSE_TAG == $foundId) {
                            return $source;
                        }
                        $this->appendCode($this->getCrlf().ST_CURLY_CLOSE.$this->getCrlf());
                        prev($this->tkns);
                        continue;
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
        return 'Encapsulate namespaces with curly braces';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
namespace NS1;
class A {
}
?>
to
<?php
namespace NS1 {
    class A {
    }
}
?>
EOT;
    }
}
