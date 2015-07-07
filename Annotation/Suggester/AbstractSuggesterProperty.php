<?php

namespace Sineflow\ElasticsearchBundle\Annotation\Suggester;

use Sineflow\ElasticsearchBundle\Annotation\AbstractProperty;

/**
 * Abstract class for various suggester annotations.
 */
abstract class AbstractSuggesterProperty extends AbstractProperty
{
    /**
     * @var string
     */
    public $type = 'completion';

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
     public $objectName;

    /**
     * @var string
     */
    public $indexAnalyzer;

    /**
     * @var string
     */
    public $searchAnalyzer;

    /**
     * @var int
     */
    public $preserveSeparators;

    /**
     * @var bool
     */
    public $preservePositionIncrements;

    /**
     * @var int
     */
    public $maxInputLength;

    /**
     * @var bool
     */
    public $payloads;
}
