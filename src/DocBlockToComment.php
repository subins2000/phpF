<?php

namespace Fmt;

final class DocBlockToComment extends AdditionalPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_DOC_COMMENT])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';
        $this->useCache = true;

        $touchedOpenTag = false;
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            $this->tkns[$this->ptr] = [$id, $text];
            $this->cache = [];

            if (T_DOC_COMMENT != $id) {
                continue;
            }

            if (!$touchedOpenTag && $this->leftUsefulTokenIs(T_OPEN_TAG)) {
                $touchedOpenTag = true;
                continue;
            }

            if ($this->isStructuralElement()) {
                continue;
            }

            $commentTokenText = &$this->tkns[$this->ptr][1];

            if ($this->rightUsefulTokenIs(T_VARIABLE)) {
                $commentTokenText = $this->updateCommentAgainstVariable($commentTokenText);
                continue;
            }

            if ($this->rightUsefulTokenIs([T_FOREACH, T_LIST])) {
                $commentTokenText = $this->updateCommentAgainstParenthesesBlock($commentTokenText);
                continue;
            }

            if (null === $this->rightUsefulToken() || $this->rightUsefulTokenIs(ST_CURLY_CLOSE)) {
                $commentTokenText = $this->updateComment($commentTokenText);
                continue;
            }

            $commentTokenText = $this->updateComment($commentTokenText);
        }

        return $this->renderLight($this->tkns);
    }

    public function getDescription()
    {
        return 'Replace docblocks with regular comments when used in non structural elements.';
    }

    public function getExample()
    {
        return <<<'EOT'
EOT;
    }

    protected function walkAndNormalizeUntil($tknid)
    {
        while (list($index, $token) = each($this->tkns)) {
            $this->ptr = $index;
            $this->cache = [];
            if ($token[0] == $tknid) {
                $t = &$this->tkns[$this->ptr];
                $t = $this->getToken($token);

                return $t;
            }
        }
    }

    private function isStructuralElement()
    {
        return $this->rightUsefulTokenIs([
            T_PRIVATE, T_PROTECTED, T_PUBLIC,
            T_FUNCTION, T_ABSTRACT, T_CONST,
            T_NAMESPACE, T_REQUIRE, T_REQUIRE_ONCE,
            T_INCLUDE, T_INCLUDE_ONCE, T_FINAL,
            T_CLASS, T_INTERFACE, T_TRAIT, T_STATIC,
        ]);
    }

    private function updateComment($commentTokenText)
    {
        return preg_replace('/\/\*\*/', '/*', $commentTokenText, 1);
    }

    private function updateCommentAgainstParenthesesBlock($commentTokenText)
    {
        $this->walkAndNormalizeUntil(ST_PARENTHESES_OPEN);
        $variables = $this->variableListFromParenthesesBlock($this->tkns, $this->ptr);

        $foundVar = false;
        foreach ($variables as $var) {
            if (false !== strpos($commentTokenText, $var)) {
                $foundVar = true;
                break;
            }
        }
        if (!$foundVar) {
            $commentTokenText = $this->updateComment($commentTokenText);
        }

        return $commentTokenText;
    }

    private function updateCommentAgainstVariable($commentTokenText)
    {
        list(, $nextText) = $this->rightUsefulToken();
        $this->ptr = $this->rightUsefulTokenIdx();
        $this->cache = [];
        if (!$this->rightUsefulTokenIs(ST_EQUAL) ||
            false === strpos($commentTokenText, $nextText)) {
            $commentTokenText = $this->updateComment($commentTokenText);
        }

        return $commentTokenText;
    }

    private function variableListFromParenthesesBlock($tkns, $ptr)
    {
        $sizeOfTkns = sizeof($tkns);
        $variableList = [];
        $count = 0;
        for ($i = $ptr; $i < $sizeOfTkns; ++$i) {
            $token = $tkns[$i];
            list($id, $text) = $this->getToken($token);

            if (T_VARIABLE == $id) {
                $variableList[] = $text;
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

        return array_unique($variableList);
    }
}
