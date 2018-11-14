<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Interface Emartech_Emarsys_Controller_DeleteControllerInterface
 */
interface Emartech_Emarsys_Controller_DeleteControllerInterface
{
    /**
     * @return Emartech_Emarsys_Model_Abstract_DeleteInterface
     */
    public function getModel();

    /**
     * Handle delete request
     *
     * @return array
     */
    public function handleDelete();
}
