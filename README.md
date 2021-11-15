# Translation client package
This package is used to call translation service as easy as possible by other services. 

## Installation
To Install it:
```
composer require sportmob/translation-client
```

## Example
this is a simple example how to use it.
```php
<?php

use SportMob\Translation\Client;

$translationServiceBaseUrl = 'http://localhost';
$redisHost = 'redis';
$redisPort = 6379;

$client = new Client($translationServiceBaseUrl, $redisHost, $redisPort);
$id = 'qweasdzxcv'; // sportmob id
$language = 'ar'; // target language

$translate = $client->getByEntityId($id, $language);
var_dump($translate);
/*
result:

string "ریال مدرید"  

*/
``` 