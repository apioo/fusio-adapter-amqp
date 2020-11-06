<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2020 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Adapter\Amqp\Action;

use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Action which allows you to create an API endpoint based on any database
 * table
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class AmqpPublish extends ActionAbstract
{
    public function getName()
    {
        return 'AMQP-Send';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $connection = $this->getConnection($configuration);

        $exchange = $request->get('exchange');
        $queue = $request->get('queue');

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
        $channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);

        $channel->queue_bind($queue, $exchange);

        $contentType = $request->get('contentType');
        $body = $request->get('body');

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

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The AMQP connection which should be used'));
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
