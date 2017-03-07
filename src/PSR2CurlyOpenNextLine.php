<?php

namespace Fmt;

final class PSR2CurlyOpenNextLine extends FormatterPass
{
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
            switch ($id) {
                case T_START_HEREDOC:
                    $this->appendCode($text);
                    $this->printUntil(T_END_HEREDOC);
                    break;
                case ST_QUOTE:
                    $this->appendCode($text);
                    $this->printUntilTheEndOfString();
                    break;
                case T_INTERFACE:
                case T_TRAIT:
                case T_CLASS:
                    $this->appendCode($text);
                    if ($this->leftUsefulTokenIs(T_DOUBLE_COLON)) {
                        break;
                    }
                    while (list($index, $token) = each($this->tkns)) {
                        list($id, $text) = $this->getToken($token);
                        $this->ptr = $index;
                        if (ST_CURLY_OPEN === $id) {
                            $this->appendCode($this->getCrlfIndent());
                            prev($this->tkns);
                            break;
                        }
                        $this->appendCode($text);
                    }
                    break;
                case T_FUNCTION:
                    if (!$this->leftTokenIs([T_DOUBLE_ARROW, T_RETURN, ST_EQUAL, ST_PARENTHESES_OPEN, ST_COMMA]) && $this->rightUsefulTokenIs([T_STRING, ST_REFERENCE])) {
                        $this->appendCode($text);
                        $touchedLn = false;
                        while (list($index, $token) = each($this->tkns)) {
                            list($id, $text) = $this->getToken($token);
                            $this->ptr = $index;
                            if (T_WHITESPACE == $id && $this->hasLn($text)) {
                                $touchedLn = true;
                            }
                            if (ST_CURLY_OPEN === $id && !$touchedLn) {
                                $this->appendCode($this->getCrlfIndent());
                                prev($this->tkns);
                                break;
                            } elseif (ST_CURLY_OPEN === $id) {
                                prev($this->tkns);
                                break;
                            } elseif (ST_SEMI_COLON === $id) {
                                $this->appendCode($text);
                                break;
                            }
                            $this->appendCode($text);
                        }
                        break;
                    }
                    $this->appendCode($text);
                    break;
                case ST_CURLY_OPEN:
                    $this->appendCode($text);
                    $this->setIndent(+1);
                    break;
                case ST_CURLY_CLOSE:
                    $this->setIndent(-1);
                    $this->appendCode($text);
                    break;
                default:
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }
}
