<?php

namespace Fmt;

final class PSR2AlignObjOp extends FormatterPass
{
    const ALIGNABLE_TOKEN = "\x2 OBJOP%d \x3";

    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[ST_SEMI_COLON]) || isset($foundTokens[T_ARRAY]) || isset($foundTokens[T_DOUBLE_ARROW]) || isset($foundTokens[T_OBJECT_OPERATOR])) {
            return true;
        }

        return false;
    }

    public function format($source)
    {
        $this->tkns = token_get_all($source);
        $this->code = '';
        $contextCounter = 0;
        $contextMetaCount = [];
        while (list($index, $token) = each($this->tkns)) {
            list($id, $text) = $this->getToken($token);
            $this->ptr = $index;
            switch ($id) {
                case ST_SEMI_COLON:
                case T_ARRAY:
                case T_DOUBLE_ARROW:
                    ++$contextCounter;
                    $this->appendCode($text);
                    break;

                case T_OBJECT_OPERATOR:
                    if (!isset($contextMetaCount[$contextCounter])) {
                        $contextMetaCount[$contextCounter] = 0;
                    }
                    if ($this->hasLnBefore() || 0 == $contextMetaCount[$contextCounter]) {
                        $this->appendCode(sprintf(self::ALIGNABLE_TOKEN, $contextCounter).$text);
                        ++$contextMetaCount[$contextCounter];
                        break;
                    }
                default:
                    $this->appendCode($text);
                    break;
            }
        }

        for ($j = 0; $j <= $contextCounter; ++$j) {
            $placeholder = sprintf(self::ALIGNABLE_TOKEN, $j);
            if (false === strpos($this->code, $placeholder)) {
                continue;
            }
            if (1 === substr_count($this->code, $placeholder)) {
                $this->code = str_replace($placeholder, '', $this->code);
                continue;
            }

            $lines = explode($this->newLine, $this->code);
            $linesWithObjop = [];
            $blockCount = 0;

            foreach ($lines as $idx => $line) {
                if (false !== strpos($line, $placeholder)) {
                    $linesWithObjop[$blockCount][] = $idx;
                    break;
                }
                ++$blockCount;
                $linesWithObjop[$blockCount] = [];
            }

            foreach ($linesWithObjop as $group) {
                $firstline = reset($group);
                $positionFirstline = strpos($lines[$firstline], $placeholder);

                foreach ($group as $idx) {
                    if ($idx == $firstline) {
                        continue;
                    }
                    $line = ltrim($lines[$idx]);
                    $line = str_replace($placeholder, str_repeat(' ', $positionFirstline).$placeholder, $line);
                    $lines[$idx] = $line;
                }
            }

            $this->code = str_replace($placeholder, '', implode($this->newLine, $lines));
        }

        return $this->code;
    }
}
