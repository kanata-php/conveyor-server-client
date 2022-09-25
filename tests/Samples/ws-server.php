<?php

require __DIR__ . '/../../vendor/autoload.php';

use Conveyor\SocketHandlers\SocketChannelPersistenceTable;
use Conveyor\SocketHandlers\SocketListenerPersistenceTable;
use Conveyor\SocketHandlers\SocketMessageRouter;
use Kanata\ConveyorServerClient\Tests\Samples\SecondaryBroadcastAction;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

// -----------------------------------------------
// Dependencies
// -----------------------------------------------

$persistence = [
    new SocketChannelPersistenceTable,
    new SocketListenerPersistenceTable,
];

// -----------------------------------------------
// Helpers
// -----------------------------------------------

function processMessage(
    string $data,
    int $fd,
    Server $server,
    array $persistence
) {
    $socketRouter = new SocketMessageRouter($persistence);
    $socketRouter->add(new SecondaryBroadcastAction);
    $socketRouter($data, $fd, $server);
}

$server = new Server('0.0.0.0', 8585);

$server->on('message', function (Server $server, Frame $frame) use ($persistence) {
    // echo '(' . $frame->fd . ') Received message: ' . $frame->data . PHP_EOL;
    processMessage($frame->data, $frame->fd, $server, $persistence);
});

$server->set([
    'log_level' => SWOOLE_LOG_ERROR,
]);

$server->start();