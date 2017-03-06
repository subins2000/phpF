<?php

namespace Fmt;

final class PSR2LnAfterNamespace extends FormatterPass
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
                        $this->appendCode($text);
                        break;
                    }
                    if ($this->leftTokenIs(ST_CURLY_CLOSE)) {
                        $this->appendCode($this->getCrlf());
                    }
                    $this->appendCode($text);
                    while (list($index, $token) = each($this->tkns)) {
                        list($id, $text) = $this->getToken($token);
                        $this->ptr = $index;
                        $this->appendCode($text);
                        if (ST_SEMI_COLON === $id) {
                            list(, $text) = $this->inspectToken();
                            if (1 === substr_count($text, $this->newLine)) {
                                $this->appendCode($this->newLine);
                            }
                            break;
                        } elseif (ST_CURLY_OPEN === $id) {
                            break;
                        }
                    }
                    break;
                default:
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }
}
