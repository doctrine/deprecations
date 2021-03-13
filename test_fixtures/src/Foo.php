<?php

declare(strict_types=1);

namespace DeprecationTests;

use Doctrine\Foo\Bar;

class Foo
{
    public static function triggerDependencyWithDeprecation(): void
    {
        $bar = new Bar();
        $bar->oldFunc();
    }

    public static function triggerDependencyWithDeprecationFromInside(): void
    {
        $bar = new Bar();
        $bar->newFunc();
    }
}
