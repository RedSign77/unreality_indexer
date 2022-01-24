<?php
/*
 * Copyright © Unreality One. All rights reserved.
 */

declare(strict_types = 1);

namespace Unreality\UsefulTools\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Magento\Framework\App\State;
use Symfony\Component\Console\Helper\Table;
use Magento\Framework\Console\Cli;
use Magento\Framework\App\Area;
use Unreality\UsefulTools\Ui\DataProvider\AttributeOptions\Listing\CollectionFactory;
use Unreality\UsefulTools\Ui\DataProvider\AttributeOptions\Listing\Collection;

class RemoveUAOptionsCommand extends Command
{

    /**
     * @var State
     */
    private State $appState;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * RemoveUnusedAttributeOptionsCommand constructor.
     *
     * @param CollectionFactory $collectionFactory
     * @param State             $appState
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        State $appState
    ) {
        /** @var Collection collection */
        $this->collection = $collectionFactory->create(['mainTable' => 'eav_attribute_option_value']);
        $this->appState   = $appState;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('catalog:product:attribute:options:cleanup');
        $this->setDescription('Removes unused product attribute options.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     * @throws LocalizedException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(true);
        $this->appState->setAreaCode(Area::AREA_GLOBAL);
        $attributeOptionValues = $this->collection->getData();
        $optionIdsForeDelete   = $this->renderTable($output, $attributeOptionValues);

        $helper   = $this->getHelper('question');
        $question = new ConfirmationQuestion('Continue to remove attribute values? (y/n) ', false);

        if (!$helper->ask($input, $output, $question)) {
            return Cli::RETURN_SUCCESS;
        }

        try {
            $connection = $this->collection->getConnection();
            $table      = $this->collection->getTable('eav_attribute_option');
            $connection->beginTransaction();
            $connection->delete($table, ['option_id in (?)' => $optionIdsForeDelete]);
            $connection->commit();

            $output->writeln('');
            $output->writeln('<info>Product attribute option values successfully cleaned up.</info>');
        } catch (\Exception $exception) {
            $output->writeln('');
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param  OutputInterface $output
     * @param  array           $attributeOptionValues
     * @return array
     */
    private function renderTable(OutputInterface $output, array $attributeOptionValues): array
    {
        $optionIdsForeDelete = [];
        $rows                = [];
        $table               = new Table($output);
        $table->setHeaders(['ID', 'Option ID', 'Value', 'Attribute Code']);
        foreach ($attributeOptionValues as $value) {
            $rowData               = [
                'ID'             => $value['value_id'],
                'Option ID'      => $value['option_id'],
                'Value'          => $value['value'],
                'Attribute Code' => $value['attribute_code'],
            ];
            $rows[]                = $rowData;
            $optionIdsForeDelete[] = $value['option_id'];
        }

        $table->addRows($rows);
        $table->render();
        return $optionIdsForeDelete;
    }
}
