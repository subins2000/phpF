<?php

namespace Fmt;

final class AutoImportPass extends FormatterPass
{
    const AUTOIMPORT_PLACEHOLDER = "/*\x2 AUTOIMPORT \x3*/";

    const OPENER_PLACEHOLDER = "<?php /*\x2 AUTOIMPORTNS \x3*/";

    private $oracle = null;

    public function __construct($oracleFn)
    {
        $this->oracle = new SQLite3($oracleFn);
    }

    public function candidate($source, $foundTokens)
    {
        return true;
    }

    public function format($source = '')
    {
        $namespaceCount = 0;
        $tokens = token_get_all($source);
        while (list(, $token) = each($tokens)) {
            list($id, $text) = $this->getToken($token);
            if (T_NAMESPACE == $id && !$this->rightUsefulTokenIs(T_NS_SEPARATOR)) {
                ++$namespaceCount;
            }
        }
        if ($namespaceCount <= 1) {
            return $this->singleNamespace($source);
        }

        $return = '';
        reset($tokens);
        while (list($index, $token) = each($tokens)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case T_NAMESPACE:
                    $return .= $text;
                    if ($this->rightUsefulTokenIs(T_NS_SEPARATOR)) {
                        break;
                    }
                    while (list($index, $token) = each($tokens)) {
                        list($id, $text) = $this->getToken($token);
                        $this->ptr = $index;
                        $return .= $text;
                        if (ST_CURLY_OPEN == $id) {
                            break;
                        }
                    }
                    $namespaceBlock = '';
                    $curlyCount = 1;
                    while (list($index, $token) = each($tokens)) {
                        list($id, $text) = $this->getToken($token);
                        $this->ptr = $index;
                        $namespaceBlock .= $text;
                        if (ST_CURLY_OPEN == $id) {
                            ++$curlyCount;
                        } elseif (ST_CURLY_CLOSE == $id) {
                            --$curlyCount;
                        }

                        if (0 == $curlyCount) {
                            break;
                        }
                    }
                    $return .= str_replace(
                        self::OPENER_PLACEHOLDER,
                        '',
                        $this->singleNamespace(self::OPENER_PLACEHOLDER.$namespaceBlock)
                    );
                    break;
                default:
                    $return .= $text;
            }
        }

