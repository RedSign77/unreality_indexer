<?php
/*
 * Copyright © Unreality One. All rights reserved.
 */

declare(strict_types = 1);

namespace Unreality\UsefulTools\Ui\DataProvider\AttributeOptions\Listing;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Catalog\Api\Data\ProductAttributeInterface;

class Collection extends SearchResult
{
    /**
     * Override _initSelect to add custom columns
     *
     * @return Collection
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->joinInner(
            ['eao' => $this->getTable('eav_attribute_option')],
            '`main_table`.option_id = `eao`.option_id',
            'attribute_id'
        )->joinInner(
            ['ea' => $this->getTable('eav_attribute')],
            '`eao`.attribute_id = `ea`.attribute_id',
            'attribute_code'
        )->joinInner(
            ['eet' => $this->getTable('eav_entity_type')],
            '`eet`.entity_type_id = `ea`.entity_type_id',
            ''
        );
        $this->addFieldToFilter('eet.entity_type_code', ['eq' => ProductAttributeInterface::ENTITY_TYPE_CODE]);
        $this->addFieldToFilter('ea.backend_type', ['eq' => 'int']);
        $this->addFieldToFilter('main_table.option_id', ['nin' => $this->getUsedOptionValueIds()]);

        return $this;
    }

    /**
     * @return array
     */
    private function getUsedOptionValueIds(): array
    {
        $idsSelect = clone $this->getSelect();
        $idsSelect->reset();
        $idsSelect->from($this->getTable('eav_attribute_option'), 'attribute_id')
                  ->group('attribute_id');
        $attributeIds = $this->getConnection()->fetchCol($idsSelect);
        $idsSelect->reset();
        $idsSelect->from($this->getTable('catalog_product_entity_int'), 'value')
                  ->where('attribute_id IN (?)', $attributeIds)
                  ->group('value');

        return $this->getConnection()->fetchCol($idsSelect);
    }
}