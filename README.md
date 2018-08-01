# Laravel wrapper for the Saudi National Address APIs

```
composer require naif/saudiaddress
```

Add service provider and alias to config/app.php
```
Naif\Saudiaddress\SaudiAddressServiceProvider::class,
'SaudiAddress' => Naif\Saudiaddress\Facades\SaudiAddress::class,
```
## API KEYS
Obtain your National Address API key from https://api.address.gov.sa/

Add these to your .env
```
SAUDI_ADDRESS_API_URL=https://apina.address.gov.sa/NationalAddress/v3.1
SAUDI_ADDRESS_API_KEY=XXXXXXXXXXXXXXXXX
```
