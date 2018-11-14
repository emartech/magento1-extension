<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Interface Emartech_Emarsys_Controller_PutControllerInterface
 */
interface Emartech_Emarsys_Controller_PutControllerInterface
{
    /**
     * @return Emartech_Emarsys_Model_Abstract_PutInterface
     */
    public function getModel();

    /**
     * Handle put request
     *
     * @return array
     */
    public function handlePut();
}
