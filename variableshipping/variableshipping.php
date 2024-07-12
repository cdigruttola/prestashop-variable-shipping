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
    public $id_carrier;

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
                'delay' => ['fr' => 'Custom', 'en' => 'Custom', Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')) => 'Custom'],
                'id_zone' => 1,
                'is_module' => true,
                'shipping_external' => true,
                'external_module_name' => 'variableshipping',
                'need_range' => true,
            ],
        ];

        $id_carrier1 = $this->installExternalCarrier($carrierConfig[0]);
        Configuration::updateValue('VARIABLE_SHIPPING_CARRIER_ID', (int) $id_carrier1);
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
        $Carrier1 = new Carrier((int) Configuration::get('VARIABLE_SHIPPING_CARRIER_ID'));

        // If external carrier is default set other one as default
        if (Configuration::get('PS_CARRIER_DEFAULT') == (int) $Carrier1->id) {
            global $cookie;
            $carriersD = Carrier::getCarriers($cookie->id_lang, true, false, false, null, PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);
            foreach ($carriersD as $carrierD) {
                if ($carrierD['active'] and !$carrierD['deleted'] and ($carrierD['name'] != $this->_config['name'])) {
                    Configuration::updateValue('PS_CARRIER_DEFAULT', $carrierD['id_carrier']);
                }
            }
        }

        // Then delete Carrier
        $Carrier1->deleted = 1;
        if (!$Carrier1->update()) {
            return false;
        }

        return true;
    }

    public static function installExternalCarrier($config)
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

        $languages = Language::getLanguages(true);
        foreach ($languages as $language) {
            if ($language['iso_code'] == 'fr') {
                $carrier->delay[(int) $language['id_lang']] = $config['delay'][$language['iso_code']];
            }
            if ($language['iso_code'] == 'en') {
                $carrier->delay[(int) $language['id_lang']] = $config['delay'][$language['iso_code']];
            }
            if ($language['iso_code'] == Language::getIsoById(Configuration::get('PS_LANG_DEFAULT'))) {
                $carrier->delay[(int) $language['id_lang']] = $config['delay'][$language['iso_code']];
            }
        }

        if ($carrier->add()) {
            $groups = Group::getGroups(true);
            foreach ($groups as $group) {
                Db::getInstance()->autoExecute(_DB_PREFIX_ . 'carrier_group', ['id_carrier' => (int) $carrier->id, 'id_group' => (int) $group['id_group']], 'INSERT');
            }

            $rangePrice = new RangePrice();
            $rangePrice->id_carrier = $carrier->id;
            $rangePrice->delimiter1 = '0';
            $rangePrice->delimiter2 = '10000';
            $rangePrice->add();

            $rangeWeight = new RangeWeight();
            $rangeWeight->id_carrier = $carrier->id;
            $rangeWeight->delimiter1 = '0';
            $rangeWeight->delimiter2 = '10000';
            $rangeWeight->add();

            $zones = Zone::getZones(true);
            foreach ($zones as $zone) {
                Db::getInstance()->autoExecute(_DB_PREFIX_ . 'carrier_zone', ['id_carrier' => (int) $carrier->id, 'id_zone' => (int) $zone['id_zone']], 'INSERT');
                Db::getInstance()->autoExecuteWithNullValues(_DB_PREFIX_ . 'delivery', ['id_carrier' => (int) $carrier->id, 'id_range_price' => (int) $rangePrice->id, 'id_range_weight' => null, 'id_zone' => (int) $zone['id_zone'], 'price' => '0'], 'INSERT');
                Db::getInstance()->autoExecuteWithNullValues(_DB_PREFIX_ . 'delivery', ['id_carrier' => (int) $carrier->id, 'id_range_price' => null, 'id_range_weight' => (int) $rangeWeight->id, 'id_zone' => (int) $zone['id_zone'], 'price' => '0'], 'INSERT');
            }

            // Copy Logo
            if (!copy(dirname(__FILE__) . '/carrier.jpg', _PS_SHIP_IMG_DIR_ . '/' . (int) $carrier->id . '.jpg')) {
                return false;
            }

            // Return ID Carrier
            return (int) $carrier->id;
        }

        return false;
    }

    public function hookDisplayBackOfficeHeader()
    {
        $out = '<script type="text/javascript" src="' . $this->_path . 'script.js"></script>';
        $out .= '<script>var variableshipping_carrier_id = ' . Configuration::get('VARIABLE_SHIPPING_CARRIER_ID') . ';</script>';
        $out .= '<script>var variableshipping_token = "' . sha1(_COOKIE_KEY_ . 'variableshipping') . '";</script>';
        $out .= '<script>var variableshipping_ajax_url = "' . $this->_path . 'ajax.php";</script>';

        return $out;
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
