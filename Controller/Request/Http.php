<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Controller_Request_Http
 */
class Emartech_Emarsys_Controller_Request_Http
{
    /**
     * @var Mage_Core_Controller_Request_Http
     */
    private $_request;

    /**
     * @var array
     */
    private $_params;

    /**
     * Emartech_Emarsys_Controller_Request_Http constructor.
     */
    public function __construct()
    {
        $this->_request = Mage::app()->getRequest();
    }

    /**
     * @return array
     */
    public function getParams()
    {
        if (null === $this->_params) {
            $params = (array)$this->_request->getParams();
            $this->_parseJsonInput($params);
            $this->_normalizeKeys($params);
            $this->_params = $params;
        }

        return $this->_params;
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        $this->getParams();
        $keyName = $this->_decamelize($key);
        return isset($this->_params[$keyName]) ? $this->_params[$keyName] : $default;
    }

    /**
     * @param array $params
     *
     * @return void
     */
    private function _parseJsonInput(&$params)
    {
        if ($this->_request->isPost() || $this->_request->isPut()) {
            $inputJSON = file_get_contents('php://input');
            if ($inputJSON) {
                $input = json_decode($inputJSON, true);
                if (is_array($input)) {
                    $params = array_merge($params, $input);
                }
            }
        }
    }

    /**
     * @param array $array
     *
     * @return void
     */
    private function _normalizeKeys(&$array)
    {
        $this->_normalizeKeysRecursive($array);
    }

    /**
     * @param array $array
     *
     * @return void
     */
    private function _normalizeKeysRecursive(&$array)
    {
        $newArray = [];
        foreach ($array as $key => $value) {
            $newKey = $key;
            if ($key !== $this->_decamelize($key)) {
                $newKey = $this->_decamelize($key);
            }

            if (is_array($value)) {
                $this->_normalizeKeysRecursive($value);
            }

            $newArray[$newKey] = $value;
        }

        $array = $newArray;
    }

    /**
     * @param $string
     *
     * @return string
     */
    private function _decamelize($string)
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
    }
}
