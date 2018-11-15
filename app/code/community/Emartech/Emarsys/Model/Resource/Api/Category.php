<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Resource_Api_Category
 */
class Emartech_Emarsys_Model_Resource_Api_Category extends Mage_Catalog_Model_Resource_Category
{
    /**
     * @var Iterator
     */
    private $_iterator;

    /**
     * @var array
     */
    private $_categoryIds = [];

    /**
     * @var array
     */
    private $_categories = [];

    /**
     * Emartech_Emarsys_Model_Resource_Api_Category constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_iterator = Mage::getResourceModel('core/iterator');
    }

    /**
     * @param int $minProductId
     * @param int $maxProductId
     *
     * @return array
     */
    public function getCategoryIds($minProductId, $maxProductId)
    {
        $this->_categoryIds = [];

        $categoryQuery = $this->_getReadAdapter()->select()
            ->from($this->getTable('catalog_category_product'), ['category_id', 'product_id'])
            ->where('product_id >= ?', $minProductId)
            ->where('product_id <= ?', $maxProductId);

        $this->_iterator->walk(
            (string)$categoryQuery,
            [[$this, 'handleCategoryId']],
            [],
            $this->_getReadAdapter()
        );

        return $this->_categoryIds;
    }

    /**
     * @param array $args
     *
     * @return void
     */
    public function handleCategoryId($args)
    {
        $productId = $args['row']['product_id'];
        $categoryId = $args['row']['category_id'];

        if (!array_key_exists($productId, $this->_categoryIds)) {
            $this->_categoryIds[$productId] = [];
        }
        $this->_categoryIds[$productId][] = $this->handleCategory($categoryId);
    }

    /**
     * @param int $categoryId
     *
     * @return string
     */
    private function handleCategory($categoryId)
    {
        $categoryData = $this->getCategory($categoryId);

        if ($categoryData instanceof Mage_Catalog_Model_Category) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $categoryData->getPath();
        }

        return '';
    }

    /**
     * @param int $categoryId
     *
     * @return Mage_Catalog_Model_Category | null
     */
    private function getCategory($categoryId)
    {
        if (!array_key_exists($categoryId, $this->_categories)) {
            $categoryCollection = Mage::getModel('catalog/category')->getCollection();
            /** @var Mage_Catalog_Model_Category $category */
            foreach ($categoryCollection as $category) {
                $this->_categories[$category->getId()] = $category;
            }
        }

        return $this->_categories[$categoryId];
    }
}
