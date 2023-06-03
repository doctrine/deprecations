<?php

declare(strict_types=1);

namespace DeprecationTests;

use Doctrine\Deprecations\Deprecation;

class ConstructorDeprecation
{
    public function __construct()
    {
        Deprecation::trigger('doctrine/bar', 'https://github.com/doctrine/deprecations/issues/44', 'This constructor is deprecated.');
    }
}
