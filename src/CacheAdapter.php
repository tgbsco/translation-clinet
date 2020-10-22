<?php

namespace SportMob\Translation;

use Predis\Client as RedisClient;

class CacheAdapter
{
    private RedisClient $client;
    private string $prefix = 'tc_'; //translation_client

    public function __construct( string $redisHost, int $redisPort = 6379)
    {
        $this->client = new RedisClient([
            'scheme' => 'tcp',
            'host' => $redisHost,
            'port' => $redisPort,
        ], ['prefix' => $this->prefix]);
    }

    public function set(string $key, string $value)
    {
        return $this->client->set($key, $value);
    }

    public function get(string $key)
    {
        return $this->client->get($key);
    }

    public function del(string $key)
    {
        return (bool)$this->client->del($key);
    }

    public function flushAll()
    {
        $this->client->flushall();
    }
}