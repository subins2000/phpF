<?php

namespace Fmt;

final class MergeNamespaceWithOpenTag extends AdditionalPass
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
                    if ($this->leftTokenIs(T_OPEN_TAG) && !$this->rightUsefulTokenIs(T_NS_SEPARATOR)) {
                        $this->rtrimAndAppendCode($this->newLine.$text);
                        break 2;
                    }

                default:
                    $this->appendCode($text);
                    break;
            }
        }
        while (list(, $token) = each($this->tkns)) {
            list(, $text) = $this->getToken($token);
            $this->appendCode($text);
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Ensure there is no more than one linebreak before namespace';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php

namespace A;
?>
to
<?php
namespace A;
?>
EOT;
    }
}
