<?php

class AdminErliRelatedProductsController extends ModuleAdminController
{

    public $plPage;

    public $perPage = 10;

    private $configuration;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'AdminErliRelatedProducts';
        parent::__construct();

        $this->configuration['name'] = 'pherli';
        $this->configuration['version'] = '1.1.1';
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
    }

    public function initHeader()
    {
        parent::initHeader();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS('https://cdn.datatables.net/1.11.1/css/jquery.dataTables.min.css', 'all');
        $this->addCSS('/modules/pherli/views/css/product_list.css', 'all');
        $this->addJS('https://cdn.datatables.net/1.11.1/js/jquery.dataTables.min.js');
        $this->addJS('/modules/pherli/views/js/product_list.js');
    }

    public function initContent()
    {
        parent::initContent();
        $sslActive = Configuration::get('PS_SSL_ENABLED');
        $ssl = 'http://';
        if ($sslActive) {
            $ssl = 'https://';
        }
        $products = DB::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_product_search` ORDER BY `externalId` ASC');
        $count = count($products);
        if (!empty($products)) {
            foreach ($products as $key => &$product) {
                $typ = 'success';
                if ($product['name'] != $product['shopName']) {
                    $typ = 'danger';
                }
                $product['typ'] = $typ;
                $sim = similar_text($product['name'], $product['shopName'], $prec);
                if ($prec == 100 || $product['inShop'] == 0) {
                    unset($products[$key]);
                }
                $product['similar'] = number_format($prec, 2, '.', '');
                $params['id_product'] = $product['externalId'];
                $product['link'] = $this->context->link->getAdminLink('AdminProducts', true, $params);
            }
        }
        $cronLink = $ssl . $this->context->shop->domain . $this->context->shop->physical_uri . 'module/pherli/ErliCron?action=searchProduct';
        $this->context->smarty->assign(array(
            'products' => $products,
            'cronLink' => $cronLink,
            'count' => $count,
        ));
    }

    public function display()
    {
        $tpl = $this->getTemplatePath() . 'related_products_list.tpl';
        $this->context->smarty->assign(array(
            'content' => $this->context->smarty->fetch($tpl)
        ));
        parent::display();
    }

    public function postProcess()
    {
        parent::postProcess();
    }

}
