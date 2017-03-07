<?php

$path_to_phpfmt = __DIR__.'/../../src/phpfmt.php';

function executeCommand($args, $stdin)
{
    global $path_to_phpfmt;

    $arguments = '';
    foreach ($args as $option => $value) {
        if (is_numeric($option)) {
            $arguments .= ' '.escapeshellarg($value);
        } else {
            $arguments .= " $option=".escapeshellarg($value);
        }
    }

    $cmd = sprintf('php %s %s -o=- -', $path_to_phpfmt, $arguments);

    $descriptorspec = array(
        0 => array('pipe', 'r'), // stdin is a pipe that the child will read from
        1 => array('pipe', 'w'), // stdout is a pipe that the child will write to
    );

    $process = proc_open($cmd, $descriptorspec, $pipes);

    if (is_resource($process)) {
        // $pipes now looks like this:
        // 0 => writeable handle connected to child stdin
        // 1 => readable handle connected to child stdout

        fwrite($pipes[0], $stdin); // file_get_contents('php://stdin')
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        // It is important that you close any pipes before calling
        // proc_close in order to avoid a deadlock
        $return_value = proc_close($process);

        return $stdout;
    }
}
