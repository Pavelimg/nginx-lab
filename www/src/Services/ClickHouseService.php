<?php

namespace App\Services;

use App\Helpers\ClientFactory;

class ClickHouseService
{
    private $client;

    public function __construct()
    {
        $this->client = ClientFactory::make('http://clickhouse:8123/', [
            'headers' => [
                'X-ClickHouse-User' => 'default',
                'X-ClickHouse-Key' => '',
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    public function executeQuery(string $query): array
    {
        $response = $this->client->post('', [
            'body' => $query,
            'query' => [
                'default_format' => 'JSONCompact'
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function createProductsTable(): array
    {
        $query = "
            CREATE TABLE IF NOT EXISTS products_analytics (
                id UUID,
                product_id Int32,
                action String,
                price Float32,
                quantity Int32,
                category String,
                timestamp DateTime DEFAULT now()
            ) ENGINE = MergeTree()
            ORDER BY (timestamp, product_id)
        ";

        return $this->executeQuery($query);
    }

    public function insertAnalytics(array $data): array
    {
        $values = json_encode($data);
        $query = "INSERT INTO products_analytics FORMAT JSONEachRow " . $values;
        
        return $this->executeQuery($query);
    }

    public function getAnalyticsStats(string $period = 'today'): array
    {
        $dateCondition = '';
        
        switch ($period) {
            case 'today':
                $dateCondition = "WHERE toDate(timestamp) = today()";
                break;
            case 'week':
                $dateCondition = "WHERE timestamp >= now() - interval 7 day";
                break;
            case 'month':
                $dateCondition = "WHERE timestamp >= now() - interval 30 day";
                break;
        }

        $query = "
            SELECT 
                action,
                count() as count,
                avg(price) as avg_price,
                sum(quantity) as total_quantity
            FROM products_analytics
            $dateCondition
            GROUP BY action
            ORDER BY count DESC
        ";

        return $this->executeQuery($query);
    }
}