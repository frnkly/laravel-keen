<?php

namespace Frnkly\LaravelKeen;

use KeenIO\Client\KeenIOClient;

/**
 * Some methods from the underlying \KeenIO\Client\KeenIOClient class
 *
 * @method string|null getProjectId()
 * @method string|null getMasterKey()
 * @method string|null getWriteKey()
 * @method mixed addEvent($collection, array $event = [])
 * @method mixed addEvents(array $events = [])
 *
 * Some methods from the underlying \GuzzleHttp\Command\Guzzle\GuzzleClient class
 *
 * @method setConfig($option, $value)
 * @method mixed getConfig($option = null)
 */
class Client
{
    /**
     * @var \KeenIO\Client\KeenIOClient
     */
    private $keen;

    /**
     * @var array[]
     */
    private $deferredEvents = [];

    /**
     * @var string[]
     */
    private $errors = [];

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
     * @param  string $name
     * @param  array  $parameters
     * @return static
     */
    public function addDeferredEvent($name, array $parameters = [])
    {
        $this->deferredEvents[$name][] = $parameters;

        return $this;
    }

    /**
     * Retrieves event data.
     *
     * @param  string $event
     * @return array|null
     */
    public function getDeferredEvents($event = null)
    {
        return $event ? $this->deferredEvents[$event] : $this->deferredEvents;
    }

    /**
     * Saves event data.
     *
     * @return string|false
     */
    public function persist()
    {
        if (!$this->deferredEvents) {
            return true;
        } elseif (! $this->keen) {
            return $this->addError('Keen client not instantiated.');
        }

        try {
            $result = $this->keen->addEvents($this->deferredEvents);

            // Reset tracked events
            $this->deferredEvents = [];

            return true;
        } catch (\Exception $e) {
            return $this->addError($e->getMessage());
        } catch (\Throwable $t) {
            return $this->addError($t->getMessage());
        }
    }

    /**
     * Pass-on method calls to Keen client.
     *
     * @param  string $method
     * @param  array  $args
     * @return mixed
     */
    public function __call($method, array $args)
    {
        return call_user_func_array([$this->keen, $method], $args);
    }

    /**
     * @param  string $message
     * @return bool
     */
    private function addError($message)
    {
        $this->errors[] = $message;

        return false;
    }

    /**
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
