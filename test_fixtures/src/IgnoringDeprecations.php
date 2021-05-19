<?php

namespace DeprecationTests;

use Doctrine\Deprecations\Deprecation;

class IgnoringDeprecations
{
    public static function run(): int
    {
        return Deprecation::runIgnoringDeprecations(
            function (): int {
                Deprecation::trigger(
                    'doctrine/deprecations',
                    'ignored-deprecation',
                    'Nobody should notice us...'
                );

                return 42;
            }
        );
    }

    public static function runNested(): int
    {
        return Deprecation::runIgnoringDeprecations(
            function (): int {
                self::run();

                Deprecation::trigger(
                    'doctrine/deprecations',
                    'ignored-deprecation',
                    'Nobody should notice us either...'
                );

                return 42;
            }
        );
    }
}
