<?php

namespace Sineflow\ElasticsearchBundle\DSL\Query;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch fuzzy_like_this_field query class.
 */
class FuzzyLikeThisFieldQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $likeText;

    /**
     * @param string $field
     * @param string $likeText
     * @param array  $parameters
     */
    public function __construct($field, $likeText, array $parameters = [])
    {
        $this->field = $field;
        $this->likeText = $likeText;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'fuzzy_like_this_field';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [
            'like_text' => $this->likeText,
        ];

        $output = [
            $this->field => $this->processArray($query),
        ];

        return $output;
    }
}
