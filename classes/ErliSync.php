<?php

class ErliSync
{

    /**
     * Insert new sync info
     * @param int $type - 1 orders, 2 products
     * @return int id sync
     */
    public static function add($type = 1)
    {
        Db::getInstance()->insert('erli_sync', array(
            'type' => (int)$type,
            'product_add_all' => 0,
            'product_add' => 0,
            'product_update_all' => 0,
            'product_update' => 0,
            'orders_add_all' => 0,
            'orders_add' => 0,
            'orders_update' => 0,
            'date_add' => date('Y-m-d H:i:s'),
            'date_end' => '0000-00-00 00:00:00',
        ));
        return (int)Db::getInstance()->Insert_ID();
    }

    public static function updateProductAddAll($id_sync, $count)
    {
        return Db::getInstance()->update('erli_sync', array(
            'product_add_all' => (int)$count
        ), 'id_sync = '.(int)$id_sync);
    }

    public static function updateProductAdd($id_sync, $count)
    {
        return Db::getInstance()->update('erli_sync', array(
            'product_add' => (int)$count
        ), 'id_sync = '.(int)$id_sync);
    }

    public static function updateProductUpdateAll($id_sync, $count)
    {
        return Db::getInstance()->update('erli_sync', array(
            'product_update_all' => (int)$count
        ), 'id_sync = '.(int)$id_sync);
    }

    public static function updateProductUpdate($id_sync, $count)
    {
        return Db::getInstance()->update('erli_sync', array(
            'product_update' => (int)$count
        ), 'id_sync = '.(int)$id_sync);
    }

    public static function updateDateEnd($id_sync)
    {
        return Db::getInstance()->update('erli_sync', array(
            'date_end' => date('Y-m-d H:i:s'),
        ), 'id_sync = '.(int)$id_sync);
    }

    public static function updateOrderAddAll($id_sync, $count)
    {
        return Db::getInstance()->update('erli_sync', array(
            'orders_add_all' => (int)$count
        ), 'id_sync = '.(int)$id_sync);
    }

    public static function updateOrderAdd($id_sync, $count)
    {
        return Db::getInstance()->update('erli_sync', array(
            'orders_add' => (int)$count
        ), 'id_sync = '.(int)$id_sync);
    }

    public static function updateOrderUpdate($id_sync, $count)
    {
        return Db::getInstance()->update('erli_sync', array(
            'orders_update' => (int)$count
        ), 'id_sync = '.(int)$id_sync);
    }

    public static function getProductAdd($id_sync)
    {
        return Db::getInstance()->getValue('SELECT `product_add` FROM `'._DB_PREFIX_.'erli_sync` WHERE `id_sync` = '.(int)$id_sync);
    }

    public static function getProductAddAll($id_sync)
    {
        return Db::getInstance()->getValue('SELECT `product_add_all` FROM `'._DB_PREFIX_.'erli_sync` WHERE `id_sync` = '.(int)$id_sync);
    }

    public static function getProductUpdate($id_sync)
    {
        return Db::getInstance()->getValue('SELECT `product_update` FROM `'._DB_PREFIX_.'erli_sync` WHERE `id_sync` = '.(int)$id_sync);
    }

    public static function getProductUpdateAll($id_sync)
    {
        return Db::getInstance()->getValue('SELECT `product_update_all` FROM `'._DB_PREFIX_.'erli_sync` WHERE `id_sync` = '.(int)$id_sync);
    }

    public static function getOrderAdd($id_sync)
    {
        return Db::getInstance()->getValue('SELECT `orders_add` FROM `'._DB_PREFIX_.'erli_sync` WHERE `id_sync` = '.(int)$id_sync);
    }

    public static function getOrderUpdate($id_sync)
    {
        return Db::getInstance()->getValue('SELECT `orders_update` FROM `'._DB_PREFIX_.'erli_sync` WHERE `id_sync` = '.(int)$id_sync);
    }

    public static function addSyncError($id_sync, $type, $id, $status, $body, $ida = 0)
    {
        return Db::getInstance()->insert('erli_sync_error', array(
            'id_sync' => (int)$id_sync,
            'type' => (int)$type,
            'id' => (int)$id,
            'ida' => (int)$ida,
            'status' => (int)$status,
            'body' => $body,
            'date_add' => date('Y-m-d H:i:s'),
        ));
    }

