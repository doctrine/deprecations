<?php

declare(strict_types=1);

namespace Doctrine\Deprecations;

use Psr\Log\LoggerInterface;

use function array_key_exists;
use function array_reduce;
use function debug_backtrace;
use function sprintf;
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
 *  - Uses @trigger_error() with E_USER_DEPRECATED
 *    \Doctrine\Deprecations\Deprecation::enableWithTriggerError();
 *
 *  - Sends deprecation messages via a PSR-3 logger
 *    \Doctrine\Deprecations\Deprecation::enableWithPsrLogger($logger);
 *
 * Packages that trigger deprecations should use the `trigger()` method.
 */
class Deprecation
{
    /** @var bool */
    private static $triggerError = false;

    /** @var LoggerInterface|null */
    private static $logger;

    /** @var array<string,bool> */
    private static $ignoredPackages = [];

    /** @var array<string,int> */
    private static $ignoredLinks = [];

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
        if (array_key_exists($link, self::$ignoredLinks)) {
            self::$ignoredLinks[$link]++;

            return;
        }

        // ignore this deprecation until the end of the request now
        self::$ignoredLinks[$link] = 1;

        // do not move this condition to the top, because we still want to
        // count occcurences of deprecations even when we are not logging them.
        if (!self::$triggerError && self::$logger === null) {
            return;
        }

        if (isset(self::$ignoredPackages[$package])) {
            return;
        }

        $message = sprintf($message, ...$args);

        if (self::$triggerError) {
            $message .= sprintf(
                ' (%s, package %s)',
                $link,
                $package
            );

            @trigger_error($message, E_USER_DEPRECATED);

            if (self::$logger === null) {
                return;
            }
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $context   = [
            'file' => $backtrace[0]['file'],
            'line' => $backtrace[0]['line'],
        ];

        $context['package'] = $package;
        $context['link']    = $link;

        self::$logger->notice($message, $context);
    }

    public static function enableWithTriggerError(): void
    {
        self::$triggerError = true;
    }

    public static function enableWithPsrLogger(LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    public static function disable(): void
    {
        self::$triggerError = true;
        self::$logger       = null;

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
