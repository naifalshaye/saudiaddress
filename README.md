# Saudi Address - Laravel Package

Laravel wrapper for the [Saudi National Address APIs](https://api.address.gov.sa/).

## Requirements

- PHP >= 7.2
- Laravel 5.5 - 12.x

## Installation

```bash
composer require naif/saudiaddress
```

**Laravel 5.5+** uses auto-discovery, so the service provider and facade are registered automatically.

For **Laravel < 5.5**, add to `config/app.php`:

```php
'providers' => [
    Naif\Saudiaddress\SaudiAddressServiceProvider::class,
],

'aliases' => [
    'SaudiAddress' => Naif\Saudiaddress\Facades\SaudiAddress::class,
],
```

## Configuration

### API Key

Get your API key from [https://api.address.gov.sa/](https://api.address.gov.sa/) and add it to your `.env`:

```
SAUDI_ADDRESS_API_KEY=your-api-key-here
```

The default API URL is already configured. Override it only if needed:

```
SAUDI_ADDRESS_API_URL=https://apina.address.gov.sa/NationalAddress/v3.1
```

### Optional Settings

```
SAUDI_ADDRESS_LANGUAGE=A        # Default language: A (Arabic) or E (English)
SAUDI_ADDRESS_TIMEOUT=30        # HTTP timeout in seconds
```

### Publish Config

To customize the configuration file:

```bash
php artisan vendor:publish --tag=saudiaddress-config
```

## Usage

### Get Regions

```php
use Naif\Saudiaddress\Facades\SaudiAddress;

$regions = SaudiAddress::regions();

// In English
$regions = SaudiAddress::regions('E');
```

### Get Cities

```php
// All cities
$cities = SaudiAddress::cities();

// Cities in a specific region
$cities = SaudiAddress::cities(1);

// In English
$cities = SaudiAddress::cities(1, 'E');
```

### Get Districts

```php
// Districts within a city
$districts = SaudiAddress::districts(3);

// In English
$districts = SaudiAddress::districts(3, 'E');
```

### Reverse Geocode

Get address details from latitude/longitude coordinates:

```php
$address = SaudiAddress::geoCode(24.774265, 46.738586);

// Access properties
echo $address->BuildingNumber; // "7596"
echo $address->Street;         // "الديوان"
echo $address->District;       // "Al Hamra Dist.,حي الحمراء"
echo $address->City;           // "RIYADH,الرياض"
echo $address->PostCode;       // "13216"
```

### Verify Address

Verify an address by building number, postal code, and additional number:

```php
$isValid = SaudiAddress::verify(7596, 13216, 2802);

if ($isValid) {
    echo 'Address is valid!';
}
```

### Free-Text Address Search

Search for addresses using free-text query:

```php
$addresses = SaudiAddress::freeTextSearch('Riyadh Olaya');

// With pagination (10 results per page)
$addresses = SaudiAddress::freeTextSearch('Riyadh Olaya', 2);

// In English
$addresses = SaudiAddress::freeTextSearch('Riyadh Olaya', 1, 'E');
```

### Fixed-Parameter Address Search

Search for addresses using specific parameters:

```php
$addresses = SaudiAddress::fixedSearch([
    'CityId' => 3,
    'DistrictId' => 100,
    'StreetName' => 'King Fahd',
]);

// With pagination
$addresses = SaudiAddress::fixedSearch(['CityId' => 3], 2);
```

### Bulk Address Search

Search for multiple addresses at once (up to 10):

```php
$addresses = SaudiAddress::bulkSearch([
    'Riyadh, Olaya, 1234',
    'Jeddah, Al Hamra, 5678',
]);
```

### Short Address Lookup

Look up a national address by short address code (4 letters + 4 digits):

```php
$addresses = SaudiAddress::shortAddress('ABCD1234');
```

### Verify Short Address

Check whether a short address code is valid:

```php
$isValid = SaudiAddress::verifyShortAddress('ABCD1234');

if ($isValid) {
    echo 'Short address is valid!';
}
```

### POI Free-Text Search

Search for points of interest using free text:

```php
$results = SaudiAddress::poiFreeTextSearch('hospital');

// With pagination
$results = SaudiAddress::poiFreeTextSearch('hospital', 2);
```

### POI Fixed-Parameter Search

Search for points of interest with specific parameters:

```php
$results = SaudiAddress::poiFixedSearch('school', 1);

// With optional filters
$results = SaudiAddress::poiFixedSearch('school', 1, ['CityId' => 3]);
```

### Nearest POI

Find nearest points of interest by coordinates:

```php
$results = SaudiAddress::nearestPoi(24.774265, 46.738586);

// With custom radius (km)
$results = SaudiAddress::nearestPoi(24.774265, 46.738586, 2.0);
```

### Service Categories

Get service categories for POI search:

```php
$categories = SaudiAddress::serviceCategories();

// In English
$categories = SaudiAddress::serviceCategories('E');
```

### Service Sub-Categories

Get sub-categories for a given service category:

```php
$subCategories = SaudiAddress::serviceSubCategories(1);
```

### Send OTP (Address By Phone)

Send OTP to a mobile number for address lookup:

```php
$response = SaudiAddress::sendOtp('STORE001', '0501234567');
```

### Address By Phone

Get addresses associated with a phone number after OTP verification:

```php
$addresses = SaudiAddress::addressByPhone('STORE001', '0501234567', '1234');
```

### Feature Extents

Get geographic extents for a feature layer:

```php
// Valid layers: regions, cities, districts, streets, zipcodes
$extents = SaudiAddress::featureExtents('regions', '1');

echo $extents->xmin;
echo $extents->ymin;
echo $extents->xmax;
echo $extents->ymax;
```

## Error Handling

All methods throw typed exceptions that extend `SaudiAddressException`:

```php
use Naif\Saudiaddress\Exceptions\SaudiAddressException;
use Naif\Saudiaddress\Exceptions\ApiRequestException;
use Naif\Saudiaddress\Exceptions\InvalidResponseException;
use Naif\Saudiaddress\Exceptions\AddressNotFoundException;
use Naif\Saudiaddress\Exceptions\InvalidConfigurationException;

try {
    $address = SaudiAddress::geoCode(24.774265, 46.738586);
} catch (AddressNotFoundException $e) {
    // No address found at these coordinates
} catch (ApiRequestException $e) {
    // Network error or API returned an error
} catch (InvalidResponseException $e) {
    // API returned unexpected data
} catch (SaudiAddressException $e) {
    // Catch-all for any Saudi Address related error
}
```

## Testing

```bash
composer test
```

## Support

- Author: Naif Alshaye (naif@naif.io)
- LinkedIn: [https://www.linkedin.com/in/naif](https://www.linkedin.com/in/naif)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
