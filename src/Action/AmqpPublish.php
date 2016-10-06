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
use PSX\Data\Writer;

/**
 * AmqpPublish
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class AmqpPublish extends ActionAbstract
{
    public function getName()
    {
        return 'Amqp-Publish';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $connection = $this->connector->getConnection($configuration->get('connection'));

        if ($connection instanceof AMQPStreamConnection) {
            $queue   = $configuration->get('queue');
            $channel = $connection->channel();
            $channel->queue_declare($queue, false, true, false, false);

            $body    = $this->jsonProcessor->write($request->getBody());
            $message = new AMQPMessage($body, ['content_type' => 'application/json', 'delivery_mode' => 2]);

            $channel->basic_publish($message, '', $queue);
            $channel->close();

            return $this->response->build(200, [], array(
                'success' => true,
                'message' => 'Push was successful'
            ));
        } else {
            throw new ConfigurationException('Given connection must be an AMQP connection');
        }
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The RabbitMQ connection which should be used'));
        $builder->add($elementFactory->newInput('queue', 'Queue', 'text', 'The name of the queue'));
    }
}
