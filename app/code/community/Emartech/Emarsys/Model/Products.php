<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Products
 */
class Emartech_Emarsys_Model_Products extends Emartech_Emarsys_Model_Abstract_Base implements Emartech_Emarsys_Model_Abstract_GetInterface
{
    /**
     * @var null|Mage_Catalog_Model_Resource_Product_Collection
     */
    private $_productCollection = null;

    /**
     * @var array
     */
    private $_storeIds = [];

    /**
     * @var Emartech_Emarsys_Model_Resource_Api_Product
     */
    private $_productResource;

    /**
     * @var Emartech_Emarsys_Model_Resource_Api_Category
     */
    private $_categoryResource;

    /**
     * @var int
     */
    private $_numberOfItems;

    /**
     * @var int
     */
    private $_minId;

    /**
     * @var int
     */
    private $_maxId;

    /**
     * @var array
     */
    private $_childrenProductIds;

    /**
     * @var string
     */
    private $_linkField = 'entity_id';

    /**
     * @var array
     */
    private $_categoryIds = [];

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
     * @var array
     */
    private $_joinedAlias = [];

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_productResource = Mage::getResourceSingleton('emartech_emarsys/api_product');
        $this->_categoryResource = Mage::getResourceSingleton('emartech_emarsys/api_category');
    }

    /**
     * @param Emartech_Emarsys_Controller_Request_Http $request
     *
     * @return array
     * @throws Emartech_Emarsys_Exception_ValidationException
     * @throws Mage_Core_Exception
     */
    public function handleGet($request)
    {
        $page = (int)$request->getParam('page', 0);
        $pageSize = (int)$request->getParam('page_size', 1000);
        $storeIds = $request->getParam('store_id', []);

        $this->_initStores($storeIds);

        if (!array_key_exists(0, $this->_storeIds)) {
            throw new Emartech_Emarsys_Exception_ValidationException('Store ID must contain 0');
        }

        $this
            ->_initCollection()
            ->_handleIds($page, $pageSize)
            ->_handleCategoryIds()
            ->_handleChildrenProductIds()
            ->_handleStockData()
            ->_handleStockData()
            ->_joinData()
            ->_setWhere()
            ->_setOrder();
        $this->_productCollection->load();

        $lastPageNumber = ceil($this->_numberOfItems / $pageSize);

        return [
            'current_page'  => $page,
            'last_page'     => $lastPageNumber,
            'page_size'     => $pageSize,
            'total_count'   => $this->_numberOfItems,
            'products' => $this->_handleProducts(),
        ];
    }

    /**
     * @return array
     * @throws Mage_Core_Exception
     */
    private function _handleProducts()
    {
        $productArray = [];
        foreach ($this->_productCollection as $product) {
            $productArray[] = [
                'type'                => $product->getTypeId(),
                'categories'          => $this->_handleCategories($product),
                'children_entity_ids' => $this->_handleChildrenEntityIds($product),
                'entity_id'           => (int) $product->getId(),
                'is_in_stock'         => (int) $this->_handleStock($product),
                'qty'                 => (int) $this->_handleQty($product),
                'sku'                 => $product->getSku(),
                'images'              => $this->_handleImages($product),
                'store_data'          => $this->_handleProductStoreData($product),
            ];
        }

        return $productArray;
    }

    /**
     * @param mixed $storeIds
     *
     * @return $this
     */
    private function _initStores($storeIds)
    {
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
     */
    private function _initCollection()
    {
        $this->_productCollection = Mage::getModel('catalog/product')->getCollection();

        return $this;
    }

    /**
     * @param int $page
     * @param int $pageSize
     *
     * @return $this
     */
    private function _handleIds($page, $pageSize)
    {
        $page--;
        $page *= $pageSize;

        $data = $this->_productResource->handleIds($page, $pageSize, $this->_linkField);

        $this->_numberOfItems = $data['numberOfItems'];
        $this->_minId = $data['minId'];
        $this->_maxId = $data['maxId'];

        return $this;
    }

    /**
     * @return $this
     */
    private function _handleCategoryIds()
    {
        $this->_categoryIds = $this->_categoryResource->getCategoryIds($this->_minId, $this->_maxId);

        return $this;
    }

    /**
     * @return $this
     */
    private function _handleChildrenProductIds()
    {
        $this->_childrenProductIds = $this->_productResource->getChildrenProductIds($this->_minId, $this->_maxId);

        return $this;
    }

    /**
     * @return $this
     */
    private function _handleStockData()
    {
        $this->_stockData = $this->_productResource->getStockData($this->_minId, $this->_maxId);

        return $this;
    }

    /**
     * @return $this
     */
    private function _joinData()
    {
        $this->_productAttributeCollection = Mage::getResourceModel('eav/entity_attribute_collection');
        $this->_productAttributeCollection
            ->addFieldToFilter('attribute_code', [
                'in' => array_values(array_merge(
                    $this->_storeProductAttributeCodes,
                    $this->_globalProductAttributeCodes
                )),
            ]);

        $mainTableName = $this->_productCollection->getResource()->getEntityTable();

        /** @var Mage_Eav_Model_Attribute $productAttribute */
        foreach ($this->_productAttributeCollection as $productAttribute) {
            if ($productAttribute->getBackendTable() === $mainTableName) {
                $this->_productCollection->addAttributeToSelect($productAttribute->getAttributeCode());
            } elseif (in_array($productAttribute->getAttributeCode(), $this->_globalProductAttributeCodes)) {
                $valueAlias = $this->_getAttributeValueAlias($productAttribute->getAttributeCode());

                $this->_joinAttribute(
                    $valueAlias,
                    'catalog_product/' . $productAttribute->getAttributeCode(),
                    $this->_linkField,
                    null,
                    'left'
                );
            } else {
                foreach (array_keys($this->_storeIds) as $storeId) {
                    $valueAlias = $this->_getAttributeValueAlias($productAttribute->getAttributeCode(), $storeId);

                    $this->_joinAttribute(
                        $valueAlias,
                        'catalog_product/' . $productAttribute->getAttributeCode(),
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
     * @param string                                          $alias
     * @param string|Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     * @param string                                          $bind
     * @param string                                          $filter
     * @param string                                          $joinType
     * @param int|null                                        $storeId
     *
     * @return $this
     */
    private function _joinAttribute($alias, $attribute, $bind, $filter = null, $joinType = 'inner', $storeId = null)
    {
        if (in_array($alias, $this->_joinedAlias)) {
            return $this;
        }

        $this->_joinedAlias[$alias] = $alias;
        try {
            $this->_productCollection->joinAttribute($alias, $attribute, $bind, $filter, $joinType, $storeId);
        } catch (Exception $e) {
            Mage::logException($e);
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
    private function _setWhere()
    {
        $this->_productCollection
            ->addFieldToFilter($this->_linkField, ['from' => $this->_minId])
            ->addFieldToFilter($this->_linkField, ['to' => $this->_maxId]);

        return $this;
    }

    /**
     * @return $this
     */
    private function _setOrder()
    {
        $this->_productCollection
            ->setOrder($this->_linkField, Varien_Data_Collection_Db::SORT_ORDER_ASC);

        return $this;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    private function _handleImages($product)
    {
        $mediaUrl = Mage::app()->getDefaultStoreView()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $imagePreUrl = $mediaUrl . 'catalog/product';

        /** @noinspection PhpUndefinedMethodInspection */
        $image = $product->getImage();
        if ($image) {
            $image = $imagePreUrl . $image;
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $smallImage = $product->getSmallImage();
        if ($smallImage) {
            $smallImage = $imagePreUrl . $smallImage;
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $thumbnail = $product->getThumbnail();
        if ($thumbnail) {
            $thumbnail = $imagePreUrl . $thumbnail;
        }

        return [
            'image'       => $image,
            'small_image' => $smallImage,
            'thumbnail'   => $thumbnail,
        ];
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return array
     */
    private function _handleCategories($product)
    {
        if (array_key_exists($product->getId(), $this->_categoryIds)) {
            return $this->_categoryIds[$product->getId()];
        }

        return [];
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return array
     */
    private function _handleChildrenEntityIds($product)
    {
        if (array_key_exists($product->getId(), $this->_childrenProductIds)) {
            return $this->_childrenProductIds[$product->getId()];
        }

        return [];
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return int
     */
    private function _handleStock($product)
    {
        if (array_key_exists($product->getId(), $this->_stockData)) {
            return $this->_stockData[$product->getId()]['is_in_stock'];
        }

        return 0;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return int
     */
    private function _handleQty($product)
    {
        if (array_key_exists($product->getId(), $this->_stockData)) {
            return $this->_stockData[$product->getId()]['qty'];
        }

        return 0;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    private function _handleProductStoreData($product)
    {
        $returnArray = [];

        foreach ($this->_storeIds as $storeId => $storeObject) {
            $returnArray[] = [
                'store_id'      => $storeId,
                'status'        => (int) $product->getData($this->_getAttributeValueAlias('status', $storeId)),
                'description'   => $product->getData($this->_getAttributeValueAlias('description', $storeId)),
                'link'          => $this->_handleLink($product, $storeObject),
                'name'          => $product->getData($this->_getAttributeValueAlias('name', $storeId)),
                'price'         => (float) $this->_handlePrice($product, $storeObject),
                'display_price' => (float) $this->_handleDisplayPrice($product, $storeObject),
                'currency_code' => $this->_getCurrencyCode($storeObject),
            ];
        }

        return $returnArray;
    }


    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Core_Model_Store      $store
     *
     * @return string
     * @throws Mage_Core_Exception
     */
    private function _handleLink($product, $store)
    {
        $link = $product->getData($this->_getAttributeValueAlias('url_key', $store->getId()));

        if ($link) {
            return $store->getBaseUrl() . $link . $this->_getProductUrlSuffix($store->getId());
        }

        return '';
    }

    /**
     * @param Mage_Core_Model_Store $store
     *
     * @return string
     */
    private function _getCurrencyCode($store)
    {
        if ($store->getId() === '0') {
            return $store->getBaseCurrencyCode();
        }
        return $store->getCurrentCurrencyCode();
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Core_Model_Store      $store
     *
     * @return int | float
     */
    private function _handleDisplayPrice($product, $store)
    {
        $price = $product->getData($this->_getAttributeValueAlias('price', $store->getId()));
        if (empty($price)) {
            $price = $product->getData($this->_getAttributeValueAlias('price', 0));
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $product->setPrice($price);
        $price = $product->getFinalPrice();

        if ($this->_getCurrencyCode($store) !== $store->getBaseCurrencyCode()) {
            try {
                $tmp = $store->getBaseCurrency()->convert($price, $store->getCurrentCurrencyCode());
                $price = $tmp;
            } catch (\Exception $e) {
                Mage::logException($e);
            }
        }

        return $price;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Core_Model_Store      $store
     *
     * @return int | float
     */
    private function _handlePrice($product, $store)
    {
        $price = $product->getData($this->_getAttributeValueAlias('price', $store->getId()));
        $specialPrice = $product->getData($this->_getAttributeValueAlias('special_price', $store->getId()));

        if (!empty($specialPrice)) {
            $specialFromDate = $product->getData($this->_getAttributeValueAlias('special_from_date', $store->getId()));
            $specialToDate = $product->getData($this->_getAttributeValueAlias('special_to_date', $store->getId()));

            if ($specialFromDate) {
                $specialFromDate = strtotime($specialFromDate);
            } else {
                $specialFromDate = false;
            }

            if ($specialToDate) {
                $specialToDate = strtotime($specialToDate);
            } else {
                $specialToDate = false;
            }

            if (($specialFromDate === false || $specialFromDate <= time()) &&
                ($specialToDate === false || $specialToDate >= time())
            ) {
                $price = $specialPrice;
            }
        }

        return $price;
    }

    /**
     * @param int $storeId
     *
     * @return string
     */
    private function _getProductUrlSuffix($storeId)
    {
        return Mage::getStoreConfig(Mage_Catalog_Helper_Product::XML_PATH_PRODUCT_URL_SUFFIX, $storeId);
    }
}
