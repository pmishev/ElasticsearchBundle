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
            )
            ->addOption(
                'cancel-current',
                null,
                InputOption::VALUE_NONE,
                'If set, any indices the write alias points to (except the live one) will be deleted'
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

        $deleteOldIndex = (bool) $input->getOption('delete-old');
        $cancelCurrent = (bool) $input->getOption('cancel-current');

        try {
            $indexManager->rebuildIndex($deleteOldIndex, $cancelCurrent);
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
