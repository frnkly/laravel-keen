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
        $client = new Client([
            'masterKey' => $masterKey = uniqid('master'),
            'writeKey'  => $writeKey = uniqid('write'),
            'readKey'   => $readKey = uniqid('read'),
            'projectId' => $projectId = uniqid('project'),
        ]);

        // Create an instance of the middleware
        $middleware = new Middleware($client);

        $this->assertEmpty($client->getDeferredEvents());

        // Add request data through helper methods
        $client->addRequestEventData('__TracksRequestTest', 1);
        $client->addRequestEventParams(['__TracksRequestTest' => 1]);
        $client->enrichRequestEvent($enrichment = ['enrichment-test']);

        $this->assertNotNull($client->getRequestEventData()['__TracksRequestTest']);
        $this->assertNotNull($client->getRequestEventData()['params']['__TracksRequestTest']);
        $this->assertSame($enrichment, $client->getRequestEventData()['keen']['addons'][0]);

        // Simulate a request cycle.
        $request    = new \Illuminate\Http\Request;
        $response   = new \Illuminate\Http\Response;
        $middleware->handle($request, function($request) use ($response) {
            return $response;
        });

        $this->assertNotEmpty($client->getDeferredEvents());
        $this->assertNotEmpty($client->getDeferredEvents()['request']);
        $this->assertTrue(array_key_exists('ip', $client->getDeferredEvents()['request'][0]));
        $this->assertTrue(array_key_exists('code', $client->getDeferredEvents()['request'][0]['response']));

        // Simulate the ending of a request cycle.
        $middleware->terminate($request, $response);

        // Since we don't have a valid connection, there should be at least one error.
        $this->assertNotEmpty($client->getErrors());

        // Make sure the underlying Keen client is accessible.
        $this->assertSame($masterKey, $client->getMasterKey());
        $this->assertSame($writeKey, $client->getWriteKey());
        $this->assertSame($readKey, $client->getReadKey());
        $this->assertSame($projectId, $client->getProjectId());

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
