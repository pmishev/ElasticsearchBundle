<?php

namespace Sineflow\ElasticsearchBundle\DSL\Bool;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\Query\BoolQuery;

/**
 * Bool operator. Can be used for filters and queries.
 *
 * @deprecated Will be removed in 1.0. Use ONGR\ElasticsearchBundle\DSL\Query\BoolQuery.
 */
class Bool extends BoolQuery
{
    /**
     * Add BuilderInterface object to bool operator.
     *
     * @param BuilderInterface $bool
     * @param string           $type
     *
     * @throws \UnexpectedValueException
     *
     * @deprecated Will be removed in 1.0. Use ONGR\ElasticsearchBundle\DSL\Query\BoolQuery::add().
     */
    public function addToBool(BuilderInterface $bool, $type = BoolQuery::MUST)
    {
        $this->add($bool, $type);
    }
}
