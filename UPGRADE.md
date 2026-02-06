Note about upgrading: [Doctrine uses static and runtime mechanisms to raise
awareness about deprecated code][deprecation-policy].

[deprecation-policy]: https://www.doctrine-project.org/policies/deprecation.html

# Upgrade to 2.0

## Added native type declarations

Some type declarations that required recent PHP versions were added to the
codebase. This means that some signatures were changed and will have to be
adjusted in sub-classes.

# Upgrade to 1.2

## Deprecated inheritance

Extending `Doctrine\Deprecations\Deprecation` or any of the methods defined in
`Doctrine\Deprecations\PHPunit\VerifyDeprecations` is deprecated and will be an
error in 2.0.
