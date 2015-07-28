<?php

namespace Sineflow\ElasticsearchBundle\Document\Provider;

/**
 * Class providing data from an Elasticsearch index source
 */
class ElasticsearchProvider implements ProviderInterface
{
    /**
     * Returns a PHP Generator for iterating over the full dataset of source data that is to be inserted in ES
     *
     * @return \Generator<DocumentInterface>
     */
    public function getDocuments()
    {

    }

    /**
     * Build and return a document entity from the data source, ready for insertion into ES
     *
     * @param int|string $id
     * @return DocumentInterface
     */
    public function getDocument($id)
    {

    }

}