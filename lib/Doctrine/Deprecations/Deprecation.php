<?php

declare(strict_types=1);

namespace Doctrine\Deprecations;

use Psr\Log\LoggerInterface;

use function array_key_exists;
use function array_reduce;
use function basename;
use function debug_backtrace;
use function is_numeric;
use function sprintf;
use function trigger_error;
use function version_compare;

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const E_USER_DEPRECATED;

/**
 * Manages Deprecation logging in different ways.
 *
 * By default triggered exceptions are not logged, only the amount of
 * depreceations triggered can be queried with `Deprecation::getUniqueTriggeredDeprecationsCount()`.
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
    private const TYPE_NONE                     = 0;
    private const TYPE_TRIGGER_ERROR            = 1;
    private const TYPE_TRIGGER_SUPPRESSED_ERROR = 2;
    private const TYPE_PSR_LOGGER               = 3;

    /** @var int */
    private static $type = self::TYPE_NONE;

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
    public static function trigger(string $package, string $version, string $link, string $message, ...$args): void
    {
        if (array_key_exists($link, self::$ignoredLinks)) {
            self::$ignoredLinks[$link]++;

            return;
        }

        // ignore this deprecation until the end of the request now
        self::$ignoredLinks[$link] = 1;

        // do not move this condition to the top, because we still want to
        // count occcurences of deprecations even when we are not logging them.
        if (self::$type === self::TYPE_NONE) {
            return;
        }

        if (isset(self::$ignoredPackages[$package]) && version_compare($version, self::$ignoredPackages[$package]) >= 0) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

        $message = sprintf($message, ...$args);

        if (self::$type === self::TYPE_TRIGGER_ERROR) {
            $message .= sprintf(
                ' (%s:%s, %s, since %s %s)',
                basename($backtrace[0]['file']),
                $backtrace[0]['line'],
                $link,
                $package,
                $version
            );

            trigger_error($message, E_USER_DEPRECATED);
        } elseif (self::$type === self::TYPE_TRIGGER_SUPPRESSED_ERROR) {
            $message .= sprintf(
                ' (%s:%s, %s, since %s %s)',
                basename($backtrace[0]['file']),
                $backtrace[0]['line'],
                $link,
                $package,
                $version
            );

            @trigger_error($message, E_USER_DEPRECATED);
        } elseif (self::$type === self::TYPE_PSR_LOGGER) {
            $context = [
                'file' => $backtrace[0]['file'],
                'line' => $backtrace[0]['line'],
            ];

            $context['package'] = $package;
            $context['since']   = $version;
            $context['link']    = $link;

            self::$logger->warning($message, $context);
        }
    }

    public static function enableWithTriggerError(): void
    {
        self::$type = self::TYPE_TRIGGER_ERROR;
    }

    public static function enableWithSuppressedTriggerError(): void
    {
        self::$type = self::TYPE_TRIGGER_SUPPRESSED_ERROR;
    }

    public static function enableWithPsrLogger(LoggerInterface $logger): void
    {
        self::$type   = self::TYPE_PSR_LOGGER;
        self::$logger = $logger;
    }

    public static function disable(): void
    {
        self::$type   = self::TYPE_NONE;
        self::$logger = null;

        foreach (self::$ignoredLinks as $link => $count) {
            self::$ignoredLinks[$link] = 0;
        }
    }

    public static function ignorePackage(string $packageName, string $version = '0.0.1'): void
    {
        self::$ignoredPackages[$packageName] = $version;
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
