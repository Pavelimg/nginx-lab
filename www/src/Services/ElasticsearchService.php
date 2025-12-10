<?php

namespace App\Services;

use App\Helpers\ClientFactory;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;

class ElasticsearchService
{
    private Client $client;

    public function __construct()
    {
        $this->client = ClientFactory::makeElasticClient();
    }

    /**
     * Создание индекса для товаров
     */
    public function createProductsIndex(): array
    {
        $params = [
            'index' => 'products',
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                    'analysis' => [
                        'analyzer' => [
                            'russian' => [
                                'type' => 'custom',
                                'tokenizer' => 'standard',
                                'filter' => ['lowercase', 'russian_morphology', 'english_morphology']
                            ]
                        ]
                    ]
                ],
                'mappings' => [
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => [
                            'type' => 'text',
                            'analyzer' => 'russian',
                            'fields' => [
                                'keyword' => ['type' => 'keyword']
                            ]
                        ],
                        'category' => ['type' => 'keyword'],
                        'price' => ['type' => 'float'],
                        'quantity' => ['type' => 'integer'],
                        'description' => [
                            'type' => 'text',
                            'analyzer' => 'russian'
                        ],
                        'tags' => ['type' => 'keyword'],
                        'created_at' => ['type' => 'date'],
                        'updated_at' => ['type' => 'date'],
                        'is_active' => ['type' => 'boolean']
                    ]
                ]
            ]
        ];

        try {
            return $this->client->indices()->create($params);
        } catch (ClientResponseException $e) {
            if ($e->getCode() === 400) {
                return ['error' => 'Индекс уже существует'];
            }
            throw $e;
        }
    }

    /**
     * Добавление товара
     */
    public function addProduct(array $productData): array
    {
        $params = [
            'index' => 'products',
            'body' => array_merge($productData, [
                'created_at' => date('c'),
                'updated_at' => date('c')
            ])
        ];

        if (isset($productData['id'])) {
            $params['id'] = $productData['id'];
        }

        return $this->client->index($params)->asArray();
    }

    /**
     * Поиск товаров
     */
    public function searchProducts(array $query, int $from = 0, int $size = 10): array
    {
        $params = [
            'index' => 'products',
            'body' => [
                'from' => $from,
                'size' => $size,
                'query' => $query
            ]
        ];

        return $this->client->search($params)->asArray();
    }

    /**
     * Поиск по тексту
     */
    public function searchByText(string $text, array $fields = ['name', 'description']): array
    {
        return $this->searchProducts([
            'multi_match' => [
                'query' => $text,
                'fields' => $fields,
                'fuzziness' => 'AUTO'
            ]
        ]);
    }

    /**
     * Фильтрация по категории и цене
     */
    public function filterProducts(?string $category = null, ?float $minPrice = null, ?float $maxPrice = null): array
    {
        $must = [];

        if ($category) {
            $must[] = ['term' => ['category.keyword' => $category]];
        }

        if ($minPrice !== null || $maxPrice !== null) {
            $range = [];
            if ($minPrice !== null) $range['gte'] = $minPrice;
            if ($maxPrice !== null) $range['lte'] = $maxPrice;
            $must[] = ['range' => ['price' => $range]];
        }

        $query = empty($must) ? ['match_all' => new \stdClass()] : ['bool' => ['must' => $must]];

        return $this->searchProducts($query);
    }

    /**
     * Получение товара по ID
     */
    public function getProduct(string $id): ?array
    {
        try {
            $params = [
                'index' => 'products',
                'id' => $id
            ];
            $response = $this->client->get($params)->asArray();
            return $response['_source'];
        } catch (ClientResponseException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Обновление товара
     */
    public function updateProduct(string $id, array $updateData): array
    {
        $params = [
            'index' => 'products',
            'id' => $id,
            'body' => [
                'doc' => array_merge($updateData, [
                    'updated_at' => date('c')
                ])
            ]
        ];

        return $this->client->update($params)->asArray();
    }

    /**
     * Удаление товара
     */
    public function deleteProduct(string $id): array
    {
        $params = [
            'index' => 'products',
            'id' => $id
        ];

        return $this->client->delete($params)->asArray();
    }

    /**
     * Получение статистики
     */
    public function getStats(): array
    {
        $params = [
            'index' => 'products',
            'body' => [
                'size' => 0,
                'aggs' => [
                    'total_products' => ['value_count' => ['field' => 'id']],
                    'avg_price' => ['avg' => ['field' => 'price']],
                    'max_price' => ['max' => ['field' => 'price']],
                    'min_price' => ['min' => ['field' => 'price']],
                    'by_category' => [
                        'terms' => ['field' => 'category.keyword', 'size' => 10]
                    ]
                ]
            ]
        ];

        return $this->client->search($params)->asArray();
    }

    /**
     * Проверка существования индекса
     */
    public function indexExists(string $index = 'products'): bool
    {
        return $this->client->indices()->exists(['index' => $index])->asBool();
    }
}