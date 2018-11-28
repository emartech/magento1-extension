<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Block_Snipets
 */
class Emartech_Emarsys_Block_Snipets extends Mage_Core_Block_Template
{
    /**
     * @var Emartech_Emarsys_Helper_Config
     */
    private $_configHelper = null;

    public function getConfigHelper()
    {
        if ($this->_configHelper === null) {
            $this->_configHelper = Mage::helper('emartech_emarsys/config');
        }

        return $this->_configHelper;
    }

    /**
     * @return bool
     */
    public function isInjectable()
    {
        return $this->getConfigHelper()->isEnabledForStore(Emartech_Emarsys_Helper_Config::INJECT_WEBEXTEND_SNIPPETS);
    }

    /**
     * Get Merchant ID
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->getConfigHelper()->getWebsiteConfigValue(Emartech_Emarsys_Helper_Config::MERCHANT_ID);
    }

    /**
     * Get Snippet Url
     *
     * @return string
     */
    public function getSnippetUrl()
    {
        return $this->getConfigHelper()->getWebsiteConfigValue(Emartech_Emarsys_Helper_Config::SNIPPET_URL);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getTrackingData()
    {
        $returnArray = [];

        if ($productData = $this->_getCurrentProduct()) {
            $returnArray['product'] = $productData;
        }

        if ($categoryData = $this->_getCategory()) {
            $returnArray['category'] = $categoryData;
        }

        if ($storeData = $this->_getStoreData()) {
            $returnArray['store'] = $storeData;
        }

        if ($searchData = $this->_getSearchData()) {
            $returnArray['search'] = $searchData;
        }

        if ($exchangeRate = $this->_getExchangeRate()) {
            $returnArray['exchangeRate'] = $exchangeRate;
        }

        if ($slugData = $this->_getStoreSlug()) {
            $returnArray['slug'] = $slugData;
        }

        if (($cartData = $this->_getCartData()) && $cartData['items']) {
            $returnArray['cart'] = $cartData;
        }

        if ($customerData = $this->_getCustomerData()) {
            $returnArray['customer'] = $customerData;
        }

        if (Mage::getSingleton('customer/session')->hasData(Emartech_Emarsys_Helper_Config::LAST_ORDER_ID_REGISTER_KEY)) {
            $lastOderId = Mage::getSingleton('customer/session')->getData(Emartech_Emarsys_Helper_Config::LAST_ORDER_ID_REGISTER_KEY);
            $returnArray['order'] = $this->_getOrderData($lastOderId);
            Mage::getSingleton('customer/session')->unsetData(Emartech_Emarsys_Helper_Config::LAST_ORDER_ID_REGISTER_KEY);
        }

        return $returnArray;
    }

    /**
     * Get Current Product
     *
     * @return bool|array
     */
    private function _getCurrentProduct()
    {
        $product = Mage::registry('current_product');
        if ($product instanceof Mage_Catalog_Model_Product) {
            return [
                'sku' => $product->getSku(),
                'id'  => $product->getId(),
            ];
        }

        return false;
    }

    /**
     * @return array|bool
     * @throws Exception
     */
    private function _getCategory()
    {
        try {
            $category = Mage::registry('current_category');
            if ($category instanceof Mage_Catalog_Model_Category) {
                $categoryList = [];

                $categoryIds = $this->_removeDefaultCategories($category->getPathIds());

                $linkField = $category->getResource()->getIdFieldName();

                /** @var Mage_Catalog_Model_Resource_Category_Collection $categoryCollection */
                $categoryCollection = Mage::getModel('catalog/category')->getCollection()
                    ->setStore(Mage::app()->getStore(0))
                    ->addAttributeToSelect('name')
                    ->addFieldToFilter($linkField, ['in' => $categoryIds]);

                /** @var Mage_Catalog_Model_Category $category */
                foreach ($categoryCollection as $categoryItem) {
                    $categoryList[] = $categoryItem->getName();
                }

                return [
                    'names' => $categoryList,
                    'ids'   => $categoryIds,
                ];
            }
        } catch (Exception $e) {
            throw $e;
        }
        return false;
    }

    /**
     * @param $categoryIds
     *
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    private function _removeDefaultCategories($categoryIds)
    {
        $returnArray = [];
        $basicCategoryIds = [
            1,
            Mage::app()->getStore()->getRootCategoryId(),
        ];
        foreach ($categoryIds as $categoryId) {
            if (!in_array($categoryId, $basicCategoryIds)) {
                $returnArray[] = $categoryId;
            }
        }

        return $returnArray;
    }

    /**
     * Get Store Data
     *
     * @return mixed
     */
    private function _getStoreData()
    {
        return [
            'merchantId' => $this->getMerchantId(),
        ];
    }

    /**
     * Get Search Data
     *
     * @return bool|array
     */
    private function _getSearchData()
    {
        $q = Mage::app()->getRequest()->getParam('q', '');
        if ($q != '') {
            return [
                'term' => $q,
            ];
        }
        return false;
    }

    /**
     * @return bool|float
     * @throws Mage_Core_Model_Store_Exception
     */
    private function _getExchangeRate()
    {
        $currentCurrency = Mage::app()->getStore()->getCurrentCurrency()->getCode();
        $baseCurrency = Mage::app()->getStore()->getBaseCurrency()->getCode();
        /* @var $currency Mage_Directory_Model_Currency */
        $currency = Mage::getModel('directory/currency');
        $rates = $currency->getCurrencyRates($baseCurrency, [$currentCurrency]);
        if (array_key_exists($currentCurrency, $rates)) {
            return (float)$rates[$currentCurrency];
        }
        return false;
    }

    /**
     * @return string|null
     * @throws Mage_Core_Model_Store_Exception
     */
    private function _getStoreSlug()
    {

        $storeSettings = json_decode(
            $this->getConfigHelper()->getStoreConfigValue(Emartech_Emarsys_Helper_Config::STORE_SETTINGS),
            true
        );
        $currentStoreId = Mage::app()->getStore()->getId();
        foreach ($storeSettings as $store) {
            if ($store['store_id'] === (int)$currentStoreId) {
                return $store['slug'];
            }
        }
        return null;
    }

    /**
     * @return array
     */
    private function _getCartData()
    {
        $returnArray = [
            'items' => [],
        ];

        /** @var Mage_Sales_Model_Quote $cart */
        $cart = Mage::getModel('checkout/cart')->getQuote();

        /** @var Mage_Sales_Model_Quote_Item $cartItem */
        foreach ($cart->getAllVisibleItems() as $cartItem) {
            $returnArray['items'][] = [
                'product_sku'         => $cartItem->getSku(),
                'product_price_value' => $cartItem->getCalculationPrice(),
                'qty'                 => $cartItem->getQty(),
                'item_id'             => $cartItem->getId(),
                'product_id'          => $cartItem->getProductId(),
                'product_name'        => $cartItem->getProduct()->getName(),
                'product_url'         => $cartItem->getProduct()->getProductUrl(),
            ];
        }

        return $returnArray;
    }

    /**
     * @return array
     */
    private function _getCustomerData()
    {
        $returnArray = [];

        /** Mage_Customer_Model_Session */
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $returnArray = [
                'email'     => $customer->getEmail(),
                'firstname' => $customer->getFirstname(),
                'lastname'  => $customer->getLastname(),
                'id'        => $customer->getId(),
            ];
        }

        return $returnArray;
    }

    /**
     * @param int $orderId
     *
     * @return array
     */
    private function _getOrderData($orderId)
    {
        $returnArray = [];

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);

        if ($order instanceof Mage_Sales_Model_Order) {
            $returnArray = [
                'orderId' => $order->getId(),
                'items'   => $this->_getOrderItems($order),
            ];
        }

        return $returnArray;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    private function _getOrderItems($order)
    {
        $returnArray = [];

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllItems() as $item) {
            if (!$item->getParentItemId()) {
                $returnArray[] = [
                    'item'     => $item->getSku(),
                    'price'    => (float)($item->getBasePrice() - $item->getBaseDiscountAmount()),
                    'quantity' => (float)$item->getQtyOrdered(),
                ];
            }
        }

        return $returnArray;
    }
}
