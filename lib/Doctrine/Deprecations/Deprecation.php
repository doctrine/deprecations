<?php

namespace Doctrine\Deprecations;

class Deprecation
{
    private const TYPE_NONE = 0;
    private const TYPE_TRIGGER_ERROR = 1;
    private const TYPE_TRIGGER_SUPPRESSED_ERROR = 2;
    private const TYPE_PSR_LOGGER = 3;

    private static $type = self::TYPE_NONE;
    private static $logger;

    private static $ignoredPackages = [];
    private static $ignoredLinks = [];

    public static function trigger(string $package, string $version, string $link, string $message, ...$args) : void
    {
        if (self::$type === self::TYPE_NONE) {
            return;
        }

        if (isset(self::$ignoredPackages[$package]) || isset(self::$ignoredLinks[$link])) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

        $message = sprintf($message, ...$args);

        if (self::$type === self::TYPE_TRIGGER_ERROR) {
            $message .= sprintf(
                " (%s:%s, %s, since %s %s)",
                basename($backtrace[0]['file']),
                $backtrace[0]['line'],
                $link,
                $package,
                $version
            );

            trigger_error($message, E_USER_DEPRECATED);
        } elseif (self::$type === self::TYPE_TRIGGER_SUPPRESSED_ERROR) {
            $message .= sprintf(
                " (%s:%s, %s, since %s %s)",
                basename($backtrace[0]['file']),
                $backtrace[0]['line'],
                $link,
                $package,
                $version
            );

            @trigger_error($message, E_USER_DEPRECATED);
        } elseif (self::$type === self::TYPE_PSR_LOGGER) {
            $context = $backtrace[0];
            unset($context['type']);

            $context['package'] = $package;
            $context['since'] = $version;
            $context['link'] = $link;

            self::$logger->debug($message, $context);
        }

        // ignore this deprecation until the end of the request now
        self::$ignoredLinks[$link] = true;
    }

    public static function enableWithTriggerError()
    {
        self::$type = self::TYPE_TRIGGER_ERROR;
    }

    public static function enableWithSuppressedTriggerError()
    {
        self::$type = self::TYPE_TRIGGER_SUPPRESSED_ERROR;
    }

    public static function enableWithPsrLogger(LoggerInterface $logger)
    {
        self::$type = self::TYPE_PSR_LOGGER;
        self::$logger = $logger;
    }

    public static function disable()
    {
        self::$type = self::TYPE_NONE;
        self::$logger = null;
    }

    public static function ignorePackages(...$packages) : void
    {
        foreach ($packages as $package) {
            self::$ignoredPackages[$package] = true;
        }
    }

    public static function ignoreDeprecations(...$links) : void
    {
        foreach ($links as $link) {
            self::$ignoredLinks[$link] = true;
        }
    }
}
