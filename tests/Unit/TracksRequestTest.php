<?php

namespace Tests\Unit;

use Frnkly\LaravelKeen\Client;
use Orchestra\Testbench\TestCase;
use Frnkly\LaravelKeen\TracksRequests as Middleware;

/**
 * Tests the middleware.
 *
 * @covers \Frnkly\LaravelKeen\TracksRequests
 */
final class TracksRequestsTest extends TestCase
{
    public function testHandle()
    {
        // Keen PHP client wrapper
        $client = new Client(
            $projectId = uniqid('project'),
            $masterKey = uniqid('master'),
            $writeKey = uniqid('write')
        );

        // Create an instance of the middleware
        $middleware = new Middleware($client);

        $this->assertEmpty($client->getDeferredEvents());

        // Simulate a request cycle.
        $middleware->handle(new \Illuminate\Http\Request, function($request) {
            return new \Illuminate\Http\Response;
        });

        $this->assertNotEmpty($client->getDeferredEvents());
        $this->assertNotEmpty($client->getDeferredEvents()['request']);

        // Make sure the underlying Keen client is accessible.
        $this->assertSame($projectId, $client->getProjectId());
        $this->assertSame($masterKey, $client->getMasterKey());
        $this->assertSame($writeKey, $client->getWriteKey());

        // Make sure the underlying Guzzle client is accesible.
        $client->setConfig('test-config', 'test-config-value');
        $this->assertSame('test-config-value', $client->getConfig('test-config'));
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
