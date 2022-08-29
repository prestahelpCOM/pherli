<?php

class pherlicheckBuyabilityModuleFrontController extends ModuleFrontController
{

    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'checkBuyability';
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

    public function initContent()
    {
        parent::initContent();
        $return = array();
        $json = file_get_contents('php://input');
        $request = json_decode($json, true);

        if (!empty($request)) {
            foreach ($request as $key => $item) {
                $item = (array)$item;
                $id_product = (int)$item['productId'];
                $product = new Product((int)$id_product, false, (int)$this->context->cookie->id_lang);
                $qty = Product::getQuantity((int)$id_product, 0);
                $return[$key]['status'] = ($product->active == 0) ? 'inactive' : 'active';
                $return[$key]['stock'] = $qty;
                $return[$key]['productId'] = "$id_product";
            }
        }
        echo json_encode($return);
        exit();
    }

    public function postProcess()
    {
        parent::postProcess();

    }

    public function display()
    {
        parent::display();
    }

}
