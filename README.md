# Doctrine Deprecations

A small layer on top of `trigger_error(E_USER_DEPRECATED)` or PSR-3 logging
with options to disable all deprecations or selectively for packages.

By default it does not log deprecations at runtime and needs to be configured
to log through either trigger_error or with a PSR-3 logger.

## Usage from consumer perspective:

Enable or Disable Doctrine deprecations to be sent as `trigger_error(E_USER_DEPRECATED)`
messages.

```php
\Doctrine\Deprecations\Deprecation::enableWithTriggerError();
\Doctrine\Deprecations\Deprecation::enableWithSuppressedTriggerError();
\Doctrine\Deprecations\Deprecation::disable();
```

Enable Doctrine deprecations to be sent to a PSR3 logger:

```php
\Doctrine\Deprecations\Deprecation::enableWithPsrLogger($logger);
```

Disable deprecations from a package, starting at given version and above

```php
\Doctrine\Deprecations\Deprecation::ignorePackages("doctrine/orm");
```

Disable a specific deprecation:

```php
\Doctrine\Deprecations\Deprecation::ignoreDeprecations("https://link/to/deprecations-description-identifier");
```

## Usage from a library perspective:

```php
\Doctrine\Deprecations\Deprecation::trigger(
    "doctrine/orm",
    "2.7",
    "https://link/to/deprecations-description",
    "message",
    ...$args
);
```

If variable arguments are provided at the end, they are used with `sprintf` on
the message.

Based on the issue link each deprecation message is only triggered once per
request, so it must be unique for each deprecation.

A limited stacktrace is included in the deprecation message to find the
offending location.
