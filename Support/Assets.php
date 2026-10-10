<?php

namespace App\Modules\Handbook\Support;

/** The built editor (dist/), served by the handbook.asset route with a version for the cache. */
class Assets
{
    public static function path(string $file): string
    {
        return dirname(__DIR__) . '/dist/' . $file;
    }

    public static function url(string $file): string
    {
        static $versions = [];
        $versions[$file] ??= substr((string) @md5_file(static::path($file)), 0, 8);

        return route('handbook.asset', $file) . '?v=' . $versions[$file];
    }
}
