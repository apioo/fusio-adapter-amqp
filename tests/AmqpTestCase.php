<?php
/*
 * Fusio - Self-Hosted API Management for Builders.
 * For the current version and information visit <https://www.fusio-project.org/>
 *
 * Copyright (c) Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Fusio\Adapter\Amqp\Tests;

use Fusio\Adapter\Amqp\Adapter;
use Fusio\Adapter\Amqp\Connection\Amqp;
use Fusio\Engine\Model\Connection;
use Fusio\Engine\Parameters;
use Fusio\Engine\Test\CallbackConnection;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase;

/**
 * AmqpTestCase
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
abstract class AmqpTestCase extends TestCase
{
    use EngineTestCaseTrait;

    protected ?AMQPStreamConnection $connection = null;

    protected function setUp(): void
    {
        if (!$this->connection) {
            $this->connection = $this->newConnection();
        }

        $connection = new Connection(1, 'foo', CallbackConnection::class, [
            'callback' => function(){
                return $this->connection;
            },
        ]);

        $this->getConnectionRepository()->add($connection);
    }

    protected function newConnection(): AMQPStreamConnection
    {
        $connector = new Amqp();

        try {
            $connection = $connector->getConnection(new Parameters([
                'host'     => '127.0.0.1',
                'port'     => 5672,
                'user'     => 'guest',
                'password' => 'guest',
                'vhost'    => '/'
            ]));

            return $connection;
        } catch (\Exception $e) {
            $this->markTestSkipped('AMQP connection not available');
        }
    }

    protected function getAdapterClass(): string
    {
        return Adapter::class;
    }
}
