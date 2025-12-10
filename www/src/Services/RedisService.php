<?php

namespace App\Services;

use Predis\Client;

class RedisService
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'scheme' => 'tcp',
            'host'   => 'redis',
            'port'   => 6379,
        ]);
    }

    public function set(string $key, $value, int $ttl = 3600): void
    {
        $this->client->setex($key, $ttl, json_encode($value));
    }

    public function get(string $key)
    {
        $data = $this->client->get($key);
        return $data ? json_decode($data, true) : null;
    }

    public function delete(string $key): void
    {
        $this->client->del([$key]);
    }

    public function exists(string $key): bool
    {
        return (bool) $this->client->exists($key);
    }

    public function getAllKeys(string $pattern = '*'): array
    {
        return $this->client->keys($pattern);
    }
}