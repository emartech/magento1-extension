<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Observer_Marketing
 */
class Emartech_Emarsys_Model_Observer_Marketing
{
    const EVENT_CUSTOMER_NEW_ACCOUNT   = 'customer_new_account_registered';
    const EVENT_NEW_ORDER              = 'sales_email_order_template';
    const EVENT_NEW_INVOICE            = 'sales_email_invoice_template';
    const EVENT_NEW_SHIPMENT           = 'sales_email_shipment_template';
    const EVENT_NEW_CREDITMEMO         = 'sales_email_creditmemo_template';
    const EVENT_NEWSLETTER_SUBSCRIBE   = 'newsletter_send_confirmation_request_email';
    const EVENT_NEWSLETTER_UNSUBSCRIBE = 'newsletter_send_unsubscription_email';


    /**
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function customerRegisterSuccess(Varien_Event_Observer $observer)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $observer->getCustomer();

        Mage::helper('emartech_emarsys/event_marketing')->storeCustomer(
            $customer,
            $customer->getWebsiteId(),
            $customer->getStoreId(),
            self::EVENT_CUSTOMER_NEW_ACCOUNT
        );
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function salesOrderPlaceAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();

        Mage::helper('emartech_emarsys/event_marketing')->storeOrder(
            $order,
            self::EVENT_NEW_ORDER
        );
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function salesOrderInvoiceSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Invoice $invoice */
        $invoice = $observer->getInvoice();

        Mage::helper('emartech_emarsys/event_marketing')->storeInvoice(
            $invoice,
            self::EVENT_NEW_INVOICE
        );
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function salesOrderShipmentSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        $shipment = $observer->getShipment();

        Mage::helper('emartech_emarsys/event_marketing')->storeShipment(
            $shipment,
            self::EVENT_NEW_SHIPMENT
        );
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function salesOrderCreditmemoSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
        $creditmemo = $observer->getCreditmemo();

        Mage::helper('emartech_emarsys/event_marketing')->storeCreditmemo(
            $creditmemo,
            self::EVENT_NEW_CREDITMEMO
        );
    }

    public function newsletterSubscriberSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Newsletter_Model_Subscriber $subscriber */
        $subscriber = $observer->getSubscriber();

        Mage::helper('emartech_emarsys/event_marketing')->storeSubscription(
            $subscriber,
            self::EVENT_NEWSLETTER_SUBSCRIBE
        );
    }

    public function newsletterSubscriberDeleteAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Newsletter_Model_Subscriber $subscriber */
        $subscriber = $observer->getSubscriber();

        Mage::helper('emartech_emarsys/event_marketing')->storeSubscription(
            $subscriber,
            self::EVENT_NEWSLETTER_UNSUBSCRIBE
        );
    }
}
