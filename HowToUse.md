# How To Use this library

## Install into your project

```bash
$ composer.phar require trismegiste/wam-prolog
```

## In standalone mode
Open a command line interface and type :
``` bash
php prolog.php
```

## Using this library
See the file test WAMService1Test.php for a real example
```php
$wam = new WAMService();
$solve = $wam->runQuery("consult('" . __DIR__ . '/fixtures/' . "fixtures1.pro').");
$solve = $wam->runQuery("grandmother(X, luke).");
```

## See a full example in real life

Try [Trismegiste/Hiragana][1]

[1]: https://github.com/Trismegiste/Hiragana
