<?php

$re = '/class (.+?) /';
$str = file_get_contents('./src/phpfmt.php');

preg_match_all($re, $str, $matches);

$stub = <<<'EOT'
<?php

namespace Fmt;


EOT;

foreach ($matches[1] as $class) {
    file_put_contents("./src/$class.php", $stub);
}
