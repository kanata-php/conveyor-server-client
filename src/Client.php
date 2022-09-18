<?php

namespace KanataPhp\ConveyorServerClient;

use WebSocket\Client as WsClient;

class Client
{
    protected ?WsClient $client;

    protected string $protocol;
    protected string $uri;
    protected int $port;
    protected string $query;
    protected string $channel;
    protected string $listen;

    /**
     * Message handler for the whole incoming object.
     *
     * @var ?callable
     */
    protected $onOpenCallback = null;

    /**
     * Callback for after the connection is established.
     *
     * @var ?callable
     */
    protected $onReadyCallback = null;

    /**
     * Message handler for only the data portion.
     *
     * @var ?callable
     */
    protected $onMessageCallback = null;

    /**
     * Message handler for the whole incoming object.
     *
     * @var ?callable
     */
    protected $onRawMessageCallback = null;

    /**
     * Message handler for the whole incoming object.
     *
     * @var ?callable
     */
    protected $onCloseCallback = null;

    /**
     * Error related callback
     *
     * @var ?callable
     */
    protected $onErrorCallback = null;

    public function __construct(array $options)
    {
        $this->protocol = $options['protocol'] ?? 'ws';
        $this->uri = $options['uri'] ?? '127.0.0.1';
        $this->port = $options['port'] ?? 8000;
        $this->query = $options['query'] ?? '';
        $this->channel = $options['channel'] ??  null;
        $this->listen = $options['listen'] ?? null;
        $this->onOpenCallback = $options['onOpenCallback'] ?? null;
        $this->onReadyCallback = $options['onReadyCallback'] ?? null;
        $this->onMessageCallback = $options['onMessageCallback'] ?? null;
        $this->onRawMessageCallback = $options['onRawMessageCallback'] ?? null;
        $this->onCloseCallback = $options['onCloseCallback'] ?? null;
        $this->onErrorCallback = $options['onErrorCallback'] ?? null;

        $this->connect();
    }

    protected function connect()
    {
        $this->client = new WsClient($this->protocol . '://' . $this->uri . ':' . $this->port . '/' . $this->query);
        while($message = $this->client->receive()) {
            $this->onMessageCallback($message);
        }
    }

    public function close()
    {
        $this->client->close();
        $this->client = null;
    }
}
