<?php

namespace Frnkly\LaravelKeen;

use KeenIO\Client\KeenIOClient;

/**
 * Some methods from the underlying \KeenIO\Client\KeenIOClient class
 *
 * @method string|null getMasterKey()
 * @method string|null getWriteKey()
 * @method string|null getReadKey()
 * @method string|null getProjectId()
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
     * @var array
     */
    private $requestEventData = [];

    /**
     * @var string[]
     */
    private $errors = [];

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->keen = KeenIOClient::factory($config);
    }

    /**
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
     * @param  string $event
     * @return array|null
     */
    public function getDeferredEvents($event = null)
    {
        return ($event && isset($this->deferredEvents[$event])) ?
            $this->deferredEvents[$event] :
            $this->deferredEvents;
    }

    /**
     * @param  string $key
     * @param  mixed  $value
     * @param  bool   $overwrite
     * @return static
     */
    public function addRequestEventData($key, $value, $overwrite = false)
    {
        if ($key && (! isset($this->requestEventData[$key]) || $overwrite)) {
            $this->requestEventData[$key] = $value;
        }

        return $this;
    }

    /**
     * @param  array  $params
     * @param  string $key
     * @return static
     */
    public function addRequestEventParams(array $params, $key = 'params')
    {
        if (! isset($this->requestEventData[$key])) {
            $this->requestEventData[$key] = [];
        }

        $this->requestEventData[$key] = array_merge($this->requestEventData[$key], $params);

        return $this;
    }

    /**
     * Adds enrichment data to request event.
     *
     * @param  array  $options
     * @return static
     */
    public function enrichRequestEvent(array $options)
    {
        // Performance check.
        if (empty($options['name']) ||
            empty($options['output']) ||
            empty($options['input'])
        ) {
            return $this;
        }

        // Append the keen prefix.
        if (strpos($options['name'], 'keen:') !== 0) {
            $options['name'] = 'keen:'.$options['name'];
        }

        $this->requestEventData['keen']['addons'][] = $options;

        return $this;
    }

    /**
     * @return array
     */
    public function getRequestEventData()
    {
        return $this->requestEventData;
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
     * Pass-on method calls to Keen/Guzzle client.
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
