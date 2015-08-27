<?php

namespace Sineflow\ElasticsearchBundle\DSL\SearchEndpoint;

/**
 * Factory for search endpoints.
 */
class SearchEndpointFactory
{
    /**
     * @var array Holds namespaces for endpoints.
     */
    private static $endpoints = [
        'query' => 'Sineflow\ElasticsearchBundle\DSL\SearchEndpoint\QueryEndpoint',
        'filter' => 'Sineflow\ElasticsearchBundle\DSL\SearchEndpoint\FilterEndpoint',
        'post_filter' => 'Sineflow\ElasticsearchBundle\DSL\SearchEndpoint\PostFilterEndpoint',
        'sort' => 'Sineflow\ElasticsearchBundle\DSL\SearchEndpoint\SortEndpoint',
        'highlight' => 'Sineflow\ElasticsearchBundle\DSL\SearchEndpoint\HighlightEndpoint',
        'aggregations' => 'Sineflow\ElasticsearchBundle\DSL\SearchEndpoint\AggregationsEndpoint',
        'suggest' => 'Sineflow\ElasticsearchBundle\DSL\SearchEndpoint\SuggestEndpoint',
    ];

    /**
     * Returns a search endpoint instance.
     *
     * @param string $type Type of endpoint.
     *
     * @return SearchEndpointInterface
     *
     * @throws \RuntimeException Endpoint does not exist.
     */
    public static function get($type)
    {
        if (!array_key_exists($type, self::$endpoints)) {
            throw new \RuntimeException('Endpoint does not exist.');
        }

        return new self::$endpoints[$type]();
    }
}
