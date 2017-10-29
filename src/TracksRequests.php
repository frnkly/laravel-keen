<?php

namespace Frnkly\LaravelKeen;

class TracksRequests
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var int
     */
    private $startTime;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client    = $client;
        $this->startTime = microtime(true);
    }

    /**
     * Track every incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, \Closure $next)
    {
        $response = $next($request);

        // Build event data
        $this->client
            ->addRequestEventData('method', $request->method())
            ->addRequestEventData('host', $request->root())
            ->addRequestEventData('path', substr($request->path(), strpos($request->path(), '/')))
            ->addRequestEventParams($request->toArray())
            ->addRequestEventData('ip', $request->ip())
            ->addRequestEventData('user_agent', $request->headers->get('user-agent'))
            ->addRequestEventData('response', [
                'time' => microtime(true) - $this->startTime,
                'code' => $response->getStatusCode(),
            ]);

        // Try to retrieve route information
        if ($request->route()) {
            $this->client
                ->addRequestEventParams($request->route()->parameters())
                ->addRequestEventData('fingerprint', $request->fingerprint());

            if ($prefix = $request->route()->getPrefix()) {
                $this->client->addRequestEventData('path_prefix', $prefix);
            }
        }

        // Add geo-location data
        // @deprecated 'services.keen.geo-data'
        if (config('services.keen.geo_data', config('services.keen.geo-data', true))) {
            $this->client->enrichRequestEvent([
                'name'  => 'keen:ip_to_geo',
                'output'=> 'geo_data',
                'input' => ['ip' => 'ip']
            ]);
        }

        // Add user-agent data
        // @deprecated 'services.keen.user-data'
        if (config('services.keen.user_data', config('services.keen.user-data', true))) {
            $this->client->enrichRequestEvent([
                'name'   => 'keen:ua_parser',
                'output' => 'user_data',
                'input'  => ['ua_string' => 'user_agent']
            ]);
        }

        $this->client->addDeferredEvent('request', $this->client->getRequestEventData());

        return $response;
    }

    /**
     * @param  \Illuminate\Http\Request     $request
     * @param  \Illuminate\Http\Response    $response
     */
    public function terminate($request, $response)
    {
        // Store the session data
        $this->client->persist();
    }
}
