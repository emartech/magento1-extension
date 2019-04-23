<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Categories
 */
class Emartech_Emarsys_Model_Categories extends Emartech_Emarsys_Model_Abstract_Base implements Emartech_Emarsys_Model_Abstract_GetInterface
{

    /**
     * @var array
     */
    private $_storeIds = [];

    /**
     * @var null|Mage_Catalog_Model_Resource_Category_Collection
     */
    private $_collection = null;

    /**
     * @var null|string
     */
    private $_linkField = null;

    /**
     * @var array
     */
    private $_storeCategoryAttributeCodes = ['name', 'image', 'description', 'is_active', 'store_id'];

    /**
     * @var array
     */
    private $_globalCategoryAttributeCodes = ['entity_id', 'path', 'children_count', 'stores'];

    /**
     * @param Emartech_Emarsys_Controller_Request_Http $request
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    public function handleGet($request)
    {
        $storeIds = $request->getParam('store_id', []);
        $page = $request->getParam('page', 0);
        $pageSize = $request->getParam('page_size', 1000);

        $this->_initStores($storeIds);

        if (!array_key_exists(0, $this->_storeIds)) {
            throw new Exception('Store ID must contain 0');
        }

        $this
            ->_initCollection()
            ->_joinData()
            ->_setOrder()
            ->_setPage($page, $pageSize);

        return [
            'current_page' => $this->_collection->getCurPage(),
            'last_page'    => $this->_collection->getLastPageNumber(),
            'page_size'    => $this->_collection->getPageSize(),
            'total_count'  => $this->_collection->getSize(),
            'categories'   => $this->_handleCategories(),
        ];
    }

    private function _initStores($storeIds)
    {
        $this->_storeIds = [];

        if (!is_array($storeIds)) {
            $storeIds = explode(',', $storeIds);
        }

        $availableStores = Mage::app()->getStores(true);

        /** @var Mage_Core_Model_Store $availableStore */
        foreach ($availableStores as $availableStore) {
            if (in_array($availableStore->getId(), $storeIds)) {
                $this->_storeIds[$availableStore->getId()] = $availableStore;
            }
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Mage_Core_Exception
     */
    private function _initCollection()
    {
        $this->_collection = Mage::getResourceModel('catalog/category_collection');
        $this->_linkField = $this->_collection->getResource()->getIdFieldName();
        return $this;
    }

    /**
     * @return $this
     * @throws Mage_Core_Exception
     */
    private function _joinData()
    {
        $entityTypeId = Mage::getSingleton('eav/config')->getEntityType(Mage_Catalog_Model_Category::ENTITY)->getId();
        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Collection $attributeCollection */
        $attributeCollection = Mage::getModel('eav/entity_attribute')->getCollection();
        $attributeCollection->setEntityTypeFilter($entityTypeId);
        $attributeCollection->addFieldToFilter('attribute_code', [
            'in' =>
                array_values(array_merge(
                    $this->_storeCategoryAttributeCodes,
                    $this->_globalCategoryAttributeCodes
                )),
        ]);

        $mainTableName = $this->_collection->getTable('catalog/category');

        $joinAttr = [];

        foreach ($attributeCollection as $attribute) {
            $joinAttr[] = $attribute->getAttributeCode();
            if ($attribute->getBackendTable() === $mainTableName) {
                $this->_collection->addAttributeToSelect($attribute->getAttributeCode());
            } elseif (in_array($attribute->getAttributeCode(), $this->_globalCategoryAttributeCodes)) {
                $valueAlias = $this->_getAttributeValueAlias($attribute->getAttributeCode());

                $this->_collection->joinAttribute(
                    $valueAlias,
                    'catalog_category/' . $attribute->getAttributeCode(),
                    $this->_linkField,
                    null,
                    'left'
                );
            } else {
                foreach (array_keys($this->_storeIds) as $storeId) {
                    $valueAlias = $this->_getAttributeValueAlias($attribute->getAttributeCode(), $storeId);
                    $this->_collection->joinAttribute(
                        $valueAlias,
                        'catalog_category/' . $attribute->getAttributeCode(),
                        $this->_linkField,
                        null,
                        'left',
                        $storeId
                    );
                }
            }
        }

        return $this;
    }

    /**
     * @param string   $attributeCode
     * @param int|null $storeId
     *
     * @return string
     */
    private function _getAttributeValueAlias($attributeCode, $storeId = null)
    {
        $returnValue = $attributeCode;
        if ($storeId !== null) {
            $returnValue .= '_' . $storeId;
        }
        return $returnValue;
    }

    /**
     * @return $this
     */
    private function _setOrder()
    {
        $this->_collection->setOrder($this->_linkField, Varien_Data_Collection::SORT_ORDER_ASC);

        return $this;
    }

    /**
     * @param int $page
     * @param int $pageSize
     *
     * @return $this
     */
    private function _setPage($page, $pageSize)
    {
        $this->_collection->setPage($page, $pageSize);

        return $this;
    }

    /**
     * @return array
     */
    private function _handleCategories()
    {
        $returnArray = [];

        /** @var Mage_Catalog_Model_Category $category */
        foreach ($this->_collection as $category) {
            $returnArray[] = [
                'path'           => $category->getPath(),
                'entity_id'      => (int) $category->getId(),
                'children_count' => (int) $category->getChildrenCount(),
                'store_data'     => $this->_handleCategoryStoreData($category),
            ];
        }

        return $returnArray;
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     *
     * @return array
     */
    private function _handleCategoryStoreData($category)
    {
        $returnArray = [];

        foreach ($this->_storeIds as $storeId => $storeObject) {
            $returnArray[] = [
                'store_id'    => $storeId,
                'is_active'   => (int) $category->getData($this->_getAttributeValueAlias('is_active', $storeId)),
                'image'       => $this->_handleImage($category, $storeObject),
                'name'        => $category->getData($this->_getAttributeValueAlias('name', $storeId)),
                'description' => $category->getData($this->_getAttributeValueAlias('description', $storeId)),
            ];
        }

        return $returnArray;
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     * @param Mage_Core_Model_Store       $store
     *
     * @return string
     */
    private function _handleImage($category, $store)
    {
        $imagePreUrl = $this->_storeIds[0]->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/category/';
        $image = $category->getData($this->_getAttributeValueAlias('image', $store->getId()));

        if ($image) {
            return $imagePreUrl . $image;
        }

        return '';
    }
}
