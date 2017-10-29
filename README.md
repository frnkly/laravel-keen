# [Laravel](https://laravel.com) + [Keen.io](https://keen.io)

Efficiently integrate [Keen.io](https://keen.io) analytics without slowing down your Laravel application.

[![Latest Stable Version](https://poser.pugx.org/frnkly/laravel-keen/version)](https://packagist.org/packages/frnkly/laravel-keen)
[![Build Status](https://travis-ci.org/frnkly/laravel-embeds.png)](https://travis-ci.org/frnkly/laravel-embeds)
[![Total Downloads](https://poser.pugx.org/frnkly/laravel-keen/downloads)](https://packagist.org/packages/frnkly/laravel-keen)
[![License](https://poser.pugx.org/frnkly/laravel-keen/license)](https://packagist.org/packages/frnkly/laravel-keen)

This package provides a light wrapper around the 
[Keen PHP client](https://github.com/keenlabs/KeenClient-PHP), as well as a 
middleware configured to track any request automatically—_after_ each request 
has been fulfilled. That means that as the number of tracked events increase, 
the impact on request time remains virtually non-existant.

## Installation

Install the Laravel + Keen package using [Composer](https://getcomposer.org):

    $ composer require frnkly/laravel-keen

This will also install the Keen PHP client as a dependency.

## Configuration

Your Keen project ID and keys should be defined in the `config/services.php` 
configuration file, but will usually come from the 
[environment configuration](https://laravel.com/docs/configuration):

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

### Data enrichment
To automatically add [data enrichment](https://keen.io/docs/api/?php#data-enrichment) 
to each request, use the following configuration options:

```php
'keen' => [
    // Other Keen settings
    
    'addons' => [
        'geo_data'  => 1,   // IP to Geo parser
        'user_data' => 1,   // User Agent parser
    ],
],
```

Each data enrichment object will appear in your Keen stream under the same key 
name as above (e.g. `geo_data`, `user_data`, etc.).

## Getting started

Thanks to [package discovery](https://laravel.com/docs/packages#package-discovery) 
in Laravel 5.5, the service provider should already be available to use. It can 
also be manually registered through the `config/app.php` configuration file:

```php
'providers' => [
    // Other Service Providers

    Frnkly\LaravelKeen\ServiceProvider::class,
]
```

Optionally, you may choose to register the pre-configured middleware globally 
in `app/Http/Kernel.php` to enable automatic tracking on every requests:

```php
protected $middleware = [
    // Other Middleware
    
    \Frnkly\LaravelKeen\TracksRequests::class,
];
```

Or within a middleware group:

```php
protected $middlewareGroups = [
    'web' => [
        // Other "Web" Middleware
        
        \Frnkly\LaravelKeen\TracksRequests::class,
    ],

    'api' => [
        // Other "API" Middleware
        
        \Frnkly\LaravelKeen\TracksRequests::class,
    ],
];
```

The middleware works with the [data enrichment config keys](#data-enrichment).
