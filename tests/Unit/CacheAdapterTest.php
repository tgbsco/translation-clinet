<?php

use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use SportMob\Translation\CacheAdapter;

class CacheAdapterTest extends TestCase
{
    protected string $prefix = 'tc_';
    protected RedisClient $redisClient;
    protected CacheAdapter $cacheAdapter;
    protected string $key = 'example key';
    protected string $value = 'example value';

    protected function setUp(): void
    {
        $this->redisClient = new RedisClient([
            'scheme' => 'tcp',
            'host'   => 'redis',
            'port'   => '6379',
        ], ['prefix' => $this->prefix]);
        $this->cacheAdapter = new CacheAdapter('redis', 6379);
    }

    public function testConnection()
    {
        $this->assertEquals('pong', strtolower($this->redisClient->ping()));
    }

    public function testSet()
    {
        $this->cacheAdapter->set($this->key, $this->value);

        $this->assertEquals($this->value, $this->redisClient->get($this->key));
    }

    public function testGet()
    {
        $this->redisClient->set($this->key, $this->value);

        $this->assertEquals($this->value, $this->cacheAdapter->get($this->key));
    }

    protected function tearDown(): void
    {
        $this->redisClient->flushall();
    }
}