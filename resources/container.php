<?php

use Fusio\Adapter\Amqp\Action\AmqpPublish;
use Fusio\Adapter\Amqp\Connection\Amqp;
use Fusio\Engine\Adapter\ServiceBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $services = ServiceBuilder::build($container);
    $services->set(Amqp::class);
    $services->set(AmqpPublish::class);
};
