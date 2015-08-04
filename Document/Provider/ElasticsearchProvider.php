<?php

namespace Sineflow\ElasticsearchBundle\Document\Provider;

use Sineflow\ElasticsearchBundle\Manager\IndexManager;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollection;

/**
 * Class providing data from an Elasticsearch index source
 */
class ElasticsearchProvider extends AbstractProvider
{
    /**
     * @var The index manager of the data source
     */
    protected $sourceIndexManager;

    /**
     * @var The type the data is coming from
     */
    protected $sourceTypeClass;

    /**
     * @param string                     $documentClass      The type the provider is for
     * @param DocumentMetadataCollection $metadata           The metadata collection for all ES types
     * @param IndexManager               $sourceIndexManager The index manager of the data source
     * @param string                     $sourceTypeClass    The type the data is coming from
     */
    public function __construct($documentClass, DocumentMetadataCollection $metadata, IndexManager $sourceIndexManager, $sourceTypeClass)
    {
        parent::__construct($documentClass, $metadata);
        $this->sourceIndexManager = $sourceIndexManager;
        $this->$sourceTypeClass = $sourceTypeClass;
    }

    /**
     * Returns a PHP Generator for iterating over the full dataset of source data that is to be inserted in ES
     *
     * @return \Generator<array>
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

    /**
     * Reindex all documents from one index to another using scan-and-scroll
     *
     * @param string $sourceIndex Index to read documents from
     * @param string $targetIndex Name of the index to populate
     * @param int    $chunkSize   Number of docs in one chunk sent to ES (default: 500)
     * @param string $scroll      Specify how long a consistent view of the index should be maintained for scrolled search
     *
     * @return void
     *
     * TODO: remove this function, as the reindexing will be handled by data provider classes
     */
    public function reindex($sourceIndex, $targetIndex, $chunkSize = 500, $scroll = '5m')
    {
        // Build a scan search request
        $params = array(
            'search_type' => 'scan',
            'scroll' => $scroll,
            'size' => $chunkSize,
            'index' => $sourceIndex,
        );

        // Get the scroll ID
        $docs = $this->client()->search($params);
        $scrollId = $docs['_scroll_id'];

        // Loop while there are results
        while (\true) {
            // Execute a scroll request
            $response = $this->client()->scroll(
                array(
                    'scroll_id' => $scrollId,
                    'scroll' => $scroll
                )
            );
            if (count($response['hits']['hits']) > 0) {
                // Bulk request for save batch
                foreach ($response['hits']['hits'] as $hit) {
                    $bulkParams['body'][] = array(
                        'index' => array(
                            '_index' => $targetIndex,
                            '_type' => $hit['_type'],
                            '_id' => $hit['_id'],
                        )
                    );
                    $bulkParams['body'][] = $hit['_source'];
                }
                $this->client()->bulk($bulkParams);
                // Get the new scroll_id
                $scrollId = $response['_scroll_id'];
            } else {
                // No more data
                break;
            }
        }
    }

}