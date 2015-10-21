<?php

namespace Sineflow\ElasticsearchBundle\Command;

use Sineflow\ElasticsearchBundle\Manager\IndexManager;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for (re)building elasticsearch index.
 */
class IndexBuildCommand extends AbstractManagerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('sineflow:es:index:build')
            ->setDescription('(Re)builds elasticsearch index.')
            ->addOption(
                'delete-old',
                null,
                InputOption::VALUE_NONE,
                'If set, the old index will be deleted upon successful rebuilding'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $indexManagerName = $input->getArgument('index');
        /** @var IndexManager $indexManager */
        $indexManager = $this->getManager($indexManagerName);

        if ($input->getOption('delete-old')) {
            $deleteOldIndex = true;
        } else {
            $deleteOldIndex = false;
        }


        try {
            $indexManager->rebuildIndex($deleteOldIndex);
            $output->writeln(
                sprintf(
                    '<info>Built index for "</info><comment>%s</comment><info>"</info>',
                    $indexManagerName
                )
            );
        } catch (\Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>Index building failed:</error> <comment>%s</comment>',
                    $e->getMessage()
                )
            );
        }
    }
}
