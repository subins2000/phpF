<?php

namespace Fmt;

final class ExtraCommaInArray extends FormatterPass
{
    const ST_SHORT_ARRAY_OPEN = 'SHORT_ARRAY_OPEN';

    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_ARRAY]) || isset($foundTokens[ST_BRACKET_OPEN])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);

        $contextStack = [];
        $touchedBracketOpen = false;

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case ST_BRACKET_OPEN:
                    $touchedBracketOpen = true;
                    $found = ST_BRACKET_OPEN;
                    if ($this->isShortArray()) {
                        $found = self::ST_SHORT_ARRAY_OPEN;
                    }
                    $contextStack[] = $found;
                    break;

                case ST_BRACKET_CLOSE:
                    if (isset($contextStack[0]) && !$this->leftTokenIs(ST_BRACKET_OPEN)) {
                        if (self::ST_SHORT_ARRAY_OPEN == end($contextStack) && ($this->hasLnLeftToken() || $this->hasLnBefore()) && !$this->leftUsefulTokenIs(ST_COMMA)) {
                            $prevTokenIdx = $this->leftUsefulTokenIdx();
                            list($tknId, $tknText) = $this->getToken($this->tkns[$prevTokenIdx]);
                            if (T_END_HEREDOC != $tknId && ST_BRACKET_OPEN != $tknId) {
                                $this->tkns[$prevTokenIdx] = [$tknId, $tknText.','];
                            }
                        } elseif (self::ST_SHORT_ARRAY_OPEN == end($contextStack) && !($this->hasLnLeftToken() || $this->hasLnBefore()) && $this->leftUsefulTokenIs(ST_COMMA)) {
                            $prevTokenIdx = $this->leftUsefulTokenIdx();
                            list($tknId, $tknText) = $this->getToken($this->tkns[$prevTokenIdx]);
                            $this->tkns[$prevTokenIdx] = [$tknId, rtrim($tknText, ',')];
                        }
                        array_pop($contextStack);
                        break;
                    }
                    $touchedBracketOpen = false;
                    break;

                case ST_PARENTHESES_OPEN:
                    $found = ST_PARENTHESES_OPEN;
                    if ($this->leftUsefulTokenIs(T_STRING)) {
                        $found = T_STRING;
                    } elseif ($this->leftUsefulTokenIs(T_ARRAY)) {
                        $found = T_ARRAY;
                    }
                    $contextStack[] = $found;
                    break;

                case ST_PARENTHESES_CLOSE:
                    if (isset($contextStack[0])) {
                        if (T_ARRAY == end($contextStack) && ($this->hasLnLeftToken() || $this->hasLnBefore()) && !$this->leftUsefulTokenIs(ST_COMMA)) {
                            $prevTokenIdx = $this->leftUsefulTokenIdx();
                            list($tknId, $tknText) = $this->getToken($this->tkns[$prevTokenIdx]);
                            if (T_END_HEREDOC != $tknId && ST_PARENTHESES_OPEN != $tknId) {
                                $this->tkns[$prevTokenIdx] = [$tknId, $tknText.','];
                            }
                        } elseif (T_ARRAY == end($contextStack) && !($this->hasLnLeftToken() || $this->hasLnBefore()) && $this->leftUsefulTokenIs(ST_COMMA)) {
                            $prevTokenIdx = $this->leftUsefulTokenIdx();
                            list($tknId, $tknText) = $this->getToken($this->tkns[$prevTokenIdx]);
                            $this->tkns[$prevTokenIdx] = [$tknId, rtrim($tknText, ',')];
                        }
                        array_pop($contextStack);
                    }
                    break;

                default:
                    $touchedBracketOpen = false;
                    break;
            }
            $this->tkns[$this->ptr] = [$id, $text];
        }

        return $this->renderLight();
    }
}
