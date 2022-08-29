<?php

use PrestaShop\PrestaShop\Adapter\CoreException;
use PrestaShop\PrestaShop\Adapter\ServiceLocator;
use PrestaShop\PrestaShop\Adapter\StockManager;

require_once __DIR__.'/../../classes/ErliOrder.php';
require_once __DIR__.'/../../classes/Erli.php';

class AdminErliOrdersController extends ModuleAdminController
{
    private $configuration;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'AdminErliOrders';
        parent::__construct();

        $this->configuration['name'] = 'pherli';
        $this->configuration['version'] = '1.0.1';
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
    }

    public function postProcess()
    {
        parent::postProcess();
        if (Tools::getIsset('addPayment')) {
            $id_order = (int)Tools::getValue('addPayment');
            if ($id_order > 0) {
                $order = ErliOrder::getOrder((int)$id_order);
                $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
                $paymentInfo = $erliApi->getPaymentsOrderInfo($order['id_payload']);
                if ($paymentInfo['status'] == 200) {
                    $body = json_decode($paymentInfo['body']);
                    if (!empty($body)) {
                        $body = $body[0];
                        Db::getInstance()->update('erli_order_payment', array(
                            'type' => $body->methodName,
                            'operator' => $body->operator,
                        ), 'id_order = '.(int)$id_order);
                    }
                }
            }
            Tools::redirectLink($_SERVER['HTTP_REFERER']);
        }

        if (Tools::getIsset('details')) {
            $id_order = (int)Tools::getValue('details');
            $order = ErliOrder::getOrder((int)$id_order);
            if (!empty($order)) {
                $order['total_pay'] = number_format($order['total'] / 100, 2, ',', '') . ' ' . $this->context->currency->sign;
                $in_shop = 0;
                if ($order['id_order_shop'] > 0) {
                    $in_shop = 1;
                }
                $order['in_shop'] = $in_shop;
            }
            $delivery = ErliOrder::getOrderDelivery((int)$id_order);
            if (!empty($delivery)) {
                $delivery['total_pay'] = number_format($delivery['price'] / 100, 2, ',', '') . ' ' . $this->context->currency->sign;
            }
            $address = ErliOrder::getOrderAddress((int)$id_order);
            $message = ErliOrder::getOrderMessage((int)$id_order);
            $items = ErliOrder::getOrderItems((int)$id_order);
            $total_product = 0;
            if (!empty($items)) {
                foreach ($items as &$item) {
                    $price = number_format($item['unitPrice'] / 100, 2, '.', '');
                    $proTotal = $price * $item['quantity'];
                    $item['total'] = number_format($proTotal, 2, ',', '') . ' ' . $this->context->currency->sign;
                    $item['unit'] = number_format($price, 2, ',', '') . ' ' . $this->context->currency->sign;
                    $total_product += $proTotal;
                    $product = new Product((int)$item['externalId'], false, (int)$this->context->cookie->id_lang);
                    $cover = Product::getCover((int)$product->id);
                    $image = $this->context->link->getImageLink($product->link_rewrite, $cover['id_image'], 'small_default');
                    $item['image'] = $image;
                }
            }
            $order['total_product'] = number_format($total_product, 2, ',', '') . ' ' . $this->context->currency->sign;

            if (Tools::getIsset('api')) {
                $detail = $this->getApiOrderInfo($order['id_payload']);
                $detail2 = json_decode($detail['body']);
                echo "<pre>";
                print_r($detail2);
                echo "</pre>";
                exit();
            }

            $payment = ErliOrder::getOrderPayment((int)$id_order);

            $this->context->smarty->assign(array(
                'order' => $order,
                'delivery' => $delivery,
                'address' => $address,
                'message' => $message,
                'items' => $items,
                'orderShop' => $this->context->link->getAdminLink('AdminErliOrders'),
                'payment' => $payment,
            ));
        } else if (Tools::getIsset('addOrderShop')) {
            $id_order = (int)Tools::getValue('addOrderShop');
            if ($id_order > 0) {
                $this->addOrderToShop($id_order);
            }
            Tools::redirect($_SERVER['HTTP_REFERER']);
        } else {
            $orders = $this->getOrders();

            $this->context->smarty->assign(array(
                'orders' => $orders,
                'bulkUrl' => $this->context->link->getAdminLink($this->className),
                'orderShop' => $this->context->link->getAdminLink('AdminErliOrders'),
            ));
        }
    }

    public function display()
    {
        $tpl =  'order_list.tpl';
        if (Tools::getIsset('details')) {
            $tpl = 'details.tpl';
        }
        $this->context->smarty->assign(array(
            'content' => $this->context->smarty->fetch($this->getTemplatePath() .$tpl)
        ));
        parent::display();
    }

    private function getOrders()
    {
        $orders = ErliOrder::getOrderList();
        if (!empty($orders)) {
            foreach ($orders as &$order) {
                $order['total_payment'] = number_format($order['total']/100, 2, ',', '').' '.$this->context->currency->sign;
                $in_shop = 0;
                if ($order['id_order_shop'] > 0) {
                    $in_shop = 1;
                }
                $order['in_shop'] = $in_shop;
                $order['link'] = $this->context->link->getAdminLink('AdminOrders', true, null, array('id_order' => (int)$order['id_order_shop'], 'vieworder' => ''));
                $statuses = ErliOrder::getOrderAllStatus((int)$order['id_order']);
                $status = end($statuses);
                $order['status'] = ErliOrder::getStatusName($status['status']);
            }
        }
        return $orders;
    }

    private function addOrderToShop($id_order_erli)
    {
        $context = Context::getContext();
        $name = 'pherli';
        $computingPrecision = 6;

        $orderErli = ErliOrder::getOrder((int)$id_order_erli);
        $delivery = ErliOrder::getOrderDelivery((int)$id_order_erli);
        $items = ErliOrder::getOrderItems((int)$id_order_erli);
        $payment = ErliOrder::getOrderPayment((int)$id_order_erli);
        $total_product = 0;
        $total_product_netto = 0;
        if (!empty($items)) {

            foreach ($items as &$item) {
                $ids = explode('-', $item['externalId']);
                if (count($ids) == 1) {
                    $id = $item['externalId'];
                    $ida = 0;
                } else {
                    $id = $ids[0];
                    $ida = $ids[1];
                }

                $product = new Product((int)$id, false, (int)$this->context->cookie->id_lang);
                $id_tax_rules_group = $product->id_tax_rules_group;
                $price = number_format($item['unitPrice'] / 100, 2, '.', '');
                $proTotal = $price * $item['quantity'];
                $total_product_netto += ($proTotal / (1 + $this->getTaxRate($id_tax_rules_group)));
                $item['tax'] = $this->getTaxRate($id_tax_rules_group);

                $item['total'] = number_format($proTotal, 2, ',', '') . ' ' . $this->context->currency->sign;
                $item['unit'] = number_format($price, 2, ',', '') . ' ' . $this->context->currency->sign;
                $item['unit_netto'] = number_format($price / (1 + ($item['tax'] / 100)), 2, '.', '');
                $item['unit_brutto'] = number_format($price, 2, '.', '');
                $total_product += $proTotal;
                $cover = Product::getCover((int)$product->id);
                $image = $this->context->link->getImageLink($product->link_rewrite, $cover['id_image'], 'small_default');
                $item['image'] = $image;
                $attr = $product->getAttributesResume((int)$this->context->cookie->id_lang);
                $attrib = array();
                if (!empty($attr)) {
                    foreach ($attr as $itema) {
                        $attrib[$itema['id_product'].'-'.$itema['id_product_attribute']] = $itema;
                    }
                }
                $attr_name = '';
                if ($ida > 0) {
                    $attr_name = $attrib[$id.'-'.$ida]['attribute_designation'];
                }
                $item['attr_name'] = $attr_name;

            }
        }

        $addressErli = ErliOrder::getOrderAddress((int)$id_order_erli);
        $addressInvoiceErli = ErliOrder::getOrderAddressInvoice((int)$id_order_erli);
        $message = ErliOrder::getOrderMessage((int)$id_order_erli);

        // add customer
        $crypto = ServiceLocator::get('\\PrestaShop\\PrestaShop\\Core\\Crypto\\Hashing');
        $password = Tools::passwdGen(8, 'RANDOM');
        $hash = $crypto->hash($password);
        $customer = new Customer();
        $customer->firstname = $addressErli['firstname'];
        $customer->lastname = $addressErli['lastname'];
        $customer->email = $addressErli['email'];
        $customer->secure_key = md5(uniqid(mt_rand(0, mt_getrandmax()), true));
        $customer->active = 1;
        $customer->id_guest = 1;
        $customer->id_default_group = 2;
        $customer->passwd  = $hash;
        $customer->add();

        // add address
        $address = new Address();
        $address->id_country = 14;
        $address->id_state = 0;
        $address->id_customer = (int)$customer->id;
        $address->alias = 'Mój adres na erli.pl';
        $address->firstname = $addressErli['firstname'];
        $address->lastname = $addressErli['lastname'];
        $address->address1 = $addressErli['address'];
        $address->address2 = '';
        $address->postcode = $addressErli['zip'];
        $address->city = $addressErli['city'];
        $address->other = '';
        $address->phone_mobile = '';
        $address->phone = $addressErli['phone'];
        $address->active = 1;
        $address->add();

        // invoice address
        if (!empty($addressInvoiceErli)) {
            $address2 = new Address();
            $address2->id_country = 14;
            $address2->id_state = 0;
            $address2->id_customer = (int)$customer->id;
            $address2->alias = 'Mój adres do faktury na erli.pl';
            $address2->firstname = !empty($addressInvoiceErli['firstname']) ? $addressInvoiceErli['firstname'] : ' ';
            $address2->lastname = !empty($addressInvoiceErli['lastname']) ? $addressInvoiceErli['lastname'] : ' ';
            $address2->address1 = $addressInvoiceErli['address'];
            $address2->address2 = '';
            $address2->postcode = $addressInvoiceErli['zip'];
            $address2->city = $addressInvoiceErli['city'];
            $address2->other = '';
            $address2->phone_mobile = '';

            $addressInvoicePhone = str_replace(' ', '', $addressErli['phone']);
            $addressInvoicePhone = str_replace('-', '', $addressInvoicePhone);
            $address2->phone = $addressInvoicePhone;
            $address2->active = 1;
            $address2->company = $addressInvoiceErli['company_name'];
            $address2->dni = $addressInvoiceErli['nip'];
            $address2->vat_number = $addressInvoiceErli['nip'];
            $address2->add();
        }

        $id_carrier_ref = (int)Erli::getIdCarrierByDeliveryName($delivery['name']);
        $carrier = Carrier::getCarrierByReference($id_carrier_ref);
        $id_carrier = (int)$carrier->id;

        // create cart
        $cart = new Cart();
        $cart->id_customer = $customer->id;
        $cart->id_delivery_address = (int)$address->id;
        $cart->id_address_invoice = (int)$address->id;
        $cart->id_currency = $context->currency->id;
        $cart->id_carrier = (int)$id_carrier;
        $cart->save();

        $cod = 0;
        $id_order_state = Configuration::get('ERLI_STATUS_SHOP_paid');
        $pos = strpos($delivery['typeId'], 'Cod');
        if ($pos != false && $pos >= 0) {
            $id_order_state = Configuration::get('ERLI_STATUS_SHOP_preparing');
            $cod = 1;
        }
        if ($delivery['cod'] == 1) {
            $cod = 1;
        }

        // add order
        $payment_id = 0;
        if (!empty($payment)) {
            $payment_id = (int)$payment['payment_id'];
        }
        if ($cod == 1) {
            $payment_id = 9999;
        }
        $payment_method = ErliOrder::getOrderPaymentName((int)$payment_id, $orderErli['id_payload'], $this->configuration);
        do {
            $reference = Order::generateReference();
        } while (Order::getByReference($reference)->count());

        $order = new Order();
        $order->id_carrier = (int)$id_carrier;
        $order->id_customer = (int) $customer->id;
        $order->id_address_invoice = (int) $address->id;
        $order->id_address_delivery = (int) $address->id;
        $order->id_currency = $context->currency->id;
        $order->id_lang = (int) $context->cookie->id_lang;
        $order->id_cart = $cart->id;
        $order->reference = $reference;
        $order->id_shop = (int) $this->context->shop->id;
        $order->id_shop_group = (int) $context->shop->id_shop_group;

        $order->secure_key = pSQL($customer->secure_key);
        $order->payment = $payment_method;
        if (isset($name)) {
            $order->module = $name;
        }
        $order->recyclable = 0;
        $order->gift = 0;
        $order->gift_message = '';
        $order->mobile_theme = 0;
        $order->conversion_rate = $context->currency->conversion_rate;
        $order->total_paid_real = 0;
        $order->total_products = Tools::ps_round(
            (float) $total_product_netto,
            $computingPrecision
        );
        $order->total_products_wt = Tools::ps_round(
            (float) $total_product,
            $computingPrecision
        );
        $order->total_discounts_tax_excl = 0;
        $order->total_discounts_tax_incl = 0;
        $order->total_discounts = 0;

        $total_delivery_netto = ($delivery['price'] / 100) / 1.23;
        $order->total_shipping_tax_excl = Tools::ps_round(
            (float) $total_delivery_netto,
            $computingPrecision
        );

        $order->total_shipping_tax_incl = Tools::ps_round(
            (float) $delivery['price'] / 100,
            $computingPrecision
        );
        $order->total_shipping = $order->total_shipping_tax_incl;

        $order->carrier_tax_rate = 23;

        $order->total_wrapping_tax_excl = 0;
        $order->total_wrapping_tax_incl = 0;
        $order->total_wrapping = $order->total_wrapping_tax_incl;

        $order->total_paid_tax_excl = Tools::ps_round(
            (float) (($orderErli['total'] / 100) / 1.23),
            $computingPrecision
        );
        $order->total_paid_tax_incl = Tools::ps_round(
            (float) ($orderErli['total'] / 100),
            $computingPrecision
        );
        $order->total_paid = $order->total_paid_tax_incl;
        $order->round_mode = Configuration::get('PS_PRICE_ROUND_MODE');
        $order->round_type = Configuration::get('PS_ROUND_TYPE');

        $order->invoice_date = '0000-00-00 00:00:00';
        $order->delivery_date = '0000-00-00 00:00:00';
        $result = $order->add();

        if ($result) {
            ErliOrder::setIdOrderShop((int)$orderErli['id_order'], (int)$order->id);
            $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
            $dane['externalOrderId'] = $order->id;
            $erliApi->updateOrder($orderErli['id_payload'], $dane);
        }

        // add products
        $order_detail = new OrderDetail();
        if (!empty($items)) {
            $use_taxes = true;
            $id_warehouse = 0;
            foreach ($items as $product) {
                $ids = explode('-', $product['externalId']);
                if (count($ids) == 1) {
                    $id = $product['externalId'];
                    $ida = 0;
                } else {
                    $id = $ids[0];
                    $ida = $ids[1];
                }
                $order_detail->id = null;
                $order_detail->id_order = (int)$order->id;
                $order_detail->product_id = (int) $id;
                $order_detail->product_attribute_id = (int)$ida;
                $order_detail->id_customization = 0;
                $name = $product['name'];
                if (!empty($item['attr_name'])) {
                    $name .= ' '.$item['attr_name'];
                }
                $order_detail->product_name = $name;
                $order_detail->product_quantity = (int) $product['quantity'];
                $order_detail->product_ean13 = empty($product['ean13']) ? null : pSQL($product['ean13']);
                $order_detail->product_isbn = empty($product['isbn']) ? null : pSQL($product['isbn']);
                $order_detail->product_upc = empty($product['upc']) ? null : pSQL($product['upc']);
                $order_detail->product_mpn = empty($product['mpn']) ? null : pSQL($product['mpn']);
                $order_detail->product_reference = empty($product['sku']) ? null : pSQL($product['sku']);
                $order_detail->product_supplier_reference = empty($product['supplier_reference']) ? null : pSQL($product['supplier_reference']);
                $order_detail->product_weight = isset($product['id_product_attribute']) ? (float) $product['weight_attribute'] : 0;//(float) $product['weight'];
                $order_detail->id_warehouse = $id_warehouse;

                $product_quantity = (int) Product::getQuantity($order_detail->product_id, $order_detail->product_attribute_id, null, null);
                $order_detail->product_quantity_in_stock = ($product_quantity - (int) $product['quantity'] < 0) ?
                    $product_quantity : (int) $product['quantity'];

                $vat_address = new Address((int) $address->id);
                if ($use_taxes) {
                    $order_detail->id_tax_rules_group = (int) Product::getIdTaxRulesGroupByIdProduct((int) $id, $context);
                    $tax_manager = TaxManagerFactory::getManager($vat_address, $order_detail->id_tax_rules_group);
                    $tax_calculator = $tax_manager->getTaxCalculator();
                    $order_detail->tax_computation_method = (int) $tax_calculator->computation_method;
                }

                $tax_rate = 0;
                $order_detail->total_shipping_price_tax_excl = (float) $delivery['price'] / 100;
                $order_detail->total_shipping_price_tax_incl = (float) ($order_detail->total_shipping_price_tax_excl * (1 + ($tax_rate / 100)));
                $order_detail->total_shipping_price_tax_incl = Tools::ps_round($order_detail->total_shipping_price_tax_incl, (int)$computingPrecision);

                $order_detail->original_product_price = Product::getPriceStatic($id, false, 0, 6, null, false, false, 1, false, null, null, null, $null, true, true, $this->context);
                $order_detail->product_price = $order_detail->original_product_price;
                $order_detail->unit_price_tax_incl = (float) $product['unit_brutto'];
                $order_detail->unit_price_tax_excl = (float) $product['unit_netto'];
                $order_detail->total_price_tax_incl = (float) $product['unit_brutto'] * $product['quantity'];
                $order_detail->total_price_tax_excl = (float) $product['unit_netto'] * $product['quantity'];

                // Set order invoice id
                $order_detail->id_order_invoice = 0;

                // Set shop id
                $order_detail->id_shop = (int) $context->shop->id;

                // Add new entry to the table
                $order_detail->save();

                // set stock
                $id_product_attribute = $ida;
                $curent_qty = StockAvailable::getQuantityAvailableByProduct((int)$id, $id_product_attribute);
                $qty = (int)$curent_qty - (int) $product['quantity'];
                StockAvailable::setQuantity((int)$id, $id_product_attribute, $qty);
            }
        }
        // order history
        // Set the order status
        $extra_vars = array();
        $new_history = new OrderHistory();
        $new_history->id_order = (int) $order->id;
        $new_history->changeIdOrderState((int) $id_order_state, $order, true);
        $new_history->addWithemail(true, $extra_vars);

        // add message
        if ($delivery['typeId'] == 'paczkomat' || $delivery['typeId'] == 'inpost') {
            $onlyPickupnumber = true;
            $pickupPlace = explode('<br />', $delivery['pickupPlace']);
            $msg = new Message();
            if ($onlyPickupnumber) {
                $messageUser = 'Numer paczkomatu: ' . $pickupPlace[0];
            } else {
                $messageUser = 'Numer paczkomatu: ' . str_replace("<br />", "<br>", $delivery['pickupPlace']);
            }
            if (Validate::isCleanHtml($messageUser)) {
                $msg->message = $messageUser;
                $msg->id_cart = (int) $cart->id;
                $msg->id_customer = (int) ($order->id_customer);
                $msg->id_order = (int) $order->id;
                $msg->private = 1;
                $msg->add();
            }

            $customer_thread = new CustomerThread();
            $customer_thread->id_contact = 0;
            $customer_thread->id_customer =  (int) $order->id_customer;
            $customer_thread->id_shop = (int) $context->shop->id;
            $customer_thread->id_order = (int) $order->id;
            $customer_thread->id_lang = (int) $context->language->id;
            $customer_thread->email = $customer->email;
            $customer_thread->status = 'open';
            $customer_thread->token = Tools::passwdGen(12);
            $customer_thread->add();

            $customer_message = new CustomerMessage();
            $customer_message->id_customer_thread = $customer_thread->id;
            $customer_message->id_employee = 0;
            $customer_message->message = $messageUser;
            $customer_message->private = 1;
            $customer_message->add();
        }
        if (!empty($message)) {
            $msg = new Message();
            $messageUser = strip_tags($message['message'], '<br>');
            if (Validate::isCleanHtml($messageUser)) {
                $msg->message = $messageUser;
                $msg->id_cart = (int) $cart->id;
                $msg->id_customer = (int) ($order->id_customer);
                $msg->id_order = (int) $order->id;
                $msg->private = 1;
                $msg->add();
            }
            // Add this message in the customer thread
            $customer_thread = new CustomerThread();
            $customer_thread->id_contact = 0;
            $customer_thread->id_customer = (int) $order->id_customer;
            $customer_thread->id_shop = (int) $context->shop->id;
            $customer_thread->id_order = (int) $order->id;
            $customer_thread->id_lang = (int) $context->language->id;
            $customer_thread->email = $customer->email;
            $customer_thread->status = 'open';
            $customer_thread->token = Tools::passwdGen(12);
            $customer_thread->add();

            $customer_message = new CustomerMessage();
            $customer_message->id_customer_thread = $customer_thread->id;
            $customer_message->id_employee = 0;
            $customer_message->message = $messageUser;
            $customer_message->private = 1;
            $customer_message->add();
        }
        // info about payment
        if ($cod == 0) {
            Db::getInstance()->insert('order_payment', array(
                'order_reference' => $order->reference,
                'id_currency' => $this->context->currency->id,
                'amount' => $order->total_paid,
                'payment_method' => $payment_method,
                'conversion_rate' => $this->context->currency->conversion_rate,
                'transaction_id' => '',
                'card_number' => '',
                'card_brand' => '',
                'card_expiration' => '',
                'card_holder' => '',
                'date_add' => date('Y-m-d H:i:s'),
            ));
        }

        // info about delivery
        Db::getInstance()->insert('order_carrier', array(
            'id_order' => (int)$order->id,
            'id_carrier' => (int)$id_carrier,
            'id_order_invoice' => 0,
            'weight' => 0,
            'shipping_cost_tax_excl' => 0,
            'shipping_cost_tax_incl' => $delivery['price'] / 100,
            'tracking_number' => '',
            'date_add' => date('Y-m-d H:i:s'),
        ));

        // sync all stock
        (new StockManager())->updatePhysicalProductQuantity(
            (int) $order->id_shop,
            (int) Configuration::get('PS_OS_ERROR'),
            (int) Configuration::get('PS_OS_CANCELED'),
            null,
            (int) $order->id
        );

        if ($cod == 1) {
            Db::getInstance()->update('order_payment', array(
                'payment_method' => 'Płatność za pobraniem - Erli.pl',
            ), 'order_reference = "'.$order->reference.'"');
        }
    }

    private function getTaxRate($id_tax_rules_group, $id_country = 14)
    {
        return Db::getInstance()->getValue('SELECT t.rate FROM `'._DB_PREFIX_.'tax` t LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON tr.`id_tax` = t.`id_tax` WHERE tr.`id_tax_rules_group` = '.(int)$id_tax_rules_group.' AND tr.`id_country` = '.(int)$id_country);
    }

    private function getApiOrderInfo($externalId)
    {
        $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
        return $erliApi->getOrder($externalId);
    }

}
