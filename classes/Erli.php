<?php

class Erli
{
    public static $active = 1;

    public static $inactive = 1;

    /**
     * add product to export
     * @param int $id_product
     * @param int $deliveryTime
     * @param int $active
     * @return bool
     */
    public static function addProductToErli($id_product, $deliveryTime = null, $deliveryPrice = null, $active = 1)
    {
        Db::getInstance()->insert('erli_product', array(
            'id_product' => (int)$id_product,
            'active' => (int)$active,
            'date_add' => date('Y-m-d H:i:s')
        ));
        $id_ep = (int)Db::getInstance()->Insert_ID();
        if ($deliveryPrice != null) {
            Db::getInstance()->update('erli_product', array(
                'deliveryPrice' => $deliveryPrice,
                'date_upd' => date('Y-m-d H:i:s')
            ), 'id_ep = '.(int)$id_ep);
        }
        if ($deliveryTime != null) {
            Db::getInstance()->update('erli_product', array(
                'deliveryTime' => (int)$deliveryTime,
                'date_upd' => date('Y-m-d H:i:s')
            ), 'id_ep = '.(int)$id_ep);
        }
        if ((int)$id_ep == 0) {
            return false;
        }
        return true;
    }

    /**
     * set product active to export
     * @param int $id_product
     * @param int $deliveryTime
     * @param int $active
     * @return bool
     */
    public static function updateProductInErli($id_product, $deliveryTime = null, $deliveryPrice = null, $active = 1)
    {
        $res = false;
        $res &= Db::getInstance()->update('erli_product', array(
            'active' => $active,
            'date_upd' => date('Y-m-d H:i:s')
        ), 'id_product = ' . (int)$id_product);
        if ($deliveryPrice != null) {
            $res &= Db::getInstance()->update('erli_product', array(
                'deliveryPrice' => $deliveryPrice,
                'date_upd' => date('Y-m-d H:i:s')
            ), 'id_product = ' . (int)$id_product);
        }
        if ($deliveryTime != null) {
            $res &= Db::getInstance()->update('erli_product', array(
                'deliveryTime' => (int)$deliveryTime,
                'date_upd' => date('Y-m-d H:i:s')
            ), 'id_product = ' . (int)$id_product);
        }
        return $res;
    }

