# PHP Laravel wrapper for the Saudi National Address APIs

## Installation
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
## Usage

Get a list of regions
```
$regions = SaudiAddress::regions();

Response:
[
  0 => {#181
    +"Id": "12"
    +"Name": " الباحة"
  }
  1 => {#182
    +"Id": "13"
    +"Name": " الجوف"
  }
  2 => {#188
    +"Id": "9"
    +"Name": " الحدود الشمالية"
  }
  3 => {#189
    +"Id": "1"
    +"Name": " الرياض"
  }
]
```
Get a list of cities within a region (by region id)
* To get a list of all cities don't pass a region id
```
$cities = SaudiAddress::cities(1);

Response:
[
  0 => {#183
    +"Id": "3"
    +"Name": "الرياض"
  }
  1 => {#189
    +"Id": "1061"
    +"Name": "الخرج"
  }
  2 => {#190
    +"Id": "828"
    +"Name": "الدرعية"
  }
  3 => {#191
    +"Id": "669"
    +"Name": "الدوادمي"
  }
]
```

Get a list of districts within a city (by city id)

```
$districts = SaudiAddress::districts(1);

Response:
[
  0 => {#184
    +"Id": "10700001041"
    +"Name": "اسكان قوى الامن العام"
  }
  1 => {#190
    +"Id": "10700001018"
    +"Name": "حي ابو سبعة"
  }
  2 => {#191
    +"Id": "10700001021"
    +"Name": "حي البساتين"
  }
  3 => {#192
    +"Id": "10700001030"
    +"Name": "حي الخالدية"
  }
  4 => {#193
    +"Id": "10700001044"
    +"Name": "حي الرابية"
  }
  5 => {#194
    +"Id": "10700001012"
    +"Name": "حي الروضة"
  }
]
```

Geocode, to get address details by geo location (latitude,longitude)
```
$address = SaudiAddress::geoCode(24.774265,46.738586);

Response:
[
  +"Title": null
  +"Address1": "7596 الديوان - Al Hamra Dist.,حي الحمراء"
  +"Address2": "RIYADH,الرياض 13216 - 2802"
  +"ObjLatLng": "1"
  +"BuildingNumber": "7596"
  +"Street": "الديوان"
  +"District": "Al Hamra Dist.,حي الحمراء"
  +"City": "RIYADH,الرياض"
  +"PostCode": "13216"
  +"AdditionalNumber": "2802"
  +"RegionName": "منطقة الرياض"
  +"PolygonString": null
  +"IsPrimaryAddress": null
  +"UnitNumber": null
  +"Latitude": null
  +"Longitude": null
  +"CityId": "3"
  +"RegionId": null
  +"Restriction": "Null"
  +"PKAddressID": null
  +"DistrictID": null
  +"Title_L2": null
  +"RegionName_L2": null
  +"City_L2": null
  +"Street_L2": null
  +"District_L2": null
  +"CompanyName_L2": null
  +"GovernorateID": null
  +"Governorate": null
  +"Governorate_L2": null
  ]
```

Verify an address by (Bulding No, PostCode, Additional No)
```
$verify = SaudiAddress::verify(7596,13216,2802);

Response:

true/false
```
To get results in English, just pass 'E' as a last paramater.
Example
```
$districts = SaudiAddress::districts(1,'E');
```
## Support:
naif@naif.io

https://www.linkedin.com/in/naif
