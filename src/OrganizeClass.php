<?php

namespace Fmt;

class OrganizeClass extends AdditionalPass
{
    const METHOD_REPLACEMENT_PLACEHOLDER = "\x2 METHODPLACEHOLDER \x3";

    const OPENER_PLACEHOLDER = "<?php /*\x2 ORDERMETHOD \x3*/";

    public function candidate($source, $foundTokens)
    {
        if (
            isset($foundTokens[T_CLASS])
            || isset($foundTokens[T_TRAIT])
            || isset($foundTokens[T_INTERFACE])
        ) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);

        $return = '';
        $classBlock = '';

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case T_CLASS:
                case T_INTERFACE:
                case T_TRAIT:
                    if ($this->leftUsefulTokenIs(T_DOUBLE_COLON)) {
                        $this->appendCode($text);
                        break;
                    }
                    $return = $text;
                    $return .= $this->walkAndAccumulateUntil($this->tkns, ST_CURLY_OPEN);
                    $classBlock = $this->walkAndAccumulateCurlyBlock($this->tkns);
                    $return .= str_replace(
                        self::OPENER_PLACEHOLDER,
                        '',
                        static::orderMethods(self::OPENER_PLACEHOLDER.$classBlock)
                    );
                    $this->appendCode($return);
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
        return 'Organize class, interface and trait structure.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
// From
class A {
    public function d(){}
    protected function b(){}
    private $a = "";
    private function c(){}
    public function a(){}
    public $b = "";
    const B = 0;
    const A = 0;
}

// To
class A {
    const A = 0;

    const B = 0;

    public $b = "";

    private $a = "";

    public function a(){}

    public function d(){}

    protected function b(){}

    private function c(){}
}
?>
EOT;
    }

    public function orderMethods($source)
    {
        $tokens = token_get_all($source);

        $attributeList = [];
        $commentStack = [];
        $constList = [];
        $docCommentStack = '';
        $functionList = [];
        $touchedDocComment = false;
        $useStack = '';

        while (list($index, $token) = each($tokens)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case T_USE:
                    if ($touchedDocComment) {
                        $touchedDocComment = false;
                        $useStack .= $docCommentStack;
                    }
                    $useStack .= $text;
                    list($foundText, $foundId) = $this->walkAndAccumulateUntilAny($tokens, [ST_CURLY_OPEN, ST_SEMI_COLON]);
                    $useStack .= $foundText;
                    if (ST_CURLY_OPEN == $foundId) {
                        $useStack .= $this->walkAndAccumulateCurlyBlock($tokens);
                    }
                    $useStack .= $this->newLine;
                    break;

                case T_COMMENT:
                    if (strpos($text, "\x2") === false) {
                        if ($this->rightTokenSubsetIsAtIdx($tokens, $this->ptr, [
                            T_ABSTRACT,
                            T_FUNCTION,
                            T_PRIVATE,
                            T_PROTECTED,
                            T_PUBLIC,
                            T_STATIC,
                        ], $this->ignoreFutileTokens)) {
                            if (!$touchedDocComment) {
                                $touchedDocComment = true;
                                $docCommentStack = ' ';
                            }
                            $docCommentStack .= $text;
                            break;
                        }
                        $commentStack[] = $text;
                    }
                    break;

                case T_DOC_COMMENT:
                    if (!$touchedDocComment) {
                        $touchedDocComment = true;
                        $docCommentStack = ' ';
                    }
                    $docCommentStack .= $text;
                    break;

                case T_CONST:
                    $stack = '';
                    if ($touchedDocComment) {
                        $touchedDocComment = false;
                        $stack .= $docCommentStack;
                    }
                    $stack .= $text;
                    $constName = $this->walkAndAccumulateUntil($tokens, T_STRING);
                    $stack .= $constName;
                    $stack .= $this->walkAndAccumulateUntil($tokens, ST_SEMI_COLON);
                    $constList[$constName] = $stack;
                    break;

                case T_ABSTRACT:
                case T_FUNCTION:
                case T_PRIVATE:
                case T_PROTECTED:
                case T_PUBLIC:
                case T_STATIC:
                case T_VARIABLE:
                case T_FINAL:
                    $stack = '';
                    if ($touchedDocComment) {
                        $touchedDocComment = false;
                        $stack .= $docCommentStack;
                    }
                    $touchedMethod = false;
                    $touchedAttribute = false;
                    $functionName = '';
                    $attributeName = '';
                    $visibilityLevel = 0;

                    $searchFor = [
                        T_ABSTRACT,
                        T_FUNCTION,
                        T_PRIVATE,
                        T_PROTECTED,
                        T_PUBLIC,
                        T_STATIC,
                        T_STRING,
                        T_VARIABLE,
                        T_FINAL,
                    ];
                    prev($tokens);

                    do {
                        list($foundText, $foundId) = $this->walkAndAccumulateUntilAny($tokens, $searchFor);
                        if (T_PROTECTED == $foundId) {
                            $visibilityLevel = 1;
                        } elseif (T_PRIVATE == $foundId) {
                            $visibilityLevel = 2;
                        } elseif (T_FUNCTION == $foundId) {
                            $touchedMethod = true;
                        } elseif (T_VARIABLE == $foundId) {
                            $touchedAttribute = true;
                            $attributeName = $foundText;
                        } elseif (T_STRING == $foundId && $touchedMethod) {
                            $functionName = $foundText;
                        }
                        $stack .= $foundText;
                    } while (empty($functionName) && empty($attributeName));

                    if ($touchedMethod) {
                        list($foundText, $foundId) = $this->walkAndAccumulateUntilAny($tokens, [ST_CURLY_OPEN, ST_SEMI_COLON]);
                        $stack .= $foundText;
                        if (ST_CURLY_OPEN == $foundId) {
                            $stack .= $this->walkAndAccumulateCurlyBlock($tokens);
                        }
                        $functionList[$visibilityLevel.':'.$functionName] = $stack;
                    } elseif ($touchedAttribute) {
                        $stack .= $this->walkAndAccumulateUntil($tokens, ST_SEMI_COLON);
                        $attributeList[$visibilityLevel.':'.$attributeName] = $stack;
                    }
                    break;

                default:
                    if ($touchedDocComment) {
                        $docCommentStack .= $text;
                        break;
                    }
                    break;
            }
        }
        ksort($constList);
        ksort($attributeList);
        ksort($functionList);

        $final = '';
        if (!empty($useStack)) {
            $final .= $useStack.$this->newLine;
        }

        foreach ($commentStack as $text) {
            $final .= ' '.$text;
            if ($this->substrCountTrailing($text, "\n") === 0) {
                $final .= $this->newLine;
            }
        }

        $final .= $this->newLine;
        foreach ($constList as $text) {
            $final .= $text.$this->newLine.$this->newLine;
        }

        $final .= $this->newLine;
        foreach ($attributeList as $text) {
            $final .= $text.$this->newLine.$this->newLine;
        }

        $final .= $this->newLine;
        foreach ($functionList as $text) {
            $final .= $text.$this->newLine.$this->newLine;
        }

        return $this->newLine.' '.trim($final).$this->newLine.ST_CURLY_CLOSE;
    }
}
