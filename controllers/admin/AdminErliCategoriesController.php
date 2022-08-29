<?php

class AdminErliCategoriesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'AdminErliCategories';
        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
    }

    public function initHeader()
    {
        parent::initHeader();
    }

    public function display()
    {
        $tpl = $this->getTemplatePath() . 'categories_list.tpl';
        $this->context->smarty->assign(array(
            'content' => $this->context->smarty->fetch($tpl)
        ));
        parent::display();
    }

    public function initContent()
    {
        parent::initContent();

        $categories = $this->getCategories();

        $this->context->smarty->assign(array(
            'categories' => $categories,
            'bulkUrl' => $this->context->link->getAdminLink($this->className),
        ));
    }

    public function postProcess()
    {
        parent::postProcess();

        if (Tools::getIsset('add_to_erli')) {
            $categories = Tools::getValue('categories');
            if (!empty($categories)) {
                foreach ($categories as $category) {
                    $inErli = Erli::checkCategoryInErli((int)$category);
                    if (empty($inErli)) {
                        Erli::addCategoryToErli((int)$category);
                    } else {
                        Erli::updateCategoryToErli((int)$category);
                    }

                }
            }
            Tools::redirect($_SERVER['HTTP_REFERER']);
        }
        if (Tools::getIsset('remove_from_erli')) {
            $categories = Tools::getValue('categories');
            if (!empty($categories)) {
                foreach ($categories as $category) {
                    $inErli = Erli::checkCategoryInErli((int)$category);
                    if (!empty($inErli)) {
                        Erli::disableCategoryInErli((int)$category);
                        $this->disabledProductCategory((int)$category);
                    }
                }
            }
            Tools::redirect($_SERVER['HTTP_REFERER']);
        }
    }

    private function getCategories()
    {
        $home = Configuration::get('PS_HOME_CATEGORY');
        $main_category = new Category((int)$home, (int)$this->context->cookie->id_lang);
        $subcategories = $main_category->getSubCategories((int)$this->context->cookie->id_lang, false);
        if (!empty($subcategories)) {
            foreach ($subcategories as &$subcategory) {
                $cat1 = new Category((int)$subcategory['id_category'], (int)$this->context->cookie->id_lang);
                $sub1 = $cat1->getSubCategories((int)$this->context->cookie->id_lang, false);
                if (!empty($sub1)) {
                    foreach ($sub1 as &$item) {
                        $cat2 = new Category((int)$item['id_category'], (int)$this->context->cookie->id_lang);
                        $sub2 = $cat2->getSubCategories((int)$this->context->cookie->id_lang, false);
                        if (!empty($sub2)) {
                            foreach ($sub2 as &$item2) {
                                $cat3 = new Category((int)$item2['id_category'], (int)$this->context->cookie->id_lang);
                                $sub3 = $cat3->getSubCategories((int)$this->context->cookie->id_lang, false);

                                $erli = Erli::checkCategoryInErli((int)$cat3->id);
                                $item2['erli'] = (int)$erli['active'];
                                $item2['subcategories'] = $sub3;
                                $sql = 'SELECT COUNT(cp.`id_product`) AS total
                                    FROM `' . _DB_PREFIX_ . 'product` p
                                    ' . Shop::addSqlAssociation('product', 'p') . '
                                    LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON p.`id_product` = cp.`id_product`
                                    WHERE cp.`id_category` = ' . (int) $cat3->id;
                                $prods = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
                                $item2['product_count'] = $prods;
                                $sql2 = 'SELECT ep.* FROM `'._DB_PREFIX_.'erli_product` ep LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON ep.`id_product` = cp.`id_product` WHERE cp.`id_category` = '.(int)$cat3->id;
                                $prods2 = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql2);
                                $ile = 0;
                                if (!empty($prods2)) {
                                    foreach ($prods2 as $item3) {
                                        if ($item3['inErli'] == 1) {
                                            $ile++;
                                        }
                                    }
                                }
                                $item2['erli_count'] = $ile;
                            }
                        }
                        $erli = Erli::checkCategoryInErli((int)$cat2->id);
                        $item['erli'] = (int)$erli['active'];
                        $item['subcategories'] = $sub2;
                        $sql = 'SELECT COUNT(cp.`id_product`) AS total
                            FROM `' . _DB_PREFIX_ . 'product` p
                            ' . Shop::addSqlAssociation('product', 'p') . '
                            LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON p.`id_product` = cp.`id_product`
                            WHERE cp.`id_category` = ' . (int) $cat2->id;
                        $prods = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
                        $item['product_count'] = $prods;
                        $sql2 = 'SELECT ep.* FROM `'._DB_PREFIX_.'erli_product` ep LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON ep.`id_product` = cp.`id_product` WHERE cp.`id_category` = '.(int)$cat2->id;
                        $prods2 = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql2);
                        $ile = 0;
                        if (!empty($prods2)) {
                            foreach ($prods2 as $item3) {
                                if ($item3['inErli'] == 1) {
                                    $ile++;
                                }
                            }
                        }
                        $item['erli_count'] = $ile;
                    }
                }
                $erli = Erli::checkCategoryInErli((int)$cat1->id);
                $subcategory['erli'] = (int)$erli['active'];
                $subcategory['subcategories'] = $sub1;
                $sql = 'SELECT COUNT(cp.`id_product`) AS total
                            FROM `' . _DB_PREFIX_ . 'product` p
                            ' . Shop::addSqlAssociation('product', 'p') . '
                            LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON p.`id_product` = cp.`id_product`
                            WHERE cp.`id_category` = ' . (int) $cat1->id;
                $prods = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
                $subcategory['product_count'] = $prods;
                $sql2 = 'SELECT ep.* FROM `'._DB_PREFIX_.'erli_product` ep LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON ep.`id_product` = cp.`id_product` WHERE cp.`id_category` = '.(int)$cat1->id;
                $prods2 = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql2);
                $ile = 0;
                if (!empty($prods2)) {
                    foreach ($prods2 as $item3) {
                        if ($item3['inErli'] == 1) {
                            $ile++;
                        }
                    }
                }
                $subcategory['erli_count'] = $ile;
            }
        }
        return $subcategories;
    }

    private function disabledProductCategory($id_category)
    {
        $this->context->customer = new Customer(1);
        $cat = new Category((int)$id_category);
        $products = $cat->getProducts((int)$this->context->cookie->id_lang, 0, 10000);
        if (!empty($products)) {
            foreach ($products as $product) {
                $isset = Erli::checkProductInErli((int)$product['id_product']);
                if (!empty($isset)) {
                    Erli::disableProductInErli((int)$product['id_product']);
                }
                $pro = new Product((int)$product['id_product']);
                $hasAttributes = $pro->hasAttributes();
                if ($hasAttributes > 0) {
                    $attributes = $pro->getAttributeCombinations((int)$this->context->cookie->id_lang);
                    foreach ($attributes as $key => $attribute) {
                        if (empty(Erli::isDisabledProductAttributeInErli($product['id_product'], $attribute['id_product_attribute']))) {
                            Erli::disableProductAttributeInErli($product['id_product'], $attribute['id_product_attribute']);
                        }
                    }
                } else {
                    if (empty(Erli::isDisabledProductAttributeInErli($product['id_product'], $attribute['id_product_attribute']))) {
                        Erli::disableProductAttributeInErli($product['id_product']);
                    }
                }
            }
        }
    }


}
