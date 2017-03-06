<?php

namespace Fmt;

final class PSR2ModifierVisibilityStaticOrder extends FormatterPass
{
    public function candidate($source, $foundTokens)
    {
        return isset($foundTokens[T_VAR]) ||
        isset($foundTokens[T_PUBLIC]) ||
        isset($foundTokens[T_PRIVATE]) ||
        isset($foundTokens[T_PROTECTED]) ||
        isset($foundTokens[T_FINAL]) ||
        isset($foundTokens[T_ABSTRACT]) ||
        isset($foundTokens[T_STATIC]) ||
        isset($foundTokens[T_CLASS])
        ;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';

        $found = [];
        $visibility = null;
        $finalOrAbstract = null;
        $static = null;
        $skipWhitespaces = false;
        $touchedClassInterfaceTrait = false;
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
                case T_CLASS:
                    $found[] = T_CLASS;
                    $touchedClassInterfaceTrait = true;
                    $this->appendCode($text);
                    $this->printUntilAny([T_EXTENDS, T_IMPLEMENTS, ST_CURLY_OPEN]);
                    break;
                case T_INTERFACE:
                    $found[] = T_INTERFACE;
                    $touchedClassInterfaceTrait = true;
                    $this->appendCode($text);
                    break;
                case T_TRAIT:
                    $found[] = T_TRAIT;
                    $touchedClassInterfaceTrait = true;
                    $this->appendCode($text);
                    break;
                case ST_CURLY_OPEN:
                case ST_PARENTHESES_OPEN:
                    if ($touchedClassInterfaceTrait) {
                        $found[] = $text;
                    }
                    $this->appendCode($text);
                    $touchedClassInterfaceTrait = false;
                    break;
                case ST_CURLY_CLOSE:
                case ST_PARENTHESES_CLOSE:
                    array_pop($found);
                    if (1 === sizeof($found)) {
                        array_pop($found);
                    }
                    $this->appendCode($text);
                    break;
                case T_WHITESPACE:
                    if (!$skipWhitespaces) {
                        $this->appendCode($text);
                    }
                    break;
                case T_VAR:
                    $text = 'public';
                case T_PUBLIC:
                case T_PRIVATE:
                case T_PROTECTED:
                    $visibility = $text;
                    $skipWhitespaces = true;
                    break;
                case T_FINAL:
                case T_ABSTRACT:
                    if (!$this->rightTokenIs([T_CLASS])) {
                        $finalOrAbstract = $text;
                        $skipWhitespaces = true;
                        break;
                    }
                    $this->appendCode($text);
                    break;
                case T_STATIC:
                    if (!is_null($visibility)) {
                        $static = $text;
                        $skipWhitespaces = true;
                        break;
                    } elseif (!$this->rightTokenIs([T_VARIABLE, T_DOUBLE_COLON]) && !$this->leftTokenIs([T_NEW])) {
                        $static = $text;
                        $skipWhitespaces = true;
                        break;
                    }
                    $this->appendCode($text);
                    break;
                case T_VARIABLE:
                    if (
                        null !== $visibility ||
                        null !== $finalOrAbstract ||
                        null !== $static
                    ) {
                        null !== $finalOrAbstract && $this->appendCode($finalOrAbstract.$this->getSpace());
                        null !== $visibility && $this->appendCode($visibility.$this->getSpace());
                        null !== $static && $this->appendCode($static.$this->getSpace());
                        $finalOrAbstract = null;
                        $visibility = null;
                        $static = null;
                        $skipWhitespaces = false;
                    }
                    $this->appendCode($text);
                    $this->printUntil(ST_SEMI_COLON);
                    break;
                case T_FUNCTION:
                    $hasFoundClassOrInterface = isset($found[0]) && (ST_CURLY_OPEN == $found[0] || T_CLASS === $found[0] || T_INTERFACE === $found[0] || T_TRAIT === $found[0]) && $this->rightUsefulTokenIs([T_STRING, ST_REFERENCE]);
                    if ($hasFoundClassOrInterface && null !== $finalOrAbstract) {
                        $this->appendCode($finalOrAbstract.$this->getSpace());
                    }
                    if ($hasFoundClassOrInterface && null !== $visibility) {
                        $this->appendCode($visibility.$this->getSpace());
                    } elseif (
                        $hasFoundClassOrInterface &&
                        !$this->leftTokenIs([T_DOUBLE_ARROW, T_RETURN, ST_EQUAL, ST_COMMA, ST_PARENTHESES_OPEN])
                    ) {
                        $this->appendCode('public'.$this->getSpace());
                    }
                    if ($hasFoundClassOrInterface && null !== $static) {
                        $this->appendCode($static.$this->getSpace());
                    }
                    $this->appendCode($text);
                    $visibility = null;
                    $static = null;
                    $skipWhitespaces = false;
                    if ('abstract' == strtolower($finalOrAbstract)) {
                        $finalOrAbstract = null;
                        $this->printUntil(ST_SEMI_COLON);
                        break;
                    }
                    $finalOrAbstract = null;
                    $this->printUntil(ST_CURLY_OPEN);
                    $this->printCurlyBlock();
                    break;
                default:
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }
}