    public static function getSyncList($perPage = 0)
    {
        $limit = '';
        if ((int)$perPage > 0) {
            $limit = ' LIMIT 0, '.$perPage;
        }
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_sync` ORDER BY `date_add` DESC'.$limit);
    }

    public static function getSyncError($id_sync)
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_sync_error` WHERE `id_sync` = '.(int)$id_sync);
    }

    public static function getSyncInfo($id_sync)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_sync` WHERE `id_sync` = '.(int)$id_sync);
    }

    public static function getErrorTypeName($type)
    {
        switch ($type) {
            case 'conflict':$name = 'Konflikt';break;

            default:$name = '';break;
        }
        return $name;
    }

    public static function getErrorDetail($type)
    {
        switch ($type) {
            case 'unique key duplication':$name = 'ID produktu jest powielone';break;

            default:$name = '';break;
        }
        return $name;
    }

    public static function getErrorDetailName($type)
    {
        $errors = array();
        if (is_array($type) && !empty($type)) {
            foreach ($type as $key => $item) {
                if ($key == 'payload') {
                    if (is_array($item)) {
                        foreach ($item as $item2) {
                            if (is_array($item2)) {
                                foreach ($item2 as $item3) {
                                    if (is_array($item3)) {
                                        foreach ($item3 as $item4) {
                                            if (is_array($item4)) {

                                            } else {
                                                $errors[] = $item4;
                                            }
                                        }
                                    } else {
                                        $errors[] = $item3;
                                    }
                                }
                            } else {
                                $errors[] = $item2;
                            }
                        }
                    } else {
                        $errors[] = $item;
                    }
                }
            }
        }
        return $errors;
    }

    public static function getErrorNameTrans($name)
    {
        return '';
    }

    /**
     * @param $id_sync
     * @return array
     */
    public static function getProductToSync($id_sync, $error = 0)
    {
        $no_error = array(404, 400);
        if ($error == 0) {
//            return Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'erli_products_sync` WHERE `id_sync` = ' . (int)$id_sync.' AND `error` != 409');
            return Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'erli_products_sync` WHERE `id_sync` = ' . (int)$id_sync);//.' AND `error` != 409');
        } else {
            return Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'erli_products_sync` WHERE `id_sync` = ' . (int)$id_sync.' AND `error` NOT IN (404, 400)');
        }
    }

    /**
     * @param $id_product
     * @param $id_product_attribute
     * @param $id_sync
     * @return bool
     */
    public static function removeProductSync($id_product, $id_product_attribute, $id_sync)
    {
        return Db::getInstance()->delete('erli_products_sync', 'id_product = '.(int)$id_product.' AND id_sync = '.(int)$id_sync.' AND id_product_attribute = '.(int)$id_product_attribute);
    }

    /**
     * @param $id_product
     * @param $id_product_attribute
     * @param $id_sync
     * @return bool
     */
    public static function getProductToSyncId($id_product, $id_product_attribute, $id_sync)
    {
        $return = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_products_sync` WHERE `id_sync` = '.(int)$id_sync.' AND id_product = '.(int)$id_product.' AND id_product_attribute = '.(int)$id_product_attribute);
        if (!empty($return)) {
            return true;
        }
        return false;
    }

    /**
     * @param $id_product
     * @param $id_product_attribute
     * @param $id_sync
     * @param $error
     * @return bool
     */
    public static function updateProductSync($id_product, $id_product_attribute, $id_sync, $error)
    {
        return Db::getInstance()->update('erli_products_sync', array(
            'error' => $error,
            'date_upd' => date('Y-m-d H:i:s'),
        ),'id_product = '.(int)$id_product.' AND id_sync = '.(int)$id_sync.' AND id_product_attribute = '.(int)$id_product_attribute);
    }

    /**
     * @param $id_sync - int
     * @param $id_product - int
     * @return bool
     */
    public static function deleteProductSyncError($id_sync, $id_product)
    {
        return Db::getInstance()->delete('erli_sync_error', 'id = '.(int)$id_product.' AND id_sync = '.(int)$id_sync);
    }

    /**
     * @param $id_sync
     * @param $id_product
     * @param $id_product_attribute
     * @return bool
     */
    public static function deleteProductAttribSyncError($id_sync, $id_product, $id_product_attribute)
    {
        return Db::getInstance()->delete('erli_sync_error', 'id = '.(int)$id_product.' AND ida = '.(int)$id_product_attribute.' AND id_sync = '.(int)$id_sync);
    }


}
