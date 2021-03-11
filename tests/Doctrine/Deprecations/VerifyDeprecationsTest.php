<?php

declare(strict_types=1);

namespace Doctrine\Deprecations;

use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use PHPUnit\Framework\TestCase;

class VerifyDeprecationsTest extends TestCase
{
    use VerifyDeprecations;

    /**
     * @before
     */
    public function setUpDisableDeprecations(): void
    {
        // prevent PHPUnit from throwing Deprecation exception in case trigger_error was enabled before
        Deprecation::disable();
    }

    public function testExpectDeprecationWithIdentifier(): void
    {
        $this->expectDeprecationWithIdentifier('http://example.com');

        Deprecation::trigger('doctrine/dbal', 'http://example.com', 'message');
    }

    public function testExpectNoDeprecationWithIdentifier(): void
    {
        $this->expectNoDeprecationWithIdentifier('http://example.com');

        Deprecation::trigger('doctrine/dbal', 'http://otherexample.com', 'message');
    }
}
