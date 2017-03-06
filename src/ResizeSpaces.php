<?php

namespace Fmt;

final class ResizeSpaces extends FormatterPass
{
    public function candidate($source, $foundTokens)
    {
        $tkns = token_get_all($source);

        $this->tkns = [];
        foreach ($tkns as $token) {
            if (T_WHITESPACE === $token[0] && !$this->hasLn($token[1])) {
                continue;
            }
            $this->tkns[] = $token;
        }

        return true;
    }

    public function format($source)
    {
        $this->code = '';
        $this->useCache = true;

        $inTernaryOperator = 0;
        $shortTernaryOperator = false;
        $touchedFunction = false;
        $touchedUse = false;
        $touchedGroupedUse = false;
        $hasEchoAfterOpenTag = false;

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            $this->memoPtr();
            $this->cache = [];

            switch ($id) {
                case T_START_HEREDOC:
                    $this->appendCode($text);
                    $this->printUntil(ST_SEMI_COLON);
                    break;

                case T_CALLABLE:
                    $this->appendCode($text.' ');
                    break;

                case '+':
                case '-':
                    if (
                        $this->leftMemoUsefulTokenIs([T_INC, T_DEC, T_LNUMBER, T_DNUMBER, T_VARIABLE, ST_PARENTHESES_CLOSE, T_STRING, T_ARRAY, T_ARRAY_CAST, T_BOOL_CAST, T_DOUBLE_CAST, T_INT_CAST, T_OBJECT_CAST, T_STRING_CAST, T_UNSET_CAST, ST_BRACKET_CLOSE])
                        &&
                        $this->rightUsefulTokenIs([T_INC, T_DEC, T_LNUMBER, T_DNUMBER, T_VARIABLE, ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE, T_STRING, T_ARRAY, T_ARRAY_CAST, T_BOOL_CAST, T_DOUBLE_CAST, T_INT_CAST, T_OBJECT_CAST, T_STRING_CAST, T_UNSET_CAST, ST_BRACKET_CLOSE, ST_BRACKET_OPEN])
                    ) {
                        $this->appendCode(' '.$text.' ');
                        break;
                    }
                    $this->appendCode($text);
                    break;

                case '*':
                    $this->rtrimLnAndappendCode(' '.$text);
                    $this->appendCode(' ');
                    break;

                case '%':
                case '/':
                case T_POW:
                case ST_QUESTION:
                case ST_CONCAT:
                    if (ST_QUESTION == $id) {
                        ++$inTernaryOperator;
                        $shortTernaryOperator = $this->rightTokenIs(ST_COLON);
                    }

                    list($prevId) = $this->inspectToken(-1);
                    list($nextId) = $this->inspectToken(+1);
                    if (
                        T_WHITESPACE === $prevId &&
                        T_WHITESPACE !== $nextId
                    ) {
                        $this->appendCode($text.$this->getSpace(!$this->rightTokenIs(ST_COLON)));
                        break;
                    } elseif (
                        T_WHITESPACE !== $prevId &&
                        T_WHITESPACE === $nextId
                    ) {
                        $this->appendCode(' '.$text);
                        break;
                    } elseif (
                        T_WHITESPACE !== $prevId &&
                        T_WHITESPACE !== $nextId
                    ) {
                        $this->appendCode(' '.$text.$this->getSpace(!$this->rightTokenIs(ST_COLON)));
                        break;
                    }
                    $this->appendCode($text);
                    break;

                case ST_COLON:
                    list($prevId) = $this->inspectToken(-1);
                    list($nextId, $nextText) = $this->inspectToken(+1);

                    if (
                        $inTernaryOperator > 0 &&
                        T_WHITESPACE === $prevId &&
                        T_WHITESPACE !== $nextId
                    ) {
                        $this->appendCode($text.' ');
                        --$inTernaryOperator;
                        break;
                    } elseif (
                        $inTernaryOperator > 0 &&
                        T_WHITESPACE !== $prevId &&
                        T_WHITESPACE === $nextId
                    ) {
                        $this->appendCode($this->getSpace(!$shortTernaryOperator).$text);
                        --$inTernaryOperator;
                        break;
                    } elseif (
                        $inTernaryOperator > 0 &&
                        T_WHITESPACE !== $prevId &&
                        T_WHITESPACE !== $nextId
                    ) {
                        $this->appendCode($this->getSpace(!$shortTernaryOperator).$text.' ');
                        --$inTernaryOperator;
                        break;
                    } elseif (0 == $inTernaryOperator && $this->leftMemoUsefulTokenIs(ST_PARENTHESES_CLOSE)) {
                        $this->appendCode($text.' ');
                        break;
                    } elseif (
                        (
                            T_WHITESPACE != $nextId
                            ||
                            (T_WHITESPACE == $nextId && !$this->hasLn($nextText))
                        )
                        && $this->rightUsefulTokenIs(T_CLOSE_TAG)
                    ) {
                        $this->appendCode($text.' ');
                        break;
                    } elseif (T_COMMENT == $nextId) {
                        $this->appendCode($text.' ');
                        break;
                    }
                    $this->appendCode($text);
                    break;

                case T_PRINT:
                    $this->appendCode($text.$this->getSpace(!$this->rightTokenIs([ST_PARENTHESES_OPEN])));
                    break;

                case T_VARIABLE:
                    if ($this->leftMemoUsefulTokenIs([T_ARRAY, T_STRING])) {
                        $this->appendCode(' ');
                    }
                    $this->appendCode($text);
                    break;

                case ST_CURLY_OPEN:
                    $touchedFunction = false;
                    if (!$touchedUse && $this->leftMemoUsefulTokenIs([T_VARIABLE, T_STRING]) && $this->rightUsefulTokenIs([T_VARIABLE, T_STRING])) {
                        $this->appendCode($text);
                        break;
                    } elseif ($this->leftMemoUsefulTokenIs([T_STRING, T_DO, T_FINALLY, ST_PARENTHESES_CLOSE]) && !$this->hasLnLeftToken()) {
                        $this->rtrimAndAppendCode(
                            ' '.
                            $text.
                            $this->getSpace($this->rightTokenIs(T_COMMENT))
                        );
                        break;
                    } elseif ($this->rightTokenIs(ST_CURLY_CLOSE) || ($this->leftMemoTokenIs([T_OBJECT_OPERATOR, ST_DOLLAR]) && $this->rightTokenIs([T_VARIABLE]))) {
                        $this->appendCode($text);
                        break;
                    } elseif (!$this->leftMemoTokenIs(T_DOUBLE_COLON) && $this->rightTokenIs([T_VARIABLE, T_INC, T_DEC, T_COMMENT])) {
                        $this->appendCode($text.' ');
                        break;
                    } elseif ($this->leftMemoUsefulTokenIs(T_NS_SEPARATOR)) {
                        $touchedGroupedUse = true;
                        $this->appendCode($text.' ');
                        break;
                    }
                    $this->appendCode($text);
                    break;

                case ST_SEMI_COLON:
                    $touchedUse = false;
                    if ($this->rightTokenIs([T_VARIABLE, T_INC, T_DEC, T_LNUMBER, T_DNUMBER, T_COMMENT, T_DOC_COMMENT, T_STRING, ST_PARENTHESES_OPEN])) {
                        $this->appendCode($text.' ');
                        break;
                    }
                    $this->appendCode($text);
                    $this->appendCode($this->getSpace($this->rightTokenIs(T_COMMENT) && !$this->hasLnAfter()));
                    break;

                case ST_PARENTHESES_OPEN:
                    if ($this->leftMemoUsefulTokenIs([T_WHILE, T_CATCH]) && !$this->hasLnLeftToken()) {
                        $this->rtrimAndAppendCode(' ');
                    }
                    $this->appendCode($text);
                    $this->appendCode($this->getSpace($this->rightTokenIs(T_COMMENT) && !$this->hasLnAfter()));
                    break;

                case ST_PARENTHESES_CLOSE:
                    $this->appendCode($text.$this->getSpace($this->rightTokenIs([T_COMMENT, T_DOC_COMMENT])));
                    break;

                case T_USE:
                    $touchedUse = true;
                    $this->appendCode($this->getSpace($this->leftMemoTokenIs(ST_PARENTHESES_CLOSE)).$text.' ');
                    break;

                case T_NAMESPACE:
                    $this->appendCode($text.$this->getSpace(!$this->rightTokenIs([ST_SEMI_COLON, T_NS_SEPARATOR, T_DOUBLE_COLON])));
                    break;

                case T_ECHO:
                    if ($this->leftMemoUsefulTokenIs(T_OPEN_TAG)) {
                        $hasEchoAfterOpenTag = false;
                    }
                    $this->appendCode($text.$this->getSpace(!$this->rightTokenIs(ST_SEMI_COLON)));
                    break;
                case T_RETURN:
                case T_YIELD:
                case T_VAR:
                case T_NEW:
                case T_CONST:
                case T_FINAL:
                case T_CASE:
                case T_BREAK:
                    $this->appendCode($text.$this->getSpace(!$this->rightTokenIs(ST_SEMI_COLON)));
                    break;

                case T_WHILE:
                    if ($this->leftMemoTokenIs(ST_CURLY_CLOSE) && !$this->hasLnBefore()) {
                        $this->appendCode(' '.$text.' ');
                        break;
                    }
                case T_DOUBLE_ARROW:
                    if (T_DOUBLE_ARROW == $id && $this->leftMemoTokenIs([T_CONSTANT_ENCAPSED_STRING, T_STRING, T_VARIABLE, T_LNUMBER, T_DNUMBER, ST_PARENTHESES_CLOSE, ST_BRACKET_CLOSE, ST_CURLY_CLOSE, ST_QUOTE])) {
                        $this->rtrimAndAppendCode(' '.$text.' ');
                        break;
                    }
                case T_STATIC:
                    $this->appendCode($text.$this->getSpace(!$this->rightTokenIs([ST_SEMI_COLON, T_DOUBLE_COLON, ST_PARENTHESES_OPEN])));
                    break;

                case T_FUNCTION:
                    $touchedFunction = true;
                    $this->appendCode($text.$this->getSpace(!$this->rightTokenIs(ST_SEMI_COLON)));
                    break;

                case T_PUBLIC:
                case T_PRIVATE:
                case T_PROTECTED:
                case T_TRAIT:
                case T_INTERFACE:
                case T_THROW:
                case T_GLOBAL:
                case T_ABSTRACT:
                case T_INCLUDE:
                case T_REQUIRE:
                case T_INCLUDE_ONCE:
                case T_REQUIRE_ONCE:
                case T_DECLARE:
                case T_IF:
                case T_FOR:
                case T_FOREACH:
                case T_SWITCH:
                case T_TRY:
                case ST_COMMA:
                    $this->appendCode($text.' ');
                    break;

                case T_CLONE:
                case T_CONTINUE:
                    $this->appendCode($text.$this->getSpace(!$this->rightTokenIs(ST_SEMI_COLON)));
                    break;

                case T_CLASS:
                    $this->appendCode(
                        $text.
                        $this->getSpace(
                            !($this->leftMemoUsefulTokenIs(T_NEW) && $this->rightTokenIs([ST_PARENTHESES_OPEN, T_EXTENDS, T_IMPLEMENTS])) &&
                            !$this->leftMemoTokenIs(T_DOUBLE_COLON) &&
                            !$this->rightTokenIs(ST_SEMI_COLON) ||
                            $this->rightTokenIs(T_DOUBLE_ARROW)
                        )
                    );
                    break;

                case T_EXTENDS:
                case T_IMPLEMENTS:
                case T_INSTANCEOF:
                case T_INSTEADOF:
                case T_AS:
                case T_COALESCE:
                    $this->rtrimAndAppendCode(' '.$text.' ');
                    break;

                case T_LOGICAL_AND:
                case T_LOGICAL_OR:
                case T_LOGICAL_XOR:
                case T_AND_EQUAL:
                case T_BOOLEAN_AND:
                case T_BOOLEAN_OR:
                case T_CONCAT_EQUAL:
                case T_DIV_EQUAL:
                case T_IS_EQUAL:
                case T_IS_GREATER_OR_EQUAL:
                case T_IS_IDENTICAL:
                case T_IS_NOT_EQUAL:
                case T_IS_NOT_IDENTICAL:
                case T_IS_SMALLER_OR_EQUAL:
                case T_SPACESHIP:
                case T_MINUS_EQUAL:
                case T_MOD_EQUAL:
                case T_MUL_EQUAL:
                case T_OR_EQUAL:
                case T_PLUS_EQUAL:
                case T_SL:
                case T_SL_EQUAL:
                case T_SR:
                case T_SR_EQUAL:
                case T_XOR_EQUAL:
                case ST_IS_GREATER:
                case ST_IS_SMALLER:
                case ST_EQUAL:
                    $this->appendCode($this->getSpace(!$this->hasLnBefore()).$text.' ');
                    break;

                case T_CATCH:
                case T_FINALLY:
                    if ($this->hasLnLeftToken()) {
                        $this->appendCode(' '.$text.' ');
                        break;
                    }
                    $this->rtrimAndAppendCode(' '.$text.' ');
                    break;

                case T_ELSEIF:
                    if (!$this->leftMemoTokenIs(ST_CURLY_CLOSE)) {
                        $this->appendCode($text.' ');
                        break;
                    }
                    $this->appendCode(' '.$text.' ');
                    break;

                case T_ELSE:
                    if (!$this->leftMemoUsefulTokenIs(ST_CURLY_CLOSE)) {
                        $this->appendCode($text);
                        break;
                    }
                    $this->appendCode($this->getSpace(!$this->leftMemoTokenIs([T_COMMENT, T_DOC_COMMENT])).$text.' ');
                    break;

                case T_ARRAY_CAST:
                case T_BOOL_CAST:
                case T_DOUBLE_CAST:
                case T_INT_CAST:
                case T_OBJECT_CAST:
                case T_STRING_CAST:
                case T_UNSET_CAST:
                case T_GOTO:
                    $this->appendCode(str_replace([' ', "\t"], '', $text).' ');
                    break;

                case ST_REFERENCE:
                    $spaceBefore = !$this->leftMemoUsefulTokenIs([ST_EQUAL, ST_PARENTHESES_OPEN, T_AS, T_DOUBLE_ARROW, ST_COMMA]) && !$this->leftMemoUsefulTokenIs([T_FUNCTION]);
                    $spaceAfter = !$touchedFunction && !$this->leftMemoUsefulTokenIs([ST_EQUAL, ST_PARENTHESES_OPEN, T_AS, T_DOUBLE_ARROW, ST_COMMA]);
                    $this->appendCode($this->getSpace($spaceBefore).$text.$this->getSpace($spaceAfter));
                    break;

                case ST_BITWISE_OR:
                case ST_BITWISE_XOR:
                    $this->appendCode(' '.$text.' ');
                    break;

                case T_COMMENT:
                    if (substr($text, 0, 2) === '//') {
                        list($leftId) = $this->inspectToken(-1);
                        $this->appendCode($this->getSpace(T_VARIABLE == $leftId || ST_BRACKET_OPEN == $leftId).$text);
                        break;
                    } elseif (!$this->hasLn($text) && !$this->hasLnBefore() && !$this->hasLnAfter() && $this->leftUsefulTokenIs(ST_COMMA) && $this->rightUsefulTokenIs(T_VARIABLE)) {
                        $this->appendCode($text.' ');
                        break;
                    }
                    $this->appendCode($text);
                    break;

                case ST_CURLY_CLOSE:
                    if ($touchedGroupedUse) {
                        $touchedGroupedUse = false;
                        $this->appendCode($this->getSpace(!$this->hasLnBefore()));
                    }
                    $this->appendCode($text);
                    $this->appendCode($this->getSpace($this->rightTokenIs(T_COMMENT) && !$this->hasLnAfter()));
                    break;

                case T_CONSTANT_ENCAPSED_STRING:
                    $this->appendCode($text);
                    $this->appendCode($this->getSpace($this->rightTokenIs(T_COMMENT) && !$this->hasLnAfter()));
                    break;

                case T_CLOSE_TAG:
                    $this->appendCode($this->getSpace(!$hasEchoAfterOpenTag && !$this->hasLnBefore()));
                    $this->appendCode($text);
                    $hasEchoAfterOpenTag = false;
                    break;

                case T_OPEN_TAG_WITH_ECHO:
                    $hasEchoAfterOpenTag = true;
                    $this->appendCode($text);
                    break;

                case T_OPEN_TAG:
                    $hasEchoAfterOpenTag = true;
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
