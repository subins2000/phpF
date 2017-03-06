<?php

namespace Fmt;

final class AutoSemicolon extends AdditionalPass
{
    const ST_CLOSURE = 'CLOSURE';

    public function candidate($source, $foundTokens)
    {
        return true;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';
        $parenStack = [];
        $curlyStack = [];
        $lastParen = null;
        $lastCurly = null;
        $ternary = 0;
        $touchedSingleColon = false;
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case T_IF:
                case T_SWITCH:
                case T_FOR:
                case T_FOREACH:
                    $parenStack[] = $id;
                    $this->appendCode($text);
                    $this->printUntil(ST_PARENTHESES_OPEN);
                    break;
                case ST_PARENTHESES_OPEN:
                    $parenStack[] = $id;
                    $this->appendCode($text);
                    break;

                case ST_PARENTHESES_CLOSE:
                    $lastParen = array_pop($parenStack);
                    $this->appendCode($text);
                    break;

                case T_FUNCTION:
                    $foundId = $id;
                    if ($this->rightUsefulTokenIs(ST_PARENTHESES_OPEN)) {
                        $foundId = self::ST_CLOSURE;
                    }
                    $curlyStack[] = $foundId;
                    $this->appendCode($text);
                    $this->printUntil(ST_CURLY_OPEN);
                    break;

                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                case ST_CURLY_OPEN:
                    $curlyStack[] = $id;
                    $this->appendCode($text);
                    break;

                case ST_CURLY_CLOSE:
                    $lastCurly = array_pop($curlyStack);
                    $this->appendCode($text);
                    break;
                case ST_QUESTION:
                    ++$ternary;
                    $this->appendCode($text);
                    break;
                case ST_COLON:
                    if ($ternary > 0) {
                        $touchedSingleColon = true;
                    }
                    $this->appendCode($text);
                    break;

                case T_WHITESPACE:
                    if (!$this->hasLn($text)) {
                        $this->appendCode($text);
                        continue;
                    }
                    if ($touchedSingleColon && $ternary) {
                        $touchedSingleColon = false;
                        --$ternary;
                    }

                    if (
                        $this->leftUsefulTokenIs([
                            ST_BRACKET_OPEN,
                            ST_COLON,
                            ST_COMMA,
                            ST_CONCAT,
                            ST_CURLY_OPEN,
                            ST_DIVIDE,
                            ST_EQUAL,
                            ST_MINUS,
                            ST_PARENTHESES_OPEN,
                            ST_PLUS,
                            ST_SEMI_COLON,
                            ST_TIMES,

                            T_ABSTRACT,
                            T_AND_EQUAL,
                            T_ARRAY,
                            T_ARRAY_CAST,
                            T_AS,
                            T_BOOL_CAST,
                            T_BOOLEAN_AND,
                            T_BOOLEAN_OR,
                            T_CALLABLE,
                            T_CASE,
                            T_CATCH,
                            T_CLASS,
                            T_CLONE,
                            T_CONCAT_EQUAL,
                            T_CONST,
                            T_DECLARE,
                            T_DEFAULT,
                            T_DIV_EQUAL,
                            T_DO,
                            T_DOUBLE_ARROW,
                            T_DOUBLE_CAST,
                            T_DOUBLE_COLON,
                            T_DOUBLE_COLON,
                            T_ECHO,
                            T_ELLIPSIS,
                            T_ELSE,
                            T_ELSEIF,
                            T_EXTENDS,
                            T_FINAL,
                            T_FINALLY,
                            T_FOR,
                            T_FOREACH,
                            T_FUNCTION,
                            T_GLOBAL,
                            T_GOTO,
                            T_IF,
                            T_IMPLEMENTS,
                            T_INC,
                            T_INCLUDE,
                            T_INCLUDE_ONCE,
                            T_INLINE_HTML,
                            T_INSTANCEOF,
                            T_INSTEADOF,
                            T_INT_CAST,
                            T_INTERFACE,
                            T_IS_EQUAL,
                            T_IS_GREATER_OR_EQUAL,
                            T_IS_IDENTICAL,
                            T_IS_NOT_EQUAL,
                            T_IS_NOT_IDENTICAL,
                            T_IS_SMALLER_OR_EQUAL,
                            T_LOGICAL_AND,
                            T_LOGICAL_OR,
                            T_LOGICAL_XOR,
                            T_MINUS_EQUAL,
                            T_MOD_EQUAL,
                            T_MUL_EQUAL,
                            T_NAMESPACE,
                            T_NEW,
                            T_NS_SEPARATOR,
                            T_OBJECT_CAST,
                            T_OBJECT_OPERATOR,
                            T_OPEN_TAG,
                            T_OR_EQUAL,
                            T_PLUS_EQUAL,
                            T_POW,
                            T_POW_EQUAL,
                            T_PRIVATE,
                            T_PROTECTED,
                            T_PUBLIC,
                            T_REQUIRE,
                            T_REQUIRE_ONCE,
                            T_SL,
                            T_SL_EQUAL,
                            T_SPACESHIP,
                            T_SR,
                            T_SR_EQUAL,
                            T_START_HEREDOC,
                            T_STATIC,
                            T_STRING_CAST,
                            T_SWITCH,
                            T_THROW,
                            T_TRAIT,
                            T_TRY,
                            T_UNSET_CAST,
                            T_USE,
                            T_VAR,
                            T_WHILE,
                        ]) ||
                        $this->leftTokenIs([
                            T_COMMENT,
                            T_DOC_COMMENT,
                        ])
                    ) {
                        $this->appendCode($text);
                        continue;
                    }
                    if (
                        $this->rightUsefulTokenIs([
                            ST_BRACKET_CLOSE,
                            ST_BRACKET_OPEN,
                            ST_COLON,
                            ST_COMMA,
                            ST_CONCAT,
                            ST_CURLY_OPEN,
                            ST_DIVIDE,
                            ST_MINUS,
                            ST_PARENTHESES_CLOSE,
                            ST_PARENTHESES_OPEN,
                            ST_PLUS,
                            ST_SEMI_COLON,
                            ST_TIMES,

                            T_BOOLEAN_AND,
                            T_BOOLEAN_OR,
                            T_IS_EQUAL,
                            T_IS_GREATER_OR_EQUAL,
                            T_IS_IDENTICAL,
                            T_IS_NOT_EQUAL,
                            T_IS_NOT_IDENTICAL,
                            T_IS_SMALLER_OR_EQUAL,
                            T_LOGICAL_AND,
                            T_LOGICAL_OR,
                            T_LOGICAL_XOR,
                            T_OBJECT_OPERATOR,
                            T_POW,

                            ST_QUESTION,
                        ]) ||
                        $this->rightTokenIs([
                            T_COMMENT,
                            T_DOC_COMMENT,
                        ])
                    ) {
                        $this->appendCode($text);
                        continue;
                    }

                    if (
                        $this->leftUsefulTokenIs(ST_PARENTHESES_CLOSE) &&
                        ST_PARENTHESES_OPEN != $lastParen
                    ) {
                        $this->appendCode($text);
                        continue;
                    }

                    if (
                        $this->leftUsefulTokenIs(ST_CURLY_CLOSE) &&
                        (
                            ST_CURLY_OPEN == $lastCurly
                            ||
                            T_FUNCTION == $lastCurly
                        )
                    ) {
                        $this->appendCode($text);
                        continue;
                    }

                    if (0 != $ternary) {
                        $this->appendCode($text);
                        continue;
                    }

                    $this->appendCode(ST_SEMI_COLON.$text);
                    break;
                default:
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }

    public function getDescription()
    {
        return 'Add semicolons in statements ends.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
// From
$a = new SomeClass()

// To
$a = new SomeClass();
?>
EOT;
    }
}
