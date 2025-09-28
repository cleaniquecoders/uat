# User Acceptance Testing

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/uat.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/uat)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/uat/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/cleaniquecoders/uat/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/uat/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/cleaniquecoders/uat/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/uat.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/uat)

This package is allowing developers to generate User Acceptance Scripts based on their Laravel codes. 

## Installation

You can install the package via composer:

```bash
composer require cleaniquecoders/uat
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="uat-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="uat-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="uat-views"
```

## Usage

```php
$uat = new CleaniqueCoders\Uat();
echo $uat->echoPhrase('Hello, CleaniqueCoders!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nasrul Hazim Bin Mohamad](https://github.com/nasrulhazim)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
