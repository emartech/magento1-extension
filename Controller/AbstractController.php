<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_AbstractController
 */
abstract class Emartech_Emarsys_Controller_AbstractController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var Emartech_Emarsys_Helper_Connector|null
     */
    private $_connectorHelper = null;

    /**
     * @var Emartech_Emarsys_Controller_Request_Http|null
     */
    protected $_apiRequest;

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
     * @return void
     * @throws Zend_Controller_Request_Exception
     */
    public function _construct()
    {
        $this->_authenticate(Mage::app()->getRequest()->getHeader('Authorization'));
        $this->_apiRequest = new Emartech_Emarsys_Controller_Request_Http();
    }

    /**
     * @return void
     */
    public function indexAction()
    {
        try {
            switch ($this->getRequest()->getMethod()) {
                case Zend_Http_Client::GET:
                default:
                    if ($this instanceof Emartech_Emarsys_Controller_GetControllerInterface) {
                        $this->_sendData($this->handleGet());
                        return;
                    }
                    break;

                case Zend_Http_Client::POST:
                    if ($this instanceof Emartech_Emarsys_Controller_PostControllerInterface) {
                        $this->_sendData($this->handlePost());
                        return;
                    }
                    break;

                case Zend_Http_Client::PUT:
                    if ($this instanceof Emartech_Emarsys_Controller_PutControllerInterface) {
                        $this->_sendData($this->handlePut());
                        return;
                    }
                    break;

                case Zend_Http_Client::DELETE:
                    if ($this instanceof Emartech_Emarsys_Controller_DeleteControllerInterface) {
                        $this->_sendData($this->handleDelete());
                        return;
                    }
                    break;
            }

            $this->_badRequestException();

        } catch (Mage_Core_Exception $e) {
            $this->_sendData(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (Exception $e) {
            $this->_internalError($e);
        }
    }

    /**
     * @return Emartech_Emarsys_Controller_Request_Http|null
     */
    public function getApiRequest()
    {
        return $this->_apiRequest;
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
     * @return void
     * @throws Emartech_Emarsys_Exception_BadRequestException
     */
    private function _badRequestException()
    {
        throw new Emartech_Emarsys_Exception_BadRequestException(
            'Bad request, ' . $this->getRequest()->getMethod() . ' method not available!'
        );
    }

    /**
     * Internal error
     *
     * @param Exception $exception
     *
     * @return void
     */
    private function _internalError($exception)
    {
        Mage::logException($exception);
        $this->_sendData(['status' => 'error', 'message' => $exception->getMessage()]);
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
