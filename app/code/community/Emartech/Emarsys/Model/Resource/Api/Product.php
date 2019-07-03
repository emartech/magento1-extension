<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Resource_Api_Product
 */
class Emartech_Emarsys_Model_Resource_Api_Product extends Mage_Catalog_Model_Resource_Product
{
    const PRODUCT_ENTITY_TYPE_ID = 4;

    /**
     * @var Iterator
     */
    private $_iterator;

    /**
     * @var array
     */
    private $_childrenProductIds = [];

    /**
     * @var array
     */
    private $_stockData = [];

    /**
     * @var array
     */
    private $_attributeData = [];

    /**
     * @var string
     */
    private $_mainTable = '';

    /**
     * @var array
     */
    private $_storeProductAttributeCodes = [
        'name',
        'price',
        'url_key',
        'description',
        'status',
        'currency',
        'display_price',
        'special_price',
        'special_from_date',
        'special_to_date',
    ];

    /**
     * @var array
     */
    private $_globalProductAttributeCodes = [
        'entity_id',
        'type',
        'children_entity_ids',
        'categories',
        'sku',
        'images',
        'qty',
        'is_in_stock',
        'stores',
        'image',
        'small_image',
        'thumbnail',
    ];

    /**
     * Emartech_Emarsys_Model_Resource_Api_Products constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_iterator = Mage::getResourceSingleton('core/iterator');
        $this->_mainTable = Mage::getResourceModel('catalog/product')->getEntityTable();
    }

    /**
     * @param int    $page
     * @param int    $pageSize
     * @param string $linkField
     *
     * @return array
     */
    public function handleIds($page, $pageSize, $linkField)
    {
        $productsTable = $this->getTable('catalog_product_entity');

        $itemsCountQuery = $this->_getReadAdapter()->select()
            ->from($productsTable, ['count' => 'count(' . $linkField . ')']);

        $numberOfItems = $this->_getReadAdapter()->fetchOne($itemsCountQuery);

        $minMaxValues = [
            'minId' => 0,
            'maxId' => 0,
        ];

        if ($numberOfItems > 0) {
            $subSelect = $this->_getReadAdapter()->select()
                ->from($productsTable, ['eid' => $linkField])
                ->order($linkField)
                ->limit($pageSize, $page);

            $idQuery = $this->_getReadAdapter()->select()
                ->from(['tmp' => $subSelect], ['minId' => 'min(tmp.eid)', 'maxId' => 'max(tmp.eid)']);

            $minMaxValues = $this->_getReadAdapter()->fetchRow($idQuery);
        }

        return [
            'numberOfItems' => (int)$numberOfItems,
            'minId'         => (int)$minMaxValues['minId'],
            'maxId'         => (int)$minMaxValues['maxId'],
        ];
    }

    /**
     * @param int $minProductId
     * @param int $maxProductId
     *
     * @return array
     */
    public function getChildrenProductIds($minProductId, $maxProductId)
    {
        $this->_childrenProductIds = [];

        $superLinkTable = $this->getTable('catalog_product_super_link');

        $superLinkQuery = $this->_getReadAdapter()->select()
            ->from($superLinkTable, ['product_id', 'parent_id'])
            ->where('parent_id >= ?', $minProductId)
            ->where('parent_id <= ?', $maxProductId);

        $this->_iterator->walk(
            (string)$superLinkQuery,
            [[$this, 'handleChildrenProductId']],
            [],
            $this->_getReadAdapter()
        );

        return $this->_childrenProductIds;
    }

    /**
     * @param array $args
     *
     * @return void
     */
    public function handleChildrenProductId($args)
    {
        $parentId = $args['row']['parent_id'];
        if (!array_key_exists($parentId, $this->_childrenProductIds)) {
            $this->_childrenProductIds[$parentId] = [];
        }
        $this->_childrenProductIds[$parentId][] = $args['row']['product_id'];
    }

    /**
     * @param int $minProductId
     * @param int $maxProductId
     *
     * @return array
     */
    public function getStockData($minProductId, $maxProductId)
    {
        $this->_stockData = [];
        $stockQuery = $this->_getReadAdapter()->select()
            ->from($this->getTable('cataloginventory_stock_item'), ['is_in_stock', 'qty', 'product_id'])
            ->where('product_id >= ?', $minProductId)
            ->where('product_id <= ?', $maxProductId)
            ->where('stock_id = ?', 1);

        $this->_iterator->walk(
            (string)$stockQuery,
            [[$this, 'handleStockItem']],
            [],
            $this->_getReadAdapter()
        );

        return $this->_stockData;
    }

    /**
     * @param array $args
     *
     * @return void
     */
    public function handleStockItem($args)
    {
        $this->_stockData[$args['row']['product_id']] = [
            'is_in_stock' => $args['row']['is_in_stock'],
            'qty'         => $args['row']['qty'],
        ];
    }

