<?php
/*
 * Copyright © Unreality One. All rights reserved.
 */

declare(strict_types = 1);

namespace Unreality\UsefulTools\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Mview\ViewInterfaceFactory;
use Magento\Framework\Mview\ViewInterface;
use Magento\Framework\Mview\View\ChangelogTableNotExistsException;
use Magento\Framework\App\State;

/**
 * Reindex of a single mview partial index
 */
class MviewIndexCommand extends Command
{
    /**
     * Index ID argument
     */
    const ID_ARGUMENT = 'id';

    /**
     * Environment argument
     */
    const ENV_ARGUMENT = 'env';

    /**
     * @var ViewInterfaceFactory
     */
    private $viewFactory;

    /**
     * @var State
     */
    private $appState;

    public function __construct(
        ViewInterfaceFactory $viewFactory,
        State $appState
    ) {
        $this->viewFactory = $viewFactory;
        $this->appState = $appState;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('indexer:mview:reindex')
             ->setDescription('Reindex a single mview index')
             ->setDefinition([
                                 new InputArgument(
                                     self::ID_ARGUMENT,
                                     InputArgument::REQUIRED,
                                     'Mview Index ID'
                                 ),
                                 new InputArgument(
                                     self::ENV_ARGUMENT,
                                     InputArgument::OPTIONAL,
                                     'Environment ID'
                                 ),
                             ]);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument(static::ID_ARGUMENT);
        $env = $input->getArgument(static::ENV_ARGUMENT);
        $returnValue = Cli::RETURN_FAILURE;
        $view = $this->viewFactory->create();
        $view->load($id);
        if (!$view->getId()) {
            $output->writeln('<error>Index ID  ' . $id . ' not found </error>');
            return $returnValue;
        }
        if (!$view->isIdle() || !$view->isEnabled()) {
            $output->writeln('<error>The index cannot trigger update. Status: '
                             . $view->getState()->getStatus() . '; Mode: '.$view->getState()->getMode().' </error>');
            return $returnValue;
        }

        $startTime = microtime(true);
        $output->writeln("<info>Index ID " . $id . " ({$this->getPendingCount($view)} in backlog)</info>");
        try {
            if ($env) {
                $this->appState->emulateAreaCode(
                    $env,
                    function() use ($view) {
                        $view->update();
                    }
                );
            } else {
                $view->update();
            }

            $returnValue = Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        // option to emulate environment
        $resultTime = floor(microtime(true) - $startTime);
        $output->writeln('<info>Completed in ' . $resultTime. ' sec</info>');

        return $returnValue;
    }

    /**
     * Returns the pending count of the view
     *
     * @param ViewInterface $view
     * @return int
     */
    private function getPendingCount(ViewInterface $view): int
    {
        $changelog = $view->getChangelog();

        try {
            $currentVersionId = $changelog->getVersion();
        } catch (ChangelogTableNotExistsException $e) {
            return 0;
        }

        $state = $view->getState();

        return count($changelog->getList($state->getVersionId(), $currentVersionId));
    }
}
