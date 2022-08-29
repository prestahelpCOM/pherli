<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__.'/classes/ErliApi.php';
require_once __DIR__.'/classes/Erli.php';
require_once __DIR__.'/classes/ErliOrder.php';
require_once __DIR__.'/classes/ErliChangeProducts.php';

class pherli extends Module
{

    private $configuration;

    private $class_name_tab = 'AdminErli';

    public function __construct()
    {
        $this->name = 'pherli';
        $this->tab = 'other';
        $this->version = '1.3.1';
        $this->author = 'PrestaHelp';
        $this->need_instance = 1;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Integracja z erli.pl');
        $this->description = $this->l('Intregracja oferty produktowej Twojego sklepu z serwisem erli.pl');
        $this->confirmUninstall = $this->l('Odinstalowanie modułu nie powoduje utraty żadnych danych.');

        $this->configuration['name'] = $this->name;
        $this->configuration['version'] = $this->version;
    }

    public function install()
    {
        include(__DIR__ . '/sql/install.php');
        $this->createTab();
        $this->registerCheckBuyAbility();
        if (!parent::install() ||
            !$this->registerHook('displayAdminProductsExtra') ||
            !$this->registerHook('actionProductUpdate') ||
            !$this->registerHook('actionProductDelete') ||
            !$this->registerHook('actionOrderStatusUpdate') ||
            !$this->registerHook('actionProductSaveBefore') ||
            !$this->registerHook('displayAdminOrder') ||
            !$this->registerHook('actionAdminOrdersTrackingNumberUpdate')
        ) {
            return false;
        } else {
            return true;
        }
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }
        return true;
    }

    /**
     * set default configuration
     */
    private function setConfiguration()
    {
        Configuration::updateValue('ERLI_API_SANDBOX', 0);
        Configuration::updateValue('ERLI_EXPORT_NEW', 0);
        Configuration::updateValue('ERLI_DELIVERY_TIME_DEFAULT', 1);
        Configuration::updateValue('ERLI_ORDER_IMPORT', 1);
        Configuration::updateValue('ERLI_PRODUCT_CHANGE', 1);
        Configuration::updateValue('ERLI_PRODUCT_PRICE', 0);
        Configuration::updateValue('ERLI_PRODUCT_PRICE_TYPE', 0);
        Configuration::updateValue('ERLI_PRODUCT_PRICE_ACTION', 0);
        Configuration::updateValue('ERLI_PRODUCT_PRICE_VALUE', 0);
        Configuration::updateValue('ERLI_PRODUCT_PRICE_CUR', 1);
        Configuration::updateValue('ERLI_CHECK_PAYMENT', 0);
        Configuration::updateValue('ERLI_IMAGE_ACTION', 0);
    }

    private function createTab()
    {
        $lang = Language::getLanguages();
        if (!Tab::getIdFromClassName('AdminErli')) {
            $tab = new Tab();
            $tab->id_parent = 0;
            $tab->module = $this->name;
            $tab->class_name = 'AdminErli';
            $tab->active = 1;
            $tab->hide_host_mode = 0;
            foreach ($lang as $l) {
                $tab->name[$l['id_lang']] = 'erli.pl';
            }
            $tab->add();
        }
        if (!Tab::getIdFromClassName('AdminErliProducts')) {
            $tab = new Tab();
            $tab->id_parent = Tab::getIdFromClassName($this->class_name_tab);
            $tab->module = $this->name;
            $tab->class_name = 'AdminErliProducts';
            $tab->active = 1;
            $tab->hide_host_mode = 0;
            foreach ($lang as $l) {
                $tab->name[$l['id_lang']] = 'Lista produktów';
            }
            $tab->add();
        }
        if (!Tab::getIdFromClassName('AdminErliCategories')) {
            $tab = new Tab();
            $tab->id_parent = Tab::getIdFromClassName($this->class_name_tab);
            $tab->module = $this->name;
            $tab->class_name = 'AdminErliCategories';
            $tab->active = 1;
            $tab->hide_host_mode = 0;
            foreach ($lang as $l) {
                $tab->name[$l['id_lang']] = 'Lista kategorii';
            }
            $tab->add();
        }
        if (!Tab::getIdFromClassName('AdminErliSync')) {
            $tab = new Tab();
            $tab->id_parent = Tab::getIdFromClassName($this->class_name_tab);
            $tab->module = $this->name;
            $tab->class_name = 'AdminErliSync';
            $tab->active = 1;
            $tab->hide_host_mode = 0;
            foreach ($lang as $l) {
                $tab->name[$l['id_lang']] = 'Synchronizacja';
            }
            $tab->add();
        }
        if (!Tab::getIdFromClassName('AdminErliOrders')) {
            $tab = new Tab();
            $tab->id_parent = Tab::getIdFromClassName($this->class_name_tab);
            $tab->module = $this->name;
            $tab->class_name = 'AdminErliOrders';
            $tab->active = 1;
            $tab->hide_host_mode = 0;
            foreach ($lang as $l) {
                $tab->name[$l['id_lang']] = 'Zamówienia';
            }
            $tab->add();
        }
        if (!Tab::getIdFromClassName('AdminErliConfiguration')) {
            $tab = new Tab();
            $tab->id_parent = Tab::getIdFromClassName($this->class_name_tab);
            $tab->module = $this->name;
            $tab->class_name = 'AdminErliConfiguration';
            $tab->active = 1;
            $tab->hide_host_mode = 0;
            foreach ($lang as $l) {
                $tab->name[$l['id_lang']] = 'Konfiguracja';
            }
            $tab->add();
        }
    }

    private function registerCheckBuyAbility()
    {
        $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
        $sslActive = Configuration::get('PS_SSL_ENABLED');
        $ssl = 'http://';
        if ($sslActive) {
            $ssl = 'https://';
        }
        $hookUrl = $ssl . $this->context->shop->domain . $this->context->shop->physical_uri . 'module/pherli/checkBuyability';
        $return = $erliApi->registerHook('checkBuyability', $hookUrl);

        if ($return['status'] == 201) {
            return true;
        }
        return false;
    }

    public function getContent()
    {
        $this->adminPost();
        $output = '';

        if (Tools::getIsset('action')) {
            $action = Tools::getValue('action');
            switch ($action) {
                case 'mapDelivery':
                    $delivery_erli = Erli::getDeliveryList();
                    $carriers = Carrier::getCarriers((int)$this->context->cookie->id_lang, true, false, false, null, 5);
                    $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
                    $this->context->smarty->assign(array(
                        'delivery' => $delivery_erli,
                        'carriers' => $carriers,
                        'backUrl' => $this->context->link->getAdminLink('AdminModules') . '&configure=pherli',
                    ));
                    $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/map_delivery.tpl');
                    break;
                case 'hideProducts':
                    $this->context->smarty->assign(array(
                        'backUrl' => $this->context->link->getAdminLink('AdminModules') . '&configure=pherli',
                    ));
                    $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/hide.tpl');
                    break;
                case 'deletedNull':
                    Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'erli_product` SET `deleted` = 0, `inErli` = 0, `active` = 0, `status` = 0');
                    $url = $this->context->link->getAdminLink('AdminModules').'&configure=pherli';
                    Tools::redirect($url);
                    break;
            }
        } else {
            $sslActive = Configuration::get('PS_SSL_ENABLED');
            $ssl = 'http://';
            if ($sslActive) {
                $ssl = 'https://';
            }
            $states = OrderState::getOrderStates((int)$this->context->cookie->id_lang);
            $cron_order = $ssl . $this->context->shop->domain . $this->context->shop->physical_uri . 'module/pherli/ErliCron?action=inbox';
            $cron_products = $ssl . $this->context->shop->domain . $this->context->shop->physical_uri . 'module/pherli/ErliCron?action=products';
            $cron_stocks = $ssl . $this->context->shop->domain . $this->context->shop->physical_uri . 'module/pherli/ErliCron?action=stocks';
            $cron_hook = $ssl . $this->context->shop->domain . $this->context->shop->physical_uri . 'module/pherli/ErliCron?action=checkBuyability';
            $cron_first = $ssl . $this->context->shop->domain . $this->context->shop->physical_uri . 'module/pherli/ErliCron?action=firstSync';
            $cron_prices = $ssl . $this->context->shop->domain . $this->context->shop->physical_uri . 'module/pherli/ErliCron?action=getPriceList';
            $cron_tracking = $ssl . $this->context->shop->domain . $this->context->shop->physical_uri . 'module/pherli/ErliCron?action=setTracking';
            $cron_hook2 = $ssl . $this->context->shop->domain . $this->context->shop->physical_uri . 'module/pherli/checkBuyability';
            $cron_hook = str_replace('modules', 'module', $cron_hook);
            $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
            $deliveryPricesApi = $erliApi->getPriceList();
            $deliveryPrices = json_decode($deliveryPricesApi['body']);
            $update = $this->update();
            $chba = $erliApi->getHookList();
            $checkBA = 1;
            if (!empty($chba)) {
                if ($chba['status'] == 200) {
                    $js = json_decode($chba['body']);
                    if (!empty($js)) {
                        $list = array();
                        foreach ($js as $j) {
                            $list[] = $j->hookName;
                        }
                        if (!empty($list)) {
                            if (!in_array('checkBuyability', $list)) {
                                $checkBA = 0;
                            }
                        }
                    }
                }
            }
            $all_products = count(Db::getInstance()->executeS('SELECT id_product FROM `' . _DB_PREFIX_ . 'erli_first_sync`'));
            $checked_products = count(Db::getInstance()->executeS('SELECT id_product FROM `' . _DB_PREFIX_ . 'erli_first_sync` WHERE `checked` = 1'));
            $prc = number_format(($checked_products * 100) / $all_products, 0, '.', '');

            $this->context->smarty->assign(array(
                'api_token' => Configuration::get('ERLI_API_TOKEN'),
                'api_sand' => (int)Configuration::get('ERLI_API_SANDBOX'),
                'export_new' => (int)Configuration::get('ERLI_EXPORT_NEW'),
                'delivery_time' => (int)Configuration::get('ERLI_DELIVERY_TIME_DEFAULT'),
                'status_1' => (int)Configuration::get('ERLI_STATUS_PREPARING'),
                'status_2' => (int)Configuration::get('ERLI_STATUS_WAITING'),
                'status_3' => (int)Configuration::get('ERLI_STATUS_SEND'),
                'order_import' => (int)Configuration::get('ERLI_ORDER_IMPORT'),
                'product_change' => (int)Configuration::get('ERLI_PRODUCT_CHANGE'),
                'ERLI_PRODUCT_CHANGE_ALL' => (int)Configuration::get('ERLI_PRODUCT_CHANGE_ALL'),
                'delivery_prices' => Configuration::get('ERLI_DELIVERY_PRICES'),
                'erli_price' => Configuration::get('ERLI_PRODUCT_PRICE'),
                'erli_type' => Configuration::get('ERLI_PRODUCT_PRICE_TYPE'),
                'erli_action' => Configuration::get('ERLI_PRODUCT_PRICE_ACTION'),
                'erli_value' => Configuration::get('ERLI_PRODUCT_PRICE_VALUE'),
                'erli_cur' => Configuration::get('ERLI_PRODUCT_PRICE_CUR'),
                'erli_dm' => Configuration::get('ERLI_DEV_MODE'),
                'image_action' => Configuration::get('ERLI_IMAGE_ACTION'),
                'ERLI_CHECK_PAYMENT' => (int)Configuration::get('ERLI_CHECK_PAYMENT'),
                'deliveryPrices' => $deliveryPrices,
                'deliveryTime' => ErliApi::$deliveryTime,
                'states' => $states,
                'current_url' => $this->context->link->getAdminLink('AdminModules') . '&configure=pherli&action=',
                'cron_order' => $cron_order,
                'cron_products' => $cron_products,
                'cron_stocks' => $cron_stocks,
                'cron_hook' => $cron_hook,
                'cron_hook2' => $cron_hook2,
                'cron_first' => $cron_first,
                'cron_prices' => $cron_prices,
                'cron_tracking' => $cron_tracking,
                'update' => $update,
                'checkBA' => $checkBA,
                'status_o1' => (int)Configuration::get('ERLI_STATUS_SHOP_preparing'),
                'status_o2' => (int)Configuration::get('ERLI_STATUS_SHOP_readyToPickup'),
                'status_o3' => (int)Configuration::get('ERLI_STATUS_SHOP_waitingForCourier'),
                'status_o4' => (int)Configuration::get('ERLI_STATUS_SHOP_sent'),
                'status_o5' => (int)Configuration::get('ERLI_STATUS_SHOP_paid'),
                'status_o6' => (int)Configuration::get('ERLI_STATUS_SHOP_nopaid'),
                'status_o7' => (int)Configuration::get('ERLI_STATUS_SHOP_canceled'),
                'all_products' => $all_products,
                'checked_products' => $checked_products,
                'prc' => $prc,
                'round' => (int)Configuration::get('ERLI_PRICE_ROUND'),
                'round_type' => (int)Configuration::get('ERLI_PRICE_ROUND_TYPE'),
                'fs_archived' => (int)Configuration::get('ERLI_FS_ARCHIVED'),
                'module_version' => $this->version,
                'module_name' => $this->displayName,
            ));
            $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/module.tpl');
        }
        return $output;
    }

    private function adminPost()
    {
        if (Tools::getIsset('submitSaveApiInfo')) {
            Configuration::updateValue('ERLI_API_TOKEN', Tools::getValue('ERLI_API_TOKEN'));
            Configuration::updateValue('ERLI_API_SANDBOX', (int)Tools::getValue('ERLI_API_SANDBOX'));
            Configuration::updateValue('ERLI_EXPORT_NEW', (int)Tools::getValue('ERLI_EXPORT_NEW'));
            Configuration::updateValue('ERLI_DELIVERY_TIME_DEFAULT', (int)Tools::getValue('ERLI_DELIVERY_TIME_DEFAULT'));
            Configuration::updateValue('ERLI_STATUS_PREPARING', (int)Tools::getValue('ERLI_STATUS_PREPARING'));
            Configuration::updateValue('ERLI_STATUS_WAITING', (int)Tools::getValue('ERLI_STATUS_WAITING'));
            Configuration::updateValue('ERLI_STATUS_SEND', (int)Tools::getValue('ERLI_STATUS_SEND'));
            Configuration::updateValue('ERLI_ORDER_IMPORT', (int)Tools::getValue('ERLI_ORDER_IMPORT'));
            Configuration::updateValue('ERLI_PRODUCT_CHANGE', (int)Tools::getValue('ERLI_PRODUCT_CHANGE'));
            Configuration::updateValue('ERLI_PRODUCT_CHANGE_ALL', (int)Tools::getValue('ERLI_PRODUCT_CHANGE_ALL'));
            Configuration::updateValue('ERLI_DELIVERY_PRICES', Tools::getValue('ERLI_DELIVERY_PRICES'));
            Configuration::updateValue('ERLI_DEV_MODE', Tools::getValue('ERLI_DEV_MODE'));
            Configuration::updateValue('ERLI_STATUS_SHOP_preparing', Tools::getValue('ERLI_STATUS_SHOP_preparing'));
            Configuration::updateValue('ERLI_STATUS_SHOP_readyToPickup', Tools::getValue('ERLI_STATUS_SHOP_readyToPickup'));
            Configuration::updateValue('ERLI_STATUS_SHOP_waitingForCourier', Tools::getValue('ERLI_STATUS_SHOP_waitingForCourier'));
            Configuration::updateValue('ERLI_STATUS_SHOP_sent', Tools::getValue('ERLI_STATUS_SHOP_sent'));
            Configuration::updateValue('ERLI_STATUS_SHOP_paid', Tools::getValue('ERLI_STATUS_SHOP_paid'));
            Configuration::updateValue('ERLI_STATUS_SHOP_nopaid', Tools::getValue('ERLI_STATUS_SHOP_nopaid'));
            Configuration::updateValue('ERLI_STATUS_SHOP_canceled', Tools::getValue('ERLI_STATUS_SHOP_canceled'));
            Configuration::updateValue('ERLI_PRICE_ROUND', (int)Tools::getValue('ERLI_PRICE_ROUND'));
            Configuration::updateValue('ERLI_PRICE_ROUND_TYPE', (int)Tools::getValue('ERLI_PRICE_ROUND_TYPE'));
            Configuration::updateValue('ERLI_FS_ARCHIVED', (int)Tools::getValue('ERLI_FS_ARCHIVED'));
            Configuration::updateValue('ERLI_CHECK_PAYMENT', (int)Tools::getValue('ERLI_CHECK_PAYMENT'));
            Configuration::updateValue('ERLI_IMAGE_ACTION', (int)Tools::getValue('ERLI_IMAGE_ACTION'));

            Tools::redirect($_SERVER['HTTP_REFERER']);
        }
        if (Tools::getIsset('submitSaveErliInfo')) {
            Configuration::updateValue('ERLI_PRODUCT_PRICE', (int)Tools::getValue('ERLI_PRODUCT_PRICE'));
            Configuration::updateValue('ERLI_PRODUCT_PRICE_TYPE', (int)Tools::getValue('ERLI_PRODUCT_PRICE_TYPE'));
            Configuration::updateValue('ERLI_PRODUCT_PRICE_ACTION', (int)Tools::getValue('ERLI_PRODUCT_PRICE_ACTION'));
            Configuration::updateValue('ERLI_PRODUCT_PRICE_VALUE', Tools::getValue('ERLI_PRODUCT_PRICE_VALUE'));
            Configuration::updateValue('ERLI_PRODUCT_PRICE_CUR', (int)Tools::getValue('ERLI_PRODUCT_PRICE_CUR'));
            Tools::redirect($_SERVER['HTTP_REFERER']);
        }
        if (Tools::getIsset('importErliDelivery')) {
            $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
            $deliveryRequest = $erliApi->getDeliveryMethods();
            $delivery = json_decode($deliveryRequest['body']);
            $deliveryVendors = $erliApi->getDeliveryVendors();
            $vendors = json_decode($deliveryVendors['body']);
            if (!empty($delivery)) {
                foreach ($delivery as $key => &$item) {
                    $deliv = Erli::getDelivery($item->id);
                    $vnd = '';
                    if (strpos(strtolower($item->name), 'erli') !== false) {
                        $vnd = 'DostawaERLI';
                    } else {
                        foreach ($vendors as $v) {
                            if (strpos(strtolower($item->name), $v) !== false) {
                                $vnd = $v;
                            }
                        }
                        if (empty($vnd)) {
                            $p1 = strpos(strtolower($item->name), 'paczka 24');
                            if ($p1 !== false) {
                                $vnd = 'pocztex24';
                            }
                            $p2 = strpos(strtolower($item->name), 'pocztex48');
                            if ($p2 !== false) {
                                $vnd = 'pocztaPolska';
                            }
                            $p3 = strpos(strtolower($item->name), 'tnt');
                            if ($p3 !== false) {
                                $vnd = 'tnt';
                            }
                            $p4 = strpos(strtolower($item->name), 'patron service');
                            if ($p4 !== false) {
                                $vnd = 'other';
                            }
                            $p5 = strpos(strtolower($item->name), 'list polecony');
                            if ($p5 !== false) {
                                $vnd = 'pocztaPolska';
                            }
                            $p6 = strpos(strtolower($item->name), 'Kurier TNT Express');
                            if ($p6 !== false) {
                                $vnd = 'tntExpress';
                            }
                            $p7 = strpos(strtolower($item->name), 'kurier tnt express');
                            if ($p7 !== false) {
                                $vnd = 'tntExpress';
                            }
                            $p8 = strpos(strtolower($item->name), 'paczka priorytet');
                            if ($p8 !== false) {
                                $vnd = 'pocztaPolska';
                            }
                            $p9 = strpos(strtolower($item->name), 'paczka ekonomiczna');
                            if ($p9 !== false) {
                                $vnd = 'pocztaPolska';
                            }
                            $p10 = strpos(strtolower($item->name), 'elektroniczna');
                            if ($p10 !== false) {
                                $vnd = 'other';
                            }
                            $p11 = strpos(strtolower($item->name), 'dostawa przez sprzedającego');
                            if ($p11 !== false) {
                                $vnd = 'other';
                            }
                            $p12 = strpos(strtolower($item->name), 'poczta polska');
                            if ($p12 !== false) {
                                $vnd = 'pocztaPolska';
                            }
                            $p13 = strpos(strtolower($item->name), 'list ekonomiczny');
                            if ($p13 !== false) {
                                $vnd = 'pocztaPolska';
                            }
                            $p14 = strpos(strtolower($item->name), 'list priorytetowy');
                            if ($p14 !== false) {
                                $vnd = 'pocztaPolska';
                            }
                            $p15 = strpos(strtolower($item->name), 'paczka 48');
                            if ($p15 !== false) {
                                $vnd = 'pocztaPolska';
                            }
                            $p16 = strpos(strtolower($item->name), 'odbiór osobisty');
                            if ($p16 !== false) {
                                $vnd = 'other';
                            }
                            $p17 = strpos(strtolower($item->name), 'automaty pocztowe');
                            if ($p17 !== false) {
                                $vnd = 'pocztaPolska';
                            }
                            $p18 = strpos(strtolower($item->name), 'kurier');
                            if ($p18 !== false) {
                                $vnd = 'other';
                            }
                            $p19 = strpos(strtolower($item->name), 'list polecony priorytet pobranie');
                            if ($p19 !== false) {
                                $vnd = 'pocztaPolska';
                            }
                            $p20 = strpos(strtolower($item->name), 'list polecony ekonomiczny pobranie');
                            if ($p20 !== false) {
                                $vnd = 'pocztaPolska';
                            }
                            $p21 = strpos(strtolower($item->name), 'paczka priorytet pobranie');
                            if ($p21 !== false) {
                                $vnd = 'pocztaPolska';
                            }
                        }
                    }
                    $item->v = $vnd;
                    if (empty($deliv)) {
                        Erli::addDelivery($item->id, $item->name, $item->vendor, $item->cod);
                    } else {
                        Erli::updateDeliveryVendor($deliv['id_delivery'], $item->vendor);
                    }
                }
            }
            Tools::redirect($_SERVER['HTTP_REFERER']);
        }
        if (Tools::getIsset('importErliPrices')) {
            $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
            $request = $erliApi->getPriceList();
            $prices = str_replace('[', '', $request['body']);
            $prices = str_replace(']', '', $prices);
            $prices = str_replace('"', '', $prices);
            $prices = explode(',', $prices);
            if (!empty($prices)) {
                foreach ($prices as $item) {
                    $price = Erli::getDeliveryPrices($item);
                    if (empty($price)) {
                        Erli::addDeliveryPrices($item);
                    }
                }
            }
            Tools::redirect($_SERVER['HTTP_REFERER']);
        }
        if (Tools::getIsset('submitMapDeliveryShop')) {
            $delivery = Tools::getValue('delivery');
//            $vendors = Tools::getValue('vendor');
            if (!empty($delivery)) {
                foreach ($delivery as $key => $item) {
                    Erli::updateDeliveryMap((int)$key, (int)$item);
                }
            }
            Tools::redirect($_SERVER['HTTP_REFERER']);
        }
        if (Tools::getIsset('updateModule')) {
            $this->updateModule();
            Tools::redirect($_SERVER['HTTP_REFERER']);
        }
        if (Tools::getIsset('submitHideProduct')) {
            $externalId = Tools::getValue('externalId');
            if (!empty($externalId)) {
                $externalIdX = explode('-', $externalId);
                $id_product = $externalIdX[0];
                $id_attribute = $externalId[1];
                $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
                $send['status'] = 'inactive';
                $send['overrideFrozen'] = 'true';
                $return = $erliApi->updateProduct($send, $externalId);
                if ($return['status'] == 202) {
                    Erli::updateInErliProduct((int)$id_product);
                    Erli::setDisableProductInErli((int)$id_product);
                    Erli::updateInErliProduct((int)$id_product, 0);
                    Erli::setDisableProductInErli((int)$id_product);
                } else {
                    print_r($return);
                    echo "<br />Odśwież stronę aby ponowić lub cofnij aby wrócić.";
                    exit();
                }
            }
            Tools::redirect($_SERVER['HTTP_REFERER']);
        }
    }

    private function updateModule()
    {
        $update103_sql = Db::getInstance()->getRow('SELECT COUNT(*) as count FROM information_schema.tables WHERE `table_name` = "' . _DB_PREFIX_ . 'erli_products"');
        if ((int)$update103_sql['count'] == 0) {
            $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'erli_products` (
                          `id_ep` int(11) NOT NULL AUTO_INCREMENT,
                          `id_product` int(11) NOT NULL,
                          `price` tinyint(1) NOT NULL,
                          `quantity` tinyint(1) NOT NULL,
                          `reference` tinyint(1) NOT NULL,
                          `ean` tinyint(1) NOT NULL,
                          `description` tinyint(1) NOT NULL,
                          `description_short` tinyint(1) NOT NULL,
                          `name` tinyint(1) NOT NULL,
                          `date_add` datetime NOT NULL,
                          `date_upd` datetime,
                          PRIMARY KEY (`id_ep`)
                        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
            Db::getInstance()->execute($sql);
        }
        $update103a_sql = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'hook` WHERE `name` = "actionProductSaveBefore"');
        if (empty($update103a_sql)) {
            Db::getInstance()->insert('hook', array(
                'name' => 'actionProductSaveBefore',
                'title' => 'actionProductSaveBefore',
                'description' => 'actionProductSaveBefore',
                'position' => 1,
            ));
        }
        $update1013a_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_products` WHERE `Field` = "images"');
        if (empty($update1013a_sql)) {
            Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'erli_products` ADD `images` tinyint(1) NULL AFTER `name`');
        }
        $update1013b_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_products` WHERE `Field` = "attributes"');
        if (empty($update1013b_sql)) {
            Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'erli_products` ADD `attributes` tinyint(1) NULL AFTER `images`');
        }
        $update1019_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_products` WHERE `Field` = "status"');
        if (empty($update1019_sql)) {
            Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'erli_products` ADD `status` tinyint(1) NULL AFTER `attributes`');
        }
        $update111_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_product` WHERE `Field` = "deliveryPrice"');
        if (empty($update111_sql)) {
            Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'erli_product` ADD `deliveryPrice` varchar(255) NULL AFTER `deliveryTime`');
        }
        $update116_sql = Db::getInstance()->getRow('SELECT COUNT(*) as count FROM information_schema.tables WHERE `table_name` = "' . _DB_PREFIX_ . 'erli_first_sync"');
        if ((int)$update116_sql['count'] == 0) {
            $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'erli_first_sync` (
                      `id_fs` int(11) NOT NULL AUTO_INCREMENT,
                      `externalId` varchar(125) NOT NULL,
                      `id_product` int(11) NOT NULL DEFAULT 0,
                      `id_product_attribute` int(11) NOT NULL DEFAULT 0,
                      `id_product_shop` int(11) NOT NULL DEFAULT 0,
                      `ean` varchar(125) NULL,
                      `sku` varchar(125) NULL,
                      `date_add` datetime NOT NULL,
                      `date_upd` datetime NOT NULL,
                      PRIMARY KEY (`id_fs`)
                    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
            Db::getInstance()->execute($sql);
        }
        $update117_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_first_sync` WHERE `Field` = "checked"');
        if (empty($update117_sql)) {
            Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'erli_first_sync` ADD `checked` tinyint(1) NULL AFTER `sku`');
        }
        $update1110_sql = Db::getInstance()->getRow('SELECT COUNT(*) as count FROM information_schema.tables WHERE `table_name` = "' . _DB_PREFIX_ . 'erli_delivery_prices"');
        if ((int)$update1110_sql['count'] == 0) {
            $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'erli_delivery_prices` (
                      `id_dp` int(11) NOT NULL AUTO_INCREMENT,
                      `name` varchar(255) NOT NULL,
                      `date_add` datetime NOT NULL,
                      `date_upd` datetime NOT NULL,
                      PRIMARY KEY (`id_dp`)
                    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
            Db::getInstance()->execute($sql);
        }
        $update122_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_product` WHERE `Field` = "deleted"');
        if (empty($update122_sql)) {
            Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'erli_product` ADD `deleted` tinyint(1) NULL AFTER `inErli`');
        }
        $update123_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_sync_error` WHERE `Field` = "ida"');
        if (empty($update123_sql)) {
            Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'erli_sync_error` ADD `ida` int(11) NULL AFTER `id`');
        }
        $update124_sql = Db::getInstance()->getRow('SELECT COUNT(*) as count FROM information_schema.tables WHERE `table_name` = "' . _DB_PREFIX_ . 'erli_product_delete"');
        if ((int)$update124_sql['count'] == 0) {
            $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_product_delete` (
                        `id_epd` int(11) NOT NULL AUTO_INCREMENT,
                        `id_product` int(11) NOT NULL,
                        `id_product_attribute` int(1) NOT NULL,
                        `id_sync` tinyint(1) NOT NULL,
                        `date_add` datetime NOT NULL,
                        PRIMARY KEY (`id_epd`)
                    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
            Db::getInstance()->execute($sql);
        }
        $update124a_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_delivery` WHERE `Field` = "vendor"');
        if (empty($update124a_sql)) {
            Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'erli_delivery` ADD `vendor` varchar(50) NULL AFTER `name`');
        }
        if (!Tab::getIdFromClassName('AdminErliConfiguration')) {
            $lang = Language::getLanguages();
            $tab = new Tab();
            $tab->id_parent = Tab::getIdFromClassName($this->class_name_tab);
            $tab->module = $this->name;
            $tab->class_name = 'AdminErliConfiguration';
            $tab->active = 1;
            $tab->hide_host_mode = 0;
            foreach ($lang as $l) {
                $tab->name[$l['id_lang']] = 'Konfiguracja';
            }
            $tab->add();
        }
        $update129a_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_product` WHERE `Field` = "skiped"');
        if (empty($update129a_sql)) {
            Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'erli_product` ADD `skiped` tinyint(1) NULL DEFAULT 0 AFTER `deleted`');
        }
        $update129_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_product` WHERE `Field` = "updated"');
        if (empty($update129_sql)) {
            Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'erli_product` ADD `updated` tinyint(1) NULL DEFAULT 0 AFTER `skiped`');
        }
        $update130a_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_order_payment` WHERE `Field` = "type"');
        if (empty($update130a_sql)) {
            Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'erli_order_payment` ADD `type` varchar(255) NULL AFTER `payment_id`');
        }
        $update130b_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_order_payment` WHERE `Field` = "operator"');
        if (empty($update130b_sql)) {
            Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'erli_order_payment` ADD `operator` varchar(255) NULL AFTER `type`');
        }
        if (!Tab::getIdFromClassName('AdminErliRelatedProducts')) {
            $lang = Language::getLanguages();
            $tab = new Tab();
            $tab->id_parent = Tab::getIdFromClassName($this->class_name_tab);
            $tab->module = $this->name;
            $tab->class_name = 'AdminErliRelatedProducts';
            $tab->active = 1;
            $tab->hide_host_mode = 0;
            foreach ($lang as $l) {
                $tab->name[$l['id_lang']] = 'Powiązane produkty';
            }
            $tab->add();
        }
        $update130d_sql = Db::getInstance()->getRow('SELECT COUNT(*) as count FROM information_schema.tables WHERE `table_name` = "' . _DB_PREFIX_ . 'erli_product_search"');
        if ((int)$update130d_sql['count'] == 0) {
            $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_product_search` (
                        `idp` int(11) NOT NULL,
                        `externalId` int(11) NOT NULL,
                        `name` varchar(500) CHARACTER SET utf8 NOT NULL,
                        `status` varchar(100) NOT NULL,
                        `inShop` tinyint(1) DEFAULT 0,
                        `shopName` varchar(500) CHARACTER SET utf8 DEFAULT NULL,
                        `sku` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
                        `reference` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
                        PRIMARY KEY (`idp`)
                    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
            Db::getInstance()->execute($sql);
        }
    }

    private function update()
    {
        $returned = false;
        $update103_sql = Db::getInstance()->getRow('SELECT COUNT(*) as count FROM information_schema.tables WHERE `table_name` = "' . _DB_PREFIX_ . 'erli_products"');
        if ((int)$update103_sql['count'] == 0) {
            $returned = true;
        }
        $update103a_sql = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'hook` WHERE `name` = "actionProductSaveBefore"');
        if (empty($update103a_sql)) {
            $returned = true;
        }
        $update1013a_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_products` WHERE `Field` = "images"');
        if (empty($update1013a_sql)) {
            $returned = true;
        }
        $update1013b_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_products` WHERE `Field` = "attributes"');
        if (empty($update1013b_sql)) {
            $returned = true;
        }
        $update1019_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_products` WHERE `Field` = "status"');
        if (empty($update1019_sql)) {
            $returned = true;
        }
        $update111_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_product` WHERE `Field` = "deliveryPrice"');
        if (empty($update111_sql)) {
            $returned = true;
        }
        $update116_sql = Db::getInstance()->getRow('SELECT COUNT(*) as count FROM information_schema.tables WHERE `table_name` = "' . _DB_PREFIX_ . 'erli_first_sync"');
        if ((int)$update116_sql['count'] == 0) {
            $returned = true;
        }
        $update117_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_first_sync` WHERE `Field` = "checked"');
        if (empty($update117_sql)) {
            $returned = true;
        }
        $update1110_sql = Db::getInstance()->getRow('SELECT COUNT(*) as count FROM information_schema.tables WHERE `table_name` = "' . _DB_PREFIX_ . 'erli_delivery_prices"');
        if ((int)$update1110_sql['count'] == 0) {
            $returned = true;
        }
        $update122_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_product` WHERE `Field` = "deleted"');
        if (empty($update122_sql)) {
            $returned = true;
        }
        $update123_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_sync_error` WHERE `Field` = "ida"');
        if (empty($update123_sql)) {
            $returned = true;
        }
        $update124_sql = Db::getInstance()->getRow('SELECT COUNT(*) as count FROM information_schema.tables WHERE `table_name` = "' . _DB_PREFIX_ . 'erli_product_delete"');
        if ((int)$update124_sql['count'] == 0) {
            $returned = true;
        }
        $update124a_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_delivery` WHERE `Field` = "vendor"');
        if (empty($update124a_sql)) {
            $returned = true;
        }
        $update125 = Tab::getIdFromClassName('AdminErliConfiguration');
        if (!$update125) {
            $returned = true;
        }
        $update129_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_product` WHERE `Field` = "updated"');
        if (empty($update129_sql)) {
            $returned = true;
        }
        $update129a_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_product` WHERE `Field` = "skiped"');
        if (empty($update129a_sql)) {
            $returned = true;
        }
        $update130a_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_order_payment` WHERE `Field` = "type"');
        if (empty($update130a_sql)) {
            $returned = true;
        }
        $update130b_sql = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'erli_order_payment` WHERE `Field` = "operator"');
        if (empty($update130b_sql)) {
            $returned = true;
        }
        $update130c = Tab::getIdFromClassName('AdminErliRelatedProducts');
        if (!$update130c) {
            $returned = true;
        }
        $update130d_sql = Db::getInstance()->getRow('SELECT COUNT(*) as count FROM information_schema.tables WHERE `table_name` = "' . _DB_PREFIX_ . 'erli_product_search"');
        if ((int)$update130d_sql['count'] == 0) {
            $returned = true;
        }
        return $returned;
    }

    public function hookActionProductSaveBefore($params)
    {
        $checkProduct = Configuration::get('ERLI_PRODUCT_CHANGE');
        if ($checkProduct == 1) {
            $id_lang = (int)$this->context->cookie->id_lang;
            $id_product = (int)Tools::getValue('id_product');
            $name = Tools::getValue('name_' . (int)$id_lang);
            $description = Tools::getValue('description_' . (int)$id_lang);
            $description_short = Tools::getValue('description_short_' . (int)$id_lang);
            $ean = Tools::getValue('ean13');
            $reference = Tools::getValue('reference');
            $price = Tools::getValue('price');
            $quantity = Tools::getValue('qty_0_shortcut');
            $product = new Product((int)$id_product, false, (int)$id_lang);

            $erliProducts = ErliChangeProducts::getProducts((int)$id_product);
            if (empty($erliProducts)) {
                ErliChangeProducts::addProduct((int)$id_product);
            }
            if ($product->name != $name) {
                ErliChangeProducts::updateProductField((int)$id_product, 'name');
            }
            if ($product->description != $description) {
                ErliChangeProducts::updateProductField((int)$id_product, 'description');
            }
            if ($product->description_short != $description_short) {
                ErliChangeProducts::updateProductField((int)$id_product, 'description_short');
            }
            if ($product->ean13 != $ean) {
                ErliChangeProducts::updateProductField((int)$id_product, 'ean');
            }
            if ($product->reference != $reference) {
                ErliChangeProducts::updateProductField((int)$id_product, 'reference');
            }
            if ($product->price != $price) {
                ErliChangeProducts::updateProductField((int)$id_product, 'price');
            }
            if ($product->quantity != $quantity) {
                ErliChangeProducts::updateProductField((int)$id_product, 'quantity');
            }
            $images = $product->getImages((int)$this->context->cookie->id_lang);
            ErliChangeProducts::updateProductField((int)$id_product, 'images', count($images));

            $attributes = $product->hasAttributes();
            ErliChangeProducts::updateProductField((int)$id_product, 'attributes', (int)$attributes);
        }
    }

    public function hookDisplayAdminOrder($params)
    {
        $output = '';
        $id_order_shop = (int)$params['id_order'];
        $order_shop = new Order($id_order_shop);
        $order = ErliOrder::getOrderByIdShop((int)$id_order_shop);
        $errors = '';
        if (!empty($order)) {
            $orderShop = new Order($id_order_shop);
            $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
            if (Tools::getIsset('updateTrackingNumber')) {
                $id_order_erli = (int)Tools::getValue('id_order_erli');
                $id_order_shop = (int)Tools::getValue('id_order');
                $order_carrier = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'order_carrier` WHERE `id_order` = ' . (int)$id_order_shop);
                if (!empty($order_carrier['tracking_number'])) {
                    $orderErli = ErliOrder::getOrder((int)$id_order_erli);
                    $deliveryErli = ErliOrder::getOrderDelivery((int)$id_order_erli);
                    $data['deliveryTracking']['trackingNumber'] = $order_carrier['tracking_number'];
                    $data['deliveryTracking']['status'] = 'sent';
                    $data['deliveryTracking']['vendor'] = Erli::getVendorByDelivery($deliveryErli['typeId']);
                    $request = $erliApi->updateOrder($orderErli['id_payload'], $data);
                    if ($request['status'] == 202) {
                        Db::getInstance()->update('erli_order_delivery', array(
                            'sendTracking' => 1,
                            'trackingNumber' => $order_carrier['tracking_number'],
                        ), 'id_order = ' . (int)$id_order_erli);
                    } else {
                        $body = json_decode($request['body']);
                        $errors = 'Błąd #'.$request['status'].': '.$body->payload->detail;
                    }
                }
                if (empty($errors)) {
                    Tools::redirect($_SERVER['HTTP_REFERER']);
                }
            }
            if (Tools::getIsset('updatePayment')) {
                $id_order_erli = (int)Tools::getValue('id_order_erli');
                $id_order_shop = (int)Tools::getValue('id_order');
                $payment_erli = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_order_payment` WHERE id_order = '.(int)$id_order_erli);
                $paymentName = ErliOrder::getOrderPaymentName($payment_erli['payment_id'], $this->configuration);
                Db::getInstance()->insert('order_payment', array(
                    'order_reference' => $orderShop->reference,
                    'id_currency' => $this->context->currency->id,
                    'amount' => $orderShop->total_paid,
                    'payment_method' => $paymentName,
                    'conversion_rate' => $this->context->currency->conversion_rate,
                    'transaction_id' => '',
                    'card_number' => '',
                    'card_brand' => '',
                    'card_expiration' => '',
                    'card_holder' => '',
                    'date_add' => date('Y-m-d H:i:s'),
                ));
                Tools::redirect($_SERVER['HTTP_REFERER']);
            }
            if (Tools::getIsset('updateDelivery')) {
                $id_order_erli = (int)Tools::getValue('id_order_erli');
                $id_order_shop = (int)Tools::getValue('id_order');
                $onlyPickupNumber = false;
                $order_delivery_erli = ErliOrder::getOrderDelivery((int)$id_order_erli);
                if (!empty($order_delivery_erli)) {
                    $erli_delivery = Erli::getDeliveryByName($order_delivery_erli['name']);
                    if (!empty($erli_delivery)) {
                        $id_carrier = $erli_delivery['id_carrier'];
                        if ($id_carrier > 0) {
                            $carrier = Carrier::getCarrierByReference($id_carrier);
                            if ($carrier) {
                                Db::getInstance()->update('orders', array(
                                    'id_carrier' => $carrier->id,
                                ), 'id_order = '.(int)$id_order_shop);
                                Db::getInstance()->update('order_carrier', array(
                                    'id_carrier' => $carrier->id,
                                ), 'id_order = '.(int)$id_order_shop);
                            }
                            if ($erli_delivery['vendor'] == 'inpost') {
                                $deliveryErliOrder = ErliOrder::getOrderDelivery($id_order_erli);
                                if (!empty($deliveryErliOrder['pickupPlace'])) {
                                    $msg = new Message();
                                    $pickupPlace = explode('<br />', $deliveryErliOrder['pickupPlace']);
                                    $msg = new Message();
                                    if ($onlyPickupNumber) {
                                        $messageUser = 'Numer paczkomatu: '.$pickupPlace[0];
                                    } else {
                                        $messageUser = 'Numer paczkomatu: '.str_replace("<br />", "<br>", $deliveryErliOrder['pickupPlace']);
                                    }
                                    if (Validate::isCleanHtml($messageUser)) {
                                        $msg->message = $messageUser;
                                        $msg->id_cart = (int) $orderShop->id_cart;
                                        $msg->id_customer = (int) ($orderShop->id_customer);
                                        $msg->id_order = (int) $orderShop->id;
                                        $msg->private = 1;
                                        $msg->add();
                                    }
                                    // Add this message in the customer thread
                                    $customer = new Customer($orderShop->id_customer);
                                    $customer_thread = new CustomerThread();
                                    $customer_thread->id_contact = 0;
                                    $customer_thread->id_customer = (int) $orderShop->id_customer;
                                    $customer_thread->id_shop = (int) $this->context->shop->id;
                                    $customer_thread->id_order = (int) $orderShop->id;
                                    $customer_thread->id_lang = (int) $this->context->language->id;
                                    $customer_thread->email = $customer->email;
                                    $customer_thread->status = 'open';
                                    $customer_thread->token = Tools::passwdGen(12);
                                    $customer_thread->add();

                                    $customer_message = new CustomerMessage();
                                    $customer_message->id_customer_thread = $customer_thread->id;
                                    $customer_message->id_employee = 0;
                                    $customer_message->message = $messageUser;
                                    $customer_message->private = 1;
                                    $customer_message->add();
                                }
                            }
                        }
                    }
                }
                Tools::redirect($_SERVER['HTTP_REFERER']);
            }
            if (Tools::getIsset('addPaymentInfo')) {
                $id_order = (int)Tools::getValue('addPaymentInfo');
                if ($id_order > 0) {
                    $order = ErliOrder::getOrder((int)$id_order);
                    $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
                    $paymentInfo = $erliApi->getPaymentsOrderInfo($order['id_payload']);
//                    print_r($paymentInfo);exit();
                    if ($paymentInfo['status'] == 200) {
                        $body = json_decode($paymentInfo['body']);
                        if (!empty($body)) {
                            $body = $body[0];
                            $pay = ErliOrder::getOrderPayment((int)$id_order);
                            if (!empty($pay)) {
                                Db::getInstance()->update('erli_order_payment', array(
                                    'type' => $body->methodName,
                                    'operator' => $body->operator,
                                ), 'id_order = ' . (int)$id_order);
                            } else {
                                Db::getInstance()->insert('erli_order_payment', array(
                                    'id_order' => $id_order,
                                    'payment_id' => $body->id,
                                    'type' => $body->methodName,
                                    'operator' => $body->operator,
                                    'date_add' => date('Y-m-d H:i:s'),
                                ));
                            }
                        }
                    }
                }
                Tools::redirectLink($_SERVER['HTTP_REFERER']);
            }

            if (Tools::getIsset('addInvoiceAddress')) {
                $id_order = (int)Tools::getValue('addInvoiceAddress');
                if ($id_order > 0) {
                    $order_erli_add = ErliOrder::getOrder((int)$id_order);
                    if (!empty($order_erli_add)) {
                        $addressInvoiceErli = ErliOrder::getOrderAddressInvoice((int)$id_order);
                        if (!empty($addressInvoiceErli)) {
                            $addressErli = ErliOrder::getOrderAddress((int)$id_order);
                            $address2 = new Address();
                            $address2->id_country = 14;
                            $address2->id_state = 0;
                            $address2->id_customer = (int)$order_shop->id_customer;
                            $address2->alias = 'Mój adres do faktury na erli.pl';
                            $address2->firstname = !empty($addressInvoiceErli['firstname']) ? $addressInvoiceErli['firstname'] : ' ';
                            $address2->lastname = !empty($addressInvoiceErli['lastname']) ? $addressInvoiceErli['lastname'] : ' ';
                            $address2->address1 = $addressInvoiceErli['address'];
                            $address2->address2 = '';
                            $address2->postcode = $addressInvoiceErli['zip'];
                            $address2->city = $addressInvoiceErli['city'];
                            $address2->other = '';
                            $address2->phone_mobile = '';
                            $addressInvoicePhone = str_replace(' ', '', $addressErli['phone']);
                            $addressInvoicePhone = str_replace('-', '', $addressInvoicePhone);
                            $address2->phone = $addressInvoicePhone;
                            $address2->active = 1;
                            $address2->company = $addressInvoiceErli['company_name'];
                            $address2->dni = $addressInvoiceErli['nip'];
                            $address2->vat_number = $addressInvoiceErli['nip'];
                            $address2->add();
                        }
                    }
                }
                Tools::redirectLink($_SERVER['HTTP_REFERER']);
            }

            $id_order = (int)$order['id_order'];
            $order['total_pay'] = number_format($order['total'] / 100, 2, ',', '') . ' ' . $this->context->currency->sign;
            $in_shop = 0;
            if ($order['id_order_shop'] > 0) {
                $in_shop = 1;
            }
            $order['in_shop'] = $in_shop;

            $delivery = ErliOrder::getOrderDelivery((int)$id_order);
            if (!empty($delivery)) {
                $delivery['total_pay'] = number_format($delivery['price'] / 100, 2, ',', '') . ' ' . $this->context->currency->sign;
            }
            $address = ErliOrder::getOrderAddress((int)$id_order);
            $message = ErliOrder::getOrderMessage((int)$id_order);
            $items = ErliOrder::getOrderItems((int)$id_order);
            $addressInvoiceErli = ErliOrder::getOrderAddressInvoice((int)$id_order);
            $total_product = 0;
            if (!empty($items)) {
                foreach ($items as &$item) {
                    $price = number_format($item['unitPrice'] / 100, 2, '.', '');
                    $proTotal = $price * $item['quantity'];
                    $item['total'] = number_format($proTotal, 2, ',', '') . ' ' . $this->context->currency->sign;
                    $item['unit'] = number_format($price, 2, ',', '') . ' ' . $this->context->currency->sign;
                    $total_product += $proTotal;
                    $product = new Product((int)$item['externalId'], false, (int)$this->context->cookie->id_lang);
                    $cover = Product::getCover((int)$product->id);
                    $image = $this->context->link->getImageLink($product->link_rewrite, $cover['id_image'], 'small_default');
                    $item['image'] = $image;
                }
            }
            $order['total_product'] = number_format($total_product, 2, ',', '') . ' ' . $this->context->currency->sign;

            $order_erli = $erliApi->getOrder($order['id_payload']);
            $x = '';
            if ($order_erli['status'] == 200) {
                $body = json_decode($order_erli['body']);
                $xy = '';
                if (isset($body->deliveryTracking->status)) {
                    $xy = $body->deliveryTracking->status;
                }
                if (!empty($xy)) {
                    switch ($xy) {
                        case 'sent':
                            $x = 'Zrealizowano';
                            break;
                        case 'preparing':
                            $x = 'W trakcie przygotowania';
                            break;
                        case 'waitingForCourier':
                            $x = 'Gotowa do wysłania';
                            break;
                        case '':

                            break;
                    }
                }
            }

            $order_payment = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'order_payment` WHERE `order_reference` = "'.$orderShop->reference.'"');
            $isPayment = true;
            if (empty($order_payment)) {
                $isPayment = false;
            }
            $isDelivery = true;
            if ($orderShop->id_carrier == 0) {
                $isDelivery = false;
            }

            $payment = ErliOrder::getOrderPayment((int)$order['id_order']);

            $this->context->smarty->assign(array(
                'order' => $order,
                'delivery' => $delivery,
                'address' => $address,
                'message' => $message,
                'items' => $items,
                'orderShop' => $this->context->link->getAdminLink('AdminErliOrders'),
                'errors' => $errors,
                'isPayment' => $isPayment,
                'isDelivery' => $isDelivery,
                'payment' => $payment,
                'addressInvoiceErli' => $addressInvoiceErli,
            ));
            $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/adminOrder.tpl');
        }
        return $output;
    }

    public function hookActionOrderStatusUpdate($params)
    {
        $id_order = (int)$params['id_order'];
        $orderErli = ErliOrder::getOrderByIdShop((int)$id_order);
        if (!empty($orderErli)) {
            $order = new Order($id_order);
            $erliDelivery = Erli::getDeliveryByCarrierId($order->id_carrier);
            if (!empty($erliDelivery) && ($erliDelivery['vendor'] != '' || $erliDelivery['vendor'] != 'DostawaERLI')) {
                $update_order_status_action_bar = Tools::getValue('update_order_status_action_bar');
                $new_order_status_id = (int)$update_order_status_action_bar['new_order_status_id'];
                if ($new_order_status_id == 0) {
                    $new_order_status_id = (int)Tools::getValue('id_order_state');
                }
                $status_preparing = (int)Configuration::get('ERLI_STATUS_SHOP_preparing');
                $status_waiting = (int)Configuration::get('ERLI_STATUS_SHOP_waitingForCourier');
                $status_ready = (int)Configuration::get('ERLI_STATUS_SHOP_readyToPickup');
                $status_send = (int)Configuration::get('ERLI_STATUS_SHOP_sent');
                $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
                $debug = Configuration::get('ERLI_DEV_MODE');
                $return = array();
                if ($debug) {
                    echo 'ID order: ' . $id_order . '<br />';
                    echo 'ID new status: ' . $new_order_status_id . '<br />';
                    echo 'ID payload: ' . $orderErli['id_payload'] . '<br />';
                    echo "Preparing: " . $status_preparing . '<br />Waiting: ' . $status_waiting . '<br />Pickup: ' . $status_ready . '<br />Send: ' . $status_send . '<br />';
                    print_R($orderErli);
                }
                if ($new_order_status_id == $status_preparing) {
                    $dane['deliveryTracking']['status'] = 'preparing';
                    $return = $erliApi->updateOrder($orderErli['id_payload'], $dane);
                    if ($return['status'] == 500) {
                        sleep(5);
                        $return = $erliApi->updateOrder($orderErli['id_payload'], $dane);
                    }
                }
                if ($new_order_status_id == $status_waiting) {
                    $dane['deliveryTracking']['status'] = 'waitingForCourier';
                    $return = $erliApi->updateOrder($orderErli['id_payload'], $dane);
                    if ($return['status'] == 500) {
                        sleep(5);
                        $return = $erliApi->updateOrder($orderErli['id_payload'], $dane);
                    }
                }
                if ($new_order_status_id == $status_ready) {
                    $dane['deliveryTracking']['status'] = 'readyToSend';
                    $return = $erliApi->updateOrder($orderErli['id_payload'], $dane);
                    if ($return['status'] == 500) {
                        sleep(5);
                        $return = $erliApi->updateOrder($orderErli['id_payload'], $dane);
                    }
                }

                if ($new_order_status_id == $status_send) {
                    $tracking = $order->shipping_number;
                    if (!empty($tracking)) {
                        $dane['deliveryTracking']['status'] = 'sent';
                        $dane['deliveryTracking']['trackingNumber'] = $tracking;
                        $dane['deliveryTracking']['vendor'] = $erliDelivery['vendor'];
                        $return = $erliApi->updateOrder($orderErli['id_payload'], $dane);
                        if ($return['status'] == 500) {
                            sleep(5);
                            $return = $erliApi->updateOrder($orderErli['id_payload'], $dane);
                        }
                    } else {
                        $dane['deliveryTracking']['status'] = 'sent';
                        $return = $erliApi->updateOrder($orderErli['id_payload'], $dane);
                        if ($return['status'] == 500) {
                            sleep(5);
                            $return = $erliApi->updateOrder($orderErli['id_payload'], $dane);
                        }
                    }
                }
                if (isset($return['status'])) {
                    if ($return['status'] != 200 && $return['status'] != 202) {
                        return false;
                    }
                } else {
                    return false;
                }
                if ($debug) {
                    if (isset($return['status'])) {
                        if ($return['status'] != 200 && $return['status'] != 202) {
                            $this->error = $return['body'];
                            (Tools::displayError($return['body']));
                        }
                    }
                    echo 'OE: ' . $orderErli['id_payload'] . ' ';
                    echo '<br />' . json_encode($dane) . '<br />';
                    print_r($dane);
                    print_r($return);
                    exit();
                }
            }
        }
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $output = '';
        $ssl = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $id_product = (int)Tools::getValue('id_product');
        if ($id_product == 0) {
            $id_product = (int)$params['id_product'];
        }
        $productErli = Erli::checkProductInErli((int)$id_product);
        $status = 0;
        if (!empty($productErli)) {
            $status = $productErli['status'];
        }
        $deliveryPrices = Erli::getDeliveryPriceList();

        $this->context->smarty->assign(array(
            'delivery_time' => (int)$productErli['deliveryTime'],
            'deliveryTime' => ErliApi::$deliveryTime,
            'idProduct' => (int)$id_product,
            'urlErli' => $ssl . $this->context->shop->domain . $this->context->shop->physical_uri . 'modules/pherli/ajax/add.php',
            'urlErliUpd' => $ssl . $this->context->shop->domain . $this->context->shop->physical_uri . 'modules/pherli/ajax/upd.php',
            'urlErliDel' => $ssl . $this->context->shop->domain . $this->context->shop->physical_uri . 'modules/pherli/ajax/del.php',
            'activeInErli' => $productErli['active'],
            'status' => $status,
            'deliveryPrices' => $deliveryPrices,
            'delivery_price' => $productErli['deliveryPrice'],
        ));
        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/product.tpl');
        return $output;
    }

    public function hookActionProductUpdate()
    {
        if (Tools::getIsset('erli_marker')) {
            $id_product = (int)Tools::getValue('id_product');
            $active = (int)Tools::getValue('erli_active', 0);
            $deliveryTime = (int)Tools::getValue('delivery_time', null);
            $deliveryPrice = Tools::getValue('delivery_price', null);
            $product = Erli::checkProductInErli((int)$id_product);
            if (empty($product)) {
                Erli::addProductToErli((int)$id_product, (int)$deliveryTime, $deliveryPrice, (int)$active);
            } else {
                Erli::updateProductInErli((int)$id_product, (int)$deliveryTime, $deliveryPrice, (int)$active);
            }
        }
        if (Tools::isSubmit('submitAddProductToErli')) {
            $id_product = (int)Tools::getValue('id_product');
            $deliveryTime = (int)Tools::getValue('delivery_time', 0);
            $deliveryPrice = Tools::getValue('delivery_price', null);
            $product = Erli::checkProductInErli((int)$id_product);
            if (empty($product)) {
                Erli::addProductToErli((int)$id_product, (int)$deliveryTime, $deliveryPrice);
            } else {
                Erli::updateProductInErli((int)$id_product, (int)$deliveryTime, $deliveryPrice);
            }
        }
    }

    public function hookActionProductDelete()
    {
        $id_product = (int)Tools::getValue('id_product');
        $productErli = Erli::checkProductInErli($id_product);
        if (!empty($productErli)) {
            Erli::disableProductInErli($id_product);
            Erli::deleteProductErli($id_product);
        }
        $product = Erli::getProduct($id_product);
        if (!empty($product)) {
            Erli::deleteProduct($id_product);
        }
    }

    public function setProductToErli($id_product, $id_product_attribiute = 0, $deliveryTime, $deliveryPrice, $active)
    {
        $productErli = Erli::checkProductInErli((int)$id_product);
        if (empty($productErli)) {
            Erli::addProductToErli((int)$id_product, (int)$deliveryTime, (int)$deliveryPrice);
        } else {
            Erli::updateProductInErli((int)$id_product, (int)$deliveryTime, (int)$deliveryPrice);
        }

        $product = new Product((int)$id_product, false, (int)$this->context->cookie->id_lang);
        $productErli = Erli::checkProductInErli((int)$id_product);
        $dispatchTime = (int)Configuration::get('ERLI_DELIVERY_TIME_DEFAULT');
        if ((int)$productErli['deliveryTime'] > 0) {
            $dispatchTime = (int)$productErli['deliveryTime'];
        }

        $sp = null;
        $price = Product::priceCalculation($this->context->shop->id, $id_product, $id_product_attribiute, $this->context->country->id, 0, '', 1, 1, 1, true, 2, 0, 1, 1, $sp, 1);
        $send['name'] = $product->name;
        $send['ean'] = $product->ean13;
        $send['sku'] = $product->reference;
        $send['price'] = $price * 100; // w groszach
        $send['status'] = 'active';
        $send['description'] = ($product->description);
        $send['dispatchTime']['period'] = $dispatchTime;
        $send['dispatchTime']['unit'] = 'day';
        $send['packaging']['tags'][0] = Configuration::get('ERLI_DELIVERY_PRICES');
        $send['stock'] = StockAvailable::getQuantityAvailableByProduct((int)$id_product, $id_product_attribiute);
        $images = $product->getImages((int)$this->context->cookie->id_lang);
        if (!empty($images)) {
            foreach ($images as $keyI => $image) {
                $send['images'][0]['url'] = $this->context->link->getImageLink($product->link_rewrite, (int)$image['id_image']);
            }
        }
        $features = Product::getFeaturesStatic((int)$id_product);
        if (!empty($features)) {
            foreach ($features as $key => &$feature) {
                if ($key < 10) {
                    $feature_name = Db::getInstance()->getValue('SELECT `name` FROM `' . _DB_PREFIX_ . 'feature_lang` WHERE `id_feature` = ' . (int)$feature['id_feature'] . ' AND `id_lang` = ' . (int)$this->context->cookie->id_lang);
                    $feature['name'] = $feature_name;
                    $feature_value = Db::getInstance()->getValue('SELECT `value` FROM `' . _DB_PREFIX_ . 'feature_value_lang` WHERE `id_feature_value` = ' . (int)$feature['id_feature_value'] . ' AND `id_lang` = ' . (int)$this->context->cookie->id_lang);
                    $feature['value'] = $feature_value;
                    $feature_erli = $feature_name . ': ' . $feature_value;
                }
            }
        }

        $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
        $return = $erliApi->addProduct($send, (int)$id_product);
        $status = 0;
        if ($return['status'] == 202) {
            $status = 1;
        }
        Erli::updateStatus((int)$id_product, $status);
        return $return;
    }

    public function updateProductToErli($id_product, $id_product_attribiute = 0, $deliveryTime, $deliveryPrice, $active)
    {
        $productErli = Erli::checkProductInErli((int)$id_product);
        if (empty($productErli)) {
            Erli::addProductToErli((int)$id_product, (int)$deliveryTime, $deliveryPrice);
        } else {
            Erli::updateProductInErli((int)$id_product, (int)$deliveryTime, $deliveryPrice);
        }

        $product = new Product((int)$id_product, false, (int)$this->context->cookie->id_lang);
        $productErli = Erli::checkProductInErli((int)$id_product);
        $dispatchTime = (int)Configuration::get('ERLI_DELIVERY_TIME_DEFAULT');
        if ((int)$productErli['deliveryTime'] > 0) {
            $dispatchTime = (int)$productErli['deliveryTime'];
        }

        $sp = null;
        $price = Product::priceCalculation($this->context->shop->id, $id_product, $id_product_attribiute, $this->context->country->id, 0, '', 1, 1, 1, true, 2, 0, 1, 1, $sp, 1);
        $send['name'] = $product->name;
        $send['ean'] = $product->ean13;
        $send['sku'] = $product->reference;
        $send['price'] = number_format($price * 100, 0, '.', ''); // w groszach
        $send['status'] = 'active';
        $send['description'] = ($product->description);
        $send['dispatchTime']['period'] = $dispatchTime;
        $send['dispatchTime']['unit'] = 'day';
        $send['stock'] = StockAvailable::getQuantityAvailableByProduct((int)$id_product, $id_product_attribiute);

        $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));

        $imageAction = (int)Configuration::get('ERLI_IMAGE_ACTION');
        if ($imageAction == 0) {
            $product_erli = $erliApi->getProduct($id_product);
            $images_erli = $product_erli->images;
            $image_in_erli = array();
            if (!empty($images_erli)) {
                foreach ($images_erli as $key => $item) {
                    $image_in_erli[] = $item->url;
                    $send['images'][$key]['url'] = $item->url;
                }
            }
            $images = $product->getImages((int)$this->context->cookie->id_lang);
            if (!empty($images)) {
                $next = count($images_erli);
                foreach ($images as $image) {
                    $img = $this->context->link->getImageLink($product->link_rewrite, (int)$image['id_image']);
                    if (!in_array($img, $image_in_erli)) {
                        $send['images'][$next]['url'] = $img;
                        $next++;
                    }
                }
            }
        } else {
            $images = $product->getImages((int)$this->context->cookie->id_lang);
            if (!empty($images)) {
                foreach ($images as $key => $image) {
                    $send['images'][$key]['url'] = $this->context->link->getImageLink($product->link_rewrite, (int)$image['id_image']);;
                }
            }
        }

        $return = $erliApi->updateProduct($send, (int)$id_product);
        $status = 0;
        if ($return['status'] == 202) {
            $status = 1;
        }
        Erli::updateStatus((int)$id_product, $status);
        return $return;
    }

    public function deleteProductToErli($id_product, $id_product_attribiute = 0)
    {
        $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
        $return = $erliApi->delectProduct((int)$id_product);
        if ($return['status'] == 202) {
            Erli::updateStatus($id_product, Erli::$inactive);
        }
        return $return;
    }

    public function hookActionAdminOrdersTrackingNumberUpdate($params)
    {
        $id_order = (int)$params['id_order'];
        $orderErli = ErliOrder::getOrderByIdShop((int)$id_order);
        if (!empty($orderErli)) {
            $order = new Order($id_order);
            $tracking = $order->shipping_number;
            if (!empty($tracking)) {
                $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
                $erliDelivery = Erli::getDeliveryByCarrierId($order->id_carrier);
                $dane['deliveryTracking']['status'] = 'sent';
                $dane['deliveryTracking']['trackingNumber'] = $tracking;
                $dane['deliveryTracking']['vendor'] = $erliDelivery['id'];
                $erliApi->updateOrder($orderErli['id_payload'], $dane);
            }
        }
    }

}
