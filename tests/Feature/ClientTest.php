<?php

namespace Kanata\ConveyorServerClient\Tests\Unit;

use Conveyor\Actions\BroadcastAction;
use Kanata\ConveyorServerClient\Client;
use Swoole\Process;
use Kanata\ConveyorServerClient\Tests\Samples\SecondaryBroadcastAction;
use Kanata\ConveyorServerClient\Tests\TestCase;
use WebSocket\Client as WsClient;
use WebSocket\TimeoutException;

class ClientTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @covers \Kanata\ConveyorServerClient\Client::connect
     * @covers \Kanata\ConveyorServerClient\Client::connectionReady
     */
    public function test_can_connect_to_ws_server()
    {
        $process = new Process(function(Process $worker) {
            $client = new Client([
                'port' => 8585,
                'onReadyCallback' => function() use ($worker) {
                    $worker->write('connected');
                },
            ]);
            $client->connect();
            $worker->write('failed');
        });

        $pid = $process->start();
        $result = $process->read();
        Process::kill($pid);

        $this->assertEquals('connected', $result);
    }

    /**
     * @covers \Kanata\ConveyorServerClient\Client::connectionReady
     */
    public function test_can_send_message()
    {
        $process = new Process(function(Process $worker) {
            $client = new Client([
                'port' => 8585,
                'onReadyCallback' => function(WsClient $currentClient) {
                    $currentClient->send('message-sent');
                },
                'onMessageCallback' => function(WsClient $currentClient, string $message) use ($worker) {
                    $worker->write($message);
                },
            ]);
            $client->connect();
        });

        $pid = $process->start();
        $result = $process->read();
        Process::kill($pid);

        $this->assertEquals(
            'message-sent',
            json_decode($result, true)['data']
        );
    }

    /**
     * @covers \Kanata\ConveyorServerClient\Client::handleChannelConnection
     */
    public function test_can_connect_to_channel()
    {
        $process1 = new Process(function(Process $worker) {
            $client = new Client([
                'port' => 8585,
                'channel' => 'sample-channel',
                'onMessageCallback' => function(WsClient $currentClient, string $message) use ($worker) {
                    $worker->write($message);
                },
                'timeout' => 1,
            ]);
            try {
                $client->connect();
            } catch (TimeoutException $e) {
                $worker->write('no-message-received');
                $worker->close();
            }
        });

        $process2 = new Process(function(Process $worker) {
            $client = new Client([
                'port' => 8585,
                'channel' => 'sample-channel',
                'onReadyCallback' => function(WsClient $currentClient) {
                    $currentClient->send(json_encode([
                        'action' => BroadcastAction::ACTION_NAME,
                        'data' => 'message-sent',
                    ]));
                },
                'timeout' => 1,
            ]);
            try {
                $client->connect();
            } catch (TimeoutException $e) {
                $worker->write('no-message-received');
                $worker->close();
            }
        });

        $pid1 = $process1->start();
        sleep(0.5);
        $pid2 = $process2->start();

        $result = $process1->read();

        Process::kill($pid1);
        Process::kill($pid2);

        $this->assertEquals(
            'message-sent',
            json_decode($result, true)['data']
        );
    }

    /**
     * @covers \Kanata\ConveyorServerClient\Client::handleListeners
     */
    public function test_can_listen_specific_actions()
    {
        $process1 = new Process(function(Process $worker) {
            $client = new Client([
                'port' => 8585,
                'channel' => 'sample-channel',
                'listen' => [SecondaryBroadcastAction::ACTION_NAME],
                'onMessageCallback' => function(WsClient $currentClient, string $message) use ($worker) {
                    $worker->write($message);
                },
                'timeout' => 1,
            ]);
            try {
                $client->connect();
            } catch (TimeoutException $e) {
                $worker->write('no-message-received');
                $worker->close();
            }
        }, true);

        $process2 = new Process(function(Process $worker) {
            $client = new Client([
                'port' => 8585,
                'channel' => 'sample-channel',
                'listen' => [BroadcastAction::ACTION_NAME],
                'onMessageCallback' => function (WsClient $currentClient, string $message) use ($worker) {
                    $worker->write($message);
                },
                'timeout' => 1,
            ]);
            try {
                $client->connect();
            } catch (TimeoutException $e) {
                $worker->write('no-message-received');
                $worker->close();
            }
        }, true);

        $process3 = new Process(function(Process $worker) {
            $client = new Client([
                'port' => 8585,
                'channel' => 'sample-channel',
                'onReadyCallback' => function(WsClient $currentClient) {
                    $currentClient->send(json_encode([
                        'action' => SecondaryBroadcastAction::ACTION_NAME,
                        'data' => 'message-sent',
                    ]));
                },
                'timeout' => 1,
            ]);
            try {
                $client->connect();
            } catch (TimeoutException $e) {
                $worker->write('no-message-received');
                $worker->close();
            }
        }, true);

        $pid1 = $process1->start();
        sleep(0.5);
        $pid2 = $process2->start();
        sleep(0.5);
        $pid3 = $process3->start();

        $result1 = $process1->read();
        $result2 = $process2->read();

        Process::kill($pid1);
        Process::kill($pid2);
        Process::kill($pid3);

        $this->assertIsArray(json_decode($result1, true));
        $this->assertIsString($result2);

        $this->assertEquals(
            'message-sent',
            json_decode($result1, true)['data']
        );

        $this->assertEquals('no-message-received', $result2);
    }

    /**
     * @covers \Kanata\ConveyorServerClient\Client::handleClientConnection
     */
    public function test_can_reconnect()
    {
        $process = new Process(function(Process $worker) {
            $client = new Client([
                'port' => 8585,
                'onReadyCallback' => function(WsClient $currentClient) {
                    $currentClient->send('message-sent');
                },
                'onMessageCallback' => function(WsClient $currentClient, string $message) use ($worker) {
                    $worker->write($message);
                },
                'reconnect' => true,
                'reconnectionAttempts' => 2,
                'reconnectionInterval' => 1,
            ]);
            $client->connect();
        }, true);

        $pid = $process->start();
        $result = $process->read();

        $this->stopWsServer();
        sleep(0.5);
        $this->startWsServer();

        // this means it reconnected successfully!
        $result2 = $process->read();

        Process::kill($pid);

        $this->assertEquals(
            'message-sent',
            json_decode($result, true)['data']
        );
        $this->assertEquals(
            'message-sent',
            json_decode($result2, true)['data']
        );
    }

    /**
     * @covers \Kanata\ConveyorServerClient\Client::handleDisconnection
     */
    public function test_disconnection_callback()
    {
        $process = new Process(function(Process $worker) {
            $client = new Client([
                'port' => 8585,
                'onDisconnectCallback' => function(WsClient $currentClient, $attemptsCount) use ($worker) {
                    $worker->write('disconnection-callbacl');
                }
            ]);
            $client->connect();
        }, true);

        $pid = $process->start();
        $this->stopWsServer();
        $result = $process->read();
        Process::kill($pid);

        $this->assertEquals('disconnection-callbacl', $result);
    }
}