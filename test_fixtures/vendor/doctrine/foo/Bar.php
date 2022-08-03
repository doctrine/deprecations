<?php

declare(strict_types=1);

namespace Doctrine\Foo;

use Doctrine\Deprecations\Deprecation;

class Bar
{
    public function oldFunc(): void
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/foo',
            'https://github.com/doctrine/foo',
            'Bar::oldFunc() is deprecated, use Bar::newFunc() instead.'
        );
    }

    public function newFunc(): void
    {
        $this->oldFunc();
    }
}
