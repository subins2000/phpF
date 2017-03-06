<?php

namespace Fmt;

class OrderAndRemoveUseClauses extends AdditionalPass
{
    const BLANK_LINE_AFTER_USE_BLOCK = true;

    const OPENER_PLACEHOLDER = "<?php /*\x2 ORDERBY \x3*/";

    const REMOVE_UNUSED = true;

    const SPLIT_COMMA = true;

    const STRIP_BLANK_LINES = true;

    const TRAIT_BLOCK_OPEN = 'TRAIT_BLOCK_OPEN';

    private $sortFunction = null;

    public function __construct(callable $sortFunction = null)
    {
        $this->sortFunction = $sortFunction;
        if (null == $sortFunction) {
            $this->sortFunction = function ($useStack) {
                natcasesort($useStack);

                return $useStack;
            };
        }
    }

    public function candidate($source, $foundTokens)
    {
        if (isset($foundTokens[T_USE])) {
            return true;
        }

        return false;
    }

    public function format($source = '')
    {
        $source = $this->sortWithinNamespaces($source);

        return $source;
    }

    public function getDescription()
    {
        return 'Order use block and remove unused imports.';
    }

    public function getExample()
    {
        return <<<'EOT'
// From
use B;
use C;
use D;
use \B;
use \D;
use Fmt\FormatterPass;

new B();
new D();
?>
EOT;
    }
}