        return $return;
    }

    private function calculateAlias($use)
    {
        if (false !== stripos($use, ' as ')) {
            return substr(strstr($use, ' as '), strlen(' as '), -1);
        }

        return basename(str_replace('\\', '/', trim(substr($use, strlen('use'), -1))));
    }

    private function singleNamespace($source)
    {
        $classList = [];
        $results = $this->oracle->query('SELECT class FROM classes ORDER BY class');
        while (($row = $results->fetchArray())) {
            $className = $row['class'];
            $classNameParts = explode('\\', $className);
            $baseClassName = '';
            while (($cnp = array_pop($classNameParts))) {
                $baseClassName = $cnp.$baseClassName;
                $classList[strtolower($baseClassName)][ltrim(str_replace('\\\\', '\\', '\\'.$className).' as '.$baseClassName, '\\')] = ltrim(str_replace('\\\\', '\\', '\\'.$className).' as '.$baseClassName, '\\');
            }
        }

        $tokens = token_get_all($source);
        $aliasCount = [];
        $namespaceName = '';
        while (list($index, $token) = each($tokens)) {
            list($id, $text) = $this->getToken($token);
            if (T_NAMESPACE == $id && !$this->rightUsefulTokenIs(T_NS_SEPARATOR)) {
                while (list($index, $token) = each($tokens)) {
                    list($id, $text) = $this->getToken($token);
                    if (T_NS_SEPARATOR == $id || T_STRING == $id) {
                        $namespaceName .= $text;
                    }
                    if (ST_SEMI_COLON == $id || ST_CURLY_OPEN == $id) {
                        break;
                    }
                }
            }
            if (T_USE == $id || T_NAMESPACE == $id || T_FUNCTION == $id || T_DOUBLE_COLON == $id || T_OBJECT_OPERATOR == $id) {
                while (list($index, $token) = each($tokens)) {
                    list($id, $text) = $this->getToken($token);
                    if (ST_SEMI_COLON == $id || ST_PARENTHESES_OPEN == $id || ST_CURLY_OPEN == $id) {
                        break;
                    }
                }
            }
            if (T_CLASS == $id) {
                while (list($index, $token) = each($tokens)) {
                    list($id, $text) = $this->getToken($token);
                    if (T_EXTENDS == $id || T_IMPLEMENTS == $id || ST_CURLY_OPEN == $id) {
                        break;
                    }
                }
            }

            $lowerText = strtolower($text);
            if (T_STRING === $id && ($this->leftTokenSubsetIsAtIdx($tokens, $index, T_NEW) || $this->rightTokenSubsetIsAtIdx($tokens, $index, T_DOUBLE_COLON))) {
                if (!isset($aliasCount[$lowerText])) {
                    $aliasCount[$lowerText] = 0;
                }
                ++$aliasCount[$lowerText];
            }
        }
        $autoImportCandidates = array_intersect_key($classList, $aliasCount);

        $tokens = token_get_all($source);
        $touchedNamespace = false;
        $touchedFunction = false;
        $return = '';
        while (list(, $token) = each($tokens)) {
            list($id, $text) = $this->getToken($token);

            if (T_NAMESPACE == $id && !$this->rightUsefulTokenIs(T_NS_SEPARATOR)) {
                $touchedNamespace = true;
            }
            if (T_FUNCTION == $id) {
                $touchedFunction = true;
            }
            if (!$touchedFunction && $touchedNamespace && (T_FINAL == $id || T_STATIC == $id || T_USE == $id || T_CLASS == $id || T_INTERFACE == $id || T_TRAIT == $id)) {
                $return .= self::AUTOIMPORT_PLACEHOLDER.$this->newLine;
                $return .= $text;

                break;
            }
            $return .= $text;
        }
        while (list(, $token) = each($tokens)) {
            list(, $text) = $this->getToken($token);
            $return .= $text;
        }

        $usedAlias = $this->usedAliasList($source);
        $replacement = '';
        foreach ($autoImportCandidates as $alias => $candidates) {
            if (isset($usedAlias[$alias])) {
                continue;
            }
            usort($candidates, function ($a, $b) use ($namespaceName) {
                return similar_text($a, $namespaceName) < similar_text($b, $namespaceName);
            });
            $replacement .= 'use '.implode(';'.$this->newLine.'//use ', $candidates).';'.$this->newLine;
        }

        $return = str_replace(self::AUTOIMPORT_PLACEHOLDER.$this->newLine, $replacement, $return);

        return $return;
    }

    private function usedAliasList($source)
    {
        $tokens = token_get_all($source);
        $useStack = [];
        $newTokens = [];
        $nextTokens = [];
        $touchedNamespace = false;
        while (list(, $popToken) = each($tokens)) {
            $nextTokens[] = $popToken;
            while (($token = array_shift($nextTokens))) {
                list($id, $text) = $this->getToken($token);
                if (T_NAMESPACE == $id && !$this->rightUsefulTokenIs(T_NS_SEPARATOR)) {
                    $touchedNamespace = true;
                }
                if (T_USE === $id) {
                    $useItem = $text;
                    while (list(, $token) = each($tokens)) {
                        list($id, $text) = $this->getToken($token);
                        if (ST_SEMI_COLON === $id) {
                            $useItem .= $text;
                            break;
                        } elseif (ST_COMMA === $id) {
                            $useItem .= ST_SEMI_COLON.$this->newLine;
                            $nextTokens[] = [T_USE, 'use'];
                            break;
                        }
                        $useItem .= $text;
                    }
                    $useStack[] = $useItem;
                    $token = new SurrogateToken();
                }
                if (T_FINAL === $id || T_ABSTRACT === $id || T_INTERFACE === $id || T_CLASS === $id || T_FUNCTION === $id || T_TRAIT === $id || T_VARIABLE === $id) {
                    if (sizeof($useStack) > 0) {
                        $newTokens[] = $this->newLine;
                        $newTokens[] = $this->newLine;
                    }
                    $newTokens[] = $token;
                    break 2;
                } elseif ($touchedNamespace && (T_DOC_COMMENT === $id || T_COMMENT === $id)) {
                    if (sizeof($useStack) > 0) {
                        $newTokens[] = $this->newLine;
                    }
                    $newTokens[] = $token;
                    break 2;
                }
                $newTokens[] = $token;
            }
        }

        natcasesort($useStack);
        $aliasList = [];
        $aliasCount = [];
        foreach ($useStack as $use) {
            $alias = $this->calculateAlias($use);
            $alias = strtolower($alias);
            $aliasList[$alias] = strtolower($use);
            $aliasCount[$alias] = 0;
        }
        foreach ($newTokens as $token) {
            if (!($token instanceof SurrogateToken)) {
                list($id, $text) = $this->getToken($token);
                $lowerText = strtolower($text);
                if (T_STRING === $id && isset($aliasList[$lowerText])) {
                    ++$aliasCount[$lowerText];
                }
            }
        }
        while (list($index, $token) = each($tokens)) {
            list($id, $text) = $this->getToken($token);
            $lowerText = strtolower($text);
            if (T_STRING === $id && isset($aliasList[$lowerText]) && ($this->leftTokenSubsetIsAtIdx($tokens, $index, T_NEW) || $this->rightTokenSubsetIsAtIdx($tokens, $index, T_DOUBLE_COLON))) {
                ++$aliasCount[$lowerText];
            } elseif (T_DOC_COMMENT === $id) {
                foreach ($aliasList as $alias => $use) {
                    if (false !== stripos($text, $alias)) {
                        ++$aliasCount[$alias];
                    }
                }
            }
        }

        return $aliasCount;
    }
}
