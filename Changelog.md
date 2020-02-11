# Change Log

The change log describes what is "Added", "Removed", "Changed" or "Fixed" between each release.

## Unreleased

## 2.2.0

- Adding support for translation files with intl domain suffix

## 2.1.0

- Adding support for `php-translation/common` 3.0

## 2.0.0

- Drop `symfony/translation` < 3.4 support by removing legacy layer
- Add strict type hinting

## 1.1.0

- Remove support of PHP < 7.2
- Remove support of Symfony < 3.4
- Add support for Symfony ^5.0

## 1.0.1

### Fixed

- The `LegacyTranslationReader` should also check for`Translation\SymfonyStorage\TranslationLoader`.

### Changed

- The `Translation\SymfonyStorage\TranslationLoader` was deprecated.

## 1.0.0

### Added

- Support for php-translation/common: 1.0

## 0.5.0

### Added

- Support for php-translation/common: 0.3
- Added the "name" attribute on the "unit" node for XLIFF 2.0.

## 0.4.0

### Added

- Introduced `LegacyTranslationReader` and `LegacyTranslationWriter` to provide BC support for Symfony 2.7 to 3.3.

### Changed

- Travis config.

### Removed

- Removed type annotation for first parameter of `FileStorage::__construct`. Type checks will be done with if-statements
to support legacy code.

## 0.3.3

### Added

- Support for Symfony 4

## 0.3.2

### Added

- Support for Symfony 2.7

## 0.3.1

### Fixed

- Make sure XliffLoader can load from resource

## 0.3.0

### Added

- Added more tests
- Improved loader and dumper of Xliff2.0 meta

### Changed

- We will create an output file if no file exists.
- Xliff2.0 is default format

## 0.2.2

### Added

- Support for options in `XliFfConverter`

## 0.2.1

### Added

- Support for options in `FileStorage`

## 0.2.0

### Added

- `XliffDumper` and `XliffLoader`
- A `XliffConverter` utility class
- The `FileStorage` has support for `TransferableStorage`

## 0.1.1

### Added

- Tests and better validation

## 0.1.0

Init release
