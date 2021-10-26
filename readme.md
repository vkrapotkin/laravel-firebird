# Firebird for Laravel

[![Latest Stable Version](https://poser.pugx.org/harrygulliford/laravel-firebird/v/stable)](https://packagist.org/packages/harrygulliford/laravel-firebird)
[![Total Downloads](https://poser.pugx.org/harrygulliford/laravel-firebird/downloads)](https://packagist.org/packages/harrygulliford/laravel-firebird)
[![Tests](https://github.com/harrygulliford/laravel-firebird/actions/workflows/tests.yml/badge.svg)](https://github.com/harrygulliford/laravel-firebird/actions/workflows/tests.yml)
[![License](https://poser.pugx.org/harrygulliford/laravel-firebird/license)](https://packagist.org/packages/harrygulliford/laravel-firebird)

This package adds support for the Firebird PDO driver in Laravel applications. Support for Laravel 8+ with PHP 7.4+ and Firebird 2.5, 3 and 4.

## Installation

You can install the package via composer:

```bash
composer require harrygulliford/laravel-firebird
```

_The package will automatically register itself._

Declare the connection within your `config/database.php` file by using `firebird` as the
driver:
```php
'connections' => [

    'firebird' => [
        'driver'   => 'firebird',
        'host'     => env('DB_HOST', 'localhost'),
        'port'     => env('DB_PORT', '3050'),
        'database' => env('DB_DATABASE', '/path_to/database.fdb'),
        'username' => env('DB_USERNAME', 'sysdba'),
        'password' => env('DB_PASSWORD', 'masterkey'),
        'charset'  => env('DB_CHARSET', 'UTF8'),
        'role'     => null,
    ],

],
```

To register this package in Lumen, you'll also need to add the following line to the service providers in your `config/app.php` file:
`$app->register(\Firebird\FirebirdServiceProvider::class);`

## Limitations
This package does not support database migrations and it should not be used for this use case.

## Credits
- [jacquestvanzuydam/laravel-firebird](https://github.com/jacquestvanzuydam/laravel-firebird)
- [sim1984/laravel-firebird](https://github.com/sim1984/laravel-firebird)

## License
Licensed under the [MIT](https://choosealicense.com/licenses/mit/) licence.