    /**
     * @param int   $minProductId
     * @param int   $maxProductId
     * @param array $storeIds
     *
     * @return array
     */
    public function getAttributeData($minProductId, $maxProductId, $storeIds)
    {
        $this->_attributeData = [];

        $attributeMapper = [];
        $mainTableFields = [];
        $attributeTables = [];

        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Collection $productAttributeCollection */
        $productAttributeCollection = Mage::getResourceModel('eav/entity_attribute_collection');
        $productAttributeCollection
            ->addFieldToFilter('entity_type_id', ['eq' => self::PRODUCT_ENTITY_TYPE_ID])
            ->addFieldToFilter('attribute_code', [
                'in' => array_values(array_merge(
                    $this->_storeProductAttributeCodes,
                    $this->_globalProductAttributeCodes
                )),
            ]);

        /** @var Mage_Eav_Model_Entity_Attribute $productAttribute */
        foreach ($productAttributeCollection as $productAttribute) {
            $attributeTable = $productAttribute->getBackendTable();
            if ($this->_mainTable === $attributeTable) {
                $mainTableFields[] = $productAttribute->getAttributeCode();
            } else {
                if (!in_array($attributeTable, $attributeTables)) {
                    $attributeTables[] = $attributeTable;
                }
                $attributeMapper[$productAttribute->getAttributeCode()] = (int)$productAttribute->getId();
            }
        }

        $this
            ->_getMainTableFieldItems($mainTableFields, $minProductId, $maxProductId, $storeIds, $attributeMapper)
            ->_getAttributeTableFieldItems($attributeTables, $minProductId, $maxProductId, $storeIds, $attributeMapper);

        return $this->_attributeData;
    }

    /**
     * @param array $mainTableFields
     * @param int   $minProductId
     * @param int   $maxProductId
     * @param array $storeIds
     * @param array $attributeMapper
     *
     * @return $this
     */
    private function _getMainTableFieldItems($mainTableFields, $minProductId, $maxProductId, $storeIds, $attributeMapper)
    {
        if ($mainTableFields) {
            if (!in_array('entity_id', $mainTableFields)) {
                $mainTableFields[] = 'entity_id';
            }
            $attributesQuery = $this->_getReadAdapter()->select()
                ->from($this->_mainTable, $mainTableFields)
                ->where('entity_id >= ?', $minProductId)
                ->where('entity_id <= ?', $maxProductId);

            $this->_iterator->walk(
                (string)$attributesQuery,
                [[$this, 'handleMainTableAttributeDataTable']],
                [
                    'storeIds'        => $storeIds,
                    'fields'          => array_diff($mainTableFields, ['entity_id']),
                    'attributeMapper' => $attributeMapper,
                ],
                $this->_getReadAdapter()
            );
        }

        return $this;
    }

    /**
     * @param array $attributeTables
     * @param int   $minProductId
     * @param int   $maxProductId
     * @param array $storeIds
     * @param array $attributeMapper
     *
     * @return $this
     */
    private function _getAttributeTableFieldItems($attributeTables, $minProductId, $maxProductId, $storeIds, $attributeMapper)
    {
        $attributeQueries = [];

        foreach ($attributeTables as $attributeTable) {
            $attributeQueries[] = $this->_getReadAdapter()->select()
                ->from($attributeTable, ['attribute_id', 'store_id', 'entity_id', 'value'])
                ->where('entity_id >= ?', $minProductId)
                ->where('entity_id <= ?', $maxProductId)
                ->where('store_id IN (?)', $storeIds)
                ->where('attribute_id IN (?)', $attributeMapper);
        }

        try {
            $unionQuery = $this->_getReadAdapter()->select()->union($attributeQueries, Zend_Db_Select::SQL_UNION_ALL);
            $this->_iterator->walk(
                (string)$unionQuery,
                [[$this, 'handleAttributeDataTable']],
                [
                    'attributeMapper' => $attributeMapper,
                ],
                $this->_getReadAdapter()
            );
        } catch (\Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

    /**
     * @param array $args
     *
     * @return void
     */
    public function handleMainTableAttributeDataTable($args)
    {
        $productId = $args['row']['entity_id'];

        foreach ($args['storeIds'] as $storeId) {
            $this->_initStoreProductData($productId, $storeId);

            foreach ($args['fields'] as $field) {
                $this->_attributeData[$productId][$storeId][$field] = $args['row'][$field];
            }
        }

    }

    /**
     * @param array $args
     *
     * @return void
     */
    public function handleAttributeDataTable($args)
    {
        $productId = $args['row']['entity_id'];
        $attributeCode = $this->_findAttributeCodeById($args['row']['attribute_id'], $args['attributeMapper']);
        $storeId = $args['row']['store_id'];

        $this->_initStoreProductData($productId, $storeId);

        $this->_attributeData[$productId][$storeId][$attributeCode] = $args['row']['value'];
    }

    /**
     * @param int   $attributeId
     * @param array $attributeMapper
     *
     * @return string
     */
    private function _findAttributeCodeById($attributeId, $attributeMapper)
    {
        foreach ($attributeMapper as $attributeCode => $attributeCodeId) {
            if ($attributeId == $attributeCodeId) {
                return $attributeCode;
            }
        }

        return '';
    }

    /**
     * @param int $productId
     * @param int $storeId
     *
     * @return void
     */
    private function _initStoreProductData($productId, $storeId)
    {
        if (!array_key_exists($productId, $this->_attributeData)) {
            $this->_attributeData[$productId] = [];
        }

        if (!array_key_exists($storeId, $this->_attributeData[$productId])) {
            $this->_attributeData[$productId][$storeId] = [];
        }
    }
}
