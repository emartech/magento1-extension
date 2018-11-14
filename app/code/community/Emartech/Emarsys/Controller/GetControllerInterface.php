<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Interface Emartech_Emarsys_Controller_GetControllerInterface
 */
interface Emartech_Emarsys_Controller_GetControllerInterface
{
    /**
     * @return Emartech_Emarsys_Model_Abstract_GetInterface
     */
    public function getModel();

    /**
     * Handle get request
     *
     * @return array
     */
    public function handleGet();
}
