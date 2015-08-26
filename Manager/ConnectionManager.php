<?php

namespace Sineflow\ElasticsearchBundle\Manager;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Forbidden403Exception;
use Sineflow\ElasticsearchBundle\Mapping\MappingTool;

/**
 * This class interacts with elasticsearch using injected client.
 */
class ConnectionManager
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $connectionSettings;

    /**
     * @var array Container for bulk queries.
     */
    private $bulkQueries;

    /**
     * @var array Holder for consistency, refresh and replication parameters.
     */
    private $bulkParams;

    /**
     * Construct.
     *
     * @param Client $client             Elasticsearch client.
     * @param array  $connectionSettings Settings array.
     */
    public function __construct($client, $connectionSettings)
    {
        $this->client = $client;
        $this->connectionSettings = $connectionSettings;
        $this->bulkQueries = [];
        $this->bulkParams = [];
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return array
     */
    public function getConnectionSettings()
    {
        return $this->connectionSettings;
    }

    /**
     * Adds query to bulk queries container.
     *
     * @param string $operation One of: index, update, delete, create.
     * @param string $index     Elasticsearch index name.
     * @param string $type      Elasticsearch type name.
     * @param array  $query     DSL to execute.
     *
     * @throws \InvalidArgumentException
     */
    public function addBulkOperation($operation, $index, $type, array $query)
    {
        if (!in_array($operation, ['index', 'create', 'update', 'delete'])) {
            throw new \InvalidArgumentException('Wrong bulk operation selected');
        }

        $this->bulkQueries['body'][] = [
            $operation => array_filter(
                [
                    '_index' => $index,
                    '_type' => $type,
                    '_id' => isset($query['_id']) ? $query['_id'] : null,
                    '_ttl' => isset($query['_ttl']) ? $query['_ttl'] : null,
                    '_parent' => isset($query['_parent']) ? $query['_parent'] : null,
                ]
            ),
        ];
        unset($query['_id'], $query['_ttl'], $query['_parent']);

        switch ($operation) {
            case 'index':
            case 'create':
                $this->bulkQueries['body'][] = $query;
                break;
            case 'update':
                $this->bulkQueries['body'][] = ['doc' => $query];
                break;
            case 'delete':
                // Body for delete operation is not needed to apply.
            default:
                // Do nothing.
                break;
        }
    }

    /**
     * Optional setter to change bulk query params.
     *
     * @param array $params Possible keys:
     *                      ['consistency'] = (enum) Explicit write consistency setting for the operation.
     *                      ['refresh']     = (boolean) Refresh the index after performing the operation.
     *                      ['replication'] = (enum) Explicitly set the replication type.
     */
    public function setBulkParams(array $params)
    {
        $this->bulkParams = $params;
    }

    /**
     * Executes the accumulated bulk queries to the index.
     *
     * @param bool $forceRefresh Make new data available for searching immediately
     *                           If immediate availability of the data for searching is not crucial, it's better
     *                           to set this to false, to get better performance. In the latter case, data would be
     *                           normally available within 1 second
     */
    public function commit($forceRefresh = true)
    {
        if (empty($this->bulkQueries)) {
            return;
        }

        $this->bulkQueries = array_merge($this->bulkQueries, $this->bulkParams);
        $this->getClient()->bulk($this->bulkQueries);
        if ($forceRefresh) {
            $this->refresh();
        }

        $this->bulkQueries = [];
    }

    /**
     * Send refresh call to index.
     *
     * Makes your documents available for search.
     */
    public function refresh()
    {
        $this->getClient()->indices()->refresh();
    }

    /**
     * Send flush call to index.
     *
     * Causes a Lucene commit to happen
     * In most cases refresh() should be used instead, as this is a very expensive operation
     */
    public function flush()
    {
        $this->getClient()->indices()->flush();
    }

    /**
     * Return all defined aliases in the ES cluster with all indices they point to
     *
     * @return array The ES aliases
     */
    public function getAliases()
    {
        $aliases = [];
        // Get all indices and their linked aliases and invert the results
        $indices = $this->getClient()->indices()->getAliases();
        foreach ($indices as $index => $data) {
            foreach ($data['aliases'] as $alias => $aliasData) {
                $aliases[$alias][$index] = [];
            }
        }

        return $aliases;
    }


//    /**
//     * Puts mapping into elasticsearch client.
//     *
//     * @param array $types Specific types to put.
//     *
//     * @return int
//     */
//    public function createTypes(array $types = [])
//    {
//        $mapping = $this->getMapping($types);
//        if (empty($mapping)) {
//            return 0;
//        }
//
//        $mapping = array_diff_key($mapping, $this->getMappingFromIndex($types));
//        if (empty($mapping)) {
//            return -1;
//        }
//
//        $this->loadMappingArray($mapping);
//
//        return 1;
//    }

//    /**
//     * Drops mapping from elasticsearch client.
//     *
//     * @param array $types Specific types to drop.
//     *
//     * @return int
//     */
//    public function dropTypes(array $types = [])
//    {
//        $mapping = $this->getMapping($types);
//
//        if (empty($mapping)) {
//            return 0;
//        }
//
//        $this->unloadMappingArray(array_keys($mapping));
//
//        return 1;
//    }
//
//    /**
//     * Updates elasticsearch client mapping.
//     *
//     * @param array $types Specific types to update.
//     *
//     * @return int
//     */
//    public function updateTypes(array $types = [])
//    {
//        if (!$this->getMapping($types)) {
//            return -1;
//        }
//
//        $tempSettings = $this->settings;
//        $tempSettings['index'] = uniqid('mapping_check_');
//        $mappingCheckConnection = new Connection($this->getClient(), $tempSettings);
//        $mappingCheckConnection->createIndex();
//        $mappingCheckConnection->createTypes($types);
//
//        $newMapping = $mappingCheckConnection->getMappingFromIndex($types);
//        $oldMapping = $this->getMappingFromIndex($types);
//
//        $mappingCheckConnection->dropIndex();
//
//        $tool = new MappingTool();
//        $updated = (int) $tool->checkMapping($oldMapping, $newMapping);
//
//        if ($updated) {
//            $this->unloadMappingArray($tool->getRemovedTypes());
//            $this->loadMappingArray($tool->getUpdatedTypes());
//        }
//
//        return $updated;
//    }

//    /**
//     * Tries to drop and create fresh elasticsearch index.
//     *
//     * @param bool $putWarmers Determines if warmers should be loaded.
//     * @param bool $noMapping  Determines if mapping should be included.
//     */
//    public function dropAndCreateIndex($putWarmers = false, $noMapping = false)
//    {
//        try {
//            $this->dropIndex();
//        } catch (\Exception $e) {
//            // Do nothing because I'm only trying.
//        }
//
//        $this->createIndex($putWarmers, $noMapping);
//    }

//    /**
//     * Checks if connection index is already created.
//     *
//     * @return bool
//     */
//    public function indexExists()
//    {
//        return $this->getClient()->indices()->exists(['index' => $this->getIndexName()]);
//    }

//    /**
//     * Returns mapping by type if defined.
//     *
//     * @param string|array $type Type names.
//     *
//     * @return array|null
//     */
//    public function getMapping($type = [])
//    {
//        if (isset($this->settings['body']['mappings'])) {
//            return $this->filterMapping($type, $this->settings['body']['mappings']);
//        }
//
//        return null;
//    }

//    /**
//     * Sets whole mapping, deleting non-existent types.
//     *
//     * @param array $mapping Mapping structure to force.
//     */
//    public function forceMapping(array $mapping)
//    {
//        $this->settings['body']['mappings'] = $mapping;
//    }
//
//    /**
//     * Sets mapping by type.
//     *
//     * @param string $type    Type name.
//     * @param array  $mapping Mapping structure.
//     */
//    public function setMapping($type, array $mapping)
//    {
//        $this->settings['body']['mappings'][$type] = $mapping;
//    }
//
//    /**
//     * Sets multiple mappings.
//     *
//     * @param array $mapping Mapping to set.
//     * @param bool  $cleanUp Cleans current mapping.
//     */
//    public function setMultipleMapping(array $mapping, $cleanUp = false)
//    {
//        if ($cleanUp === true) {
//            unset($this->settings['body']['mappings']);
//        }
//
//        foreach ($mapping as $type => $map) {
//            $this->setMapping($type, $map);
//        }
//    }
//
//    /**
//     * Mapping is compared with loaded, if needed updates it and returns true.
//     *
//     * @param array $types Types to update.
//     *
//     * @return bool
//     *
//     * @throws \LogicException
//     *
//     * @deprecated Will be removed in 1.0. Please now use Connection#updateTypes().
//     */
//    public function updateMapping(array $types = [])
//    {
//        return $this->updateTypes($types);
//    }

//    /**
//     * Closes index.
//     */
//    public function close()
//    {
//        $this->getClient()->indices()->close(['index' => $this->getIndexName()]);
//    }
//
//    /**
//     * Returns whether the index is opened.
//     *
//     * @return bool
//     */
//    public function isOpen()
//    {
//        try {
//            $this->getClient()->indices()->recovery(['index' => $this->getIndexName()]);
//        } catch (Forbidden403Exception $ex) {
//            return false;
//        }
//
//        return true;
//    }
//
//    /**
//     * Opens index.
//     */
//    public function open()
//    {
//        $this->getClient()->indices()->open(['index' => $this->getIndexName()]);
//    }
//
//    /**
//     * Returns mapping from index.
//     *
//     * @param array|string $types Returns only certain set of types if set.
//     *
//     * @return array
//     */
//    public function getMappingFromIndex($types = [])
//    {
//        $mapping = $this
//            ->getClient()
//            ->indices()
//            ->getMapping(['index' => $this->getIndexName()]);
//
//        if (array_key_exists($this->getIndexName(), $mapping)) {
//            return $this->filterMapping($types, $mapping[$this->getIndexName()]['mappings']);
//        }
//
//        return [];
//    }

//
//    /**
//     * Adds warmer to container.
//     *
//     * @param WarmerInterface $warmer
//     */
//    public function addWarmer(WarmerInterface $warmer)
//    {
//        $this->warmers->addWarmer($warmer);
//    }
//
//    /**
//     * Loads warmers into elasticseach.
//     *
//     * @param array $names Warmers names to put.
//     *
//     * @return bool
//     */
//    public function putWarmers(array $names = [])
//    {
//        return $this->warmersAction('put', $names);
//    }
//
//    /**
//     * Deletes warmers from elasticsearch index.
//     *
//     * @param array $names Warmers names to delete.
//     *
//     * @return bool
//     */
//    public function deleteWarmers(array $names = [])
//    {
//        return $this->warmersAction('delete', $names);
//    }

//    /**
//     * Executes warmers actions.
//     *
//     * @param string $action Action name.
//     * @param array  $names  Warmers names.
//     *
//     * @return bool
//     *
//     * @throws \LogicException
//     */
//    private function warmersAction($action, $names = [])
//    {
//        $status = false;
//        $warmers = $this->warmers->getWarmers();
//        $this->validateWarmers($names, array_keys($warmers));
//
//        foreach ($warmers as $name => $body) {
//            if (empty($names) || in_array($name, $names)) {
//                switch ($action) {
//                    case 'put':
//                        $this->getClient()->indices()->putWarmer(
//                            [
//                                'index' => $this->getIndexName(),
//                                'name' => $name,
//                                'body' => $body,
//                            ]
//                        );
//                        break;
//                    case 'delete':
//                        $this->getClient()->indices()->deleteWarmer(
//                            [
//                                'index' => $this->getIndexName(),
//                                'name' => $name,
//                            ]
//                        );
//                        break;
//                    default:
//                        throw new \LogicException('Unknown warmer action');
//                }
//            }
//
//            $status = true;
//        }
//
//        return $status;
//    }
//
//    /**
//     * Warmer names validation.
//     *
//     * @param array $names       Names to check.
//     * @param array $warmerNames Warmer names loaded.
//     *
//     * @throws \RuntimeException
//     */
//    private function validateWarmers($names, $warmerNames = [])
//    {
//        if (empty($warmerNames)) {
//            $warmerNames = array_keys($this->warmers->getWarmers());
//        }
//
//        $unknown = array_diff($names, $warmerNames);
//
//        if (!empty($unknown)) {
//            throw new \RuntimeException(
//                'Warmer(s) named ' . implode(', ', $unknown)
//                . ' do not exist. Available: ' . implode(', ', $warmerNames)
//            );
//        }
//    }
//
//    /**
//     * Puts mapping into elasticsearch.
//     *
//     * @param array $mapping Mapping to put into client.
//     */
//    private function loadMappingArray(array $mapping)
//    {
//        foreach ($mapping as $type => $properties) {
//            $this->getClient()->indices()->putMapping(
//                [
//                    'index' => $this->getIndexName(),
//                    'type' => $type,
//                    'body' => [
//                        $type => $properties,
//                    ],
//                ]
//            );
//        }
//    }
//
//    /**
//     * Drops mapping from elasticsearch client.
//     *
//     * @param array $mapping Mapping to drop from client.
//     */
//    private function unloadMappingArray(array $mapping)
//    {
//        foreach ($mapping as $type) {
//            $this->getClient()->indices()->deleteMapping(
//                [
//                    'index' => $this->getIndexName(),
//                    'type' => $type,
//                ]
//            );
//        }
//    }
//
//    /**
//     * Filters out mapping from given type.
//     *
//     * @param string|array $type    Types to filter from mapping.
//     * @param array        $mapping Mapping array.
//     *
//     * @return array
//     */
//    private function filterMapping($type, $mapping)
//    {
//        if (empty($type)) {
//            return $mapping;
//        } elseif (is_string($type) && array_key_exists($type, $mapping)) {
//            return $mapping[$type];
//        } elseif (is_array($type)) {
//            return array_intersect_key($mapping, array_flip($type));
//        }
//
//        return [];
//    }
}
