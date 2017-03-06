<?php

namespace Fmt\Cache;

interface Cacher
{
    const DEFAULT_CACHE_FILENAME = '.php.tools.cache';

    public function create_db();

    public function is_changed($target, $filename);

    public function upsert($target, $filename, $content);
}
