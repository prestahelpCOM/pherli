<?php

require_once __DIR__.'/ErliApi.php';

class ErliOrder
{

    /**
     * @param string $id_erli - erli order ID
     * @return array
     */
    public static function checkOrderExist($id_payload)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_order` WHERE `id_payload` = "'.$id_payload.'"');
    }

    /**
     * @param array $data
     * @return int
     */
    public static function addOrder($data)
    {
        Db::getInstance()->insert('erli_order', array(
            'id_erli' => $data['id'],
            'total' => $data['total'],
            'id_payload' => $data['id_payload'],
            'date_add' => date('Y-m-d H:i:s', strtotime($data['created'])),
        ));
        return (int)Db::getInstance()->Insert_ID();
    }

    /**
     * @param int $id_order
     * @param array $data
     * @return bool
     */
    public static function addAddress($id_order, $data)
    {
        return Db::getInstance()->insert('erli_order_address', array(
            'id_order' => (int)$id_order,
            'email' => $data['email'],
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'address' => $data['address'],
            'zip' => $data['zip'],
            'city' => $data['city'],
            'country' => $data['country'],
            'phone' => $data['phone'],
            'date_add' => date('Y-m-d H:i:s', strtotime($data['created'])),
        ));
    }

    /**
     * @param int $id_order
     * @param array $data
     * @return bool
     */
    public static function addInvoiceAddress($id_order, $data)
    {
        return Db::getInstance()->insert('erli_order_address_inv', array(
            'id_order' => (int)$id_order,
            'type' => $data['type'],
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'nip' => $data['nip'],
            'company_name' => $data['company_name'],
            'address' => $data['address'],
            'zip' => $data['zip'],
            'city' => $data['city'],
            'country' => $data['country'],
            'date_add' => date('Y-m-d H:i:s', strtotime($data['created'])),
        ));
    }

    /**
     * @param int $id_order
     * @param array $data
     * @return bool
     */
    public static function addDelivery($id_order, $data)
    {
        return Db::getInstance()->insert('erli_order_delivery', array(
            'id_order' => (int)$id_order,
            'name' => $data['name'],
            'typeId' => $data['typeId'],
            'price' => $data['price'],
            'cod' => $data['cod'],
            'pickupPlace' => $data['pickupPlace'],
            'date_add' => date('Y-m-d H:i:s', strtotime($data['created'])),
        ));
    }

    /**
     * @param int $id_order
     * @param array $data
     * @return bool
     */
    public static function addItems($id_order, $data)
    {
        return Db::getInstance()->insert('erli_order_items', array(
            'id_order' => (int)$id_order,
            'id_erli' => $data['id_erli'],
            'externalId' => $data['externalId'],
            'quantity' => $data['quantity'],
            'unitPrice' => $data['unitPrice'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'sku' => $data['sku'],
            'date_add' => date('Y-m-d H:i:s', strtotime($data['created'])),
        ));
    }

    /**
     * @param int $id_order
     * @param array $data
     * @return bool
     */
    public static function addMessage($id_order, $data)
    {
        return Db::getInstance()->insert('erli_order_message', array(
            'id_order' => (int)$id_order,
            'message' => $data['message'],
            'date_add' => date('Y-m-d H:i:s', strtotime($data['created'])),
        ));
    }

    /**
     * @param int $id_order
     * @param array $data
     * @return bool
     */
    public static function addPayment($id_order, $data)
    {
        return Db::getInstance()->insert('erli_order_payment', array(
            'id_order' => (int)$id_order,
            'payment_id' => $data['payment_id'],
            'date_add' => date('Y-m-d H:i:s', strtotime($data['created'])),
        ));
    }

    /**
     * @param int $id_order
     * @param array $data
     * @return bool
     */
    public static function addStatus($id_order, $data)
    {
        return Db::getInstance()->insert('erli_order_status', array(
            'id_order' => (int)$id_order,
            'status' => $data['status'],
            'date_add' => date('Y-m-d H:i:s', strtotime($data['created'])),
        ));
    }

    public static function setIdOrderShop($id_order, $id_order_shop)
    {
        return Db::getInstance()->update('erli_order', array(
            'id_order_shop' => (int)$id_order_shop,
        ), 'id_order = '.(int)$id_order);
    }

    public static function getOrderList()
    {
        $orders = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_order` ORDER BY `date_add` DESC');
        if (!empty($orders)) {
            foreach ($orders as &$order) {
                $order['delivery'] = self::getOrderDelivery((int)$order['id_order']);
                $order['customer'] = self::getOrderCustomer((int)$order['id_order']);
            }
        }
        return $orders;
    }

    public static function getOrder($id_order)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_order` WHERE `id_order` = '.(int)$id_order);
    }

    public static function getOrderAddress($id_order)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_order_address` WHERE `id_order` = '.(int)$id_order);
    }

    public static function getOrderAddressInvoice($id_order)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_order_address_inv` WHERE `id_order` = '.(int)$id_order);
    }

    public static function getOrderDelivery($id_order)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_order_delivery` WHERE `id_order` = '.(int)$id_order);
    }

    public static function getOrderCustomer($id_order)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_order_address` WHERE `id_order` = '.(int)$id_order);
    }

    public static function getOrderMessage($id_order)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_order_message` WHERE `id_order` = '.(int)$id_order);
    }

    public static function getOrderPayment($id_order)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_order_payment` WHERE `id_order` = '.(int)$id_order);
    }

    public static function getOrderItems($id_order)
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_order_items` WHERE `id_order` = '.(int)$id_order);
    }

    public static function getOrderByIdShop($id_order_shop)
    {
        return Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_order` WHERE `id_order_shop` = '.(int)$id_order_shop);
    }

    public static function getOrdersList()
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_order` ORDER BY `id_order_shop` ASC');
    }

    public static function getOrderAllStatus($id_order)
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_order_status` WHERE `id_order` = '.(int)$id_order.' ORDER BY `date_add` ASC');
    }

    public static function getStatusName($status)
    {
        switch ($status) {
            case 'orderCreated':$name = 'Utworzono zamówienie';break;
            case 'paid':$name = 'Opłacone';break;
            default:
                $name = 'b/d';
                break;
        }
        return $name;
    }

    public static function getOrderPaymentName($id_payment, $orderId, $configuration)
    {
        $name = 'Płatność on-line na Erli.pl';
        if ($id_payment == 1) {
            $name = 'Płatność za pobraniem na Erli.pl';
        } else {
            $checkPayment = (int)Configuration::get('ERLI_CHECK_PAYMENT');
            if ($checkPayment) {
                $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
                $paymentInfo = $erliApi->getPaymentsOrderInfo($orderId);
                if ($paymentInfo['status'] == 200) {
                    $body = json_decode($paymentInfo['body']);
                    $body = $body[0];
                    $name = 'Erli.pl - ' . $body->operator . ' - ' . $body->methodName;
                } else {
                    self::getOrderPaymentName($id_payment, $orderId, $configuration);
                }
            } else {
                $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
                $paymentInfo = $erliApi->getPaymentsOrderInfo($orderId);
                if ($paymentInfo['status'] == 200) {
                    $body = json_decode($paymentInfo['body']);
                    $body = $body[0];
                    if ($body->methodName == 'Przelew tradycyjny') {
                        $name = $body->methodName.' na Erli.pl';
                    }
                } else {
                    self::getOrderPaymentName($id_payment, $orderId, $configuration);
                }
            }
        }
        return $name;
    }

}
