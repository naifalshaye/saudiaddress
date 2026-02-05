# Changelog

All notable changes to this package will be documented in this file.

## [2.0.0] - 2026-02-05

### Added
- Laravel 12 support
- Guzzle HTTP client for reliable API requests with timeout support
- Custom exception classes for structured error handling:
  - `SaudiAddressException` (base exception)
  - `InvalidConfigurationException` (missing API key or URL)
  - `ApiRequestException` (network/HTTP errors)
  - `InvalidResponseException` (malformed JSON or missing fields)
  - `AddressNotFoundException` (geocode returns no results)
- Type hints and return types on all public methods
- Input validation for the language parameter (`A` or `E` only)
- Configuration validation on instantiation
- Configurable default language via `SAUDI_ADDRESS_LANGUAGE` env variable
- Configurable HTTP timeout via `SAUDI_ADDRESS_TIMEOUT` env variable
- Default API URL in config (no longer required to set `SAUDI_ADDRESS_API_URL`)
- Publishable config: `php artisan vendor:publish --tag=saudiaddress-config`
- Laravel auto-discovery support (provider and facade registered automatically)
- PHPDoc `@method` annotations on the Facade for IDE autocompletion
- Comprehensive test suite (44 tests, 74 assertions)
- `.gitignore`, `.editorconfig`, `phpunit.xml`

### Changed
- Config key changed from `SaudiAddress` to `saudiaddress` (Laravel convention)
- Service binding changed from `bind()` to `singleton()` for better performance
- `verify()` method now uses config instead of `env()` directly
- HTTP client is now injected via constructor (testable, decoupled)
- All methods now accept `null` for language to use the configured default

### Fixed
- Typo in `verify()` parameter: `$bulding_number` renamed to `$buildingNumber`
- Inconsistent `env()` usage in `verify()` method (now uses config like all other methods)
- `geoCode()` no longer crashes on empty results (throws `AddressNotFoundException`)

### Removed
- `iconv()` encoding conversion (Guzzle handles encoding correctly for JSON APIs)
- `file_get_contents()` replaced with Guzzle HTTP client

### Migration Guide from v1.x

1. **Config key changed**: If you access config values directly, update `config('SaudiAddress.x')` to `config('saudiaddress.x')`.

2. **Error handling**: Methods now throw typed exceptions instead of PHP warnings. Wrap calls in try/catch:
   ```php
   use Naif\Saudiaddress\Exceptions\SaudiAddressException;

   try {
       $regions = SaudiAddress::regions();
   } catch (SaudiAddressException $e) {
       // Handle error
   }
   ```

3. **Verify parameter name**: If using named arguments (PHP 8.0+), the first parameter of `verify()` changed from `$bulding_number` to `$buildingNumber`.

4. **GeoCode empty results**: `geoCode()` now throws `AddressNotFoundException` instead of an undefined offset error when no address is found.
