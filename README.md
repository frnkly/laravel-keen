# [Laravel](https://laravel.com) + [Keen.io](https://keen.io)

Efficiently integrate [Keen.io](https://keen.io) analytics without slowing down your Laravel application.

[![Latest Stable Version](https://poser.pugx.org/frnkly/laravel-keen/version)](https://packagist.org/packages/frnkly/laravel-keen)
[![Latest Unstable Version](https://poser.pugx.org/frnkly/laravel-keen/v/unstable)](//packagist.org/packages/frnkly/laravel-keen)
[![Total Downloads](https://poser.pugx.org/frnkly/laravel-keen/downloads)](https://packagist.org/packages/frnkly/laravel-keen)
[![License](https://poser.pugx.org/frnkly/laravel-keen/license)](https://packagist.org/packages/frnkly/laravel-keen)

This package provides a light wrapper around the [Keen PHP client](https://github.com/keenlabs/KeenClient-PHP), as well 
as a middleware configured to track any request automatically—_after_ each request has been fulfilled. That means that
as the number of tracked events increase, the impact on request time remains virtually non-existant.

## Installation

Install the Laravel + Keen package using [Composer](https://getcomposer.org):

    $ composer require frnkly/laravel-keen

This will also install the Keen PHP client as a dependency.

## Configuration

Your Keen project ID and keys should be defined in the `config/services.php` configuration file, but will usually come
from the [environment configuration](https://laravel.com/docs/configuration):

```php
return [
    // Other 3rd-party Services
    
    'keen' => [
        'id'     => env('KEEN_PROJECT_ID'),
        'master' => env('KEEN_MASTER_KEY'),
        'write'  => env('KEEN_WRITE_KEY'),
    ],
];
```

To automatically add [data enrichment](https://keen.io/docs/api/?php#data-enrichment) to each request, use the following
configuration options:

```php
'keen' => [
    // Other Keen settings
    
    'addons' => [
        'geo-data'  => 1,   // IP to Geo parser
        'user-data' => 1,   // User Agent parser
    ],
],
```

Each data enrichment object will appear in your Keen stream under the same key name as above.

## Getting started

All you need to get started is the [service provider](https://laravel.com/docs/providers) and the 
[middleware](https://laravel.com/docs/middleware).

Start by registering the service provider in the `config/app.php` configuration file:

```php
'providers' => [
    // Other Service Providers

    Frnkly\LaravelKeen\ServiceProvider::class,
]
```

Then register the middleware globally in `app/Http/Kernel.php` if you'd like to automatically track every request. 
This step is optional and works with the data enrichment config keys.

```php
protected $middleware = [
    // Other Middleware
    
    \Frnkly\LaravelKeen\TrackRequests::class,
];
```
