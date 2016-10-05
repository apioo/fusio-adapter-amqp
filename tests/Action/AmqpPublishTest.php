<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2016 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fusio\Adapter\Amqp\Tests\Action;

use Fusio\Adapter\Amqp\Action\AmqpPublish;
use Fusio\Adapter\Amqp\Tests\AmqpTestCase;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Model\Action;
use Fusio\Engine\Response;
use Fusio\Engine\ResponseInterface;
use Fusio\Engine\Test\CallbackAction;
use PSX\Record\Record;

/**
 * AmqpPublishTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class AmqpPublishTest extends AmqpTestCase
{
    public function testHandle()
    {
        $parameters = $this->getParameters([
            'connection' => 1,
            'queue'      => 'foo_queue',
        ]);

        $data     = Record::fromArray(['foo' => 'bar']);
        $action   = $this->getActionFactory()->factory(AmqpPublish::class);
        $response = $action->handle($this->getRequest('POST', [], [], [], $data), $parameters, $this->getContext());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals(['success' => true, 'message' => 'Push was successful'], $response->getBody());

        // check whether we can get the message from the queue
        $callback = $this->getMock('stdClass', array('callback'));
        $callback->expects($this->once())
            ->method('callback')
            ->with($this->callback(function($msg){
                $this->assertJsonStringEqualsJsonString('{"foo": "bar"}', $msg->body);
            }));

        $channel = $this->connection->channel();
        $channel->queue_declare('foo_queue', false, true, false, false);
        $channel->basic_qos(null, 1, null);
        $channel->basic_consume('foo_queue', '', false, false, false, false, [$callback, 'callback']);
    }

    public function testGetForm()
    {
        $action  = $this->getActionFactory()->factory(AmqpPublish::class);
        $builder = new Builder();
        $factory = $this->getFormElementFactory();

        $action->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());
    }
}