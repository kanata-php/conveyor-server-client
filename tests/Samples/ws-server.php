<?php

require __DIR__ . '/../../vendor/autoload.php';

use Conveyor\SubProtocols\Conveyor\Conveyor;
use Kanata\ConveyorServerClient\Tests\Samples\SecondaryBroadcastAction;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server;

// -----------------------------------------------
// Helpers
// -----------------------------------------------

$server = new Server('0.0.0.0', 8585);

$server->on('message', function (Server $server, Frame $frame) {
    // echo '(' . $frame->fd . ') Received message: ' . $frame->data . PHP_EOL;
    Conveyor::init()
        ->server($server)
        ->fd($frame->fd)
        ->persistence()
        ->addActions([
            new SecondaryBroadcastAction,
        ])
        ->run($frame->data);
});

$server->set([
    'log_level' => SWOOLE_LOG_ERROR,
]);

$server->start();
