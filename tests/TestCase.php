<?php

namespace Kanata\ConveyorServerClient\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use OpenSwoole\Process;

class TestCase extends BaseTestCase
{
    protected int $serverProcessPid;

    public function setUp(): void
    {
        parent::setUp();
        $this->startWsServer();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->stopWsServer();
    }

    protected function startWsServer()
    {
        $serverProcess = new Process(function(Process $worker) {
            $worker->exec('/usr/bin/php', [
                __DIR__ . '/Samples/ws-server.php',
            ]);
        });
        $this->serverProcessPid = $serverProcess->start();
        sleep(1);
    }

    protected function stopWsServer()
    {
        Process::kill($this->serverProcessPid);
    }
}
