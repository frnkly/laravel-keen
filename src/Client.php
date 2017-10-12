<?php

namespace Frnkly\LaravelKeen;

use KeenIO\Client\KeenIOClient;

class Client
{
    /**
     * @var \KeenIO\Client\KeenIOClient
     */
    private $keen;

    /**
     * @var array
     */
    private $trackedEvents = [];

    /**
     * @param string $projectId
     * @param string $masterKey
     * @param string $writeKey
     */
    public function __construct($projectId = null, $masterKey = null, $writeKey = null)
    {
        if ($projectId && ($masterKey || $writeKey)) {
            $this->keen = KeenIOClient::factory([
                'projectId' => $projectId,
                'masterKey' => $masterKey,
                'writeKey'  => $writeKey,
            ]);
        }
    }

    /**
     * Tracks an event.
     *
     * @param  string $event
     * @param  array  $parameters
     * @return static
     */
    public function addEvent($event, array $parameters = [])
    {
        $this->trackedEvents[$event][] = $parameters;

        return $this;
    }

    /**
     * Retrieves event data.
     *
     * @param  string $event
     * @return array|null
     */
    public function getEvents($event = null)
    {
        return $event ? $this->trackedEvents[$event] : $this->trackedEvents;
    }

    /**
     * Saves event data.
     *
     * @return string|false
     */
    public function persist()
    {
        if (! $this->trackedEvents || ! $this->keen) {
            return false;
        }

        try {
            $result = $this->keen->addEvents($this->trackedEvents);

            // Reset tracked events
            $this->trackedEvents = [];
        } catch (\Exception $e) {
            $result = $e->getMessage();
        } catch (\Throwable $t) {
            $result = $t->getMessage();
        }

        return $result;
    }
}
