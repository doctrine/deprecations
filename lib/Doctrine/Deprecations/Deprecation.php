<?php

declare(strict_types=1);

namespace Doctrine\Deprecations;

use Psr\Log\LoggerInterface;

use function array_key_exists;
use function array_reduce;
use function basename;
use function debug_backtrace;
use function sprintf;
use function strpos;
use function trigger_error;

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const E_USER_DEPRECATED;

/**
 * Manages Deprecation logging in different ways.
 *
 * By default triggered exceptions are not logged, only the amount of
 * deprecations triggered can be queried with `Deprecation::getUniqueTriggeredDeprecationsCount()`.
 *
 * To enable different deprecation logging mechanisms you can call the
 * following methods:
 *
 *  - Uses trigger_error with E_USER_DEPRECATED
 *    \Doctrine\Deprecations\Deprecation::enableWithTriggerError();
 *
 *  - Uses @trigger_error with E_USER_DEPRECATED
 *    \Doctrine\Deprecations\Deprecation::enableWithSuppressedTriggerError();
 *
 *  - Sends deprecation messages via a PSR-3 logger
 *    \Doctrine\Deprecations\Deprecation::enableWithPsrLogger($logger);
 *
 * Packages that trigger deprecations should use the `trigger()` method.
 */
class Deprecation
{
    private const TYPE_NONE               = 0;
    private const TYPE_TRACK_DEPRECATIONS = 1;
    private const TYPE_TRIGGER_ERROR      = 2;
    private const TYPE_PSR_LOGGER         = 4;

    /** @var int */
    private static $type = self::TYPE_NONE;

    /** @var LoggerInterface|null */
    private static $logger;

    /** @var array<string,bool> */
    private static $ignoredPackages = [];

    /** @var array<string,int> */
    private static $ignoredLinks = [];

    /** @var bool */
    private static $deduplication = true;

    /**
     * Trigger a deprecation for the given package, starting with given version.
     *
     * The link should point to a Github issue or Wiki entry detailing the
     * deprecation. It is additionally used to de-duplicate the trigger of the
     * same deprecation during a request.
     *
     * @param mixed $args
     */
    public static function trigger(string $package, string $link, string $message, ...$args): void
    {
        if (self::$type === self::TYPE_NONE) {
            return;
        }

        if (array_key_exists($link, self::$ignoredLinks)) {
            self::$ignoredLinks[$link]++;
        } else {
            self::$ignoredLinks[$link] = 1;
        }

        if (self::$deduplication === true && self::$ignoredLinks[$link] > 1) {
            return;
        }

        if (isset(self::$ignoredPackages[$package])) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $message = sprintf($message, ...$args);

        self::delegateTriggerToBackend($message, $backtrace, $link, $package);
    }

    /**
     * @param mixed $args
     */
    public static function triggerIfCalledFromOutside(string $package, string $link, string $message, ...$args): void
    {
        if (self::$type === self::TYPE_NONE) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        // "outside" means we assume that $package is currently installed as a
        // dependency and the caller is not a file in that package.
        // When $package is installed as a root package, then this deprecation
        // is always ignored
        // first check that the caller is not from a tests folder, in which case we always let deprecations pass
        if (strpos($backtrace[1]['file'], '/tests/') === false) {
            if (strpos($backtrace[0]['file'], '/vendor/' . $package . '/') === false) {
                return;
            }

            if (strpos($backtrace[1]['file'], '/vendor/' . $package . '/') !== false) {
                return;
            }
        }

        if (array_key_exists($link, self::$ignoredLinks)) {
            self::$ignoredLinks[$link]++;
        } else {
            self::$ignoredLinks[$link] = 1;
        }

        if (self::$deduplication === true && self::$ignoredLinks[$link] > 1) {
            return;
        }

        if (isset(self::$ignoredPackages[$package])) {
            return;
        }

        $message = sprintf($message, ...$args);

        self::delegateTriggerToBackend($message, $backtrace, $link, $package);
    }

    /**
     * @param array<mixed> $backtrace
     */
    private static function delegateTriggerToBackend(string $message, array $backtrace, string $link, string $package): void
    {
        if ((self::$type & self::TYPE_PSR_LOGGER) > 0) {
            $context = [
                'file' => $backtrace[0]['file'],
                'line' => $backtrace[0]['line'],
            ];

            $context['package'] = $package;
            $context['link']    = $link;

            self::$logger->notice($message, $context);
        }

        if (! ((self::$type & self::TYPE_TRIGGER_ERROR) > 0)) {
            return;
        }

        $message .= sprintf(
            ' (%s:%d called by %s:%d, %s, package %s)',
            basename($backtrace[0]['file']),
            $backtrace[0]['line'],
            basename($backtrace[1]['file']),
            $backtrace[1]['line'],
            $link,
            $package
        );

        @trigger_error($message, E_USER_DEPRECATED);
    }

    public static function enableTrackingDeprecations(): void
    {
        self::$type |= self::TYPE_TRACK_DEPRECATIONS;
    }

    public static function enableWithTriggerError(): void
    {
        self::$type |= self::TYPE_TRIGGER_ERROR;
    }

    public static function enableWithPsrLogger(LoggerInterface $logger): void
    {
        self::$type  |= self::TYPE_PSR_LOGGER;
        self::$logger = $logger;
    }

    public static function withoutDeduplication(): void
    {
        self::$deduplication = false;
    }

    public static function disable(): void
    {
        self::$type          = self::TYPE_NONE;
        self::$logger        = null;
        self::$deduplication = true;

        foreach (self::$ignoredLinks as $link => $count) {
            self::$ignoredLinks[$link] = 0;
        }
    }

    public static function ignorePackage(string $packageName): void
    {
        self::$ignoredPackages[$packageName] = true;
    }

    public static function ignoreDeprecations(string ...$links): void
    {
        foreach ($links as $link) {
            self::$ignoredLinks[$link] = 0;
        }
    }

    public static function getUniqueTriggeredDeprecationsCount(): int
    {
        return array_reduce(self::$ignoredLinks, static function (int $carry, int $count) {
            return $carry + $count;
        }, 0);
    }

    /**
     * Returns each triggered deprecation link identifier and the amount of occurrences.
     *
     * @return array<string,int>
     */
    public static function getTriggeredDeprecations(): array
    {
        return self::$ignoredLinks;
    }
}