    /**
     * check product to export
     * @param int $id_product
     * @return array|bool|object|null
     */
    public static function checkProductInErli($id_product)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_product` WHERE `id_product` = '.(int)$id_product);
    }

    /**
     * update product status
     * @param int $id_product
     * @param int $status
     * @return bool
     */
    public static function updateStatus($id_product, $status)
    {
        return Db::getInstance()->update('erli_product', array(
            'status' => (int)$status,
            'date_upd' => date('Y-m-d H:i:s')
        ), 'id_product = '.(int)$id_product);
    }

    /**
     * set disabled product in erli
     * @param int $id_product
     * @return bool
     */
    public static function disableProductInErli($id_product)
    {
        return Db::getInstance()->update('erli_product', array(
            'active' => 0,
            'deleted' => 1,
            'date_upd' => date('Y-m-d H:i:s')
        ), 'id_product = '.(int)$id_product);
    }

    public static function setDisableProductInErli($id_product)
    {
        return Db::getInstance()->update('erli_product', array(
            'deleted' => 0,
            'date_upd' => date('Y-m-d H:i:s')
        ), 'id_product = '.(int)$id_product);
    }

    public static function updateInErliProduct($id_product, $inErli = 1)
    {
        return Db::getInstance()->update('erli_product', array(
            'inErli' => $inErli,
            'date_upd' => date('Y-m-d H:i:s')
        ), 'id_product = '.(int)$id_product);
    }

    public static function getProductByErliExist($inErli = 0)
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_product` WHERE `active` = 1 AND `inErli` = '.(int)$inErli.' ORDER BY `id_product` ASC');
    }

    public static function getProductByErliExistWithLimit($inErli = 0, $limit = 55)
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_product` WHERE `active` = 1 AND `inErli` = '.(int)$inErli.' AND `skiped` = 0 ORDER BY `id_product` ASC LIMIT 0, '.(int)$limit);
    }

    public static function getProductAllToUpdate($limit = 55)
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_product` WHERE `inErli` = 1 AND `skiped` = 0 AND `updated` = 1 ORDER BY `id_product` ASC LIMIT 0, '.(int)$limit);
    }

    public static function setProductAllUpdated($id_product)
    {
        return (bool)Db::getInstance()->update('erli_product', array(
            'updated' => 1,
        ), 'id_product = '.(int)$id_product);
    }

    public static function getAllProductErli()
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_product` ORDER BY `id_product` ASC');
    }

    public static function deleteProductErli($id_product)
    {
        return Db::getInstance()->delete('erli_product', 'id_product = '.(int)$id_product);
    }

    /**
     * @param int $active
     * @param int $inErli
     * @return array|bool|mysqli_result|PDOStatement|resource|null
     */
    public static function getProductByErliExistActive(int $active = 0, int $inErli = 0)
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_product` WHERE `active` = '.(int)$active.' AND `inErli` = '.(int)$inErli.' ORDER BY `id_product` ASC');
    }

    public static function getProductToUnActivated()
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_product` WHERE `deleted` = 1 ORDER BY `id_product` ASC');
    }

    public static function getProductToUpdate()
    {
        $products = array();
        $product_in_erli = Erli::getProductByErliExist(1);
        if (!empty($product_in_erli)) {
            foreach ($product_in_erli as $item) {
                $productUpdate = Erli::getProduct($item['id_product']);
                if (!empty($productUpdate)) {
                    if ($productUpdate['price'] == 1 ||
                        $productUpdate['quantity'] == 1 ||
                        $productUpdate['reference'] == 1 ||
                        $productUpdate['ean'] == 1 ||
                        $productUpdate['description'] == 1 ||
                        $productUpdate['name'] == 1 ||
                        $productUpdate['description_short'] == 1 ||
                        $productUpdate['status'] == 1
                    ) {
                        $products[] = $item;
                    }
                }
            }
        }
        $product_in_erli = Erli::getProductByErliExistActive(0, 0);
        if (!empty($product_in_erli)) {
            foreach ($product_in_erli as $item) {
                $productUpdate = Erli::getProduct($item['id_product']);
                if (!empty($productUpdate)) {
                    if ($productUpdate['price'] == 1 ||
                        $productUpdate['quantity'] == 1 ||
                        $productUpdate['reference'] == 1 ||
                        $productUpdate['ean'] == 1 ||
                        $productUpdate['description'] == 1 ||
                        $productUpdate['name'] == 1 ||
                        $productUpdate['description_short'] == 1 ||
                        $productUpdate['status'] == 1
                    ) {
                        $products[] = $item;
                    }
                }
            }
        }
        $product_in_erli = Erli::getProductByErliExistActive(0, 1);
        if (!empty($product_in_erli)) {
            foreach ($product_in_erli as $item) {
                $productUpdate = Erli::getProduct($item['id_product']);
                if (!empty($productUpdate)) {
                    if ($productUpdate['price'] == 1 ||
                        $productUpdate['quantity'] == 1 ||
                        $productUpdate['reference'] == 1 ||
                        $productUpdate['ean'] == 1 ||
                        $productUpdate['description'] == 1 ||
                        $productUpdate['name'] == 1 ||
                        $productUpdate['description_short'] == 1 ||
                        $productUpdate['status'] == 1
                    ) {
                        $products[] = $item;
                    }
                }
            }
        }
        return $products;
    }

    /**
     * @param int $id_product
     * @return bool
     */
    public static function setProductUpdate($id_product)
    {
        return (bool)Db::getInstance()->update('erli_products', array(
            'price' => 0,
            'quantity' => 0,
            'reference' => 0,
            'ean' => 0,
            'description' => 0,
            'description_short' => 0,
            'name' => 0,
            'images' => 0,
            'attributes' => 0,
            'status' => 0,
        ), 'id_product = '.(int)$id_product);
    }

    public static function setProductInsert($id_product)
    {
        return (bool)Db::getInstance()->insert('erli_products', array(
            'id_product' => (int)$id_product,
            'price' => 0,
            'quantity' => 0,
            'reference' => 0,
            'ean' => 0,
            'description' => 0,
            'description_short' => 0,
            'name' => 0,
            'images' => 0,
            'attributes' => 0,
            'status' => 0,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        ));
    }

    public static function getProduct($id_product)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_products` WHERE `id_product` = '.(int)$id_product);
    }

    public static function getAllProduct()
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_products` ORDER BY `id_product` ASC');
    }

    /**
     * @param $id_product
     * @param $field
     * @param $value
     * @return bool
     */
    public static function setProducts($id_product, $field, $value)
    {
        return Db::getInstance()->update('erli_products', array(
            $field => $value,
        ), 'id_product = '.(int)$id_product);
    }

    /**
     * @param $id_product
     * @return bool
     */
    public static function deleteProduct($id_product)
    {
        return Db::getInstance()->delete('erli_products', 'id_product = '.(int)$id_product);
    }

    public static function checkCategoryInErli($id_category)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_category` WHERE `id_category` = '.(int)$id_category);
    }

    public static function getCategoryList()
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_category` WHERE `active` = 1 ORDER BY `id_category` ASC');
    }

    public static function addCategoryToErli($id_category, $active = 1, $status = 1)
    {
        return Db::getInstance()->insert('erli_category', array(
            'id_category' => (int)$id_category,
            'active' => (int)$active,
            'status' => (int)$status,
            'date_add' => date('Y-m-d H:i:s')
        ));
    }

    public static function updateCategoryToErli($id_category, $active = 1)
    {
        return Db::getInstance()->update('erli_category', array(
            'active' => $active,
            'date_upd' => date('Y-m-d H:i:s')
        ), 'id_category = '.(int)$id_category);
    }

    public static function disableCategoryInErli($id_category)
    {
        return Db::getInstance()->update('erli_category', array(
            'active' => 0,
            'date_upd' => date('Y-m-d H:i:s')
        ), 'id_category = '.(int)$id_category);
    }

    public static function getDeliveryList()
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_delivery` ORDER BY `id_delivery` ASC');
    }

    public static function getDelivery($id)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_delivery` WHERE `id` = "'.$id.'"');
    }

    public static function getDeliveryByCarrierId($id_carrier)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_delivery` WHERE `id_carrier` = '.(int)$id_carrier);
    }

    public static function getDeliveryByName($name)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_delivery` WHERE `name` = "'.$name.'"');
    }

    public static function addDelivery($id, $name, $vendor, $cod = 0)
    {
        return Db::getInstance()->insert('erli_delivery', array(
            'id' => $id,
            'name' => $name,
            'vendor' => $vendor,
            'cod' => (int)$cod,
        ));
    }

    public static function updateDeliveryMap($id_delivery, $id_carrier)
    {
        return Db::getInstance()->update('erli_delivery', array(
            'id_carrier' => (int)$id_carrier,
        ), 'id_delivery = '.(int)$id_delivery);
    }

    public static function updateDeliveryVendor($id_delivery, $vendor)
    {
        return Db::getInstance()->update('erli_delivery', array(
            'vendor' => $vendor,
        ), 'id_delivery = '.(int)$id_delivery);
    }

    public static function getIdCarrierByDelivery($type)
    {
        return Db::getInstance()->getValue('SELECT `id_carrier` FROM `'._DB_PREFIX_.'erli_delivery` WHERE `id` = "'.$type.'"');
    }

    public static function getIdCarrierByDeliveryName($name)
    {
        return Db::getInstance()->getValue('SELECT `id_carrier` FROM `'._DB_PREFIX_.'erli_delivery` WHERE `name` = "'.$name.'"');
    }

    public static function getVendorByDelivery($type)
    {
        return Db::getInstance()->getValue('SELECT `vendor` FROM `'._DB_PREFIX_.'erli_delivery` WHERE `id` = "'.$type.'"');
    }

    public static function getDeliveryPrices($name)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_delivery_prices` WHERE `name` = "'.$name.'"');
    }

    public static function addDeliveryPrices($name)
    {
        return Db::getInstance()->insert('erli_delivery_prices', array(
            'name' => $name,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        ));
    }

    public static function getDeliveryPriceList()
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_delivery_prices`');
    }

    public static function isDisabledProductAttributeInErli($id_product, $id_attribute = 0)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_product_delete` WHERE `id_product` = '.(int)$id_product.' AND `id_product_attribute` = '.(int)$id_attribute);
    }

    public static function disableProductAttributeInErli($id_product, $id_attribute = 0)
    {
        return (bool)Db::getInstance()->insert('erli_product_delete', array(
            'id_product' => $id_product,
            'id_product_attribute' => $id_attribute,
            'date_add' => date('Y-m-d H:i:s')
        ));
    }

    public static function getAllDisableProductAttributeInErli()
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_product_delete` ORDER BY `id_product` ASC');
    }

    public static function getProductDisableProductAttributeInErli($id_product)
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_product_delete` WHERE `id_product` = '.(int)$id_product.' ORDER BY `id_product` ASC');
    }

    public static function deleteDisableProductAttributeInErli($id_product, $id_attribute = 0)
    {
        return (bool)Db::getInstance()->delete('erli_product_delete', 'id_product = '.(int)$id_product.' AND id_product_attribute = '.(int)$id_attribute);
    }

}
