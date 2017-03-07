<?php

namespace Fmt;

final class ConstructorPass extends FormatterPass
{
    const TYPE_CAMEL_CASE = 'camel';

    const TYPE_GOLANG = 'golang';

    const TYPE_SNAKE_CASE = 'snake';

    private $type;

    public function __construct($type = self::TYPE_CAMEL_CASE)
    {
        $this->type = self::TYPE_CAMEL_CASE;
        if (self::TYPE_CAMEL_CASE == $type || self::TYPE_SNAKE_CASE == $type || self::TYPE_GOLANG == $type) {
            $this->type = $type;
        }
    }

    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_CLASS])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';

        $classAttributes = [];
        $functionList = [];
        $touchedVisibility = false;
        $touchedFunction = false;
        $curlyCount = null;

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case T_CLASS:
                    $classAttributes = [];
                    $functionList = [];
                    $touchedVisibility = false;
                    $touchedFunction = false;
                    $curlyCount = null;
                    $this->appendCode($text);
                    while (list($index, $token) = each($this->tkns)) {
                        list($id, $text) = $this->getToken($token);
                        $this->ptr = $index;
                        if (ST_CURLY_OPEN == $id) {
                            ++$curlyCount;
                        }
                        if (ST_CURLY_CLOSE == $id) {
                            --$curlyCount;
                        }
                        if (0 === $curlyCount) {
                            break;
                        }
                        $this->appendCode($text);
                        if (T_PUBLIC == $id) {
                            $touchedVisibility = T_PUBLIC;
                        } elseif (T_PRIVATE == $id) {
                            $touchedVisibility = T_PRIVATE;
                        } elseif (T_PROTECTED == $id) {
                            $touchedVisibility = T_PROTECTED;
                        }
                        if (
                            T_VARIABLE == $id &&
                            (
                                T_PUBLIC == $touchedVisibility ||
                                T_PRIVATE == $touchedVisibility ||
                                T_PROTECTED == $touchedVisibility
                            )
                        ) {
                            $classAttributes[] = $text;
                            $touchedVisibility = null;
                        } elseif (T_FUNCTION == $id) {
                            $touchedFunction = true;
                        } elseif ($touchedFunction && T_STRING == $id) {
                            $functionList[] = $text;
                            $touchedVisibility = null;
                            $touchedFunction = false;
                        }
                    }
                    $functionList = array_combine($functionList, $functionList);
                    if (!isset($functionList['__construct'])) {
                        $this->appendCode('function __construct('.implode(', ', $classAttributes).'){'.$this->newLine);
                        foreach ($classAttributes as $var) {
                            $this->appendCode($this->generate($var));
                        }
                        $this->appendCode('}'.$this->newLine);
                    }

                    $this->appendCode($text);
                    break;
                default:
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }

    private function generate($var)
    {
        switch ($this->type) {
            case self::TYPE_SNAKE_CASE:
                $ret = $this->generateSnakeCase($var);
                break;
            case self::TYPE_GOLANG:
                $ret = $this->generateGolang($var);
                break;
            case self::TYPE_CAMEL_CASE:
            default:
                $ret = $this->generateCamelCase($var);
                break;
        }

        return $ret;
    }

    private function generateCamelCase($var)
    {
        $str = '$this->set'.ucfirst(str_replace('$', '', $var)).'('.$var.');'.$this->newLine;

        return $str;
    }

    private function generateGolang($var)
    {
        $str = '$this->Set'.ucfirst(str_replace('$', '', $var)).'('.$var.');'.$this->newLine;

        return $str;
    }

    private function generateSnakeCase($var)
    {
        $str = '$this->set_'.(str_replace('$', '', $var)).'('.$var.');'.$this->newLine;

        return $str;
    }
}
