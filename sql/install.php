<?php

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_product` (
          `id_ep` int(11) NOT NULL AUTO_INCREMENT,
          `id_product` int(11) NOT NULL,
          `deliveryTime` int(11) NOT NULL DEFAULT 0,
          `deliveryPrice` varchar(255) NULL,
          `status` tinyint(1) NOT NULL,
          `active` tinyint(1) NOT NULL,
          `inErli` tinyint(1) NOT NULL,
          `deleted` tinyint(1) NOT NULL,
          `skiped` tinyint(1) NOT NULL,
          `updated` tinyint(1) NOT NULL,
          `date_add` datetime NOT NULL,
          `date_upd` datetime,
          PRIMARY KEY (`id_ep`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_category` (
          `id_ec` int(11) NOT NULL AUTO_INCREMENT,
          `id_category` int(11) NOT NULL,
          `status` tinyint(1) NOT NULL,
          `active` tinyint(1) NOT NULL,
          `date_add` datetime NOT NULL,
          `date_upd` datetime,
          PRIMARY KEY (`id_ec`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_order` (
          `id_order` int(11) NOT NULL AUTO_INCREMENT,
          `id_order_shop` int(11) NOT NULL,
          `id_erli` varchar(25) NOT NULL,
          `total` int(11) NOT NULL,
          `id_payload` varchar(15) NOT NULL,
          `date_add` datetime NOT NULL,
          `date_upd` datetime,
          PRIMARY KEY (`id_order`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_order_address` (
          `id_address` int(11) NOT NULL AUTO_INCREMENT,
          `id_order` int(11) NOT NULL,
          `email` varchar(255) NOT NULL,
          `firstname` varchar(100) NOT NULL,
          `lastname` varchar(100) NOT NULL,
          `address` varchar(150) NOT NULL,
          `zip` varchar(10) NOT NULL,
          `city` varchar(100) NOT NULL,
          `country` varchar(10) NOT NULL,
          `phone` int(11) NOT NULL,
          `date_add` datetime NOT NULL,
          PRIMARY KEY (`id_address`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_order_delivery` (
          `id_delivery` int(11) NOT NULL AUTO_INCREMENT,
          `id_order` int(11) NOT NULL,
          `name` varchar(255) NOT NULL,
          `typeId` varchar(255) NOT NULL,
          `price` int(11) NOT NULL,
          `cod` int(11) NOT NULL,
          `pickupPlace` text NOT NULL,
          `sendTracking` int(11) NULL default 0,
          `trackingNumber` varchar(100) NULL,
          `date_add` datetime NOT NULL,
          PRIMARY KEY (`id_delivery`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_order_items` (
          `id_item` int(11) NOT NULL AUTO_INCREMENT,
          `id_order` int(11) NOT NULL,
          `id_erli` int(11) NOT NULL,
          `externalId` varchar(255) NOT NULL,
          `quantity` int(11) NOT NULL,
          `unitPrice` int(11) NOT NULL,
          `name` varchar(255) NOT NULL,
          `slug` varchar(255) NOT NULL,
          `sku` varchar(255) NOT NULL,
          `date_add` datetime NOT NULL,
          PRIMARY KEY (`id_item`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_order_message` (
          `id_message` int(11) NOT NULL AUTO_INCREMENT,
          `id_order` int(11) NOT NULL,
          `message` text NOT NULL,
          `date_add` datetime NOT NULL,
          PRIMARY KEY (`id_message`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_order_payment` (
          `id_payment` int(11) NOT NULL AUTO_INCREMENT,
          `id_order` int(11) NOT NULL,
          `payment_id` int(11) NOT NULL,
          `date_add` datetime NOT NULL,
          PRIMARY KEY (`id_payment`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_order_status` (
          `id_status` int(11) NOT NULL AUTO_INCREMENT,
          `id_order` int(11) NOT NULL,
          `status` varchar(255) NOT NULL,
          `date_add` datetime NOT NULL,
          PRIMARY KEY (`id_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_delivery` (
          `id_delivery` int(11) NOT NULL AUTO_INCREMENT,
          `id` varchar(255) NOT NULL,
          `name` varchar(255) NOT NULL,
          `cod` int(11) NOT NULL,
          `vendor` varchar(50) NOT NULL,
          `id_carrier` int(11) NOT NULL,
          PRIMARY KEY (`id_delivery`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_sync` (
          `id_sync` int(11) NOT NULL AUTO_INCREMENT,
          `type` int(11) NOT NULL,
          `product_add_all` int(11) NOT NULL,
          `product_add` int(11) NOT NULL,
          `product_update_all` int(11) NOT NULL,
          `product_update` int(11) NOT NULL,
          `orders_add_all` int(11) NOT NULL,
          `orders_add` int(11) NOT NULL,
          `orders_update` int(11) NOT NULL,
          `date_add` datetime NOT NULL,
          `date_end` datetime NOT NULL,
          PRIMARY KEY (`id_sync`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_sync_error` (
          `id_error` int(11) NOT NULL AUTO_INCREMENT,
          `id_sync` int(11) NOT NULL,
          `type` int(11) NOT NULL,
          `id` varchar(20) NOT NULL,
          `ida` int(11) NOT NULL,
          `status` int(11) NOT NULL,
          `body` text NOT NULL,
          `date_add` datetime NOT NULL,
          PRIMARY KEY (`id_error`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_products` (
            `id_ep` int(11) NOT NULL AUTO_INCREMENT,
            `id_product` int(11) NOT NULL,
            `price` tinyint(1) NOT NULL,
            `quantity` tinyint(1) NOT NULL,
            `reference` tinyint(1) NOT NULL,
            `ean` tinyint(1) NOT NULL,
            `description` tinyint(1) NOT NULL,
            `description_short` tinyint(1) NOT NULL,
            `name` tinyint(1) NOT NULL,
            `images` tinyint(1) NOT NULL,
            `attributes` tinyint(1) NOT NULL,
            `status` tinyint(1) NOT NULL,
            `date_add` datetime NOT NULL,
            `date_upd` datetime,
            PRIMARY KEY (`id_ep`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_order_address_inv` (
          `id_address` int(11) NOT NULL AUTO_INCREMENT,
          `id_order` int(11) NOT NULL,
          `type` varchar(255) NOT NULL,
          `nip` varchar(15) DEFAULT NULL,
          `company_name` varchar(255) DEFAULT NULL,
          `firstname` varchar(100) DEFAULT NULL,
          `lastname` varchar(100) DEFAULT NULL,
          `address` varchar(150) NOT NULL,
          `zip` varchar(10) NOT NULL,
          `city` varchar(100) NOT NULL,
          `country` varchar(10) NOT NULL,
          `phone` int(11) NOT NULL,
          `date_add` datetime NOT NULL,
          PRIMARY KEY (`id_address`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_products_sync` (
          `id_ps` int(11) NOT NULL AUTO_INCREMENT,
          `id_sync` int(11) NOT NULL,
          `id_product` int(11) NOT NULL,
          `id_product_attribute` int(11) NOT NULL DEFAULT 0,
          `type` varchar(255) NOT NULL,
          `error` varchar(15) DEFAULT NULL,
          `date_add` datetime NOT NULL,
          `date_upd` datetime NOT NULL,
          PRIMARY KEY (`id_ps`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_first_sync` (
          `id_fs` int(11) NOT NULL AUTO_INCREMENT,
          `externalId` varchar(125) NOT NULL,
          `id_product` int(11) NOT NULL DEFAULT 0,
          `id_product_attribute` int(11) NOT NULL DEFAULT 0,
          `id_product_shop` int(11) NOT NULL DEFAULT 0,
          `ean` varchar(125) NULL,
          `sku` varchar(125) NULL,
          `checked` tinyint(1) NULL default 0,
          `date_add` datetime NOT NULL,
          `date_upd` datetime NOT NULL,
          PRIMARY KEY (`id_fs`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_delivery_prices` (
          `id_dp` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL,
          `date_add` datetime NOT NULL,
          `date_upd` datetime NOT NULL,
          PRIMARY KEY (`id_dp`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_product_delete` (
            `id_epd` int(11) NOT NULL AUTO_INCREMENT,
            `id_product` int(11) NOT NULL,
            `id_product_attribute` int(1) NOT NULL,
            `id_sync` tinyint(1) NOT NULL,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_epd`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erli_product_search` (
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

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
