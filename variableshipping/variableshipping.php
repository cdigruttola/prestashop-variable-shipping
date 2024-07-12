<?php
/**
 * Copyright since 2024 Carmine Di Gruttola
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    cdigruttola <c.digruttola@hotmail.it>
 * @copyright Copyright since 2007 Carmine Di Gruttola
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Variableshipping extends CarrierModule
{

    public function __construct()
    {
        $this->name = 'variableshipping';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Gennady Kovshenin';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Variable Shipping', [], 'Modules.Variableshipping.Admin');
        $this->description = $this->trans('Allows a variable shipping price to be set in the backend (for manual orders)', [], 'Modules.Variableshipping.Admin');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        $carrierConfig = [
            0 => ['name' => 'Variable Shipping',
                'id_tax_rules_group' => 0,
                'active' => true,
                'deleted' => 0,
                'shipping_handling' => false,
                'range_behavior' => 0,
                'delay' => [
                    'it' => $this->trans('Custom', [], 'Modules.Variableshipping.Admin'),
                ],
                'id_zone' => 1,
                'is_module' => true,
                'shipping_external' => true,
                'external_module_name' => 'variableshipping',
                'need_range' => false,
            ],
        ];

        $id_carrier = $this->installExternalCarrier($carrierConfig[0]);
        Configuration::updateValue('VARIABLE_SHIPPING_CARRIER_ID', (int) $id_carrier);
        if (!parent::install() || !$this->registerHook('displayBackOfficeHeader')) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        // Uninstall
        if (!parent::uninstall() || !$this->unregisterHook('displayBackOfficeHeader')) {
            return false;
        }

        // Delete External Carrier
        $carrier = new Carrier((int) Configuration::get('VARIABLE_SHIPPING_CARRIER_ID'));

        // Then delete Carrier
        $carrier->deleted = true;
        if (!$carrier->update()) {
            return false;
        }

        return true;
    }

    public function installExternalCarrier($config)
    {
        $carrier = new Carrier();
        $carrier->name = $config['name'];
        $carrier->id_tax_rules_group = $config['id_tax_rules_group'];
        $carrier->id_zone = $config['id_zone'];
        $carrier->active = $config['active'];
        $carrier->deleted = $config['deleted'];
        $carrier->delay = $config['delay'];
        $carrier->shipping_handling = $config['shipping_handling'];
        $carrier->range_behavior = $config['range_behavior'];
        $carrier->is_module = $config['is_module'];
        $carrier->shipping_external = $config['shipping_external'];
        $carrier->external_module_name = $config['external_module_name'];
        $carrier->need_range = $config['need_range'];

        $languages = Language::getLanguages();
        foreach ($languages as $language) {
            $carrier->delay[(int) $language['id_lang']] = $config['delay'][$language['iso_code']];
        }

        if ($carrier->add()) {
            $groups = Group::getGroups(true);
            $carrier->setGroups($groups);

            $zones = Zone::getZones(true);
            foreach ($zones as $zone) {
                $carrier->addZone($zone);
            }

            return (int) $carrier->id;
        }

        return false;
    }

    public function hookDisplayBackOfficeHeader()
    {
        if ($this->active) {
            $this->context->controller->addJS($this->_path . 'views/js/script.js');

            Media::addJsDef(
                [
                    'variableshipping_carrier_id' => Configuration::get('VARIABLE_SHIPPING_CARRIER_ID'),
                    'variableshipping_token' => sha1(_COOKIE_KEY_ . 'variableshipping'),
                    'variableshipping_ajax_url' =>  $this->_path . 'ajax.php',
                ]
            );
        }
    }

    public function getOrderShippingCost($params, $shipping_cost)
    {
        return $this->getOrderShippingCostExternal($params);
    }

    public function getOrderShippingCostExternal($params)
    {
        $context = Context::getContext();
        if (!$context->employee || !$context->employee->id) {
            return false;
        }

        $value = 10;

        return $value ? round(floatval($value), 2) : 0.00;
    }
}
