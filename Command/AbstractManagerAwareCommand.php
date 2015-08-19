<?php

namespace Sineflow\ElasticsearchBundle\Command;

use Sineflow\ElasticsearchBundle\Manager\IndexManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class providing common methods for commands working with an index manager
 */
abstract class AbstractManagerAwareCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addArgument(
            'index',
            InputArgument::REQUIRED,
            'The identifier of the index'
        );
    }

    /**
     * Returns index manager by name from the service container.
     *
     * @param string $name Index name as defined in the configuration.
     *
     * @return IndexManager
     *
     * @throws \RuntimeException If index manager was not found.
     */
    protected function getManager($name)
    {
        $id = $this->getIndexManagerId($name);

        if ($this->getContainer()->has($id)) {
            return $this->getContainer()->get($id);
        }

        throw new \RuntimeException(
            sprintf(
                'Index manager named `%s` not found. Available: `%s`.',
                $name,
                implode('`, `', array_keys($this->getContainer()->getParameter('sfes.indices')))
            )
        );
    }

    /**
     * Formats manager service id from its name.
     *
     * @param string $name Manager name.
     *
     * @return string Service id.
     */
    private function getIndexManagerId($name)
    {
        return sprintf('sfes.index.%s', $name);
    }
}
