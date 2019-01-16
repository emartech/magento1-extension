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
    private $_stockData = [];

    /**
     * @var array
     */
    private $_attributeData = [];

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
            ->_handleAttributes()
            ->_setWhere()
            ->_setOrder();
        $this->_productCollection->load();

        $lastPageNumber = ceil($this->_numberOfItems / $pageSize);

        return [
            'current_page' => (int)$page,
            'last_page'    => (int)$lastPageNumber,
            'page_size'    => (int)$pageSize,
            'total_count'  => (int)$this->_numberOfItems,
            'products'     => $this->_handleProducts(),
        ];
    }

    /**
     * @return array
     * @throws Mage_Core_Exception
     */
    private function _handleProducts()
    {
        $productArray = [];
        /** @var Mage_Catalog_Model_Product $product */
        foreach ($this->_productCollection as $product) {
            $productId = (int)$product->getId();

            $productArray[] = [
                'type'                => $product->getTypeId(),
                'categories'          => $this->_handleCategories($productId),
                'children_entity_ids' => $this->_handleChildrenEntityIds($productId),
                'entity_id'           => $productId,
                'is_in_stock'         => (int)$this->_handleStock($productId),
                'qty'                 => (int)$this->_handleQty($productId),
                'sku'                 => $product->getSku(),
                'images'              => $this->_handleImages($productId),
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
    private function _handleAttributes()
    {
        $this->_attributeData = $this->_productResource->getAttributeData($this->_minId, $this->_maxId, array_keys($this->_storeIds));

        return $this;
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
     * @param int $productId
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    private function _handleImages($productId)
    {
        $mediaUrl = Mage::app()->getDefaultStoreView()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $imagePreUrl = $mediaUrl . 'catalog/product';

        $image = $this->_getStoreData($productId, 0, 'image');
        if ($image) {
            $image = $imagePreUrl . $image;
        }
        $smallImage = $this->_getStoreData($productId, 0, 'small_image');
        if ($smallImage) {
            $smallImage = $imagePreUrl . $smallImage;
        }
        $thumbnail = $this->_getStoreData($productId, 0, 'thumbnail');
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
     * @param int $productId
     *
     * @return array
     */
    private function _handleCategories($productId)
    {
        if (array_key_exists($productId, $this->_categoryIds)) {
            return $this->_categoryIds[$productId];
        }

        return [];
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    private function _handleChildrenEntityIds($productId)
    {
        if (array_key_exists($productId, $this->_childrenProductIds)) {
            return $this->_childrenProductIds[$productId];
        }

        return [];
    }

    /**
     * @param int $productId
     *
     * @return int
     */
    private function _handleStock($productId)
    {
        if (array_key_exists($productId, $this->_stockData)) {
            return $this->_stockData[$productId]['is_in_stock'];
        }

        return 0;
    }

    /**
     * @param int $productId
     *
     * @return int
     */
    private function _handleQty($productId)
    {
        if (array_key_exists($productId, $this->_stockData)) {
            return $this->_stockData[$productId]['qty'];
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
            $productId = (int)$product->getId();

            $returnArray[] = [
                'store_id'      => $storeId,
                'status'        => $this->_getStoreData($productId, $storeId, 'status'),
                'description'   => $this->_getStoreData($productId, $storeId, 'description'),
                'link'          => $this->_handleLink($productId, $storeObject),
                'name'          => $this->_getStoreData($productId, $storeId, 'name'),
                'price'         => (float)$this->_handlePrice($productId, $storeId),
                'display_price' => (float)$this->_handleDisplayPrice($product, $storeObject),
                'currency_code' => $this->_getCurrencyCode($storeObject),
            ];
        }

        return $returnArray;
    }

    /**
     * @param int    $productId
     * @param int    $storeId
     * @param string $attributeCode
     *
     * @return string|null
     */
    private function _getStoreData($productId, $storeId, $attributeCode)
    {
        if (
            array_key_exists($productId, $this->_attributeData)
            && array_key_exists($storeId, $this->_attributeData[$productId])
            && array_key_exists($attributeCode, $this->_attributeData[$productId][$storeId])
        ) {
            return $this->_attributeData[$productId][$storeId][$attributeCode];
        }

        return null;
    }


    /**
     * @param int                   $productId
     * @param Mage_Core_Model_Store $store
     *
     * @return string
     * @throws Mage_Core_Exception
     */
    private function _handleLink($productId, $store)
    {
        $link = $this->_getStoreData($productId, $store->getId(), 'url_key');

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
        if (0 === (int)$store->getId()) {
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
        $price = $this->_getStoreData($product->getId(), $store->getId(), 'price');
        if (!$price) {
            $price = $this->_getStoreData($product->getId(), 0, 'price');
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
     * @param int $productId
     * @param int $storeId
     *
     * @return int | float
     */
    private function _handlePrice($productId, $storeId)
    {
        $price = $this->_getStoreData($productId, $storeId, 'price');
        $specialPrice = $this->_getStoreData($productId, $storeId, 'special_price');

        if ($specialPrice) {
            $specialFromDate = $this->_getStoreData($productId, $storeId, 'special_from_date');
            $specialToDate = $this->_getStoreData($productId, $storeId, 'special_to_date');

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
