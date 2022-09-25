<?php

namespace Kanata\ConveyorServerClient;

use WebSocket\Client as WsClient;

class Client
{
    protected ?WsClient $client;

    protected string $protocol;
    protected string $uri;
    protected int $port;
    protected string $query;
    protected ?string $channel;

    /**
     * @var array|null string[]
     */
    protected ?array $listen;

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
     * Message handler.
     *
     * @var ?callable
     */
    protected $onMessageCallback = null;

    /**
     * Connection timeout.
     *
     * @var int
     */
    protected $timeout;

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
        $this->onMessageCallback = $options['onMessageCallback'] ?? function(){};
        $this->timeout = $options['timeout'] ?? -1;
    }

    public function connect()
    {
        $this->client = new WsClient(
            uri: $this->protocol . '://' . $this->uri . ':' . $this->port . '/' . $this->query,
            options: ['timeout' => $this->timeout],
        );

        $this->handleChannelConnection();
        $this->handleListeners();
        $this->connectionReady();

        while($message = $this->client->receive()) {
            call_user_func($this->onMessageCallback, $this->client, $message);
        }
    }

    protected function handleChannelConnection()
    {
        if (null === $this->channel) {
            return;
        }

        $this->client->send(json_encode([
            'action' => 'channel-connect',
            'channel' => $this->channel,
        ]));
    }

    protected function handleListeners()
    {
        if (null === $this->listen) {
            return;
        }

        foreach ($this->listen as $actionName) {
            $this->client->send(json_encode([
                'action' => 'add-listener',
                'listen' => $actionName,
            ]));
        }
    }

    protected function connectionReady()
    {
        if (null === $this->onReadyCallback) {
            return;
        }

        call_user_func($this->onReadyCallback, $this->client);
    }

    public function close()
    {
        $this->client->close();
        $this->client = null;
    }
}
