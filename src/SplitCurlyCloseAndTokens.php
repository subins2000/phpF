<?php

namespace Fmt;

class SplitCurlyCloseAndTokens extends FormatterPass
{
    public function candidate($source, $foundTokens)
    {
        if (!isset($foundTokens[ST_CURLY_CLOSE])) {
            return false;
        }

        $this->tkns = token_get_all($source);
        while (list($index, $token) = each($this->tkns)) {
            list($id) = $this->getToken($token);
            $this->ptr = $index;

            if (ST_CURLY_CLOSE == $id && !$this->hasLnAfter()) {
                return true;
            }
        }

        return false;
    }

    public function format($source)
    {
        reset($this->tkns);
        $sizeofTkns = sizeof($this->tkns);

        $this->code = '';
        $blockStack = [];
        $touchedBlock = null;

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;

            switch ($id) {
                case T_DO:
                case T_ELSE:
                case T_ELSEIF:
                case T_FOR:
                case T_FOREACH:
                case T_FUNCTION:
                case T_IF:
                case T_SWITCH:
                case T_WHILE:
                case T_TRY:
                case T_CATCH:
                    $touchedBlock = $id;
                    $this->appendCode($text);
                    break;

                case ST_SEMI_COLON:
                case ST_COLON:
                    $touchedBlock = null;
                    $this->appendCode($text);
                    break;

                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                    $this->appendCode($text);
                    $this->printCurlyBlock();
                    break;

                case ST_BRACKET_OPEN:
                    $this->appendCode($text);
                    $this->printBlock(ST_BRACKET_OPEN, ST_BRACKET_CLOSE);
                    break;

                case ST_PARENTHESES_OPEN:
                    $this->appendCode($text);
                    $this->printBlock(ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE);
                    break;

                case ST_CURLY_OPEN:
                    $this->appendCode($text);
                    if (null !== $touchedBlock) {
                        $blockStack[] = $touchedBlock;
                        $touchedBlock = null;
                        break;
                    }
                    $this->printCurlyBlock();
                    break;

                case ST_CURLY_CLOSE:
                    $this->appendCode($text);
                    $poppedBlock = array_pop($blockStack);
                    if (
                        ($this->ptr + 1) < $sizeofTkns &&
                        (
                            T_ELSE == $poppedBlock ||
                            T_ELSEIF == $poppedBlock ||
                            T_FOR == $poppedBlock ||
                            T_FOREACH == $poppedBlock ||
                            T_IF == $poppedBlock ||
                            T_WHILE == $poppedBlock
                        ) &&
                        !$this->hasLnAfter() &&
                        !$this->rightTokenIs([
                            ST_BRACKET_OPEN,
                            ST_CURLY_CLOSE,
                            ST_PARENTHESES_CLOSE,
                            ST_PARENTHESES_OPEN,
                            T_COMMENT,
                            T_DOC_COMMENT,
                            T_ELSE,
                            T_ELSEIF,
                            T_IF,
                            T_OBJECT_OPERATOR,
                            T_CLOSE_TAG,
                        ])
                    ) {
                        $this->appendCode($this->newLine);
                    }
                    break;

                default:
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }
}
