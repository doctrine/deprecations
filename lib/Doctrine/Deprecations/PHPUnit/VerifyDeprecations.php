<?php

declare(strict_types=1);

namespace Doctrine\Deprecations\PHPUnit;

use Doctrine\Deprecations\Deprecation;

use function sprintf;

trait VerifyDeprecations
{
    /** @var array<string,int> */
    private $doctrineDeprecationsExpectations = [];

    public function expectDeprecationWithIdentifier(string $identifier): void
    {
        $this->doctrineDeprecationsExpectations[$identifier] = Deprecation::getTriggeredDeprecations()[$identifier] ?? 0;
    }

    /**
     * @after
     */
    public function verifyDeprecationsAreTriggered(): void
    {
        foreach ($this->doctrineDeprecationsExpectations as $identifier => $expectation) {
            $actualCount = Deprecation::getTriggeredDeprecations()[$identifier] ?? 0;

            $this->assertTrue(
                $actualCount > $expectation,
                sprintf(
                    "Expected deprecation with identifier '%s' was not triggered by code executed in test.",
                    $identifier
                )
            );
        }
    }
}
