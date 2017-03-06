<?php

namespace Fmt;

abstract class AdditionalPass extends FormatterPass
{
    abstract public function getDescription();

    abstract public function getExample();
}
