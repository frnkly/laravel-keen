<?php

namespace Tests\Unit;

use Frnkly\LaravelKeen\Client;
use Orchestra\Testbench\TestCase;
use Frnkly\LaravelKeen\TracksRequests as Middleware;

/**
 * @covers \Frnkly\LaravelKeen\TracksRequests
 */
final class TracksRequestsTest extends TestCase
{
    public function testHandle()
    {
        // Create an instance of the middleware.
        $client     = new Client;
        $middleware = new Middleware($client);

        $this->assertEmpty($client->getEvents());

        // Simulate a request cycle.
        $middleware->handle(new \Illuminate\Http\Request, function($request) {
            return new \Illuminate\Http\Response;
        });

        $this->assertNotEmpty($client->getEvents());
        $this->assertNotEmpty($client->getEvents()['request']);
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return ['Frnkly\LaravelKeen\ServiceProvider'];
    }
}
