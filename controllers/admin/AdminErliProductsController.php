<?php

class AdminErliProductsController extends ModuleAdminController
{

    public $plPage;

    public $perPage = 10;

    private $configuration;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'AdminErliProducts';
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
        $debug = Configuration::get('ERLI_DEV_MODE');

        $show_status = false;
        if (Tools::getIsset('showStatus') && Tools::getValue('showStatus') == 1) {
            $show_status = true;
        }

        if (Tools::getIsset('hideErli') && Tools::getValue('hideErli') == 1) {
            $productsErli = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_product` WHERE `inErli` = 1');
            if (!empty($productsErli)) {
                foreach ($productsErli as $item) {
                    $xxx = Db::getInstance()->update('erli_product', array(
                        'inErli' => 0,
                    ), 'id_ep = '.(int)$item['id_ep']);
                    if ($debug) {
                        var_dump($xxx);
                    }
                }
            }
            if ($debug) {
                print_r($productsErli);
                exit();
            }
            Tools::redirect($_SERVER['HTTP_REFERER']);
        }

        if (Tools::getIsset('submitClearFiltr')) {
            Tools::redirect($_SERVER['HTTP_REFERER']);
        }
        if (Tools::getIsset('plPage')) {
            $this->plPage = (int)Tools::getValue('plPage', 1) - 1;
        }

        $start = $this->plPage * $this->perPage;
        $limit = (int)Configuration::get('ERLI_PL_PERPAGE', $this->perPage);
        if ($limit == 0) {
            $limit = 10;
        }
        $orderBy = 'id_product';
        $orderWay = 'DESC';

        $cennik = '';
        $czas = '';
        $aktywny = '';
        $erlif = '';
        $filtr = 0;
        if (Tools::getIsset('submitFiltr')) {
            $cennik = Tools::getValue('filter_delivery_price');
            $czas = Tools::getValue('filter_delivery_time');
            $aktywny = Tools::getValue('filter_active', '');
            $erlif = Tools::getValue('filter_erli', '');
            $filtr = 1;
            if ($debug) {
                print_R($_POST);
            }
        }
        $products = Product::getProducts((int)$this->context->cookie->id_lang, $start, $limit, $orderBy, $orderWay);
        $added = 0;
        if (!empty($products)) {
            foreach ($products as $key => &$product) {
                $product['price2'] = Product::getPriceStatic((int)$product['id_product'], true, 0, 2, null, false, true, 1);
                $category = new Category((int)$product['id_category_default'], (int)$this->context->cookie->id_lang);
                $product['category_name'] = $category->name;
                $cover = Product::getCover((int)$product['id_product']);
                $product['cover'] = $cover;
                $image = $this->context->link->getImageLink($product['link_rewrite'], $cover['id_image'], 'small_default');
                $product['imageCover'] = $image;
                $params = array(
                    'id_product' => $product['id_product'],
                );
                $product['productLink'] = $this->context->link->getAdminLink('AdminProducts', true, $params);
                $erli = Erli::checkProductInErli((int)$product['id_product']);
                $product['erli'] = (int)$erli['active'];
                $product['inErli'] = (int)$erli['inErli'];

                $dt = (int)$erli['deliveryTime'];
                if ($dt > 0) {
                    $deliveryTime = ErliApi::$deliveryTime[$dt];
                } else {
                    $dtc = (int)Configuration::get('ERLI_DELIVERY_TIME_DEFAULT');
                    $deliveryTime = ErliApi::$deliveryTime[$dtc];
                }
                $product['deliveryTime'] = $deliveryTime;
                $dp = $erli['deliveryPrice'];
                if ($dp != null) {
                    $deliveryPrice = $dp;
                } else {
                    $deliveryPrice = Configuration::get('ERLI_DELIVERY_PRICES');
                }
                $product['deliveryPrice'] = $deliveryPrice;
                // filter
                if (!empty($cennik)) {
                    if ($deliveryPrice != $cennik) {
                        unset($products[$key]);
                    }
                }
                if (!empty($czas)) {
                    if ($deliveryTime != $czas) {
                        unset($products[$key]);
                    }
                }
                if (!empty($aktywny)) {
                    if ($aktywny == 2) {
                        $aktywny = 0;
                    }
                    if ($product['active'] != $aktywny) {
                        unset($products[$key]);
                    }
                }
                if (!empty($erlif)) {
                    if (!empty($erli)) {
                        $eif = 1;
                        if ($erlif == 2) {
                            $eif = 0;
                        }
                        if ($debug) {
                            echo $erli['inErli'].' != '.$eif.'<br />';
                        }
                        if ((int)$erli['inErli'] != $eif) {
                            unset($products[$key]);
                        }
                    } else {
                        unset($products[$key]);
                    }
                }
                $pro = new Product((int)$product['id_product']);
                $hasAttributes = $pro->hasAttributes();
                $product['attributes'] = (int)$hasAttributes;
                if ($erli['inErli'] == 1) {
                    if ($hasAttributes == 0) {
                        $hasAttributes = 1;
                    }
                    $added += $hasAttributes;
                }
            }
        }

        $ssl = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $request_uri = explode('&plPage', $_SERVER['REQUEST_URI']);
        $currentPage = $ssl . $_SERVER['HTTP_HOST'] . $request_uri[0];
        $pagination = array(
            5, 10, 25, 50, 100, 300
        );
        $product_list_all = Product::getProducts((int)$this->context->cookie->id_lang, 0, 200000, $orderBy, $orderWay);
        $pages_all = ceil(count($product_list_all) /$limit);
        $deliveryPrices = Erli::getDeliveryPriceList();

        $total = count($product_list_all);
        if ($filtr == 1) {
            $total = count($products);
        }

        $this->context->smarty->assign(array(
            'products' => $products,
            'pagination' => $pagination,
            'currentPage' => $currentPage,
            'selected_pagination' => (int)Configuration::get('ERLI_PL_PERPAGE', $limit),
            'list_total' => $total,
            'plPage' => $this->plPage + 1,
            'pages_all' => $pages_all,
            'bulkUrl' => $this->context->link->getAdminLink($this->className),
            'deliveryPrices' => $deliveryPrices,
            'deliveryTime' => ErliApi::$deliveryTime,
            'delivery_price' => Tools::getValue('filter_delivery_price'),
            'delivery_time' => Tools::getValue('filter_delivery_time'),
            'f_active' => $aktywny,
            'f_erli' => $erlif,
            'filtr' => $filtr,
            'show_status' => $show_status,
            'added' => $added,
        ));
    }

    public function display()
    {
        $tpl = $this->getTemplatePath() . 'products_list.tpl';
        $this->context->smarty->assign(array(
            'content' => $this->context->smarty->fetch($tpl)
        ));
        parent::display();
    }

    public function postProcess()
    {
        parent::postProcess();

        if (Tools::getIsset('ERLI_PL_PERPAGE')) {
            Configuration::updateValue('ERLI_PL_PERPAGE', (int)Tools::getValue('ERLI_PL_PERPAGE'));
            Tools::redirect($_SERVER['HTTP_REFERER']);
        }
        if (isset($_POST['plPage'])) {
            $url = Tools::getValue('plUrl').'&plPage='.(int)Tools::getValue('plPage');
            Tools::redirectLink($url);
        }

        if (Tools::getIsset('add_to_erli')) {
            $checkProduct = Configuration::get('ERLI_PRODUCT_CHANGE');
            $products = Tools::getValue('products');
            if (!empty($products)) {
                foreach ($products as $product) {
                    $inErli = Erli::checkProductInErli((int)$product);
                    if (empty($inErli)) {
                        Erli::addProductToErli((int)$product);
                    } else {
                        Erli::updateProductInErli((int)$product);
                    }
                    if ($checkProduct == 1) {
                        $pro = Erli::getProduct((int)$product);
                        if (empty($pro)) {
                            Erli::setProductInsert((int)$product);
                        }
                        Erli::setProducts((int)$product, 'status', 1);
                    }
                }
            }
            Tools::redirect($_SERVER['HTTP_REFERER']);
        }

        if (Tools::getIsset('remove_from_erli')) {
            $products = Tools::getValue('products');
            $checkProduct = Configuration::get('ERLI_PRODUCT_CHANGE');
            if (!empty($products)) {
                foreach ($products as $product) {
                    $inErli = Erli::checkProductInErli((int)$product);
                    if (!empty($inErli)) {
                        Erli::disableProductInErli((int)$product);
                    }
                    $pro = new Product((int)$product);
                    $hasAttributes = $pro->hasAttributes();
                    if ($hasAttributes > 0) {
                        $attributes = $pro->getAttributeCombinations((int)$this->context->cookie->id_lang);
                        foreach ($attributes as $key => $attribute) {
                            if (empty(Erli::isDisabledProductAttributeInErli($product, $attribute['id_product_attribute']))) {
                                Erli::disableProductAttributeInErli($product, $attribute['id_product_attribute']);
                            }
                        }
                    } else {
                        if (empty(Erli::isDisabledProductAttributeInErli($product, $attribute['id_product_attribute']))) {
                            Erli::disableProductAttributeInErli($product);
                        }
                    }
                    if ($checkProduct == 1) {
                        $pro = Erli::getProduct((int)$product);
                        if (empty($pro)) {
                            Erli::setProductInsert((int)$product);
                        }
                        Erli::setProducts((int)$product, 'status', 1);
                    }
                }
            }
            Tools::redirect($_SERVER['HTTP_REFERER']);
        }

        if (Tools::getIsset('submitClearProducts')) {
            $products = Erli::getAllProduct();
            if (!empty($products)) {
                foreach ($products as $product) {
                    $p = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'product` WHERE id_product = '.(int)$product['id_product']);
                    if (empty($p)) {
                        Erli::deleteProduct((int)$product['id_product']);
                    }
                }
            }
            $productErli = Erli::getAllProductErli();
            if (!empty($productErli)) {
                foreach ($productErli as $product) {
                    $p = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'product` WHERE id_product = '.(int)$product['id_product']);
                    if (empty($p)) {
                        Erli::deleteProductErli((int)$product['id_product']);
                    } else {
                        Erli::setProductUpdate((int)$product['id_product']);
                    }
                }
            }
            Tools::redirect($_SERVER['HTTP_REFERER']);
        }

        if (Tools::getIsset('saveDeliveryInfo')) {
            $products = Tools::getValue('products');
            if (!empty($products)) {
                $deliveryTime = Tools::getValue('delivery_time', 0);
                $deliveryPrice = Tools::getValue('delivery_price', '');
                foreach ($products as $product) {
                    $iss = Erli::checkProductInErli($product);
                    if (empty($iss)) {
                        Erli::addProductToErli($product, 0, null, 0);
                    }
                    if ($deliveryTime > 0) {
                        Db::getInstance()->update('erli_product', array(
                            'deliveryTime' => (int)$deliveryTime,
                        ), 'id_product = '.(int)$product);
                    }
                    if (!empty($deliveryPrice)) {
                        Db::getInstance()->update('erli_product', array(
                            'deliveryPrice' => $deliveryPrice,
                        ), 'id_product = '.(int)$product);
                    }
                }
            }
            Tools::redirect($_SERVER['HTTP_REFERER']);
        }
    }

}
