<?php

namespace Fmt;

final class JoinToImplode extends AliasToMaster
{
    protected static $aliasList = [
        'join' => 'implode',
    ];

    public function getDescription()
    {
        return 'Replace implode() alias (join() -> implode()).';
    }

    public function getExample()
    {
        return <<<'EOT'
<?php
$a = join(',', $arr);

$a = implode(',', $arr);
?>
EOT;
    }
}
