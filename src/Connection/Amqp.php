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

namespace Fusio\Adapter\Amqp\Connection;

use Fusio\Engine\Connection\PingableInterface;
use Fusio\Engine\ConnectionAbstract;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Amqp
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class Amqp extends ConnectionAbstract implements PingableInterface
{
    public function getName(): string
    {
        return 'AMQP';
    }

    public function getConnection(ParametersInterface $config): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            $config->get('host'),
            $config->get('port') ?: 5672,
            $config->get('user'),
            $config->get('password'),
            $config->get('vhost') ?: '/'
        );
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newInput('host', 'Host', 'text', 'The IP or hostname of the RabbitMQ server'));
        $builder->add($elementFactory->newInput('port', 'Port', 'number', 'The port used to connect to the AMQP broker. The port default is 5672'));
        $builder->add($elementFactory->newInput('user', 'User', 'text', 'The login string used to authenticate with the AMQP broker'));
        $builder->add($elementFactory->newInput('password', 'Password', 'password', 'The password string used to authenticate with the AMQP broker'));
        $builder->add($elementFactory->newInput('vhost', 'VHost', 'text', 'The virtual host to use on the AMQP broker'));
    }

    public function ping(mixed $connection): bool
    {
        if ($connection instanceof AMQPStreamConnection) {
            return $connection->isConnected();
        } else {
            return false;
        }
    }
}
