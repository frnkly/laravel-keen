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
        $eventData = [
            'method'        => $request->method(),
            'host'          => $request->root(),
            'path'          => substr($request->path(), strpos($request->path(), '/')),
            'params'        => $request->toArray(),
            'fingerprint'   => '',
            'ip'            => $request->ip(),
            'user_agent'    => $request->headers->get('user-agent'),
            'response'      => [
                'time' => microtime(true) - $this->startTime,
                'code' => $response->getStatusCode(),
            ],
        ];

        // Try to retrieve route information
        if ($request->route()) {
            $eventData['params'] += $request->route()->parameters();
            $eventData['fingerprint'] = $request->fingerprint();
        }

        // Add geo-location data
        if (config('services.keen.geo-data', true)) {
            $eventData['keen']['addons'][] = [
                'name'  => 'keen:ip_to_geo',
                'output'=> 'geo_data',
                'input' => ['ip' => 'ip']
            ];
        }

        // Add user-agent data
        if (config('services.keen.user-data', true)) {
            $eventData['keen']['addons'][] = [
                'name'   => 'keen:ua_parser',
                'output' => 'user_data',
                'input'  => ['ua_string' => 'user_agent']
            ];
        }

        $this->client->addDeferredEvent('request', $eventData);

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
