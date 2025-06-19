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

namespace Fusio\Adapter\Amqp\Tests\Connection;

use Fusio\Adapter\Amqp\Connection\Amqp;
use Fusio\Adapter\Amqp\Tests\AmqpTestCase;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Form\Element\Input;
use Fusio\Engine\Parameters;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * AmqpTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class AmqpTest extends AmqpTestCase
{
    public function testGetConnection(): void
    {
        /** @var Amqp $connectionFactory */
        $connectionFactory = $this->getConnectionFactory()->factory(Amqp::class);

        $config = new Parameters([
            'host'     => '127.0.0.1',
            'port'     => 5672,
            'user'     => 'guest',
            'password' => 'guest',
            'vhost'    => '/'
        ]);

        $connection = $connectionFactory->getConnection($config);

        $this->assertInstanceOf(AMQPStreamConnection::class, $connection);
    }

    public function testConfigure(): void
    {
        $connection = $this->getConnectionFactory()->factory(Amqp::class);
        $builder    = new Builder();
        $factory    = $this->getFormElementFactory();

        $connection->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());

        $elements = $builder->getForm()->getElements();
        $this->assertEquals(5, count($elements));
        $this->assertInstanceOf(Input::class, $elements[0]);
        $this->assertInstanceOf(Input::class, $elements[1]);
        $this->assertInstanceOf(Input::class, $elements[2]);
        $this->assertInstanceOf(Input::class, $elements[3]);
        $this->assertInstanceOf(Input::class, $elements[4]);
    }

    public function testPing(): void
    {
        /** @var Amqp $connectionFactory */
        $connectionFactory = $this->getConnectionFactory()->factory(Amqp::class);

        $config = new Parameters([
            'host'     => '127.0.0.1',
            'port'     => 5672,
            'user'     => 'guest',
            'password' => 'guest',
            'vhost'    => '/'
        ]);

        $connection = $connectionFactory->getConnection($config);

        $this->assertTrue($connectionFactory->ping($connection));
    }
}
