<?php
namespace Fmt\Cache;

final class Cache implements Cacher
{
    public function create_db()
    {
    }

    public function is_changed($target, $filename)
    {
        return file_get_contents($filename);
    }

    public function upsert($target, $filename, $content)
    {
    }
}
