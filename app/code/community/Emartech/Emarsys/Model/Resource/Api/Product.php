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
     * Emartech_Emarsys_Model_Resource_Api_Products constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_iterator = Mage::getResourceSingleton('core/iterator');
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

        $subSelect = $this->_getReadAdapter()->select()
            ->from($productsTable, ['eid' => $linkField])
            ->order($linkField)
            ->limit($pageSize, $page);

        $idQuery = $this->_getReadAdapter()->select()
            ->from(['tmp' => $subSelect], ['minId' => 'min(tmp.eid)', 'maxId' => 'max(tmp.eid)']);

        $minMaxValues = $this->_getReadAdapter()->fetchRow($idQuery);

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
        $productId = $args['row']['product_id'];
        $parentId = $args['row']['parent_id'];
        if (!array_key_exists($parentId, $this->_childrenProductIds)) {
            $this->_childrenProductIds[$parentId] = [];
        }
        $this->_childrenProductIds[$parentId][] = $productId;
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
        $productId = $args['row']['product_id'];
        $isInStock = $args['row']['is_in_stock'];
        $qty = $args['row']['qty'];

        $this->_stockData[$productId] = [
            'is_in_stock' => $isInStock,
            'qty'         => $qty,
        ];
    }
}
