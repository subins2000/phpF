<?php

namespace Fmt;

final class ReindentColonBlocks extends FormatterPass
{
    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_ENDIF]) || isset($foundTokens[T_ENDWHILE]) || isset($foundTokens[T_ENDFOREACH]) || isset($foundTokens[T_ENDFOR])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';

        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;

            if (
                T_ENDIF == $id || T_ELSEIF == $id ||
                T_ENDFOR == $id || T_ENDFOREACH == $id || T_ENDWHILE == $id ||
                (T_ELSE == $id && !$this->rightUsefulTokenIs(ST_CURLY_OPEN))
            ) {
                $this->setIndent(-1);
            }
            switch ($id) {
                case T_ENDFOR:
                case T_ENDFOREACH:
                case T_ENDWHILE:
                case T_ENDIF:
                    $this->appendCode($text);
                    break;

                case T_ELSE:
                    $this->appendCode($text);
                    $this->indentBlock();
                    break;

                case T_FOR:
                case T_FOREACH:
                case T_WHILE:
                case T_ELSEIF:
                case T_IF:
                    $this->appendCode($text);
                    $this->printUntil(ST_PARENTHESES_OPEN);
                    $this->printBlock(ST_PARENTHESES_OPEN, ST_PARENTHESES_CLOSE);
                    $this->indentBlock();
                    break;

                case T_START_HEREDOC:
                    $this->appendCode($text);
                    $this->printUntil(T_END_HEREDOC);
                    break;

                default:
                    $hasLn = $this->hasLn($text);
                    if ($hasLn) {
                        if ($this->rightTokenIs([T_ENDIF, T_ELSE, T_ELSEIF, T_ENDFOR, T_ENDFOREACH, T_ENDWHILE])) {
                            $this->setIndent(-1);
                            $text = str_replace($this->newLine, $this->newLine.$this->getIndent(), $text);
                            $this->setIndent(+1);
                        } else {
                            $text = str_replace($this->newLine, $this->newLine.$this->getIndent(), $text);
                        }
                    }
                    $this->appendCode($text);
                    break;
            }
        }

        return $this->code;
    }

    private function indentBlock()
    {
        $foundId = $this->printUntilAny([ST_COLON, ST_SEMI_COLON, ST_CURLY_OPEN]);
        if (ST_COLON === $foundId && !$this->rightTokenIs([T_CLOSE_TAG])) {
            $this->setIndent(+1);
        }
    }
}
