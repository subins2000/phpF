<?php

namespace Fmt;

final class ReindentComments extends FormatterPass
{
    public $commentStack = [];

    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_COMMENT])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        reset($this->commentStack);
        $this->tkns = token_get_all($source);
        $this->code = '';
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            $this->tkns[$this->ptr] = [$id, $text];
            if (T_COMMENT == $id) {
                if (LeftAlignComment::NON_INDENTABLE_COMMENT == $text) {
                    continue;
                }

                $oldComment = current($this->commentStack);
                next($this->commentStack);
                if (substr($text, 0, 2) != '/*') {
                    continue;
                }

                list($ptId, $ptText) = $this->inspectToken(-1);
                if (T_WHITESPACE != $ptId) {
                    continue;
                }

                $indent = substr(strrchr($ptText, 10), 1);
                $indentLevel = strlen($indent);
                $innerIndentLevel = $indentLevel + 1;
                $innerIndent = str_repeat($this->indentChar, $innerIndentLevel);

                $lines = explode($this->newLine, $oldComment[1]);
                $forceIndentation = false;
                $leftMostIndentation = -1;
                foreach ($lines as $idx => $line) {
                    if (trim($line) == '') {
                        continue;
                    }
                    if (substr($line, 0, 2) == '/*') {
                        continue;
                    }
                    if (substr($line, -2, 2) == '*/') {
                        continue;
                    }

                    if (substr($line, 0, $innerIndentLevel) != $innerIndent) {
                        $forceIndentation = true;
                    }

                    if (!$forceIndentation) {
                        continue;
                    }

                    $lenLine = strlen($line);
                    for ($i = 0; $i < $lenLine; ++$i) {
                        if ("\t" != $line[$i]) {
                            break;
                        }
                    }
                    if (-1 == $leftMostIndentation) {
                        $leftMostIndentation = $i;
                    }
                    $leftMostIndentation = min($leftMostIndentation, $i);
                }

                if ($forceIndentation) {
                    foreach ($lines as $idx => $line) {
                        if (trim($line) == '') {
                            continue;
                        }
                        if (substr($line, 0, 2) == '/*') {
                            continue;
                        }
                        if (substr($line, -2, 2) == '*/') {
                            $lines[$idx] = str_repeat($this->indentChar, $indentLevel).'*/';
                            continue;
                        }
                        $lines[$idx] = $innerIndent.substr($line, $leftMostIndentation);
                    }
                }
                $this->tkns[$this->ptr] = [T_COMMENT, implode($this->newLine, $lines)];
            }
        }

        return $this->renderLight($this->tkns);
    }
}
