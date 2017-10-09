<?php

namespace Frnkly\LaravelKeen;

class TrackRequests
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

        // Gather defaults to track every request
        $path = substr($request->path(), strpos($request->path(), '/'));
        $params = $request->toArray();
        $fingerprint = null;

        // Try to retrieve route information
        if ($request->route()) {
            $params += $request->route()->parameters();
            $fingerprint = $request->fingerprint();
        }

        $this->client->addEvent('request', [
            'method'        => $request->method(),
            'host'          => $request->root(),
            'path'          => $path,
            'params'        => $params,
            'fingerprint'   => $fingerprint,
            'ip'            => $request->ip(),
            'user_agent'    => $request->headers->get('user-agent'),
            'response'      => [
                'time' => microtime(true) - $this->startTime,
                'code' => $response->getStatusCode(),
            ],

            // Keen add-ons
            'keen' => [
                'addons' => [
                    [
                        'name'   => 'keen:ip_to_geo',
                        'output' => 'geo_data',
                        'input'  => ['ip' => 'ip'],
                    ],
                    [
                        'name'   => 'keen:ua_parser',
                        'output' => 'user_data',
                        'input'  => ['ua_string' => 'user_agent'],
                    ]
                ]
            ]
        ]);

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
