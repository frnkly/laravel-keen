<?php

namespace Frnkly\LaravelKeen;

class TracksRequests
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var int
     */
    protected $startTime;

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

        // Check if middleware should run
        if (! $this->shouldRun($request, $response)) {
            return $response;
        }

        // Track request
        $this->buildRequestEventData($request, $response);
        $this->client->addDeferredEvent('request', $this->client->getRequestEventData());

        return $response;
    }

    /**
     * Determines if the middleware should run or not.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response $response
     * @return bool
     */
    protected function shouldRun($request, $response)
    {
        // Skip specific response codes.
        if (in_array($response->getStatusCode(), $this->skipResponseCodes)) {
            return false;
        }

        return true;
    }

    /**
     * Builds request event data. Override this method to customize what gets
     * tracked to Keen on each request.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response $response
     */
    protected function buildRequestEventData($request, $response)
    {
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
                ->addRequestEventData('route', [
                    'name'          => $request->route()->getName(),
                    'fingerprint'   => $request->fingerprint(),
                ]);

            if ($prefix = $request->route()->getPrefix()) {
                $this->client->addRequestEventData('path_prefix', $prefix);
            }
        }

        // Add geo-location data
        if (config('services.keen.addons.ip_to_geo', true)) {
            $this->client->enrichRequestEvent([
                'name'  => 'keen:ip_to_geo',
                'output'=> 'ip_to_geo',
                'input' => ['ip' => 'ip']
            ]);
        }

        // Add user-agent data
        if (config('services.keen.addons.ua_parser', true)) {
            $this->client->enrichRequestEvent([
                'name'   => 'keen:ua_parser',
                'output' => 'ua_parser',
                'input'  => ['ua_string' => 'user_agent']
            ]);
        }
    }

    /**
     * @param  \Illuminate\Http\Request     $request
     * @param  \Illuminate\Http\Response    $response
     */
    public function terminate($request, $response)
    {
        // We persist event data even if the middleware determined it should
        // not run, in case other deferred events should be sent to Keen.
        $this->client->persist();
    }
}
