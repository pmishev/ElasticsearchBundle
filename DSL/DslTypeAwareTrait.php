<?php

namespace Sineflow\ElasticsearchBundle\DSL;

use Elasticsearch\Common\Exceptions\InvalidArgumentException;

/**
 * A trait which handles dsl type.
 */
trait DslTypeAwareTrait
{
    /**
     * @var string
     */
    private $dslType;

    /**
     * Returns a dsl type.
     *
     * @return string
     */
    public function getDslType()
    {
        return $this->dslType;
    }

    /**
     * Sets a dsl type.
     *
     * @param string $dslType
     *
     * @throws InvalidArgumentException
     */
    public function setDslType($dslType)
    {
        if ($dslType !== 'filter' && $dslType !== 'query') {
            throw new InvalidArgumentException('Not supported dsl type');
        }
        $this->dslType = $dslType;
    }
}
