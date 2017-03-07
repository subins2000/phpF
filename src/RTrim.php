<?php

namespace Fmt;

final class RTrim extends FormatterPass
{
    public function candidate($source, $foundTokens)
    {
        return true;
    }

    public function format($source)
    {
        return preg_replace('/\h+$/mu', '', $source);
    }
}
