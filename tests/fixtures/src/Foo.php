<?php

declare(strict_types=1);

namespace DeprecationTests;

use Doctrine\Foo\Bar;

class Foo
{
    public function triggerDependencyWithDeprecation(): void
    {
        $bar = new Bar();
        $bar->oldFunc();
    }

    public function triggerDependencyWithDeprecationFromInside(): void
    {
        $bar = new Bar();
        $bar->newFunc();
    }
}
