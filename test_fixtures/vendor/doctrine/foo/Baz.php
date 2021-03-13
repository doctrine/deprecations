<?php

declare(strict_types=1);

namespace Doctrine\Foo;

class Baz
{
    public function usingOldFunc(): void
    {
        $bar = new Bar();
        $bar->oldFunc();
    }
}
