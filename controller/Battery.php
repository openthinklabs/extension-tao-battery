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
use oat\tao\helpers\Template;
use oat\taoBattery\model\BatteryService;

class Battery extends \tao_actions_RdfController
{
    use OntologyAwareTrait;

    /**
     * Battery constructor.
     * Instantiate the battery service
     */
    public function __construct()
    {
        $this->service = BatteryService::singleton();
    }

    /**
     * Edit a battery class
     *
     * @throws \Exception
     * @throws \common_exception_Error
     */
    public function editBatteryClass()
    {
        $clazz = $this->getClass($this->getRequestParameter('id'));

        if($this->hasRequestParameter('property_mode')){
            $this->setSessionAttribute('property_mode', $this->getRequestParameter('property_mode'));
        }

        $myForm = $this->getClassForm($clazz, $this->getClassService()->getRootClass());

        if ($this->hasWriteAccess($clazz->getUri())) {
            if($myForm->isSubmited()){
                if($myForm->isValid()){
                    if($clazz instanceof \core_kernel_classes_Resource){
                        $this->setData("selectNode", \tao_helpers_Uri::encode($clazz->getUri()));
                    }
                    $this->setData('message', __('Class saved'));
                    $this->setData('reload', true);
                }
            }
        } else {
            $myForm->setActions(array());
        }
        $this->setData('formTitle', __('Edit battery class'));
        $this->setData('myForm', $myForm->render());
        $this->setView('form.tpl', 'tao');
    }

    /**
     * Create a new instance of battery class with unique label
     *
     * @throws \Exception
     */
    public function create()
    {
        if(! \tao_helpers_Request::isAjax()){
            throw new \Exception(__("Wrong request mode"));
        }

        $resource = $this->getResource($this->getRequestParameter('id'));
        if ($resource->isClass()) {
            $clazz = $this->getClass($resource->getUri());
        } else {
            $clazz = reset($resource->getTypes());
        }

        $label = $this->getClassService()->createUniqueLabel($clazz);
        $item = $this->getClassService()->createInstance($clazz, $label);

        if(! is_null($item)){
            $response = array(
                'label'	=> $item->getLabel(),
                'uri' 	=> $item->getUri()
            );
        } else {
            $response = false;
        }

        $this->returnJson($response);
    }

    /**
     * Edit a battery instance
     *
     * @throws \Exception
     * @throws \tao_models_classes_MissingRequestParameterException
     * @throws \tao_models_classes_dataBinding_GenerisFormDataBindingException
     */
    public function editInstance()
    {
        $clazz = $this->getCurrentClass();
        $battery = $this->getCurrentInstance();
        $myFormContainer = new \tao_actions_form_Instance($clazz, $battery);

        $myForm = $myFormContainer->getForm();
        if($myForm->isSubmited()){
            if($myForm->isValid()){

                $values = $myForm->getValues();
                // save properties
                $binder = new \tao_models_classes_dataBinding_GenerisFormDataBinder($battery);
                $battery = $binder->bind($values);
                $message = __('Battery saved');

                $this->setData('message', $message);
                $this->setData('reload', true);
            }
        }

        // Display the tree of deliveries
        $property = $this->getProperty(BatteryService::BATTERY_DELIVERIES);
        $tree = \tao_helpers_form_GenerisTreeForm::buildTree($battery, $property);
        $tree->setTitle(__('Deliveries'));
        $tree->setTemplate(Template::getTemplate('widgets/displayTree.tpl'));
        $this->setData('deliveriesTree', $tree->render());

        // Display the tree of groups
        $property = $this->getProperty(BatteryService::BATTERY_GROUPS);
        $tree = \tao_helpers_form_GenerisTreeForm::buildTree($battery, $property);
        $tree->setTitle(__('Assign to'));
        $tree->setTemplate(Template::getTemplate('widgets/displayTree.tpl'));
        $this->setData('groupsTree', $tree->render());

        $this->setData('formTitle', __('Edit Battery'));
        $this->setData('form', $myForm->render());
        $this->setData('uri', $battery->getUri());
        $this->setView('editBattery.tpl', 'taoBattery');
    }

    /**
     * Get the root class of battery
     *
     * @return \core_kernel_classes_Class
     * @throws \common_exception_Error
     */
    protected function getRootClass()
    {
        return $this->getClassService()->getRootClass();
    }
}