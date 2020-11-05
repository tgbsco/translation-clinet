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

$client = new Client('127.0.0.1', 'redis', 6379);
$keyword = 'Real Madrid';
$language = 'fa'; // target language
$translate = $client->translate($keyword, $language);
var_dump($translate);
/*
result:

string "رئال مادرید"  

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