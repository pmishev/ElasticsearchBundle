<?php

namespace Sineflow\ElasticsearchBundle\Profiler\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handler that saves all records to itself.
 */
class CollectionHandler extends AbstractProcessingHandler
{
    /**
     * @var array
     */
    private $records = [];

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            $record['extra']['requestUri'] = $request->getRequestUri();
            $record['extra']['route'] = $request->attributes->get('_route');
        }
        $this->records[] = $record;
    }

    /**
     * Returns recorded data.
     *
     * @return array
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * Clears recorded data.
     */
    public function clearRecords()
    {
        $this->records = [];
    }
}
