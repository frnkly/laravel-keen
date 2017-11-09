# [Laravel](https://laravel.com) + [Keen.io](https://keen.io)

Defer sending event data to [Keen.io](https://keen.io) until after a request
has been fulfilled–automatically and transparently.

[![Latest Stable Version](https://poser.pugx.org/frnkly/laravel-keen/version)](https://packagist.org/packages/frnkly/laravel-keen)
[![Build Status](https://travis-ci.org/frnkly/laravel-keen.png)](https://travis-ci.org/frnkly/laravel-keen)
[![Total Downloads](https://poser.pugx.org/frnkly/laravel-keen/downloads)](https://packagist.org/packages/frnkly/laravel-keen)
[![License](https://poser.pugx.org/frnkly/laravel-keen/license)](https://packagist.org/packages/frnkly/laravel-keen)

This package provides a light wrapper around the
[Keen PHP client](https://github.com/keenlabs/KeenClient-PHP), as well as a
middleware configured to defer sending event data to Keen until after each 
request has been fulfilled. That means that as the number of tracked events 
increase, the impact on request time remains virtually non-existent.
For convenience, the middleware can also track every request automatically.

- [Installation](#installation)
- [Configuration](#configuration)
- [Getting started](#getting-started)
- [Automatic tracking of every request](#automatic-tracking-of-every-request)
    - [Data enrichment](#data-enrichment)
    - [Using your own middleware](#using-your-own-middleware)
    - [Skipping requests](#skipping-requests)
- [Type-hinting](#type-hinting)
- [Features](#features)

# Installation

Install the Laravel + Keen package using [Composer](https://getcomposer.org):

    $ composer require frnkly/laravel-keen

This will also install the Keen PHP client as a dependency.

# Configuration

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

And in your `.env` file, you might add something like:

```
KEEN_PROJECT_ID=your-project-id
KEEN_MASTER_KEY=your-master-key
KEEN_WRITE_KEY=your-write-key
```

# Getting started

Register the middleware globally in `app/Http/Kernel.php`:

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

Thanks to [package discovery](https://laravel.com/docs/packages#package-discovery) 
in Laravel 5.5, the service provider should already be available. It can 
also be manually registered through the `config/app.php` configuration file:

```php
'providers' => [
    // Other Service Providers
    // ...

    Frnkly\LaravelKeen\ServiceProvider::class,
]
```

# Automatic tracking of every request

Automatic tracking is enabled by default. You may disable this behaviour 
through the configuration file:

```php
'keen' => [
    // Other Keen options
    // ...
    
    'track_requests' => false,
],
```

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

Each data enrichment object will appear in your Keen stream under the same  
name as above (i.e. `ip_to_geo` and `ua_parser`).

## Using your own middleware

The included middleware makes it simple to extend and gain more
granular control over the data that gets sent to Keen. You can create your own 
middleware using `Artisan`, and then have it extend 
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
        $this->client->addRequestEventData('host', $host, $overwrite)
        
        // Add parameters to array values.
        ->addRequestEventParams([
            'user' => $request->user()->id
        ])
        ->addRequestEventParams([
            'mime' => $request->getMimeType('html')
        ], 'response')
        
        // Add data enrichment.
        ->addRequestEventData('target_url', 'https://example.com')
        ->enrichRequestEvent([
            'name'  => 'url_parser',
            'output'=> 'url_parser',
            'input' => ['url' => 'target_url']
        ]);
    }
}
```

Remember to update your `app/Http/Kernel.php` file to use your own middleware
class instead of the pre-configured one.

## Skipping requests

Some response codes do not need to be tracked, such as redirects. You 
can configure a list of response codes to ignore in your middleware:

```php
/**
 * @var array
 */
protected $skipResponseCodes = [
    100,
    101,
    301,
    302,
    307,
    308,
];
```

You may also override the `shouldRun` method in the middleware:

```php
/**
 * Determines if the middleware should run or not.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  \Illuminate\Http\Response $response
 * @return bool
 */
protected function shouldRun($request, $response) : bool
{
    if (! parent::shouldRun($request, $response)) {
        return false;
    }
    
    // Define your own custom logic here.
    // ...

    return true;
}
```

# Type-hinting

The LaravelKeen client can be [type-hinted](https://laravel.com/docs/container#automatic-injection) 
in the constructor of any class that is resolved by the container:

```php
namespace App\Events;

use App\Events\OrderShipped;
use Frnkly\LaravelKeen\Client;

class OrderShipped
{
    private $client;
    
    public function __construct(Client $client)
    {
        // The client can now be accessed by all methods in this class.
        $this->client = $client;
    }
    
    public function handle(OrderShipped $event)
    {
        // By adding a deferred event, we can continue working on the request
        // without waiting on a reply back from Keen.io. The event will be
        // processed once the request is done–automatically and transparently.
        $this->client->addDeferredEvent('order-shipped', [
            // Event data...
        ]);
    }
}
```

Controllers can also take advantage of method injection:

```php
use Frnkly\LaravelKeen\Client;

class UserController extends Controller
{
    public function store(Client $client)
    {
        $client->addDeferredEvent('new-user', [
            // Event data...
        ]);
    }
}
```

# Features

- [x] Deferred event tracking :raised_hands:
- [ ] Support data enrichment out of the box
    - [x] [IP to Geo](https://keen.io/docs/streams/ip-to-geo-enrichment)
    - [x] [User Agent](https://keen.io/docs/streams/user-agent-enrichment)
    - [ ] [Referrer](https://keen.io/docs/streams/referrer-enrichment)
- [x] Extensible middleware
- [x] Option to ignore specific response codes (e.g. `302`, `404`, etc.)
    - [x] Or any request based on custom logic

-----------

This project is licensed under the 
[MIT license](https://github.com/frnkly/laravel-keen/blob/dev/LICENSE).

I'd love to hear your comments or questions about this project. If you have 
an idea on how to improve it, 
[create an issue](https://github.com/frnkly/laravel-keen/issues/new) or 
[get in touch](https://frnk.ca).
