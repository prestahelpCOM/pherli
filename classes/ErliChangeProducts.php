<?php

class ErliChangeProducts
{

    /**
     * get info list of product
     * @param $id_product
     * @return array|bool|object|null
     */
    public static function getProducts($id_product)
    {
        return DB::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_products` WHERE `id_product` = '.(int)$id_product);
    }

    /**
     * add new product of list
     * @param $id_product
     * @return bool
     */
    public static function addProduct($id_product)
    {
        return  (bool)Db::getInstance()->insert('erli_products', array(
            'id_product' => (int)$id_product,
            'price' => 0,
            'quantity' => 0,
            'reference' => 0,
            'ean' => 0,
            'description' => 0,
            'description_short' => 0,
            'name' => 0,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * update product params to updating to erli
     * @param $id_product
     * @param $field_name
     * @param int $field_value
     * @return bool
     */
    public static function updateProductField($id_product, $field_name, $field_value = 1)
    {
        return (bool)Db::getInstance()->update('erli_products', array(
            $field_name => $field_value,
            'date_upd' => date('Y-m-d H:i:s'),
        ), 'id_product = '.(int)$id_product);
    }

}
