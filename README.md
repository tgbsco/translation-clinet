# Translation client package
This package is used to call translation service as easy as possible by other services. 

## Install
To Install it:
```
composer require sportmob/translation-client
```

## Use 
this is a simple example how to use it.
```php
<?php

use SportMob\Translation\Client;

$translationServiceBaseUrl = 'http://localhost';
$redisHost = 'redis';
$redisPort = 6379;

$client = new Client($translationServiceBaseUrl, $redisHost, $redisPort);
$keyword = 'Real Madrid';
$language = 'fa'; // target language

$translate = $client->getTranslate($keyword, $language);
var_dump($translate);
/*
result:

string "رئال مادرید"  

*/

$translates = $client->translateAll($keyword);
/*
result:

[
  "ar" => "ريال مدريد",
  "fa" => "رئال مادرید",
  "en" => "Real Madrid"
]
*/

$keyword = 'رئال مادرید';
$language = 'fa';  // origin language
$translates = $client->search($keyword, $language);
/*
result:

[
    "Real Madrid CF",
    "Real Madrid"
]
*/


``` 