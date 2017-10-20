<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoBattery\controller;

use oat\generis\model\OntologyAwareTrait;
use oat\taoBattery\model\BatteryException;
use oat\taoBattery\model\service\BatteryService;

class DeliveryTree extends \tao_actions_GenerisTree
{
    use OntologyAwareTrait;

    /**
     * Callback for delivery tree to register deliveries to battery
     * Foreach deliveries received, it will be deleted from all batteries before set it to current
     * 
     * @throws \common_exception_IsAjaxAction
     */
    public function setValues()
    {
        if (!\tao_helpers_Request::isAjax()) {
            throw new \common_exception_IsAjaxAction(__FUNCTION__);
        }

        $values = \tao_helpers_form_GenerisTreeForm::getSelectedInstancesFromPost();

        $resource = $this->getResource($this->getRequestParameter('resourceUri'));
        $property = $this->getProperty($this->getRequestParameter('propertyUri'));

        try {
            foreach ($values as $delivery) {
                $this->getBatteryService()->deleteDeliveryFromBatteries($this->getResource($delivery));
            }
        } catch (BatteryException $e) {
            echo json_encode(array('saved' => false));
            return;
        }

        $success = $resource->editPropertyValues($property, $values);
        echo json_encode(array('saved' => $success));
    }

    /**
     * Return the battery service
     *
     * @return BatteryService
     */
    protected function getBatteryService()
    {
        return $this->getServiceManager()->get(BatteryService::SERVICE_ID);
    }
}