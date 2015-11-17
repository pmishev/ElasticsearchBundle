<?php

namespace Sineflow\ElasticsearchBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for creating elasticsearch index.
 */
class IndexCreateCommand extends AbstractManagerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('sineflow:es:index:create')
            ->setDescription('Creates elasticsearch index.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $indexManagerName = $input->getArgument('index');
        $indexManager = $this->getManager($indexManagerName);
        try {
            $indexManager->createIndex();
            $output->writeln(
                sprintf(
                    '<info>Created index for "</info><comment>%s</comment><info>"</info>',
                    $indexManagerName
                )
            );
        } catch (\Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>Index creation failed:</error> <comment>%s</comment>',
                    $e->getMessage()
                )
            );
        }
    }
}
