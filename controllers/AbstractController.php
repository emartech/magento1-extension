<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_AbstractController
 */
abstract class Emartech_Emarsys_AbstractController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var Emartech_Emarsys_Helper_Connector|null
     */
    private $_connectorHelper = null;

    /**
     * @return Emartech_Emarsys_Helper_Connector
     */
    private function _getConnectorHelper()
    {
        if ($this->_connectorHelper === null) {
            $this->_connectorHelper = Mage::helper('emartech_emarsys/connector');
        }

        return $this->_connectorHelper;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    abstract public function handleRequest($params);

    /**
     * @return void
     */
    public function _construct()
    {
        $this->_authenticate(Mage::app()->getRequest()->getHeader('Authorization'));
    }

    /**
     * @return void
     */
    public function indexAction()
    {
        $params = $this->getRequest()->getParams();
        if (!$params) {
            $params = [];
        }
        $this->_sendData($this->handleRequest($params));
    }

    /**
     * @param string $authorizationHeader
     *
     * @return void
     */
    private function _authenticate($authorizationHeader)
    {
        if (!$this->_getConnectorHelper()->validAuthorization($authorizationHeader)) {
            $this->getResponse()
                ->setHeader('Content-type', 'application/json; charset=UTF-8')
                ->setHeader('HTTP/1.1', '401 Unauthorized')
                ->sendHeadersAndExit();
        }
    }

    /**
     * @param array $data
     *
     * @return void
     */
    private function _sendData($data)
    {
        if (!is_string($data)) {
            try {
                $data = json_encode($data);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        $this->getResponse()
            ->setHeader('Content-type', 'application/json; charset=UTF-8')
            ->setBody($data)
            ->sendResponse();
        exit;
    }
}