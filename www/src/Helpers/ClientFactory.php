<?php

namespace App\Helpers;

use GuzzleHttp\Client;

class ClientFactory
{
    public static function make(string $baseUri, array $options = []): Client
    {
        $defaultOptions = [
            'base_uri' => $baseUri,
            'timeout'  => 10.0,
            'headers'  => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ];

        return new Client(array_merge($defaultOptions, $options));
    }

    public static function makeElasticClient(): \Elastic\Elasticsearch\Client
    {
        return \Elastic\Elasticsearch\ClientBuilder::create()
            ->setHosts(['http://elasticsearch:9200'])
            ->build();
    }
}