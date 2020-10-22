# Translation client package
This package is used to call translation service as easy as possible by other services. 

## Install
Add following code to your composer.json:
```json
  "repositories": [
    {
      "type": "vcs",
      "url": "git@git.lan:sportmob/translation-client.git"
    }
  ]
```
Then Install it:
```
composer require sportmob/translation:1.0.4
```

## Use 
this is a simple example how to use it.
```php
<?php

use SportMob\Translation;

$client = new Client('redis', 6379);
$keywords = ['Real Madrid', 'Internazionale'];
$languages = ['ar', 'fa'];
$translates = $client->translate($keywords, $languages);
print_r($translates);
/*
result:

{
    "Internazionale": {
        "ar": "إنترناسيونالي",
        "fa": "اینتر میلان"
    },
    "Real Madrid": {
        "ar": "ريال مدريد",
        "fa": "رئال مادرید"
    }
}

*/
$keyword = 'رئال مادرید';
$language = 'fa';
$translates = $client->translate($keyword, $language);

/*
result:

[
    "Real Madrid CF",
    "Real Madrid"
]
*/

``` 