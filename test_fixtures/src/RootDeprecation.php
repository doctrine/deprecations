<?php

declare(strict_types=1);

namespace DeprecationTests;

use Doctrine\Deprecations\Deprecation;

class RootDeprecation
{
    public static function run(): void
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/orm',
            'https://github.com/doctrine/deprecations/4444',
            'this is deprecated %s %d',
            'foo',
            1234
        );
    }
}
