<?php

namespace Kanata\ConveyorServerClient;

use Exception;
use WebSocket\Client as WsClient;
use WebSocket\TimeoutException;

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

    /**
     * Reconnect.
     *
     * @var bool
     */
    protected bool $reconnect;

    /**
     * Reconnection attempts to retry.
     *
     * @var int
     */
    protected int $reconnectionAttempts;

    /**
     * Reconnection interval in seconds.
     *
     * @var int
     */
    protected int $reconnectionInterval;

    /**
     * Reconnection attempts count for control.
     *
     * @var int
     */
    protected int $reconnectionAttemptsCount = 0;

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
        $this->onDisconnectCallback = $options['onDisconnectCallback'] ?? null;
        $this->timeout = $options['timeout'] ?? -1;
        $this->reconnect = isset($options['reconnect']) ? $options['reconnect'] : false;
        $this->reconnectionAttempts = $options['reconnectionAttempts'] ?? 0;
        $this->reconnectionInterval = $options['reconnectionInterval'] ?? 2;
    }

    public function connect()
    {
        try {
            $this->handleClientConnection();
            $this->reconnectionAttemptsCount = 0;
        } catch (TimeoutException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->handleDisconnection();

            if (
                $this->reconnect
                && $this->reconnectionAttemptsCount < $this->reconnectionAttempts
            ) {
                sleep($this->reconnectionInterval);
                echo 'Reconnecting (attempt ' . $this->reconnectionAttemptsCount . ')...' . PHP_EOL;
                $this->reconnectionAttemptsCount++;
                $this->connect();
                return;
            }

            throw $e;
        }
    }

    protected function handleDisconnection()
    {
        if (null === $this->onDisconnectCallback) {
            return;
        }

        call_user_func(
            $this->onDisconnectCallback,
            $this->client,
            $this->reconnectionAttemptsCount
        );
    }

    protected function handleClientConnection()
    {
        $this->client = new WsClient(
            uri: $this->protocol . '://' . $this->uri . ':' . $this->port . '/' . $this->query,
            options: ['timeout' => $this->timeout],
        );

        $this->handleChannelConnection();
        $this->handleListeners();
        $this->connectionReady();

        while($message = $this->client->receive()) {
            if (null === $this->onMessageCallback) {
                continue;
            }
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
