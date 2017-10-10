# [Keen.io](https://keen.io) + [Laravel](https://laravel.com)

Efficiently integrate [Keen.io](https://keen.io) analytics without slowing down your Laravel application.

[![Latest Stable Version](https://poser.pugx.org/frnkly/laravel-keen/version)](https://packagist.org/packages/frnkly/laravel-keen)
[![Latest Unstable Version](https://poser.pugx.org/frnkly/laravel-keen/v/unstable)](//packagist.org/packages/frnkly/laravel-keen)
[![Total Downloads](https://poser.pugx.org/frnkly/laravel-keen/downloads)](https://packagist.org/packages/frnkly/laravel-keen)
[![License](https://poser.pugx.org/frnkly/laravel-keen/license)](https://packagist.org/packages/frnkly/laravel-keen)

This package provides a light wrapper around the Keen PHP client, as well as a middleware configured to track any
request automatically—_after_ each request has been fulfilled. That means virtually no impact on speed.

## Getting started

Start by requiring the LaravelKeen package

    $ composer require frnkly/laravel-keen

Todo:

- Add provider `\Frnkly\LaravelKeen\ServiceProvider` to app.php
- Add middleware `\Frnkly\LaravelKeen\TrackRequests` to Kernel
- Update services config:
```php
'keen' => [
    'id'     => env('KEEN_PROJECT_ID'),
    'master' => env('KEEN_MASTER_KEY'),
    'write'  => env('KEEN_WRITE_KEY'),
],
```
- Explain other config values:
    - `services.keen.track-requests`
    - `services.keen.geo-data`\Frnkly\LaravelKeen\TrackRequests
    - `services.keen.user-data`
- Add option to publish default configs