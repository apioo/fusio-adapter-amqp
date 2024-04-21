<?php
/*
 * Fusio is an open source API management platform which helps to create innovative API solutions.
 * For the current version and information visit <https://www.fusio-project.org/>
 *
 * Copyright 2015-2023 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Adapter\Amqp\Action;

use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * Action which publishes a message to a queue
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class AmqpPublish extends ActionAbstract
{
    public function getName(): string
    {
        return 'AMQP-Publish';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): HttpResponseInterface
    {
        $connection = $this->getConnection($configuration);

        $exchange = $configuration->get('exchange');
        if (empty($exchange)) {
            $exchange = $request->get('exchange');
        }

        $queue = $configuration->get('queue');
        if (empty($queue)) {
            $queue = $request->get('queue');
            $contentType = $request->get('contentType');
            $body = $request->get('body');
        } else {
            $contentType = 'application/json';
            $body = \json_encode($request->getPayload());
        }

        $channel = $connection->channel();

        /*
            name: $queue
            passive: false
            durable: true // the queue will survive server restarts
            exclusive: false // the queue can be accessed in other channels
            auto_delete: false //the queue won't be deleted once the channel is closed.
        */
        $channel->queue_declare($queue, false, true, false, false);

        /*
            name: $exchange
            type: direct
            passive: false
            durable: true // the exchange will survive server restarts
            auto_delete: false //the exchange won't be deleted once the channel is closed.
        */
        $channel->exchange_declare($exchange, 'direct', false, true, false);

        $channel->queue_bind($queue, $exchange);

        $message = new AMQPMessage($body, [
            'content_type' => $contentType,
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);

        $channel->basic_publish($message, $exchange);
        $channel->close();

        return $this->response->build(200, [], [
            'success' => true,
            'message' => 'Message successful published',
        ]);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The AMQP connection which should be used'));
        $builder->add($elementFactory->newInput('exchange', 'Exchange', 'text', 'The exchange'));
        $builder->add($elementFactory->newInput('queue', 'Queue', 'text', 'The queue'));
    }

    protected function getConnection(ParametersInterface $configuration): AMQPStreamConnection
    {
        $connection = $this->connector->getConnection($configuration->get('connection'));
        if (!$connection instanceof AMQPStreamConnection) {
            throw new ConfigurationException('Given connection must be a AMQP connection');
        }

        return $connection;
    }
}
