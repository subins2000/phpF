<?php

namespace Fmt;

class ExternalPass
{
    private $passName = '';

    public function __construct($passName)
    {
        $this->passName = $passName;
    }

    public function candidate()
    {
        return !empty($this->passName);
    }

    public function format($source)
    {
        $descriptorspec = [
            0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

        $cwd = getcwd();
        $env = [];
        $argv = $_SERVER['argv'];
        $pipes = null;

        $external = str_replace('fmt.', 'fmt-external.', $cwd . DIRECTORY_SEPARATOR . $argv[0]);

        $cmd = $_SERVER['_'] . ' ' . $external . ' --pass=' . $this->passName;
        $process = proc_open(
            $cmd,
            $descriptorspec,
            $pipes,
            $cwd,
            $env
        );
        if (!is_resource($process)) {
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            return $source;
        }
        fwrite($pipes[0], $source);
        fclose($pipes[0]);

        $source = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        fclose($pipes[2]);
        proc_close($process);

        return $source;
    }
}
