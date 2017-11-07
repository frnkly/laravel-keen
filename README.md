# [Laravel](https://laravel.com) + [Keen.io](https://keen.io)

Don't let an external service unnecessarily slow down your application.
Efficiently integrate the awesome [Keen.io](https://keen.io) analytics service
with virtually no impact on request time.

[![Latest Stable Version](https://poser.pugx.org/frnkly/laravel-keen/version)](https://packagist.org/packages/frnkly/laravel-keen)
[![Build Status](https://travis-ci.org/frnkly/laravel-keen.png)](https://travis-ci.org/frnkly/laravel-keen)
[![Total Downloads](https://poser.pugx.org/frnkly/laravel-keen/downloads)](https://packagist.org/packages/frnkly/laravel-keen)
[![License](https://poser.pugx.org/frnkly/laravel-keen/license)](https://packagist.org/packages/frnkly/laravel-keen)

This package provides a light wrapper around the 
[Keen PHP client](https://github.com/keenlabs/KeenClient-PHP), as well as a 
middleware configured to track any request automatically—_after_ each request 
has been fulfilled. That means that as the number of tracked events increase, 
the impact on request time remains virtually non-existant.

- [Installation](#installation)
- [Configuration](#configuration)
- [Getting started](#getting-started)
- [Data enrichment](#data-enrichment)
- [Using your own middleware](#using-your-own-middleware)
- [Features](#features)

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
    // ...
    
    // Add your Keen information here
    'keen' => [
        'id'     => env('KEEN_PROJECT_ID'),
        'master' => env('KEEN_MASTER_KEY'),
        'write'  => env('KEEN_WRITE_KEY'),
    ],
];
```

And in your `.env` file, you might add something similar to:

```
KEEN_PROJECT_ID=your-project-id
KEEN_MASTER_KEY=your-master-key
KEEN_WRITE_KEY=your-write-key
```

## Getting started

Thanks to [package discovery](https://laravel.com/docs/packages#package-discovery) 
in Laravel 5.5, the service provider should already be available to use. It can 
also be manually registered through the `config/app.php` configuration file:

```php
'providers' => [
    // Other Service Providers
    // ...

    Frnkly\LaravelKeen\ServiceProvider::class,
]
```

Optionally, you may choose to register the pre-configured middleware globally 
in `app/Http/Kernel.php` to enable automatic tracking on every requests:

```php
protected $middleware = [
    // Other Middleware
    // ...
    
    \Frnkly\LaravelKeen\TracksRequests::class,
];
```

Or within a middleware group:

```php
protected $middlewareGroups = [
    'web' => [
        // Other "Web" Middleware
        // ...
        
        \Frnkly\LaravelKeen\TracksRequests::class,
    ],

    'api' => [
        // Other "API" Middleware
        // ...
        
        \Frnkly\LaravelKeen\TracksRequests::class,
    ],
];
```

The middleware works with the [data enrichment config keys](#data-enrichment).

## Data enrichment
To automatically add [data enrichment](https://keen.io/docs/api/?php#data-enrichment) 
to each request, use the following configuration options:

```php
'keen' => [
    // Other Keen settings
    // ...
    
    'addons' => [
        'ip_to_geo' => true,    // IP to Geo parser
        'ua_parser' => true,    // User Agent parser
    ],
],
```

Each data enrichment object will appear in your Keen stream under the same key 
name as above (i.e. `ip_to_geo` and `ua_parser`).

## Using your own middleware

The included middleware is easily extensible, and can help you gain more
granular control over the data that gets sent to Keen. You can create your own 
middleware using `Artisan`, and then make it extend 
`\Frnkly\LaravelKeen\TracksRequests`:

    $ php artisan make:middleware TracksRequests
    
Inside the new middleware, override the protected method 
`buildRequestEventData`:

```php
<?php

namespace App\Http\Middleware;

class TracksRequests extends \Frnkly\LaravelKeen\TracksRequests
{
    /**
     * Data for "request" event.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response $response
     */
    protected function buildRequestEventData($request, $response)
    {
        parent::buildRequestEventData($request, $response);
        
        // Add or overwrite values.
        $host      = 'staging';
        $overwrite = true;
        $this->client->addRequestEventData('host', $host, $overwrite);
        
        // Add parameters to array values.
        $this->client->addRequestEventParams([
            'user' => $request->user()->id
        ]);
        $this->client->addRequestEventParams([
            'mime' => $request->getMimeType('html')
        ], 'response');
        
        // Add data enrichment.
        $this->client->addRequestEventData('target_url', 'https://example.com');
        $this->client->enrichRequestEvent([
            'name'  => 'keen:url_parser',
            'output'=> 'url_parser',
            'input' => ['url' => 'target_url']
        ]);
    }
}
```

Remember to update your `app/Http/Kernel.php` file to use your own middleware
class, instead of the pre-configured one.

## Features

- [x] Deferred event tracking :raised_hands:
- [ ] Support data enrichment out of the box
    - [x] [IP to Geo](https://keen.io/docs/streams/ip-to-geo-enrichment)
    - [x] [User Agent](https://keen.io/docs/streams/user-agent-enrichment)
    - [ ] [Referrer](https://keen.io/docs/streams/referrer-enrichment)
- [x] Extensible middleware

-----------
This project is licensed under the MIT license.

I'd love to hear your comments or questions about this project. If you have an idea on how to improve it, [create an issue](https://github.com/frnkly/laravel-keen/issues/new) or [get in touch](https://frnk.ca).
