<?php

namespace Fmt;

final class ClassToStatic extends ClassToSelf
{
    const PLACEHOLDER = 'static';

    public function getDescription()
    {
        return '"static" is preferred within class, trait or interface.';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
// From
class A {
    const constant = 1;
    function b(){
        A::constant;
    }
}

// To
class A {
    const constant = 1;
    function b(){
        static::constant;
    }
}
?>
EOT;
    }
}
