<?php

namespace Fusio\Adapter\Memcache;

use Fusio\Engine\AdapterInterface;

class Adapter implements AdapterInterface
{
    public function getDefinition()
    {
        return __DIR__ . '/../definition.json';
    }
}
