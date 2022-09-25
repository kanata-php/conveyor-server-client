<p align="center">
<img height="180" src="./imgs/logo.svg"/>
</p>

<p>
  <!-- badges -->
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
use WebSocket\Client as WsClient;

$options = [
    'onMessageCallback' => function(WsClient $currentClient, string $message) {
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
    'protocol' => 'ws',
    'uri' => '127.0.0.1',
    'port' => 8000,
    'query' => '',
    'channel' =>  null,
    'listen' => null,
    'onOpenCallback' => null,
    'onReadyCallback' => null,
    'onMessageCallback' => function(){},
    'timeout' => -1, // when positive, considered in seconds
]
```

## Author

👤 **Savio Resende**

* Website: https://savioresende.com.br
* GitHub: [@lotharthesavior](https://github.com/lotharthesavior)

## 📝 License

Copyright © 2022 [Savio Resende](https://github.com/lotharthesavior).
