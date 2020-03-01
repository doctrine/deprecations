# Doctrine Deprecations

A small layer on top of `trigger_error(E_USER_DEPRECATED)` or PSR-3 logging
with options to disable all deprecations or selectively for packages.

By default it logs deprecations through the default PHP deprecation mechanism
via `E_USER_DEPRECATED` and `trigger_error()`.

## Usage from a library perspective:

```php
\Doctrine\Deprecations\Deprecation::trigger("doctrine/orm", "2.7", "https://link/to/deprecations-description", "message", ...$args);
```

If variable arguments are provided at the end, they are used with `sprintf` on
the message.

Based on the issue link each deprecation message is only triggered once per
request, so it must be unique for each deprecation.

A limited stacktrace is included in the deprecation message to find the
offending location.

## Usage from users perspective:

Enable or Disable Doctrine deprecations to be sent as `trigger_error(E_USER_DEPRECATED)`
messages.

```php
\Doctrine\Deprecations\Logger::enableWithTriggerError();
\Doctrine\Deprecations\Logger::enableWithSuppressedTriggerError();
\Doctrine\Deprecations\Logger::disableTriggerError();
```

Enable Doctrine deprecations to be sent to a PSR3 logger:

```php
\Doctrine\Deprecations\Logger::enableWithPsr3Logger($logger);
```

Disable deprecations from a package, starting at given version and above

```php
\Doctrine\Deprecations\Logger::ignorePackage("doctrine/orm", "2.7");
```

Disable a specific deprecation:

```php
\Doctrine\Deprecations\Logger::ignoreDeprecation("https://link/to/deprecations-description");
```
