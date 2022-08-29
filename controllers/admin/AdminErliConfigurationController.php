<?php

class AdminErliConfigurationController extends ModuleAdminController
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
        parent::display();
    }

    public function initContent()
    {
        parent::initContent();
        $url = $this->context->link->getAdminLink('AdminModules').'&configure=pherli';
        Tools::redirectLink($url);
    }

}
