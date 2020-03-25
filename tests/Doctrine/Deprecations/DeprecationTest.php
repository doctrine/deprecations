<?php

namespace Doctrine\Deprecations;

use PHPUnit\Framework\TestCase;

class DeprecationTest extends TestCase
{
    public function testDeprecation()
    {
        Deprecation::enableWithTriggerError();

        $this->expectDeprecation('this is deprecated foo 1234 (DeprecationTest.php:23, https://github.com/doctrine/deprecations/1234, since doctrine/orm 2.7)');

        Deprecation::trigger(
            "doctrine/orm",
            "2.7",
            "https://github.com/doctrine/deprecations/1234",
            "this is deprecated %s %d",
            "foo",
            1234
        );
    }
}
