<?php

namespace Jh\Import\Type;

use Illuminate\Support\Collection;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class FileMatcher
{
    /**
     * @param string $pattern
     * @param array $files
     * @return Collection
     */
    public function matched(string $pattern, array $files): Collection
    {
        $files = collect($files);

        if ($pattern === '*') {
            return $files;
        }

        //treat as regex
        if ($pattern[0] === '/') {
            return $files->filter(function ($file) use ($pattern) {
                return preg_match($pattern, pathinfo($file, PATHINFO_BASENAME));
            })->values();
        }

        //else treat as single file match
        return $files->filter(function ($file) use ($pattern) {
            return $pattern === pathinfo($file, PATHINFO_BASENAME);
        })->values();
    }

    public function matches(string $pattern, string $file): bool
    {
        return $this->matched($pattern, [$file])->count() === 1;
    }
}
