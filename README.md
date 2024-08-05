<p align="center">
<img height="250" src="./imgs/logo.svg"/>
</p>

<p>
  <!-- badges -->
    <a href="https://github.com/kanata-php/conveyor-server-client/actions/workflows/php.yml"><img src="https://github.com/kanata-php/conveyor-server-client/actions/workflows/php.yml/badge.svg" alt="Tests"></a>
</p>


> A server-side client for the php package [kanata-php/socket-conveyor](https://socketconveyor.com)

## Prerequisites

- PHP >= 8.0
- [PHP OpenSwoole Extension](https://openswoole.com/)

## Install

```sh
composer require kanata-php/conveyor-server-client
```

## Description

This is a server-side client for the [Socket Conveyor](https://socketconveyor.com) PHP package.

## Usage

Once installed, the following example shows how it works to use this package.

```php
use Kanata\ConveyorServerClient\Client;

$options = [
    'onMessageCallback' => function(Client $currentClient, string $message) {
        echo 'Message received: ' . $message . PHP_EOL;
    },
];

$client = new Client($options);
$client->connect();
```

With the previous example you'll have a PHP script connected and waiting for messages to come. For each message received there will be a printed message in the terminal executing this script. This client will try to connect to `ws://127.0.0.1:8000`. To understand more Socket Conveyor channel and listeners you can head to its [documentation](https://socketconveyor.com).

> **Important:** this example doesn't have **timeout**. This means it will be running until its process gets killed. If you just need to listen for a limited time, or need a timeout for any other reason, use the `timeout` option.

This package has the following options (showing its respective defaults):

```php
[
    /**
     * @var string
     */
    'protocol' => 'ws',
    
    /**
     * @var string
     */
    'uri' => '127.0.0.1',
    
    /**
     * @var int
     */
    'port' => 8000,
    
    /**
     * @var string
     */
    'query' => '',
    
    /**
     * @var ?string
     */
    'channel' =>  null,
    
    /**
     * @var ?string
     */
    'listen' => null,
    
    /**
     * @var ?callable
     */
    'onOpenCallback' => null,
    
    /**
     * @var ?callable
     */
    'onReadyCallback' => null,
    
    /**
     * Callback for incoming messages.
     * Passed parameters:
     *   - \WebSocket\Client $client
     *   - string $message
     *
     * @var ?callable
     */
    'onMessageCallback' => null,
    
    /**
     * Callback for disconnection.
     * Passed parameters:
     *   - \WebSocket\Client $client
     *   - int $reconnectionAttemptsCount
     *
     * @var ?callable
     */
    'onDisconnectCallback' => null,
    
    /**
     * Callback for Reconnection moment.
     * Passed parameters:
     *   - \WebSocket\Client $client
     *   - int \Throwable $e
     *
     * @var ?callable
     */
    'onReconnectionCallback' => null,
    
    /**
     * When positive, considered in seconds
     *
     * @var int
     */
    'timeout' => -1,
    
    /**
     * @var bool
     */
    'reconnect' => false;
    
    /**
     * Number of attempts if disconnects
     * For this to keeps trying forever, set it to -1. 
     *
     * @var int
     */
    'reconnectionAttempts' => = 0;
    
    /**
     * Interval to reconnect in seconds 
     * 
     * @var int 
     */
    'reconnectionInterval' => = 2;
]
```

This is this package's Conveyor Client interface:

```php
namespace Kanata\ConveyorServerClient;

use WebSocket\Client;

interface ClientInterface
{
    public function connect(): void;
    public function getClient(): ?Client;
    public function close(): void;
    public function send(string $message): void;
    public function sendRaw(string $message): void;
}
```

## Author

👤 **Savio Resende**

* Website: https://savioresende.com.br
* GitHub: [@lotharthesavior](https://github.com/lotharthesavior)

## 📝 License

Copyright © 2022 [Savio Resende](https://github.com/lotharthesavior).
