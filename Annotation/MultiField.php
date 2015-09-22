<?php

namespace Sineflow\ElasticsearchBundle\Annotation;

use Sineflow\ElasticsearchBundle\Mapping\DumperInterface;

/**
 * Annotation that can be used to define multi-field parameters.
 *
 * @Annotation
 * @Target("ANNOTATION")
 */
final class MultiField implements DumperInterface
{
    /**
     * @var string
     *
     * @Required
     */
    public $name;

    /**
     * @var string
     *
     * @Required
     */
    public $type;

    /**
     * @var string
     */
    public $index;

    /**
     * @var string
     */
    public $analyzer;

    /**
     * @var string
     */
    public $indexAnalyzer;

    /**
     * @var string
     */
    public $searchAnalyzer;

    /**
     * {@inheritdoc}
     */
    public function dump(array $options = [])
    {
        $result = [
            'type' => $this->type,
            'index' => $this->index,
            'analyzer' => $this->analyzer,
            'index_analyzer' => $this->indexAnalyzer,
            'search_analyzer' => $this->searchAnalyzer,
        ];

        // Remove any empty, non-boolean values
        return array_filter(
            $result,
            function ($value) {
                return $value || is_bool($value);
            }
        );

    }
}
