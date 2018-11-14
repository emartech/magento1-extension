<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Interface Emartech_Emarsys_Controller_PostControllerInterface
 */
interface Emartech_Emarsys_Controller_PostControllerInterface
{
    /**
     * @return Emartech_Emarsys_Model_Abstract_PostInterface
     */
    public function getModel();

    /**
     * Handle post request
     *
     * @return array
     */
    public function handlePost();
}
