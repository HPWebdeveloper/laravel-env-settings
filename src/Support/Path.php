<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Support;

/**
 * Filesystem path helpers.
 *
 * Both operate on the path as a string and never touch the filesystem, so
 * they work for directories that have not been created yet.
 *
 * @internal
 */
final class Path
{
    /**
     * Determine whether a path is absolute on the current platform.
     *
     * Covers POSIX roots (`/srv/...`), Windows drive roots (`C:\...`, `C:/...`)
     * and UNC shares (`\\server\share`).
     */
    public static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    /**
     * Normalise separators and collapse `.` and `..` segments.
     *
     * Used to compare two paths for containment, so both sides must go
     * through it before any prefix check.
     */
    public static function canonicalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $prefix = '';

        if (preg_match('/^([A-Za-z]:)\//', $path, $matches) === 1) {
            $prefix = $matches[1];
            $path = substr($path, 2);
        }

        $isAbsolute = str_starts_with($path, '/');
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        return $prefix.($isAbsolute ? '/' : '').implode('/', $segments);
    }
}
