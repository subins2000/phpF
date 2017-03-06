<?php

namespace Fmt;

final class ReindentObjOps extends FormatterPass
{
    const ALIGN_WITH_INDENT = 1;

    public function candidate($source, $foundTokens)
    {
        if (
            isset($foundTokens[T_OBJECT_OPERATOR]) ||
            isset($foundTokens[T_DOUBLE_COLON])
        ) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';

        $levelCounter = 0;
        $levelEntranceCounter = [];
        $contextCounter = [];
        $touchCounter = [];
        $alignType = [];
        $printedPlaceholder = [];
        $maxContextCounter = [];
        $touchedParenOpen = false;

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case ST_QUOTE:
                    $this->appendCode($text);
                    $this->printUntilTheEndOfString();
                    break;
                case T_CLOSE_TAG:
                    $this->appendCode($text);
                    $this->printUntil(T_OPEN_TAG);
                    break;
                case T_START_HEREDOC:
                    $this->appendCode($text);
                    $this->printUntil(T_END_HEREDOC);
                    break;

                case T_WHILE:
                case T_IF:
                case T_FOR:
                case T_FOREACH:
                case T_SWITCH:
                    $this->appendCode($text);
                    $this->printUntil(ST_PARENTHESES_OPEN);
                    $this->printBlock(ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE);
                    break;

                case T_NEW:
                    $this->appendCode($text);
                    if ($touchedParenOpen) {
                        $touchedParenOpen = false;
                        $foundToken = $this->printUntilAny([ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE, ST_COMMA]);
                        if (ST_PARENTHESES_OPEN == $foundToken) {
                            $this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
                            $this->printBlock(ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE);
                            $this->printUntilAny([ST_PARENTHESES_CLOSE, ST_COMMA]);
                        }
                    }
                    break;

                case T_FUNCTION:
                    $this->appendCode($text);
                    break;

                case T_VARIABLE:
                case T_STRING:
                    $this->appendCode($text);
                    if (!isset($levelEntranceCounter[$levelCounter])) {
                        $levelEntranceCounter[$levelCounter] = 0;
                    }
                    if (!isset($contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]])) {
                        $contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
                        $maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
                        $touchCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
                        $alignType[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
                        $printedPlaceholder[$levelCounter][$levelEntranceCounter[$levelCounter]][$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]] = 0;
                    }
                    break;

                case ST_PARENTHESES_OPEN:
                case ST_BRACKET_OPEN:
                    $this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
                    $this->appendCode($text);
                    break;

                case ST_PARENTHESES_CLOSE:
                case ST_BRACKET_CLOSE:
                    --$levelCounter;
                    $this->appendCode($text);
                    break;

                case T_DOUBLE_COLON:
                case T_OBJECT_OPERATOR:
                    if (!isset($touchCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]) || 0 == $touchCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]) {
                        if (!isset($touchCounter[$levelCounter][$levelEntranceCounter[$levelCounter]])) {
                            $touchCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
                        }
                        ++$touchCounter[$levelCounter][$levelEntranceCounter[$levelCounter]];
                        if ($this->hasLnBefore()) {
                            $alignType[$levelCounter][$levelEntranceCounter[$levelCounter]] = self::ALIGN_WITH_INDENT;
                            $this->appendCode($this->getIndent(+1).$text);
                            $foundToken = $this->printUntilAny([ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE, ST_SEMI_COLON, $this->newLine]);
                            if (ST_SEMI_COLON == $foundToken) {
                                $this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
                            } elseif (ST_PARENTHESES_OPEN == $foundToken || ST_PARENTHESES_CLOSE == $foundToken) {
                                $this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
                                $this->indentParenthesesContent();
                            }
                            break;
                        }
                    } elseif ($this->hasLnBefore() || $this->hasLnLeftToken()) {
                        ++$touchCounter[$levelCounter][$levelEntranceCounter[$levelCounter]];
                        $this->appendCode($this->getIndent(+1).$text);
                        $foundToken = $this->printUntilAny([ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE, ST_SEMI_COLON, $this->newLine]);
                        if (ST_SEMI_COLON == $foundToken) {
                            $this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
                        } elseif (ST_PARENTHESES_OPEN == $foundToken || ST_PARENTHESES_CLOSE == $foundToken) {
                            $this->incrementCounters($levelCounter, $levelEntranceCounter, $contextCounter, $maxContextCounter, $touchCounter, $alignType, $printedPlaceholder);
                            $this->indentParenthesesContent();
                        }
                        break;
                    }
                    $this->appendCode($text);
                    break;

                case T_COMMENT:
                case T_DOC_COMMENT:
                    if (
                        isset($alignType[$levelCounter]) &&
                        isset($levelEntranceCounter[$levelCounter]) &&
                        isset($alignType[$levelCounter][$levelEntranceCounter[$levelCounter]]) &&
                        ($this->hasLnBefore() || $this->hasLnLeftToken()) &&
                        self::ALIGN_WITH_INDENT == $alignType[$levelCounter][$levelEntranceCounter[$levelCounter]]
                    ) {
                        $this->appendCode($this->getIndent(+1));
                    }
                    $this->appendCode($text);
                    if ($this->leftUsefulTokenIs([T_OBJECT_OPERATOR, T_DOUBLE_COLON]) && $this->hasLn($text)) {
                        $this->appendCode($this->getIndent(+1));
                    }
                    break;

                case ST_COMMA:
                case ST_SEMI_COLON:
                    if (!isset($levelEntranceCounter[$levelCounter])) {
                        $levelEntranceCounter[$levelCounter] = 0;
                    }
                    ++$levelEntranceCounter[$levelCounter];
                    $this->appendCode($text);
                    break;

                case T_WHITESPACE:
                    $this->appendCode($text);
                    if ($this->leftUsefulTokenIs([T_OBJECT_OPERATOR, T_DOUBLE_COLON]) && $this->hasLn($text)) {
                        $this->appendCode($this->getIndent(+1));
                    }
                    break;

                default:
                    $touchedParenOpen = false;
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }

    protected function incrementCounters(
        &$levelCounter,
        &$levelEntranceCounter,
        &$contextCounter,
        &$maxContextCounter,
        &$touchCounter,
        &$alignType,
        &$printedPlaceholder
    ) {
        ++$levelCounter;
        if (!isset($levelEntranceCounter[$levelCounter])) {
            $levelEntranceCounter[$levelCounter] = 0;
        }
        ++$levelEntranceCounter[$levelCounter];
        if (!isset($contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]])) {
            $contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
            $maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
            $touchCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
            $alignType[$levelCounter][$levelEntranceCounter[$levelCounter]] = 0;
            $printedPlaceholder[$levelCounter][$levelEntranceCounter[$levelCounter]][$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]] = 0;
        }
        ++$contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]];
        $maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]] = max($maxContextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]], $contextCounter[$levelCounter][$levelEntranceCounter[$levelCounter]]);
    }

    protected function indentParenthesesContent()
    {
        $count = 0;
        $sizeofTokens = sizeof($this->tkns);
        for ($i = $this->ptr; $i < $sizeofTokens; ++$i) {
            $token = &$this->tkns[$i];
            list($id, $text) = $this->getToken($token);
            if (
                (T_WHITESPACE == $id || T_DOC_COMMENT == $id || T_COMMENT == $id)
                && $this->hasLn($text)
            ) {
                $token[1] = $text.$this->getIndent(+1);
                continue;
            }
            if (ST_PARENTHESES_OPEN == $id) {
                ++$count;
            }
            if (ST_PARENTHESES_CLOSE == $id) {
                --$count;
            }
            if (0 == $count) {
                break;
            }
        }
    }

    protected function injectPlaceholderParenthesesContent($placeholder)
    {
        $count = 0;
        $sizeofTokens = sizeof($this->tkns);
        for ($i = $this->ptr; $i < $sizeofTokens; ++$i) {
            $token = &$this->tkns[$i];
            list($id, $text) = $this->getToken($token);
            if ((T_WHITESPACE == $id || T_DOC_COMMENT == $id || T_COMMENT == $id)
                && $this->hasLn($text)) {
                $token[1] = str_replace($this->newLine, $this->newLine.$placeholder, $text);
                continue;
            }
            if (ST_PARENTHESES_OPEN == $id) {
                ++$count;
            }
            if (ST_PARENTHESES_CLOSE == $id) {
                --$count;
            }
            if (0 == $count) {
                break;
            }
        }
    }

    private function hasLnInBlock($tkns, $ptr, $start, $end)
    {
        $sizeOfTkns = sizeof($tkns);
        $count = 0;
        for ($i = $ptr; $i < $sizeOfTkns; ++$i) {
            $token = $tkns[$i];
            list($id, $text) = $this->getToken($token);
            if ($start == $id) {
                ++$count;
            }
            if ($end == $id) {
                --$count;
            }
            if (0 == $count) {
                break;
            }
            if ($this->hasLn($text)) {
                return true;
            }
        }

        return false;
    }
}
