<?php

namespace Fmt;

final class SortUseNameSpace extends AdditionalPass
{
    private $pass = null;

    public function __construct()
    {
        $sortFunction = function ($useStack) {
            usort($useStack, function ($a, $b) {
                $len = strlen($a) - strlen($b);
                if (0 === $len) {
                    return strcmp($a, $b);
                }

                return $len;
            });

            return $useStack;
        };
        $this->pass = new OrderAndRemoveUseClauses($sortFunction);
    }

    public function candidate($source, $foundTokens)
    {
        return $this->pass->candidate($source, $foundTokens);
    }

    public function format($source)
    {
        return $this->pass->format($source);
    }

    public function getDescription()
    {
        return 'Organize use clauses by length and alphabetic order.';
    }

    public function getExample()
    {
        return '';
    }
}
