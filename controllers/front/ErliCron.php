<?php

require_once __DIR__.'/../../classes/Erli.php';
require_once __DIR__.'/../../classes/ErliOrder.php';
require_once __DIR__.'/../../classes/ErliApi.php';
require_once __DIR__.'/../../classes/ErliSync.php';

use PrestaShop\PrestaShop\Adapter\CoreException;
use PrestaShop\PrestaShop\Adapter\ServiceLocator;
use PrestaShop\PrestaShop\Adapter\StockManager;

class pherliErliCronModuleFrontController extends ModuleFrontController
{

    public $name = 'pherli';

    public $version = '1.0.1';

    private $configuration;

    private $debug = false;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'ErliCron';
        parent::__construct();

        $this->configuration['name'] = $this->name;
        $this->configuration['version'] = $this->version;
        $debug = Configuration::get('ERLI_DEV_MODE');
        if ($debug == 1) {
            $this->debug = true;
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }
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
        if (Tools::getIsset('action')) {
            $action = Tools::getValue('action');
            if (Tools::getIsset('debug') && Tools::getValue('debug') == 1) {
                $this->debug = true;
            }
            switch ($action) {
                case 'inbox':
                    $this->checkInbox();
                    break;
                case 'products':
                    $this->checkProducts();
                    break;
                case 'stocks':
                    $this->updateStocks();
                    break;
                case 'checkBuyability':
                    $this->registerHookCheckBuyability();
                    break;
                case 'firstSync':
                    $this->createFirstSync();
                    break;
                case 'getPriceList':
                    $this->getPriceList();
                    break;
                case 'setTracking':
                    $this->setTracking();
                    break;
                case 'search':
                    $this->searchProduct();
                    break;
                case 'searchProduct':
                    $this->searchProduct2();
                    break;
            }
        }
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

    private function checkInbox()
    {
        $id_sync = ErliSync::add(1);
        $addOrder = (int)Configuration::get('ERLI_ORDER_IMPORT');
        $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
        $return = $erliApi->getInbox();
        $context = Context::getContext();
        if ($this->debug) {
            echo 'Dodaj: '.$addOrder.'<br />';
        }

        $code = $return['status'];
        if ($code == 200) {
            $request = json_decode($return['body']);
            if (!empty($request)) {
                $ordersAddCount = 0;
                foreach ($request as $item2) {
                    if (!$item2->read) {
                        if ($item2->type == ErliApi::ERLI_ORDER_CREATED) {
                            $ordersAddCount++;
                        }
                    }
                }
                ErliSync::updateOrderAddAll((int)$id_sync, (int)$ordersAddCount);

                $request_add = array();
                $request_upd = array();
                foreach ($request as $item) {
                    if (!$item->read) {
                        if ($item->type == ErliApi::ERLI_ORDER_CREATED) {
                            $request_add[] = $item;
                        }
                        if ($item->type == ErliApi::ERLI_ORDER_CHANGED) {
                            $request_upd[] = $item;
                        }
                    }
                }
                if (!empty($request_add)) {
                    foreach ($request_add as $item) {
                        if (!$item->read) {
                            if ($item->type == ErliApi::ERLI_ORDER_CREATED) {
                                $isset = ErliOrder::checkOrderExist($item->payload->id);
                                if (empty($isset)) {
                                    $data['id'] = $item->id;
                                    $data['total'] = $item->payload->totalPrice;
                                    $data['id_payload'] = $item->payload->id;
                                    $data['created'] = $item->created;
                                    $id_order = (int)ErliOrder::addOrder($data);
                                    if ((int)$id_order > 0) {
                                        /* customer address */
                                        $user = $item->payload->user;
                                        $users['email'] = $user->email;
                                        $users['firstname'] = str_replace('.', '', $user->deliveryAddress->firstName);
                                        $users['lastname'] = str_replace('.', '', $user->deliveryAddress->lastName);
                                        $users['address'] = $user->deliveryAddress->address;
                                        $users['zip'] = $user->deliveryAddress->zip;
                                        $users['city'] = $user->deliveryAddress->city;
                                        $users['country'] = $user->deliveryAddress->country;
                                        $phone = str_replace(' ', '', $user->deliveryAddress->phone);
                                        $phone = str_replace('-', '', $phone);
                                        $users['phone'] = $phone; //$user->deliveryAddress->phone;
                                        $users['created'] = $item->created;
                                        ErliOrder::addAddress((int)$id_order, $users);
                                        /* invoice address */
                                        if (isset($item->payload->user->invoiceAddress)) {
                                            $invoice = $item->payload->user->invoiceAddress;
                                            $inv['address'] = $invoice->address;
                                            $inv['zip'] = $invoice->zip;
                                            $inv['city'] = $invoice->city;
                                            $inv['country'] = $invoice->country;
                                            $inv['firstname'] = isset($invoice->firstName) ? str_replace('.', '', $invoice->firstName) : '';
                                            $inv['lastname'] = isset($invoice->lastName) ? str_replace('.', '', $invoice->lastName) : '';
                                            $inv['type'] = $invoice->type;
                                            $inv['company_name'] = isset($invoice->companyName) ? $invoice->companyName : '';
                                            $inv['nip'] = isset($invoice->nip) ? $invoice->nip : '';
                                            $inv['created'] = $item->created;
                                            ErliOrder::addInvoiceAddress((int)$id_order, $inv);
                                        }

                                        /* order delivery */
                                        $deliveryErli = $item->payload->delivery;
                                        $pickupPlace = '';
                                        if ($deliveryErli->typeId == 'paczkomat') {
                                            $pickupPlaceErli = $deliveryErli->pickupPlace;
                                            $pickupPlace = $pickupPlaceErli->externalId . '<br />' . $pickupPlaceErli->address . ' ' . $pickupPlaceErli->zip . ' ' . $pickupPlaceErli->city;
                                        }
                                        $delivery['name'] = $deliveryErli->name;
                                        $delivery['typeId'] = Erli::getVendorByDelivery($deliveryErli->typeId);
                                        $delivery['price'] = $deliveryErli->price;
                                        $delivery['cod'] = $deliveryErli->cod;
                                        $delivery['pickupPlace'] = $pickupPlace;
                                        $delivery['created'] = $item->created;
                                        ErliOrder::addDelivery((int)$id_order, $delivery);
                                        /* order items */
                                        $items = $item->payload->items;
                                        if (!empty($items)) {
                                            foreach ($items as $product) {
                                                $pro['id_erli'] = $product->id;
                                                $pro['externalId'] = $product->externalId;
                                                $pro['quantity'] = $product->quantity;
                                                $pro['unitPrice'] = $product->unitPrice;
                                                $pro['name'] = $product->name;
                                                $pro['slug'] = $product->slug;
                                                $pro['sku'] = $product->sku;
                                                $pro['created'] = $item->created;
                                                ErliOrder::addItems((int)$id_order, $pro);
                                            }
                                        }
                                        /* order message */
                                        if (isset($item->payload->comment)) {
                                            $message = $item->payload->comment;
                                            if (!empty($message)) {
                                                $mes['message'] = $message;
                                                $mes['created'] = $item->created;
                                                ErliOrder::addMessage((int)$id_order, $mes);
                                            }
                                        }
                                        /* order status */
                                        $statusS['status'] = 'orderCreated';
                                        $statusS['created'] = $item->created;
                                        ErliOrder::addStatus((int)$id_order, $statusS);
                                        if ($addOrder == 1) {
                                            $status_order = 0;
                                            if ($item->payload->status == 'purchased') {
                                                if (isset($item->payload->payment->id) && (int)$item->payload->payment->id > 0) {
                                                    $status_order = 1; // płatnosć online na erli.pl
                                                } else {
                                                    if ($item->payload->delivery->cod == 1) {
                                                        $status_order = 3; // zapłac przy odbiorze
                                                    } else {
                                                        $status_order = 1;
                                                    }
                                                }
                                            } elseif ($item->payload->status == 'pending') {
                                                $status_order = 2; // zamówienie nieopłacone online
                                            }
                                            $this->addOrderToShop((int)$id_order, $status_order);
                                        }
                                        $adding = (int)ErliSync::getOrderAdd((int)$id_sync);
                                        $addErli = (int)$adding + 1;
                                        ErliSync::updateOrderAdd((int)$id_sync, (int)$addErli);
                                    }
                                }
                                /* mark messge as readed */
                                $erliApi->setInboxReadMark($item->id);
                            }
                        }
                    }
                } // end request_add

                if (!empty($request_upd)) {
                    sleep(5);
                    foreach ($request_upd as $item) {
                        if (!$item->read) {
                            if ($item->type == ErliApi::ERLI_ORDER_CHANGED) {
                                $isset = ErliOrder::checkOrderExist($item->payload->id);
                                if (!empty($isset)) {
                                    $id_order = (int)$isset['id_order'];
                                    $status = $item->payload->status;
                                    switch ($status) {
                                        case 'purchased':
                                            if ($isset['id_order_shop'] > 0) {
                                                $payment['payment_id'] = $item->payload->payment->id;
                                                $payment['created'] = $item->created;
                                                ErliOrder::addPayment((int)$id_order, $payment);
                                                // udpate payment info
                                                $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
                                                $orderErli = ErliOrder::getOrder((int)$id_order);
                                                $paymentInfo = $erliApi->getPaymentsOrderInfo($orderErli['id_payload']);
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

                                                $statusErli['status'] = 'paid';
                                                $statusErli['created'] = $item->created;
                                                ErliOrder::addStatus((int)$id_order, $statusErli);
                                                $order = new Order($isset['id_order_shop']);
                                                $current_state = $order->getCurrentStateFull($context->cookie->id_lang);
                                                $state_history = Db::getInstance()->executeS('SELECT `id_order_state` FROM `'._DB_PREFIX_.'order_history` WHERE `id_order` = '.(int)$id_order);
                                                $states = array();
                                                if (!empty($state_history)) {
                                                    foreach ($state_history as $sh) {
                                                        $states[] = $sh['id_order_state'];
                                                    }
                                                }
                                                // add status
                                                $id_order_state = (int)Configuration::get('ERLI_STATUS_SHOP_paid'); // 2

                                                if ($current_state['id_order_state'] != $id_order_state) { // 12 != 2
                                                    $add = true;
                                                    if (!empty($states)) {
                                                        if (in_array($id_order_state, $states)) { // 2 | 3,4
                                                            $add = false;
                                                        }
                                                    }
                                                    if ($add) {
                                                        $extra_vars = array();
                                                        $new_history = new OrderHistory();
                                                        $new_history->id_order = (int)$order->id;
                                                        $new_history->changeIdOrderState((int)$id_order_state, $order, true);
                                                        $new_history->addWithemail(true, $extra_vars);
                                                    }
                                                }
                                                /* mark messge as readed */
                                                $erliApi->setInboxReadMark($item->id);

                                                $adding = (int)ErliSync::getOrderUpdate((int)$id_sync);
                                                $addErli = (int)$adding + 1;
                                                ErliSync::updateOrderUpdate((int)$id_sync, (int)$addErli);
                                            }
                                            break;
                                        case "cancelled":
                                            $id_order_state = (int)Configuration::get('ERLI_STATUS_SHOP_canceled');
                                            if ((int)$isset['id_order_shop'] > 0) {
                                                $order = new Order($isset['id_order_shop']);
                                                $extra_vars = array();
                                                $new_history = new OrderHistory();
                                                $new_history->id_order = (int) $order->id;
                                                $new_history->changeIdOrderState((int) $id_order_state, $order, true);
                                                $new_history->addWithemail(true, $extra_vars);

                                                /* mark messge as readed */
                                                $erliApi->setInboxReadMark($item->id);

                                                $adding = (int)ErliSync::getOrderUpdate((int)$id_sync);
                                                $addErli = (int)$adding + 1;
                                                ErliSync::updateOrderUpdate((int)$id_sync, (int)$addErli);
                                            }
                                            break;
                                    }
                                }
                            }
                        }
                    }
                } // end request_upd
            }
        }

        // jakby się nie zaktualizowało poprawnie
        $date = new DateTime(date('Y-m-d'));
        $date->modify('-2 day');
        $end_date =  $date->format('Y-m-d');
        $payments = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_order_status` WHERE `status` = "paid" AND `date_add` > "'.$end_date.'"');
        if (!empty($payments)) {
            foreach ($payments as $payment) {
                $order_info = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_order` WHERE `id_order` = '.(int)$payment['id_order']);
                if (!empty($order_info)) {
                    $id_order = $order_info['id_order_shop'];
                    $order = new Order((int)$id_order);
                    $current_state = $order->getCurrentStateFull($context->cookie->id_lang);
                    $state_history = Db::getInstance()->executeS('SELECT `id_order_state` FROM `'._DB_PREFIX_.'order_history` WHERE `id_order` = '.(int)$id_order);
                    $states = array();
                    if (!empty($state_history)) {
                        foreach ($state_history as $sh) {
                            $states[] = $sh['id_order_state'];
                        }
                    }
                    $id_order_state = (int)Configuration::get('ERLI_STATUS_SHOP_paid');
                    if ($current_state['id_order_state'] != $id_order_state) {
                        $add = true;
                        if (!empty($states)) {
                            if (in_array($id_order_state, $states)) {
                                $add = false;
                            }
                        }
                        if ($add) {
                            $extra_vars = array();
                            $new_history = new OrderHistory();
                            $new_history->id_order = (int)$order->id;
                            $new_history->changeIdOrderState((int)$id_order_state, $order, true);
                            $new_history->addWithemail(true, $extra_vars);
                        }
                    }
                }
            }
        }

        ErliSync::updateDateEnd((int)$id_sync);
        echo "zakończono aktualizację zamówień";
    }

    private function addOrderToShop($id_order_erli, $order_status = 0)
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

        $addressDeliveryPhone = str_replace(' ', '', $addressErli['phone']);
        $addressDeliveryPhone = str_replace('-', '', $addressDeliveryPhone);
        $address->phone = $addressDeliveryPhone;
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
        if (!empty($addressInvoiceErli)) {
            $cart->id_address_invoice = (int)$address2->id;
        } else {
            $cart->id_address_invoice = (int)$address->id;
        }
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

        // zamówienie opłacone online na erli.pl
        if ($order_status == 1) {
            $id_order_state = (int)Configuration::get('ERLI_STATUS_SHOP_paid');
        }
        // zamówienie nieopłacone
        if ($order_status == 2) {
            $id_order_state = (int)Configuration::get('ERLI_STATUS_SHOP_nopaid');
        }
        // płatność przy odbiorze
        if ($order_status == 3) {
            $id_order_state = (int)Configuration::get('ERLI_STATUS_SHOP_preparing');
        }

        // add order
        $payment_id = 0;
        if (!empty($$payment)) {
            $payment_id = (int)$payment['payment_id'];
        }
        if ($cod == 1) {
            $payment_id = 1;
        }

        $payment_method = ErliOrder::getOrderPaymentName((int)$payment_id, $orderErli['id_payload'], $this->configuration);
        do {
            $reference = Order::generateReference();
        } while (Order::getByReference($reference)->count());

        $order = new Order();
        $order->id_carrier = (int)$id_carrier;
        $order->id_customer = (int) $customer->id;
        if (!empty($addressInvoiceErli)) {
            $order->id_address_invoice = (int)$address2->id;
        } else {
            $order->id_address_invoice = (int)$address->id;
        }
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

        if ($this->debug) {
            print_r($order);
            print_r($result);
        }

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
                $order_detail->product_attribute_id = (int) $ida;
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
            $transaction_id = $this->getTransactionId($orderErli['id_payload']);
            Db::getInstance()->insert('order_payment', array(
                'order_reference' => $order->reference,
                'id_currency' => $this->context->currency->id,
                'amount' => $order->total_paid,
                'payment_method' => $payment_method,
                'conversion_rate' => $this->context->currency->conversion_rate,
                'transaction_id' => $transaction_id,
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

    private function getTransactionId($id_payload)
    {
        $transaction_id = '';
        $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
        $paymentInfo = $erliApi->getPaymentsOrderInfo($id_payload);
        if ($paymentInfo['status'] == 200) {
            $body = json_decode($paymentInfo['body']);
            $body = $body[0];
            if (!empty($body) && !empty($body->externalPaymentId)) {
                $transaction_id = $body->externalPaymentId;
            }
        } else {
            $this->getTransactionId($id_payload);
        }
        return $transaction_id;
    }

    private function getTaxRate($id_tax_rules_group, $id_country = 14)
    {
        return Db::getInstance()->getValue('SELECT t.rate FROM `'._DB_PREFIX_.'tax` t LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON tr.`id_tax` = t.`id_tax` WHERE tr.`id_tax_rules_group` = '.(int)$id_tax_rules_group.' AND tr.`id_country` = '.(int)$id_country);
    }

    /**
     *  function check products
     */
    private function checkProducts()
    {
        $this->addProductFromCategory();
        $this->exportProducts();
    }

    /**
     *  function add product from category
     */
    private function addProductFromCategory()
    {
        $context = Context::getContext();
        $categorys = Erli::getCategoryList();
        if (!empty($categorys)) {
            foreach ($categorys as $category) {
                $cat = new Category((int)$category['id_category']);
                $products = $cat->getProducts((int)$context->cookie->id_lang, 0, 10000);
                if (!empty($products)) {
                    foreach ($products as $product) {
                        $isset = Erli::checkProductInErli((int)$product['id_product']);
                        if (empty($isset)) {
                            Erli::addProductToErli((int)$product['id_product'], null, null, 1);
                        } else {
                            Erli::updateProductInErli((int)$product['id_product'], null, null, 1);
                        }
                    }
                }
            }
        }
    }

    /**
     *  function Export product
     */
    private function exportProducts()
    {
        set_time_limit(-1);
        $id_sync = ErliSync::add(2);
        $productsToAdd = Erli::getProductByErliExist();
//        $productsToAdd = Erli::getProductByErliExistWithLimit();
        $checkProduct = Configuration::get('ERLI_PRODUCT_CHANGE');
        $checkProductAll = Configuration::get('ERLI_PRODUCT_CHANGE_ALL');

        if ($checkProduct == 1) {
            $productsToUpdate = Erli::getProductToUpdate();
        } else {
            if ($checkProductAll == 1) {
                $productsToUpdate = Erli::getProductAllToUpdate();
            } else {
                $productsToUpdate = Erli::getProductByErliExist(1);
            }
        }

        if (!empty($productsToAdd)) {
            $this->addProductToSync($productsToAdd, (int)$id_sync, 1);
        }
        if (!empty($productsToUpdate)) {
            $this->addProductToSync($productsToUpdate, (int)$id_sync, 2);
        }

        $productsToSync = ErliSync::getProductToSync((int)$id_sync);
        if (!empty($productsToSync)) {
            echo "sync 1<br />";
            $this->syncProduct($productsToSync, (int)$id_sync);
        }
        // after 5 sec
        $productsToSync_5 = ErliSync::getProductToSync((int)$id_sync);
        if (!empty($productsToSync_5)) {
            sleep(5);
            echo "sync 2<br />";
            $this->syncProduct($productsToSync_5, (int)$id_sync);
        }
        // after 5 sec
        $productsToSync_15 = ErliSync::getProductToSync((int)$id_sync);
        if (!empty($productsToSync_15)) {
            sleep(15);
            echo "sync 3<br />";
            $this->syncProduct($productsToSync_15, (int)$id_sync);
        }
        // after 30 sec
        $productsToSync_30 = ErliSync::getProductToSync((int)$id_sync);
        if (!empty($productsToSync_30)) {
            sleep(30);
            echo "sync 4<br />";
            $this->syncProduct($productsToSync_30, (int)$id_sync);
        }
        // after 60 sec
        $productsToSync_60 = ErliSync::getProductToSync((int)$id_sync);
        if (!empty($productsToSync_60)) {
            sleep(60);
            echo "sync 5<br />";
            $this->syncProduct($productsToSync_60, (int)$id_sync);
        }
        // after 60 sec
        $productsToSync_60 = ErliSync::getProductToSync((int)$id_sync);
        if (!empty($productsToSync_60)) {
            sleep(60);
            echo "sync 6<br />";
            $this->syncProduct($productsToSync_60, (int)$id_sync);
        }
        // after 120 sec
        $productsToSync_120 = ErliSync::getProductToSync((int)$id_sync);
        $this->syncProducts($id_sync, $productsToSync_120);

        // remove file from erli - disabled for erli
        $this->removeFromErli((int)$id_sync);

        ErliSync::updateDateEnd((int)$id_sync);

        echo "Zakończono";
    }

    private function syncProducts($id_sync, $productsToSync)
    {
        if (!empty($productsToSync)) {
            sleep(120);
            echo "sync 7 ".date('Y-m-d H:i:s')."<br />";
            $this->syncProduct($productsToSync, (int)$id_sync);
        }
        $productsToSync = ErliSync::getProductToSync((int)$id_sync);
        if (!empty($productsToSync)) {
            $this->syncProducts($id_sync, $productsToSync);
        }
    }

    /**
     * @param $products
     * @param $id_sync
     * @param $type
     */
    private function addProductToSync($products, $id_sync, $type)
    {
        $count = 0;
        if (!empty($products)) {
            foreach ($products as $product) {
                $pro = new Product((int)$product['id_product']);
                $hasAttributes = $pro->hasAttributes();
                if ($hasAttributes > 0) {
                    $attributes = $pro->getAttributeCombinations((int)$this->context->cookie->id_lang);
                    foreach ($attributes as $key => $attribute) {
                        $isset = ErliSync::getProductToSyncId((int)$product['id_product'], (int)$attribute['id_product_attribute'], $id_sync);
                        if (!$isset) {
                            Db::getInstance()->insert('erli_products_sync', array(
                                'id_sync' => (int)$id_sync,
                                'id_product' => (int)$product['id_product'],
                                'id_product_attribute' => (int)$attribute['id_product_attribute'],
                                'type' => (int)$type,
                                'error' => '',
                                'date_add' => date('Y-m-d H:i:s'),
                                'date_upd' => date('Y-m-d H:i:s'),
                            ));
                            $count++;
                        }
                    }
                } else {
                    $isset = ErliSync::getProductToSyncId((int)$product['id_product'], 0, $id_sync);
                    if (!$isset) {
                        Db::getInstance()->insert('erli_products_sync', array(
                            'id_sync' => (int)$id_sync,
                            'id_product' => (int)$product['id_product'],
                            'id_product_attribute' => 0,
                            'type' => (int)$type,
                            'error' => '',
                            'date_add' => date('Y-m-d H:i:s'),
                            'date_upd' => date('Y-m-d H:i:s'),
                        ));
                    }
                    $count++;
                }
            }
        }
        switch ($type) {
            case 1:
                ErliSync::updateProductAddAll((int)$id_sync, (int)$count);
                break;
            case 2:
                ErliSync::updateProductUpdateAll((int)$id_sync, (int)$count);
                break;
        }
    }

    /**
     * @param $products
     * @param $id_sync
     */
    private function syncProduct($products, $id_sync)
    {
        if (!empty($products)) {
            foreach ($products as $product) {
                switch ((int)$product['type']) {
                    case 1:
                        // add product
                        if (!empty($product['error']) && $product['error'] == 409) {
                            $this->updateProductInErli((int)$product['id_product'], (int)$product['id_product_attribute'], (int)$id_sync, 1);
                        } else {
                            $this->addProductToErli((int)$product['id_product'], (int)$product['id_product_attribute'], (int)$id_sync);
                        }
                        break;
                    case 2:
                        // update product
                        if (!empty($product['error'])) {
                            if ($product['error'] == 404) {
                                $this->addProductToErli((int)$product['id_product'], (int)$product['id_product_attribute'], (int)$id_sync, 1);
                            } else {
                                $this->updateProductInErli((int)$product['id_product'], (int)$product['id_product_attribute'], (int)$id_sync);
                            }
                        } else {
                            $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
                            if ((int)$product['id_product_attribute'] > 0) {
                                $erliProduct = (array)$erliApi->getProduct((int)$product['id_product'] . '-' . (int)$product['id_product_attribute']);
                            } else {
                                $erliProduct = (array)$erliApi->getProduct((int)$product['id_product']);
                            }
                            if (!isset($erliProduct['externalId'])) {
                                $this->addProductToErli((int)$product['id_product'], (int)$product['id_product_attribute'], (int)$id_sync, 1);
                            } else {
                                $this->updateProductInErli((int)$product['id_product'], (int)$product['id_product_attribute'], (int)$id_sync);
                            }
                        }
                        break;
                    case 3:
                        $this->updateProductsStatusInErli((int)$product['id_product'], (int)$product['id_product_attribute'], (int)$id_sync);
                        break;
                }
                $iss = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_products_sync` WHERE `id_sync` = '.(int)$id_sync.' AND `id_product` = '.(int)$product['id_product']);
                if (empty($iss)) {
                    $checkProduct = Configuration::get('ERLI_PRODUCT_CHANGE');
                    if ($checkProduct == 1) {
                        Erli::setProductUpdate((int)$product['id_product']);
                    }
                }
                sleep(1);
            }
        }
    }

    /**
     * @param $body
     * @return false|string
     */
    private static function cleanBody($body)
    {
        $newBody = json_decode($body, true);
        if (!empty($newBody)) {
            foreach ($newBody as &$nb1) {
                if (is_array($nb1)) {
                    foreach ($nb1 as &$nb2) {
                        if (is_array($nb2)) {
                            foreach ($nb2 as &$nb3) {
                                if (is_array($nb3)) {

                                } else {
                                    $nb3 = str_replace('"', "", $nb3);
                                }
                            }
                        } else {
                            $nb2 = str_replace('"', "", $nb2);
                        }
                    }
                } else {
                    $nb1 = str_replace('"', "", $nb1);
                }
            }
        } else {
            $newBody = $body;
        }
        return json_encode($newBody);
    }

    private function updateStocks()
    {
        $id_sync = ErliSync::add(3);
        $productsToUpdate = Erli::getProductByErliExist(1);
        ErliSync::updateProductUpdateAll((int)$id_sync, (int)count($productsToUpdate));
        if (!empty($productsToUpdate)) {
            $erli_price = Configuration::get('ERLI_PRODUCT_PRICE');
            $price_type = Configuration::get('ERLI_PRODUCT_PRICE_TYPE');
            $price_action = Configuration::get('ERLI_PRODUCT_PRICE_ACTION');
            $price_value = Configuration::get('ERLI_PRODUCT_PRICE_VALUE');
            $price_cur = Configuration::get('ERLI_PRODUCT_PRICE_CUR');
            $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
            foreach ($productsToUpdate as $product) {
                $id_product = (int)$product['id_product'];
                $id_product_attribiute = 0;
                $pro = new Product((int)$id_product, false, $this->context->cookie->id_lang);
                $hasAttributes = $pro->hasAttributes();
                if ($hasAttributes > 0) {
                    $attributes = $pro->getAttributeCombinations((int)$this->context->cookie->id_lang);
                    $attr = $pro->getAttributesResume((int)$this->context->cookie->id_lang);
                    $attrib = array();
                    if (!empty($attr)) {
                        foreach ($attr as $item) {
                            $attrib[$item['id_product'].'-'.$item['id_product_attribute']] = $item;
                        }
                    }
                    foreach ($attributes as $key => $attribute) {
                        $id_product_attribiute = $attribute['id_product_attribute'];
                        $sp = null;
                        $price = Product::priceCalculation($this->context->shop->id, $id_product, $id_product_attribiute, $this->context->country->id, 0, '', 1, 1, 1, true, 2, 0, 1, 1, $sp, 1);
                        if ($erli_price == 1) {
                            if ($price_type == 1) { // %
                                $mnoznik = 1;
                                if ($price_action == 1) { // down
                                    $mnoznik = (100 - $price_value) / 100;
                                } else if ($price_action == 2) { // up
                                    $mnoznik = 1 + ($price_value / 100);
                                }
                                if ($price_cur == 1) { // netto
                                    $price = $pro->price * $mnoznik;
                                    $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `' . _DB_PREFIX_ . 'tax` t LEFT JOIN `' . _DB_PREFIX_ . 'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = ' . $pro->id_tax_rules_group . ' AND tr.`id_country` = 14');
                                    $tax = 1 + ($getRate / 100);
                                    $price = $price * $tax;
                                } else if ($price_cur == 2) { // brutto
                                    $price = $price * $mnoznik;
                                }
                            } else if ($price_type == 2) { // amount
                                if ($price_cur == 1) { // netto
                                    if ($price_action == 1) { // down
                                        $price = $pro->price - $price_value;
                                    } else if ($price_action == 2) { // up
                                        $price = $pro->price + $price_value;
                                    }
                                    $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `' . _DB_PREFIX_ . 'tax` t LEFT JOIN `' . _DB_PREFIX_ . 'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = ' . $pro->id_tax_rules_group . ' AND tr.`id_country` = 14');
                                    $tax = 1 + ($getRate / 100);
                                    $price = $price * $tax;
                                } else if ($price_cur == 2) { // brutto
                                    if ($price_action == 1) { // down
                                        $price -= $price_value;
                                    } else if ($price_action == 2) { // up
                                        $price += $price_value;
                                    }
                                }
                            }
                            $price = number_format($price, 2, '.', '');
                        }
                        $send['price'] = number_format($price * 100, 0, '.', ''); // w groszach
                        $send['stock'] = StockAvailable::getQuantityAvailableByProduct((int)$id_product, $id_product_attribiute);
                        $return = $erliApi->updateProduct($send, $id_product.'-'.$id_product_attribiute);
                        $status = 0;
                        if ($return['status'] == 202) {
                            $status = 1;
                            Erli::updateInErliProduct((int)$id_product);
                            $produc_upd = ErliSync::getProductUpdate((int)$id_sync);
                            $ile = $produc_upd + 1;
                            ErliSync::updateProductUpdate((int)$id_sync, $ile);
                        } else {
                            if ($this->debug) {
                                print_r($return);
                            }
                            $cb = array(400, 503);//, 429);
                            if (in_array($return['status'], $cb)) {
                                $return['body'] = $return['body'];
                            } elseif ($return['status'] == 429) {
                                $return['body'] = 'Błąd podczas aktualizacji stanów magazynowych i ceny. Nieoczekiwany błąd: '.$return['body'].' '.$return['info'];
                            } else {
                                $body = $return['info'];
                                $return['body'] = 'Błąd podczas aktualizacji stanów magazynowych i ceny. E#'.$return['status'].' '.$body;
                            }
                            ErliSync::addSyncError((int)$id_sync, 2, (int)$id_product, $return['status'], $return['body']);
                        }
                        Erli::updateStatus((int)$id_product, $status);
                        sleep(2);
                    }
                } else {
                    $price = Product::priceCalculation($this->context->shop->id, $id_product, $id_product_attribiute, $this->context->country->id, 0, '', 1, 1, 1, true, 2, 0, 1, 1, $sp, 1);
                    if ($erli_price == 1) {
                        if ($price_type == 1) { // %
                            $mnoznik = 1;
                            if ($price_action == 1) { // down
                                $mnoznik = (100 - $price_value) / 100;
                            } else if ($price_action == 2) { // up
                                $mnoznik = 1 + ($price_value / 100);
                            }
                            if ($price_cur == 1) { // netto
                                $price = $pro->price * $mnoznik;
                                $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `' . _DB_PREFIX_ . 'tax` t LEFT JOIN `' . _DB_PREFIX_ . 'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = ' . $pro->id_tax_rules_group . ' AND tr.`id_country` = 14');
                                $tax = 1 + ($getRate / 100);
                                $price = $price * $tax;
                            } else if ($price_cur == 2) { // brutto
                                $price = $price * $mnoznik;
                            }
                        } else if ($price_type == 2) { // amount
                            if ($price_cur == 1) { // netto
                                if ($price_action == 1) { // down
                                    $price = $pro->price - $price_value;
                                } else if ($price_action == 2) { // up
                                    $price = $pro->price + $price_value;
                                }
                                $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `' . _DB_PREFIX_ . 'tax` t LEFT JOIN `' . _DB_PREFIX_ . 'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = ' . $pro->id_tax_rules_group . ' AND tr.`id_country` = 14');
                                $tax = 1 + ($getRate / 100);
                                $price = $price * $tax;
                            } else if ($price_cur == 2) { // brutto
                                if ($price_action == 1) { // down
                                    $price -= $price_value;
                                } else if ($price_action == 2) { // up
                                    $price += $price_value;
                                }
                            }
                        }
                    }
                    $send['price'] = number_format($price * 100, 0, '.', ''); // w groszach
                    $send['stock'] = StockAvailable::getQuantityAvailableByProduct((int)$id_product, $id_product_attribiute);
                    $return = $erliApi->updateProduct($send, (int)$id_product);
                    $status = 0;
                    if ($return['status'] == 202) {
                        $status = 1;
                        Erli::updateInErliProduct((int)$id_product);
                        $produc_upd = ErliSync::getProductUpdate((int)$id_sync);
                        $ile = $produc_upd + 1;
                        ErliSync::updateProductUpdate((int)$id_sync, $ile);
                    } else {
                        if ($this->debug) {
                            print_r($return);
                        }
                        $cb = array(400, 503);//, 429);
                        if (in_array($return['status'], $cb)) {
                            $return['body'] = $return['info'];
                        } elseif ($return['status'] == 429) {
                            $return['body'] = 'Błąd podczas aktualizacji stanów magazynowych i ceny. Nieoczekiwany błąd: '.$return['body'].' '.$return['info'];
                        } else {
                            $body = $return['info'];
                            $return['body'] = 'Błąd podczas aktualizacji stanów magazynowych i ceny. E#'.$return['status'].' '.$body;
                        }
                        ErliSync::addSyncError((int)$id_sync, 2, (int)$id_product, $return['status'], $return['body']);
                    }
                    Erli::updateStatus((int)$id_product, $status);
                    sleep(2);
                }
            }
        }
        ErliSync::updateDateEnd((int)$id_sync);
    }

    /**
     *
     */
    public function registerHookCheckBuyability()
    {
        $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
        $sslActive = Configuration::get('PS_SSL_ENABLED');
        $ssl = 'http://';
        if ($sslActive) {
            $ssl = 'https://';
        }
        $hookUrl = $ssl.$this->context->shop->domain.$this->context->shop->physical_uri.'module/pherli/checkBuyability';
        $return = $erliApi->registerHook('checkBuyability', $hookUrl);

        if ($return['status'] == 201) {
            echo "Dodano pomyślnie!<br />Możesz zamknąć okno.";
        } else {
            echo "Wystąpił błąd: #E".$return['status'].' - '.$return['info'].'<br />'.$return['body'];
        }
        exit();
    }

    /**
     * @param $step
     * @return int
     */
    private function getSleep($step)
    {
        $sleep = 5;
        switch ($step) {
            case 0:$sleep = 5;break;
            case 1:$sleep = 30;break;
            case 2:$sleep = 60;break;
            case 3:$sleep = 120;break;
            case 4:$sleep = 240;break;
            case 5:$sleep = 360;break;
        }
        return $sleep;
    }

    /**
     * @param $id_product
     * @param $id_product_attribiute
     * @param $id_sync
     */
    private function addProductToErli($id_product, $id_product_attribiute, $id_sync, $upd = 0)
    {
        if ($this->debug) {
            echo "<pre>";
        }
        $send = array();
        $erli_price = Configuration::get('ERLI_PRODUCT_PRICE');
        $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
        $productErli = Erli::checkProductInErli((int)$id_product);
        $dispatchTime = (int)Configuration::get('ERLI_DELIVERY_TIME_DEFAULT');
        if ((int)$productErli['deliveryTime'] > 0) {
            $dispatchTime = (int)$productErli['deliveryTime'];
        }
        $product = new Product((int)$id_product, false, (int)$this->context->cookie->id_lang);
        if ((int)$id_product_attribiute > 0) {
            $attribute0 = $product->getAttributeCombinationsById($id_product_attribiute, $this->context->cookie->id_lang);
            $attribute = $attribute0[0];
            $attributesImage = $product->getCombinationImages((int)$this->context->cookie->id_lang);
            $attributes = $product->getAttributeCombinations((int)$this->context->cookie->id_lang);
            $attr = $product->getAttributesResume((int)$this->context->cookie->id_lang);
            $attrib = array();
            if (!empty($attr)) {
                foreach ($attr as $item) {
                    $attrib[$item['id_product'].'-'.$item['id_product_attribute']] = $item;
                }
            }

            $sp = null;
            $price = Product::priceCalculation($this->context->shop->id, $id_product, $id_product_attribiute, $this->context->country->id, 0, '', 1, 1, 1, true, 2, 0, 1, 1, $sp, 1);
            if ($erli_price == 1) {
                $price_type = Configuration::get('ERLI_PRODUCT_PRICE_TYPE');
                $price_action = Configuration::get('ERLI_PRODUCT_PRICE_ACTION');
                $price_value = Configuration::get('ERLI_PRODUCT_PRICE_VALUE');
                $price_cur = Configuration::get('ERLI_PRODUCT_PRICE_CUR');
                if ($price_type == 1) { // %
                    $mnoznik = 1;
                    if ($price_action == 1) { // down
                        $mnoznik = (100 - $price_value) / 100;
                    } else if ($price_action == 2) { // up
                        $mnoznik = 1 + ($price_value/100);
                    }
                    if ($price_cur == 1) { // netto
                        $price = $product->price * $mnoznik;
                        $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `'._DB_PREFIX_.'tax` t LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = '.$product->id_tax_rules_group.' AND tr.`id_country` = 14');
                        $tax = 1 + ($getRate / 100);
                        $price = $price * $tax;
                    } else if ($price_cur == 2) { // brutto
                        $price = $price * $mnoznik;
                    }
                } else if ($price_type == 2) { // amount
                    if ($price_cur == 1) { // netto
                        if ($price_action == 1) { // down
                            $price = $product->price - $price_value;
                        } else if ($price_action == 2) { // up
                            $price = $product->price + $price_value;
                        }
                        $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `'._DB_PREFIX_.'tax` t LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = '.$product->id_tax_rules_group.' AND tr.`id_country` = 14');
                        $tax = 1 + ($getRate / 100);
                        $price = $price * $tax;
                    } else if ($price_cur == 2) { // brutto
                        if ($price_action == 1) { // down
                            $price -= $price_value;
                        } else if ($price_action == 2) { // up
                            $price += $price_value;
                        }
                    }
                }
                $price = number_format($price, 2, '.', '');
            }
            $round = (int)Configuration::get('ERLI_PRICE_ROUND');
            if ($round) {
                $round_type = (int)Configuration::get('ERLI_PRICE_ROUND_TYPE');
                if ($round_type == 1) {
                    $price = round($price, 0,  PHP_ROUND_HALF_UP);
                } else {
                    $price = round($price, 0, PHP_ROUND_HALF_DOWN);
                }
            }
            $send['name'] = mb_substr($product->name, 0, 75);
            $send['ean'] = $product->ean13;
            $send['sku'] = !empty($attribute['reference']) ? $attribute['reference'] : $product->reference;
            $send['price'] = (int)($price * 100); // brutto w groszach
            $send['status'] = 'active';
            $send['deliveryPriceList'] = '';
            $send['description'] = ($product->description);
            $send['dispatchTime']['period'] = $dispatchTime;
            $send['dispatchTime']['unit'] = 'day';
            $delivery_price_default = Configuration::get('ERLI_DELIVERY_PRICES');
            if (!is_null($productErli['deliveryPrice'])) {
                $delivery_price_default = $productErli['deliveryPrice'];
            }
            $send['packaging']['tags'][0] = $delivery_price_default;
            $send['deliveryPriceList'] = $delivery_price_default;
            $send['packaging']['weight'] = $product->weight * 1000;
            $send['stock'] = StockAvailable::getQuantityAvailableByProduct((int)$id_product, $id_product_attribiute);
            $img_variant = array();

            if (!empty($attributesImage)) {
                if (isset($attributesImage[$id_product_attribiute])) {
                    $img_variant = $attributesImage[$id_product_attribiute][0];
                }
                $images = $product->getImages((int)$this->context->cookie->id_lang);
                $new_images = array();
                if (!empty($images)) {
                    $i = 1;
                    foreach ($images as $image) {
                        if (!empty($img_variant)) {
                            if ($image['id_image'] == $img_variant['id_image']) {
                                $new_images[0] = $image;
                            } else {
                                $new_images[$i] = $image;
                                $i++;
                            }
                        }
                    }
                    if (empty($new_images)) {
                        $new_images = $images;
                    }
                }
                if (!empty($new_images)) {
                    foreach ($new_images as $keyI => $image) {
                        $send['images'][$keyI]['url'] = $this->context->link->getImageLink($product->link_rewrite, (int)$image['id_image']);
                    }
                    ksort($send['images']);
                }
            } else {
                $images = $product->getImages((int)$this->context->cookie->id_lang);
                if (!empty($images)) {
                    foreach ($images as $keyI => $image) {
                        $send['images'][$keyI]['url'] = $this->context->link->getImageLink($product->link_rewrite, (int)$image['id_image']);
                    }
                }
            }
            // shop category list

            $categories = $product->getCategories();
            if (!empty($categories)) {
                $home = (int)Configuration::get('PS_HOME_CATEGORY');
                if (count($categories) == 1) {
                    $send['externalCategories'][0]['source'] = 'shop';
                    $send['externalCategories'][0]['index'] = 0;
                    foreach ($categories as $keyc => $c) {
                        $cat = new Category((int)$c, (int)$this->context->cookie->id_lang);
                        $send['externalCategories'][0]['breadcrumb'][$keyc]['name'] = $cat->name;
                        $send['externalCategories'][0]['breadcrumb'][$keyc]['id'] = $c;
                        if ($cat->id_parent != $home) {
                            $cat_parent = new Category((int)$cat->id_parent, (int)$this->context->cookie->id_lang);
                            $send['externalCategories'][0]['breadcrumb'][$keyc+1]['name'] = $cat_parent->name;
                            $send['externalCategories'][0]['breadcrumb'][$keyc+1]['id'] = $cat->id_parent;
                            if ($cat_parent->id != $home) {
                                $cat_parent2 = new Category((int)$cat_parent->id_parent, (int)$this->context->cookie->id_lang);
                                $send['externalCategories'][0]['breadcrumb'][$keyc+2]['name'] = $cat_parent2->name;
                                $send['externalCategories'][0]['breadcrumb'][$keyc+2]['id'] = $cat_parent->id_parent;
                                if ($cat_parent2->id != $home) {
                                    $cat_parent3 = new Category((int)$cat_parent2->id_parent, (int)$this->context->cookie->id_lang);
                                    $send['externalCategories'][0]['breadcrumb'][$keyc+3]['name'] = $cat_parent3->name;
                                    $send['externalCategories'][0]['breadcrumb'][$keyc+3]['id'] = $cat_parent2->id_parent;
                                }
                            }
                        }
                    }
                } else {

                }

            } else {
                $send['externalCategories'][0]['source'] = 'shop';
                $send['externalCategories'][0]['index'] = 0;
            }
            $fa = 0;
            $send['externalAttributes'][$fa]['id'] = '001';
            $send['externalAttributes'][$fa]['name'] = 'Stan';
            $send['externalAttributes'][$fa]['source'] = 'shop';
            $send['externalAttributes'][$fa]['type'] = 'string';
            $send['externalAttributes'][$fa]['index'] = $fa;
            $send['externalAttributes'][$fa]['values'][0] = $product->condition;
            $fa++;
            if (!empty($product->id_manufacturer)) {
                $manu = new Manufacturer((int)$product->id_manufacturer, (int)$this->context->cookie->id_lang);
                $send['externalAttributes'][$fa]['id'] = '002';
                $send['externalAttributes'][$fa]['name'] = 'Marka';
                $send['externalAttributes'][$fa]['source'] = 'shop';
                $send['externalAttributes'][$fa]['type'] = 'string';
                $send['externalAttributes'][$fa]['index'] = $fa;
                $send['externalAttributes'][$fa]['values'][0] = $manu->name;
                $fa++;
            }
            $attribs = array();
            foreach ($attributes as $key3 => $attribute2) {
                $attribs[$attribute2['group_name']][$attribute2['attribute_name']] = $attribute2['id_attribute'];
            }

            $attribute_designation = $attrib[$product->id.'-'.$id_product_attribiute]['attribute_designation'];
            $attribute_designation2 = explode(', ', $attribute_designation);
            $attribute_name_group = '';
            foreach ($attribute_designation2 as $ad2) {
                $attribute_designation3 = explode(' - ', $ad2);
                $attribute_name_group .= $attribute_designation3[0].', ';
            }
            $attribute_name_group = substr($attribute_name_group,0,  -2);
            $attrib_name = $attrib[$product->id.'-'.$id_product_attribiute];
            $attr_a = explode(',', $attrib_name['attribute_designation']);
            $count = count($attr_a);
            $name = '';
            if (!empty($attr_a)) {
                foreach ($attr_a as $key_a => $item) {
                    $n_v = explode(' - ', $item);
                    if ($count == 1) {
                        $name = trim($n_v[0]);
                    }
                    $id_attribute = $attribs[trim($n_v[0])][$n_v[1]];
//                                $send['externalAttributes'][$fa]['id'] = $product->id.'-'.$id_product_attribiute.'-'.$id_attribute;
                    $send['externalAttributes'][$fa]['id'] = $id_attribute;
                    $send['externalAttributes'][$fa]['name'] = trim($n_v[0]);
                    $send['externalAttributes'][$fa]['source'] = 'shop';
                    $send['externalAttributes'][$fa]['type'] = 'string';
                    $send['externalAttributes'][$fa]['index'] = $fa;
                    $send['externalAttributes'][$fa]['values'][0] = $n_v[1];
                    $fa++;
                }
            }
            $gn = $product->id.' '.$attribute_name_group;
            if ($count == 1) {
                $gn = $name;
            }
            $send['externalVariantGroup']['id'] = $gn;
            $send['externalVariantGroup']['source'] = 'integration';
            $send['externalVariantGroup']['attributes'] = array();
            if (!empty($img_variant)) {
                $send['externalVariantGroup']['attributes'][] = 'thumbnail';
                for ($i = 1;$i < ($count+1); $i++) {
                    $send['externalVariantGroup']['attributes'][] = $i;
                }
            } else {
                for ($i = 0;$i < $count; $i++) {
                    $send['externalVariantGroup']['attributes'][] = $i;
                }
            }

            ksort($send['externalVariantGroup']['attributes']);
            $features = Product::getFeaturesStatic((int)$id_product);
            if (!empty($features)) {
                foreach ($features as &$feature) {
                    if ($feature['id_feature'] > 0 && $feature['id_feature_value'] > 0) {
                        $feature_name = Db::getInstance()->getValue('SELECT `name` FROM `' . _DB_PREFIX_ . 'feature_lang` WHERE `id_feature` = ' . (int)$feature['id_feature'] . ' AND `id_lang` = ' . (int)$this->context->cookie->id_lang);
                        $feature['name'] = $feature_name;
                        $feature_value = Db::getInstance()->getValue('SELECT `value` FROM `' . _DB_PREFIX_ . 'feature_value_lang` WHERE `id_feature_value` = ' . (int)$feature['id_feature_value'] . ' AND `id_lang` = ' . (int)$this->context->cookie->id_lang);
                        $feature['value'] = $feature_value;

                        $send['externalAttributes'][$fa]['id'] = $product->id . '-' . $feature['id_feature_value'];
                        $send['externalAttributes'][$fa]['name'] = trim($feature['name']);
                        $send['externalAttributes'][$fa]['source'] = 'shop';
                        $send['externalAttributes'][$fa]['type'] = 'string';
                        $send['externalAttributes'][$fa]['index'] = $fa;
                        $send['externalAttributes'][$fa]['values'][0] = $feature['value'];
                        $fa++;
                    }
                }
            }

            $return = $erliApi->addProduct($send, (int)$id_product.'-'.$id_product_attribiute);
            $status = 0;
            if ($return['status'] == 202) {
                $status = 1;
                Erli::updateInErliProduct((int)$id_product);
                $produc_add = ErliSync::getProductAdd((int)$id_sync);
                $ile = $produc_add + 1;
                ErliSync::updateProductAdd((int)$id_sync, $ile);
                if ($upd == 1) {
                    $upd2 = ErliSync::getProductAddAll((int)$id_sync);
                    $ile2 = $upd2 + 1;
                    ErliSync::updateProductAddAll((int)$id_sync, $ile2);
                }
                ErliSync::removeProductSync((int)$id_product, (int)$id_product_attribiute, (int)$id_sync);
                ErliSync::deleteProductAttribSyncError((int)$id_sync, (int)$id_product, (int)$id_product_attribiute);
            } else {
                if ($this->debug) {
                    echo "add 1";
                    print_r($send);
                    print_r($return);
                }
                $cb = array(503);//, 429);
                if ($return['status'] == 400){
                    $return['body'] = 'Błąd podczas dodawania produktu. E#'.$return['status'].' '.$return['body'];
                } elseif ($return['status'] == 409) {
                    $bdy = json_decode($return['body']);
                    $body = $return['info'].': '.$bdy->failureType.' - '.$bdy->payload;
                    $return['body'] = 'Błąd podczas dodawania produktu. E#'.$return['status'].' '.$body;
                } else {
                    $body = $return['info'];
                    $return['body'] = 'Błąd podczas dodawania produktu. E#'.$return['status'].' '.$body;
                }
                ErliSync::addSyncError((int)$id_sync, 2, (int)$id_product, $return['status'], $return['body'], (int)$id_product_attribiute);
                ErliSync::updateProductSync((int)$id_product, (int)$id_product_attribiute, (int)$id_sync, $return['status']);
            }
            Erli::updateStatus((int)$id_product, $status);
        } else {
            $sp = null;
            $price = Product::priceCalculation($this->context->shop->id, $id_product, $id_product_attribiute, $this->context->country->id, 0, '', 1, 1, 1, true, 2, 0, 1, 1, $sp, 1);
            $send['name'] = mb_substr($product->name, 0, 75);
            $send['ean'] = $product->ean13;
            $send['sku'] = $product->reference;
            if ($erli_price == 1) {
                $price_type = Configuration::get('ERLI_PRODUCT_PRICE_TYPE');
                $price_action = Configuration::get('ERLI_PRODUCT_PRICE_ACTION');
                $price_value = Configuration::get('ERLI_PRODUCT_PRICE_VALUE');
                $price_cur = Configuration::get('ERLI_PRODUCT_PRICE_CUR');
                if ($price_type == 1) { // %
                    $mnoznik = 1;
                    if ($price_action == 1) { // down
                        $mnoznik = (100 - $price_value) / 100;
                    } else if ($price_action == 2) { // up
                        $mnoznik = 1 + ($price_value/100);
                    }
                    if ($price_cur == 1) { // netto
                        $price = $product->price * $mnoznik;
                        $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `'._DB_PREFIX_.'tax` t LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = '.$product->id_tax_rules_group.' AND tr.`id_country` = 14');
                        $tax = 1 + ($getRate / 100);
                        $price = $price * $tax;
                    } else if ($price_cur == 2) { // brutto
                        $price = $price * $mnoznik;
                    }
                } else if ($price_type == 2) { // amount
                    if ($price_cur == 1) { // netto
                        if ($price_action == 1) { // down
                            $price = $product->price - $price_value;
                        } else if ($price_action == 2) { // up
                            $price = $product->price + $price_value;
                        }
                        $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `'._DB_PREFIX_.'tax` t LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = '.$product->id_tax_rules_group.' AND tr.`id_country` = 14');
                        $tax = 1 + ($getRate / 100);
                        $price = $price * $tax;
                    } else if ($price_cur == 2) { // brutto
                        if ($price_action == 1) { // down
                            $price -= $price_value;
                        } else if ($price_action == 2) { // up
                            $price += $price_value;
                        }
                    }
                }
                $price = number_format($price, 2, '.', '');
            }

            $round = (int)Configuration::get('ERLI_PRICE_ROUND');
            if ($round) {
                $round_type = (int)Configuration::get('ERLI_PRICE_ROUND_TYPE');
                if ($round_type == 1) {
                    $price = round($price, 0,  PHP_ROUND_HALF_UP);
                } else {
                    $price = round($price, 0, PHP_ROUND_HALF_DOWN);
                }
            }

            $send['price'] = (int)($price * 100); // w groszach
            $send['status'] = 'active';
            $send['description'] = ($product->description);
            $send['dispatchTime']['period'] = $dispatchTime;
            $send['dispatchTime']['unit'] = 'day';
            $delivery_price_default = Configuration::get('ERLI_DELIVERY_PRICES');
            if (!is_null($productErli['deliveryPrice'])) {
                $delivery_price_default = $productErli['deliveryPrice'];
            }
            $send['packaging']['tags'][0] = $delivery_price_default;
            $send['deliveryPriceList'] = $delivery_price_default;
            $send['stock'] = StockAvailable::getQuantityAvailableByProduct((int)$id_product, $id_product_attribiute);
            $images = $product->getImages((int)$this->context->cookie->id_lang);
            if (!empty($images)) {
                foreach ($images as $keyI => $image) {
                    $send['images'][$keyI]['url'] = $this->context->link->getImageLink($product->link_rewrite, (int)$image['id_image']);
                }
            }
            $fa = 0;
            $send['externalAttributes'][$fa]['id'] = $product->id.'-001';
            $send['externalAttributes'][$fa]['name'] = 'Stan';
            $send['externalAttributes'][$fa]['source'] = 'shop';
            $send['externalAttributes'][$fa]['type'] = 'string';
            $send['externalAttributes'][$fa]['index'] = $fa;
            $send['externalAttributes'][$fa]['values'][0] = $product->condition;
            $fa++;
            if (!empty($product->id_manufacturer)) {
                $manu = new Manufacturer((int)$product->id_manufacturer, (int)$this->context->cookie->id_lang);
                $send['externalAttributes'][$fa]['id'] = $product->id.'-002';
                $send['externalAttributes'][$fa]['name'] = 'Marka';
                $send['externalAttributes'][$fa]['source'] = 'shop';
                $send['externalAttributes'][$fa]['type'] = 'string';
                $send['externalAttributes'][$fa]['index'] = $fa;
                $send['externalAttributes'][$fa]['values'][0] = $manu->name;
                $fa++;
            }
            $features = Product::getFeaturesStatic((int)$id_product);
            if (!empty($features)) {
                foreach ($features as &$feature) {
                    if ($feature['id_feature'] > 0 && $feature['id_feature_value'] > 0) {
                        $feature_name = Db::getInstance()->getValue('SELECT `name` FROM `' . _DB_PREFIX_ . 'feature_lang` WHERE `id_feature` = ' . (int)$feature['id_feature'] . ' AND `id_lang` = ' . (int)$this->context->cookie->id_lang);
                        $feature['name'] = $feature_name;
                        $feature_value = Db::getInstance()->getValue('SELECT `value` FROM `' . _DB_PREFIX_ . 'feature_value_lang` WHERE `id_feature_value` = ' . (int)$feature['id_feature_value'] . ' AND `id_lang` = ' . (int)$this->context->cookie->id_lang);
                        $feature['value'] = $feature_value;

                        $send['externalAttributes'][$fa]['id'] = $product->id . '-' . $feature['id_feature_value'];
                        $send['externalAttributes'][$fa]['name'] = trim($feature['name']);
                        $send['externalAttributes'][$fa]['source'] = 'shop';
                        $send['externalAttributes'][$fa]['type'] = 'string';
                        $send['externalAttributes'][$fa]['index'] = $fa;
                        $send['externalAttributes'][$fa]['values'][0] = $feature['value'];
                        $fa++;
                    }
                }
            }
            // shop category list
            $send['externalCategories'][0]['source'] = 'shop';
            $send['externalCategories'][0]['index'] = 0;
            $categories = $product->getCategories();
            if (!empty($categories)) {
                foreach ($categories as $key => $c) {
                    $cat = new Category((int)$c, (int)$this->context->cookie->id_lang);
                    $send['externalCategories'][0]['breadcrumb'][$key]['name'] = $cat->name;
                    $send['externalCategories'][0]['breadcrumb'][$key]['id'] = $c;
                }
            }
            $return = $erliApi->addProduct($send, (int)$id_product);
            $status = 0;
            if ($return['status'] == 202) {
                $status = 1;
                Erli::updateInErliProduct((int)$id_product);
                $produc_add = ErliSync::getProductAdd((int)$id_sync);
                $ile = $produc_add + 1;
                ErliSync::updateProductAdd((int)$id_sync, $ile);
                if ($upd == 1) {
                    $upd2 = ErliSync::getProductAddAll((int)$id_sync);
                    $ile2 = $upd2 + 1;
                    ErliSync::updateProductAddAll((int)$id_sync, $ile2);
                }
                ErliSync::removeProductSync((int)$id_product, (int)$id_product_attribiute, (int)$id_sync);
                ErliSync::deleteProductSyncError((int)$id_sync, (int)$id_product);
            } else {
                if ($this->debug) {
                    echo "add 2";
                    print_r($send);
                    print_r($return);
                }
                $cb = array(503);//, 429);
                $body = $return['body'];
                $return['body'] = 'Błąd podczas dodawania produktu. E#'.$return['status'].' '.$body;
                
                ErliSync::addSyncError((int)$id_sync, 2, (int)$id_product, $return['status'], $return['body']);
                ErliSync::updateProductSync((int)$id_product, (int)$id_product_attribiute, (int)$id_sync, $return['status']);
            }
            Erli::updateStatus((int)$id_product, $status);
        }
    }

    /**
     * @param $id_product
     * @param $id_product_attribute
     * @param $id_sync
     */
    private function updateProductInErli($id_product, $id_product_attribute, $id_sync, $add = 0)
    {
        $erli_price = Configuration::get('ERLI_PRODUCT_PRICE');
        $checkProduct = Configuration::get('ERLI_PRODUCT_CHANGE');
        $price_type = Configuration::get('ERLI_PRODUCT_PRICE_TYPE');
        $price_action = Configuration::get('ERLI_PRODUCT_PRICE_ACTION');
        $price_value = Configuration::get('ERLI_PRODUCT_PRICE_VALUE');
        $price_cur = Configuration::get('ERLI_PRODUCT_PRICE_CUR');
        $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
        $productErli = Erli::checkProductInErli((int)$id_product);
        $dispatchTime = (int)Configuration::get('ERLI_DELIVERY_TIME_DEFAULT');
        $updateAll = (int)Configuration::get('ERLI_PRODUCT_CHANGE_ALL');
        $imageAction = (int)Configuration::get('ERLI_IMAGE_ACTION');
        if ((int)$productErli['deliveryTime'] > 0) {
            $dispatchTime = (int)$productErli['deliveryTime'];
        }
        $send = array();
        $pro = new Product((int)$id_product, false, (int)$this->context->cookie->id_lang);
        if ((int)$id_product_attribute > 0) {
            $attributesImage = $pro->getCombinationImages((int)$this->context->cookie->id_lang);
            $attributes = $pro->getAttributeCombinations((int)$this->context->cookie->id_lang);
            $attr = $pro->getAttributesResume((int)$this->context->cookie->id_lang);
            $attrib = array();
            if (!empty($attr)) {
                foreach ($attr as $item) {
                    $attrib[$item['id_product'].'-'.$item['id_product_attribute']] = $item;
                }
            }
            $sp = null;
            $price = Product::priceCalculation($this->context->shop->id, $id_product, $id_product_attribute, $this->context->country->id, 0, '', 1, 1, 1, true, 2, 0, 1, 1, $sp, 1);
            $erliProduct = (array)$erliApi->getProduct($id_product.'-'.$id_product_attribute);
//            echo "<pre>";print_r($erliProduct);
            $productUpdate = Erli::getProduct($id_product);
            if ($checkProduct == 1) {
                if (!empty($productUpdate)) {
                    if ($productUpdate['name'] == 1) {
                        $send['name'] = $pro->name;
                    }
                    if ($productUpdate['ean'] == 1) {
                        $send['ean'] = $pro->ean13;
                    }
                    if ($productUpdate['reference'] == 1) {
                        $send['sku'] = $pro->reference;
                    }
                    if ($productUpdate['price'] == 1) {
                        if ($erli_price == 1) {
                            if ($price_type == 1) { // %
                                $mnoznik = 1;
                                if ($price_action == 1) { // down
                                    $mnoznik = (100 - $price_value) / 100;
                                } else if ($price_action == 2) { // up
                                    $mnoznik = 1 + ($price_value / 100);
                                }
                                if ($price_cur == 1) { // netto
                                    $price = $pro->price * $mnoznik;
                                    $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `' . _DB_PREFIX_ . 'tax` t LEFT JOIN `' . _DB_PREFIX_ . 'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = ' . $pro->id_tax_rules_group . ' AND tr.`id_country` = 14');
                                    $tax = 1 + ($getRate / 100);
                                    $price = $price * $tax;
                                } else if ($price_cur == 2) { // brutto
                                    $price = $price * $mnoznik;
                                }
                            } else if ($price_type == 2) { // amount
                                if ($price_cur == 1) { // netto
                                    if ($price_action == 1) { // down
                                        $price = $pro->price - $price_value;
                                    } else if ($price_action == 2) { // up
                                        $price = $pro->price + $price_value;
                                    }
                                    $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `' . _DB_PREFIX_ . 'tax` t LEFT JOIN `' . _DB_PREFIX_ . 'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = ' . $pro->id_tax_rules_group . ' AND tr.`id_country` = 14');
                                    $tax = 1 + ($getRate / 100);
                                    $price = $price * $tax;
                                } else if ($price_cur == 2) { // brutto
                                    if ($price_action == 1) { // down
                                        $price -= $price_value;
                                    } else if ($price_action == 2) { // up
                                        $price += $price_value;
                                    }
                                }
                            }
                            $price = number_format($price, 2, '.', '');
                        }
                        $round = (int)Configuration::get('ERLI_PRICE_ROUND');
                        if ($round) {
                            $round_type = (int)Configuration::get('ERLI_PRICE_ROUND_TYPE');
                            if ($round_type == 1) {
                                $price = round($price, 0,  PHP_ROUND_HALF_UP);
                            } else {
                                $price = round($price, 0, PHP_ROUND_HALF_DOWN);
                            }
                        }
                        $send['price'] = number_format($price * 100, 0, '.', ''); // w groszach
                    }
                    if ($productUpdate['quantity'] == 1) {
                        $send['stock'] = StockAvailable::getQuantityAvailableByProduct((int)$id_product, $id_product_attribute);
                    }
                    if ($productUpdate['description'] == 1) {
                        $send['description'] = ($pro->description);
                    }
                    if ($productUpdate['status'] == 1) {
                        $state = (int)$productErli['active'];
                        $send['status'] = ($state == 0) ? 'inactive' : 'active';
                    } else {
                        $send['status'] = ($pro->active == 0) ? 'inactive' : 'active';
                    }
                    $img_variant = array();
                    if (!empty($attributesImage)) {
                        if (isset($attributesImage[$id_product_attribute])) {
                            $img_variant = $attributesImage[$id_product_attribute][0];
                        }
                        $images = $pro->getImages((int)$this->context->cookie->id_lang);
                        $new_images = array();
                        if (!empty($images)) {
                            $i = 1;
                            foreach ($images as $image) {
                                if (!empty($img_variant)) {
                                    if ($image['id_image'] == $img_variant['id_image']) {
                                        $new_images[0] = $image;
                                    } else {
                                        $new_images[$i] = $image;
                                        $i++;
                                    }
                                }
                            }
                        }

                        if (!empty($new_images)) {
                            foreach ($new_images as $keyI => $image) {
                                $send['images'][$keyI]['url'] = $this->context->link->getImageLink($pro->link_rewrite, (int)$image['id_image']);
                            }
                            ksort($send['images']);
                        }
                    } else {
                        $images = $pro->getImages((int)$this->context->cookie->id_lang);
                        if (!empty($images)) {
                            foreach ($images as $keyI => $image) {
                                $send['images'][$keyI]['url'] = $this->context->link->getImageLink($pro->link_rewrite, (int)$image['id_image']);
                            }
                        }
                    }

                }
            } else {
                if ($erli_price == 1) {
                    if ($price_type == 1) { // %
                        $mnoznik = 1;
                        if ($price_action == 1) { // down
                            $mnoznik = (100 - $price_value) / 100;
                        } else if ($price_action == 2) { // up
                            $mnoznik = 1 + ($price_value / 100);
                        }
                        if ($price_cur == 1) { // netto
                            $price = $pro->price * $mnoznik;
                            $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `' . _DB_PREFIX_ . 'tax` t LEFT JOIN `' . _DB_PREFIX_ . 'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = ' . $pro->id_tax_rules_group . ' AND tr.`id_country` = 14');
                            $tax = 1 + ($getRate / 100);
                            $price = $price * $tax;
                        } else if ($price_cur == 2) { // brutto
                            $price = $price * $mnoznik;
                        }
                    } else if ($price_type == 2) { // amount
                        if ($price_cur == 1) { // netto
                            if ($price_action == 1) { // down
                                $price = $pro->price - $price_value;
                            } else if ($price_action == 2) { // up
                                $price = $pro->price + $price_value;
                            }
                            $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `' . _DB_PREFIX_ . 'tax` t LEFT JOIN `' . _DB_PREFIX_ . 'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = ' . $pro->id_tax_rules_group . ' AND tr.`id_country` = 14');
                            $tax = 1 + ($getRate / 100);
                            $price = $price * $tax;
                        } else if ($price_cur == 2) { // brutto
                            if ($price_action == 1) { // down
                                $price -= $price_value;
                            } else if ($price_action == 2) { // up
                                $price += $price_value;
                            }
                        }
                    }
                    $price = number_format($price, 2, '.', '');
                }
                $round = (int)Configuration::get('ERLI_PRICE_ROUND');
                if ($round) {
                    $round_type = (int)Configuration::get('ERLI_PRICE_ROUND_TYPE');
                    if ($round_type == 1) {
                        $price = round($price, 0,  PHP_ROUND_HALF_UP);
                    } else {
                        $price = round($price, 0, PHP_ROUND_HALF_DOWN);
                    }
                }
                $send['price'] = number_format($price * 100, 0, '.', ''); // w groszach
                $send['name'] = $pro->name;
                $send['ean'] = $pro->ean13;
                $send['sku'] = !empty($attribute['reference']) ? $attribute['reference'] : $pro->reference;
                $send['status'] = ($pro->active == 0) ? 'inactive' : 'active';
                $send['description'] = ($pro->description);
                $send['price'] = number_format($price * 100, 0, '.', ''); // w groszach
                $send['dispatchTime']['period'] = $dispatchTime;
                $send['dispatchTime']['unit'] = 'day';
                $delivery_price_default = Configuration::get('ERLI_DELIVERY_PRICES');
                if (!is_null($productErli['deliveryPrice'])) {
                    $delivery_price_default = $productErli['deliveryPrice'];
                }
                $send['packaging']['tags'][0] = $delivery_price_default;
                $send['deliveryPriceList'] = $delivery_price_default;
                $send['stock'] = StockAvailable::getQuantityAvailableByProduct((int)$id_product, $id_product_attribute);
                $img_variant = array();
                if (!empty($attributesImage)) {
                    if (isset($attributesImage[$id_product_attribute])) {
                        $img_variant = $attributesImage[$id_product_attribute][0];
                    }
                    $images = $pro->getImages((int)$this->context->cookie->id_lang);
                    $new_images = array();
                    if (!empty($images)) {
                        $i = 1;
                        foreach ($images as $image) {
                            if (!empty($img_variant)) {
                                if ($image['id_image'] == $img_variant['id_image']) {
                                    $new_images[0] = $image;
                                } else {
                                    $new_images[$i] = $image;
                                    $i++;
                                }
                            }
                        }
                    }
                    if (!empty($new_images)) {
                        foreach ($new_images as $keyI => $image) {
                            $send['images'][$keyI]['url'] = $this->context->link->getImageLink($pro->link_rewrite, (int)$image['id_image']);
                        }
                        ksort($send['images']);
                    }
                } else {
                    if ($imageAction == 0) {
                        $product_erli = $erliApi->getProduct($id_product);
                        $images_erli = $product_erli->images;
                        $image_in_erli = array();
                        if (!empty($images_erli)) {
                            foreach ($images_erli as $keyIE => $item) {
                                $image_in_erli[] = $item->url;
                                $send['images'][$keyIE]['url'] = $item->url;
                            }
                        }
                        $images = $pro->getImages((int)$this->context->cookie->id_lang);
                        if (!empty($images)) {
                            $next = count($images_erli);
                            foreach ($images as $image) {
                                $img = $this->context->link->getImageLink($pro->link_rewrite, (int)$image['id_image']);
                                if (!in_array($img, $image_in_erli)) {
                                    $send['images'][$next]['url'] = $img;
                                    $next++;
                                }
                            }
                        }
                    } else {
                        $images = $pro->getImages((int)$this->context->cookie->id_lang);
                        if (!empty($images)) {
                            foreach ($images as $keyI => $image) {
                                $send['images'][$keyI]['url'] = $this->context->link->getImageLink($pro->link_rewrite, (int)$image['id_image']);;
                            }
                        }
                    }
                }
            }
            if ($this->debug) {
                echo '#'.$id_product.'-'.$id_product_attribute.'<br />';
                print_r($send);
                echo "<br />";
            }
            $status = 0;
            if (!empty($send)) {
                $return = $erliApi->updateProduct($send, (int)$id_product.'-'.$id_product_attribute);
                if ($this->debug) {
                    print_r($return);
                    if ($return['status'] == 400) {
//                        exit();
                    }
                }
                if ($return['status'] == 202) {
                    $status = 1;
                    Erli::updateInErliProduct((int)$id_product);
                    $produc_upd = ErliSync::getProductUpdate((int)$id_sync);
                    $ile = $produc_upd + 1;
                    if ($add == 1) {
                        $upd = ErliSync::getProductUpdateAll((int)$id_sync);
                        $ile2 = $upd + 1;
                        ErliSync::updateProductUpdateAll((int)$id_sync, $ile2);
                        $add1 = ErliSync::getProductAddAll((int)$id_sync);
                        $add2 = $add1 - 1;
                        ErliSync::updateProductUpdateAll((int)$id_sync, $add2);
                    }
                    ErliSync::updateProductUpdate((int)$id_sync, $ile);
                    ErliSync::removeProductSync((int)$id_product, (int)$id_product_attribute, (int)$id_sync);
                    ErliSync::deleteProductAttribSyncError((int)$id_sync, (int)$id_product, (int)$id_product_attribute);
                } else {
                    $cb = array(400, 503);
                    $body = self::cleanBody($return['body']);
                    $body = json_decode($body);
                    if (isset($body->failureType)) {
                        $bdy = ' ['.$body->failureType.'] '.$body->payload;
                    } else {
                        $bdy = ': '.$body;
                    }
                    $return['body'] = 'Błąd podczas aktualizacji produktu. E#'.$return['status'].$bdy;
                    ErliSync::addSyncError((int)$id_sync, 2, (int)$id_product, $return['status'], $return['body'], (int)$id_product_attribute);
                    ErliSync::updateProductSync((int)$id_product, (int)$id_product_attribute, (int)$id_sync, $return['status']);
                }
                if ($this->debug) {
                    print_r($return);
                    echo "<br />";
                    print_r($erliApi->getProduct($id_product.'-'.$id_product_attribute));
                }
            }
            Erli::updateStatus($id_product, $status);
        } else {
            $sp = null;
            $price = Product::priceCalculation($this->context->shop->id, $id_product, $id_product_attribute, $this->context->country->id, 0, '', 1, 1, 1, true, 2, 0, 1, 1, $sp, 1);
            $erliProduct = (array)$erliApi->getProduct((int)$id_product);
            $productUpdate = Erli::getProduct((int)$id_product);
            if ($checkProduct == 1) {
                if (!empty($productUpdate)) {
                    if ($productUpdate['name'] == 1) {
                        $send['name'] = $pro->name;
                    }
                    if ($productUpdate['ean'] == 1) {
                        $send['ean'] = $pro->ean13;
                    }
                    if ($productUpdate['reference'] == 1) {
                        $send['sku'] = $pro->reference;
                    }
                    if ($productUpdate['price'] == 1) {
                        if ($erli_price == 1) {
                            if ($price_type == 1) { // %
                                $mnoznik = 1;
                                if ($price_action == 1) { // down
                                    $mnoznik = (100 - $price_value) / 100;
                                } else if ($price_action == 2) { // up
                                    $mnoznik = 1 + ($price_value/100);
                                }
                                if ($price_cur == 1) { // netto
                                    $price = $pro->price * $mnoznik;
                                    $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `'._DB_PREFIX_.'tax` t LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = '.$pro->id_tax_rules_group.' AND tr.`id_country` = 14');
                                    $tax = 1 + ($getRate / 100);
                                    $price = $price * $tax;
                                } else if ($price_cur == 2) { // brutto
                                    $price = $price * $mnoznik;
                                }
                            } else if ($price_type == 2) { // amount
                                if ($price_cur == 1) { // netto
                                    if ($price_action == 1) { // down
                                        $price = $pro->price - $price_value;
                                    } else if ($price_action == 2) { // up
                                        $price = $pro->price + $price_value;
                                    }
                                    $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `'._DB_PREFIX_.'tax` t LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = '.$pro->id_tax_rules_group.' AND tr.`id_country` = 14');
                                    $tax = 1 + ($getRate / 100);
                                    $price = $price * $tax;
                                } else if ($price_cur == 2) { // brutto
                                    if ($price_action == 1) { // down
                                        $price -= $price_value;
                                    } else if ($price_action == 2) { // up
                                        $price += $price_value;
                                    }
                                }
                            }
                            $price = number_format($price, 2, '.', '');
                        }
                        $round = (int)Configuration::get('ERLI_PRICE_ROUND');
                        if ($round) {
                            $round_type = (int)Configuration::get('ERLI_PRICE_ROUND_TYPE');
                            if ($round_type == 1) {
                                $price = round($price, 0,  PHP_ROUND_HALF_UP);
                            } else {
                                $price = round($price, 0, PHP_ROUND_HALF_DOWN);
                            }
                        }
                        $send['price'] = number_format($price * 100, 0, '.', ''); // w groszach
                    }
                    if ($productUpdate['quantity'] == 1) {
                        $send['stock'] = StockAvailable::getQuantityAvailableByProduct((int)$id_product, $id_product_attribute);
                    }
                    if ($productUpdate['description'] == 1) {
                        $send['description'] = ($pro->description);
                    }
                    if ($productUpdate['status'] == 1) {
                        $state = $productErli['active'];
                        $send['status'] = ($state == 0) ? 'inactive' : 'active';
                    } else {
                        $send['status'] = ($pro->active == 0) ? 'inactive' : 'active';
                    }
//                    $send['status'] = ($pro->active == 0) ? 'inactive' : 'active';
                    $ie = array();
                    $imagesErli = array();
                    if (isset($erliProduct->images)) {
                        $imagesErli = $erliProduct->images;
                        if (!empty($imagesErli)) {
                            foreach ($imagesErli as $item) {
                                $ie[] = $item->url;
                            }
                        }
                    }
                    $keyIm = count($ie);
                    if (count($imagesErli) < $productUpdate['images']) {
                        $images = $pro->getImages((int)$this->context->cookie->id_lang);
                        if (!empty($images)) {
                            foreach ($images as $keyI => $image) {
                                $url = $this->context->link->getImageLink($pro->link_rewrite, (int)$image['id_image']);
                                if (!in_array($url, $ie)) {
                                    $send['images'][$keyIm]['url'] = $url;
                                    $keyIm++;
                                }
                            }
                        }
                    }
                }
            } else {
                if ($erli_price == 1) {
                    if ($price_type == 1) { // %
                        $mnoznik = 1;
                        if ($price_action == 1) { // down
                            $mnoznik = (100 - $price_value) / 100;
                        } else if ($price_action == 2) { // up
                            $mnoznik = 1 + ($price_value/100);
                        }
                        if ($price_cur == 1) { // netto
                            $price = $pro->price * $mnoznik;
                            $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `'._DB_PREFIX_.'tax` t LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = '.$pro->id_tax_rules_group.' AND tr.`id_country` = 14');
                            $tax = 1 + ($getRate / 100);
                            $price = $price * $tax;
                        } else if ($price_cur == 2) { // brutto
                            $price = $price * $mnoznik;
                        }
                    } else if ($price_type == 2) { // amount
                        if ($price_cur == 1) { // netto
                            if ($price_action == 1) { // down
                                $price = $pro->price - $price_value;
                            } else if ($price_action == 2) { // up
                                $price = $pro->price + $price_value;
                            }
                            $getRate = DB::getInstance()->getValue('SELECT t.`rate` FROM `'._DB_PREFIX_.'tax` t LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON t.`id_tax` = tr.`id_tax` WHERE tr.`id_tax_rules_group` = '.$pro->id_tax_rules_group.' AND tr.`id_country` = 14');
                            $tax = 1 + ($getRate / 100);
                            $price = $price * $tax;
                        } else if ($price_cur == 2) { // brutto
                            if ($price_action == 1) { // down
                                $price -= $price_value;
                            } else if ($price_action == 2) { // up
                                $price += $price_value;
                            }
                        }
                    }
                    $price = number_format($price, 2);
                }
                $send['name'] = $pro->name;
                $send['ean'] = $pro->ean13;
                $send['sku'] = $pro->reference;
                $send['status'] = ($pro->active == 0) ? 'inactive' : 'active';
                $send['description'] = ($pro->description);
                $round = (int)Configuration::get('ERLI_PRICE_ROUND');
                if ($round) {
                    $round_type = (int)Configuration::get('ERLI_PRICE_ROUND_TYPE');
                    if ($round_type == 1) {
                        $price = round($price, 0,  PHP_ROUND_HALF_UP);
                    } else {
                        $price = round($price, 0, PHP_ROUND_HALF_DOWN);
                    }
                }
                $prc = (float)((float)$price * 100);
                $send['price'] = number_format($prc, 0, '.', ''); // w groszach
                $send['dispatchTime']['period'] = $dispatchTime;
                $send['dispatchTime']['unit'] = 'day';
                $delivery_price_default = Configuration::get('ERLI_DELIVERY_PRICES');
                if (!is_null($productErli['deliveryPrice'])) {
                    $delivery_price_default = $productErli['deliveryPrice'];
                }
                $send['packaging']['tags'][0] = $delivery_price_default;
                $send['deliveryPriceList'] = $delivery_price_default;
                $send['stock'] = StockAvailable::getQuantityAvailableByProduct((int)$id_product, $id_product_attribute);
                if ($imageAction == 0) {
                    $product_erli = $erliApi->getProduct($id_product);
                    $images_erli = $product_erli->images;
                    $image_in_erli = array();
                    if (!empty($images_erli)) {
                        foreach ($images_erli as $keyIE => $item) {
                            $image_in_erli[] = $item->url;
                            $send['images'][$keyIE]['url'] = $item->url;
                        }
                    }
                    $images = $pro->getImages((int)$this->context->cookie->id_lang);
                    if (!empty($images)) {
                        $next = count($images_erli);
                        foreach ($images as $image) {
                            $img = $this->context->link->getImageLink($pro->link_rewrite, (int)$image['id_image']);
                            if (!in_array($img, $image_in_erli)) {
                                $send['images'][$next]['url'] = $img;
                                $next++;
                            }
                        }
                    }
                } else {
                    $images = $pro->getImages((int)$this->context->cookie->id_lang);
                    if (!empty($images)) {
                        foreach ($images as $keyI => $image) {
                            $send['images'][$keyI]['url'] = $this->context->link->getImageLink($pro->link_rewrite, (int)$image['id_image']);;
                        }
                    }
                }
            }
            $status = 0;
            if (!empty($send)) {
                $return = $erliApi->updateProduct($send, (int)$id_product);
                if ($this->debug) {
                    print_r($return);
                    if ($return['status'] == 400) {
//                        exit();
                    }
                }
                if ($return['status'] == 202) {
                    $status = 1;
                    Erli::updateInErliProduct((int)$id_product);
                    $produc_upd = ErliSync::getProductUpdate((int)$id_sync);
                    $ile = $produc_upd + 1;
                    ErliSync::updateProductUpdate((int)$id_sync, $ile);
                    if ($add == 1) {
                        $upd = ErliSync::getProductUpdateAll((int)$id_sync);
                        $ile2 = $upd + 1;
                        ErliSync::updateProductUpdateAll((int)$id_sync, $ile2);
                        $add1 = ErliSync::getProductAddAll((int)$id_sync);
                        $add2 = $add1 - 1;
                        ErliSync::updateProductUpdateAll((int)$id_sync, $add2);
                    }
                    if ($checkProduct == 1) {
                        Erli::setProductUpdate((int)$id_product);
                    }
                    ErliSync::removeProductSync((int)$id_product, (int)$id_product_attribute, (int)$id_sync);
                    ErliSync::deleteProductSyncError((int)$id_sync, (int)$id_product);
                    if ($updateAll == 1) {
                        Erli::setProductAllUpdated((int)$id_product);
                    }
                } else {
                    $cb = array(400, 503);
                    $body = self::cleanBody($return['body']);
                    $body = json_decode($body);
                    if (isset($body->failureType)) {
                        $bdy = ' ['.$body->failureType.'] '.$body->payload;
                    } else {
                        $bdy = ': '.$body;
                    }
                    $return['body'] = 'Błąd podczas aktualizacji produktu. E#'.$return['status'].$bdy;
                    ErliSync::addSyncError((int)$id_sync, 2, (int)$id_product, $return['status'], $return['body']);
                    ErliSync::updateProductSync((int)$id_product, (int)$id_product_attribute, (int)$id_sync, $return['status']);
                }
            }
            Erli::updateStatus((int)$id_product, $status);
        }
    }

    /**
     * @param $id_product
     * @param $id_product_attribute
     * @param $id_sync
     */
    private function updateProductsStatusInErli($id_product, $id_product_attribute, $id_sync)
    {
        $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
        $send = array();
        if ((int)$id_product_attribute > 0) {
            $prods = $erliApi->getProduct($id_product.'-'.$id_product_attribute);
            $update = true;
            if (!empty($prods)) {
                if ($this->debug) {
//                    print_r($prods);
                }
                if (isset($prods->status)) {
                    $state = $prods->status;
                    if ($state == 'inactive') {
                        $update = false;
                    }
                } else {
                    if (isset($prods->failureType)) {
                        Erli::updateInErliProduct((int)$id_product, 0);
                        Erli::setDisableProductInErli((int)$id_product);
                    }
                }
            }
            if ($update) {
                $send['status'] = 'inactive';
                $send['overrideFrozen'] = 'true';
                $status = 0;
                if (!empty($send)) {
                    $return = $erliApi->updateProduct($send, (int)$id_product . '-' . $id_product_attribute);
                    if ($this->debug) {
                        print_r($return);
                    }
                    if ($return['status'] == 202) {
                        $status = 1;
                        Erli::updateInErliProduct((int)$id_product);
                        Erli::setDisableProductInErli((int)$id_product);
                        $produc_upd = ErliSync::getProductUpdate((int)$id_sync);
                        $ile = $produc_upd + 1;
                        ErliSync::updateProductUpdate((int)$id_sync, $ile);
                        $upd = ErliSync::getProductUpdateAll((int)$id_sync);
                        $ile2 = $upd + 1;
                        ErliSync::updateProductUpdateAll((int)$id_sync, $ile2);
                        ErliSync::removeProductSync((int)$id_product, (int)$id_product_attribute, (int)$id_sync);
                        Erli::deleteDisableProductAttributeInErli((int)$id_product, $id_product_attribute);
                        ErliSync::deleteProductSyncError((int)$id_sync, (int)$id_product);
                        ErliSync::deleteProductAttribSyncError((int)$id_sync, (int)$id_product, $id_product_attribute);
                    } else {
                        $cb = array(400, 503);//, 429);
                        if (in_array($return['status'], $cb)) {
                            $return['body'] = self::cleanBody($return['info']);
                        } elseif ($return['status'] == 429) {
                            $return['body'] = 'Błąd podczas aktualizacji statusu. Nieoczekiwany błąd: ' . $return['body'] . ' ' . $return['info'];
                        } elseif ($return['status'] == 404) {
                            ErliSync::removeProductSync((int)$id_product, (int)$id_product_attribute, (int)$id_sync);
                        } else {
                            $body = self::cleanBody($return['body']);
                            $body = json_decode($body);
                            if (isset($body->failureType)) {
                                $bdy = ' ['.$body->failureType.'] '.$body->payload;
                            } else {
                                $bdy = ': '.$body;
                            }
                            $return['body'] = 'Błąd podczas aktualizacji statusu. E#'.$return['status'].$bdy;
                        }
                        ErliSync::addSyncError((int)$id_sync, 2, (int)$id_product, $return['status'], $return['body'], $id_product_attribute);
                        ErliSync::updateProductSync((int)$id_product, (int)$id_product_attribute, (int)$id_sync, $return['status']);
                    }
                }
                Erli::updateStatus($id_product, $status);
            } else {
                ErliSync::removeProductSync((int)$id_product, (int)$id_product_attribute, (int)$id_sync);
            }
        } else {
            $prods = $erliApi->getProduct($id_product);
            $update = true;
            if (!empty($prods)) {
                if (isset($prods->status)) {
                    $state = $prods->status;
                    if ($state == 'inactive') {
                        $update = false;
                    }
                } else {
                    if (isset($prods->failureType)) {
                        Erli::updateInErliProduct((int)$id_product, 0);
                        Erli::setDisableProductInErli((int)$id_product);
                    }
                }
            }
            if ($update) {
                $status = 0;
                $send['status'] = 'inactive';
                $send['overrideFrozen'] = 'true';
                if (!empty($send)) {
                    $return = $erliApi->updateProduct($send, (int)$id_product);
                    if ($this->debug) {
                        print_r($return);
                    }
                    if ($return['status'] == 202) {
                        $status = 1;
                        Erli::updateInErliProduct((int)$id_product);
                        Erli::setDisableProductInErli((int)$id_product);
                        $produc_upd = ErliSync::getProductUpdate((int)$id_sync);
                        $ile = $produc_upd + 1;
                        ErliSync::updateProductUpdate((int)$id_sync, $ile);
                        $upd = ErliSync::getProductUpdateAll((int)$id_sync);
                        $ile2 = $upd + 1;
                        ErliSync::updateProductUpdateAll((int)$id_sync, $ile2);
                        ErliSync::removeProductSync((int)$id_product, (int)$id_product_attribute, (int)$id_sync);
                        ErliSync::deleteProductSyncError((int)$id_sync, (int)$id_product);
                        Erli::deleteDisableProductAttributeInErli((int)$id_product);
                    } else {
                        $cb = array(400, 503);//, 429);
                        if (in_array($return['status'], $cb)) {
                            $return['body'] = self::cleanBody($return['info']);
                        } elseif ($return['status'] == 429) {
                            $return['body'] = 'Błąd podczas aktualizacji statusu. Nieoczekiwany błąd: ' . $return['body'] . ' ' . $return['info'];
                        }elseif ($return['status'] == 404) {
                            ErliSync::removeProductSync((int)$id_product, (int)$id_product_attribute, (int)$id_sync);
                        } else {
                            $body = self::cleanBody($return['body']);
                            $body = json_decode($body);
                            if (isset($body->failureType)) {
                                $bdy = ' ['.$body->failureType.'] '.$body->payload;
                            } else {
                                $bdy = ': '.$body;
                            }
                            $return['body'] = 'Błąd podczas aktualizacji statusu. E#'.$return['status'].$bdy;
                        }
                        ErliSync::addSyncError((int)$id_sync, 2, (int)$id_product, $return['status'], $return['body']);
                        ErliSync::updateProductSync((int)$id_product, (int)$id_product_attribute, (int)$id_sync, $return['status']);
                    }
                }
                Erli::updateStatus((int)$id_product, $status);
            } else {
                ErliSync::removeProductSync((int)$id_product, (int)$id_product_attribute, (int)$id_sync);
            }
        }
    }

    private function removeFromErli($id_sync)
    {
        $products = Erli::getAllDisableProductAttributeInErli();
        if (!empty($products)) {
            echo "RMV 1<br />";
            $this->removeProductsFromErli($products, $id_sync);
        }
        sleep(5);
        $products = Erli::getAllDisableProductAttributeInErli();
        $this->removeFromErlis($id_sync, $products);
    }

    private function removeFromErli_($id_sync)
    {
        $products = Erli::getProductToUnActivated();
        if (!empty($products)) {
            echo "RMV 1<br />";
            $this->removeProductsFromErli($products, $id_sync);
        }
        $products = Erli::getProductToUnActivated();
        if (!empty($products)) {
            sleep(5);
            echo "RMV 2<br />";
            $this->removeProductsFromErli($products, $id_sync);
        }
        $products = Erli::getProductToUnActivated();
        if (!empty($products)) {
            sleep(10);
            echo "RMV 3<br />";
            $this->removeProductsFromErli($products, $id_sync);
        }
        $products = Erli::getProductToUnActivated();
        if (!empty($products)) {
            sleep(20);
            echo "RMV 4<br />";
            $this->removeProductsFromErli($products, $id_sync);
        }
        $products = Erli::getProductToUnActivated();
        if (!empty($products)) {
            sleep(30);
            echo "RMV 5<br />";
            $this->removeProductsFromErli($products, $id_sync);
        }
        $products = Erli::getProductToUnActivated();
        if (!empty($products)) {
            sleep(40);
            echo "RMV 6<br />";
            $this->removeProductsFromErli($products, $id_sync);
        }

        $products = Erli::getProductToUnActivated();
        $this->removeFromErlis($id_sync, $products);
    }

    private function removeFromErlis($id_sync, $productsToSync)
    {
        if (!empty($productsToSync)) {
            sleep(30);
            echo "disabled ".date('Y-m-d H:i:s')."<br />";
            $this->removeProductsFromErli($productsToSync, $id_sync);
        }
        $productsToSync = Erli::getAllDisableProductAttributeInErli();
        if (!empty($productsToSync)) {
            $this->removeFromErlis($id_sync, $productsToSync);
        }
    }

    private function removeFromErlis_($id_sync, $productsToSync)
    {
        if (!empty($productsToSync)) {
            sleep(30);
            echo "sync 7<br />";
            $this->removeProductsFromErli($productsToSync, $id_sync);
        }
        $productsToSync = Erli::getProductToUnActivated();
        if (!empty($productsToSync)) {
            $this->removeFromErlis($id_sync, $productsToSync);
        }
    }

    private function removeProductsFromErli($products, $id_sync)
    {
        if (!empty($products)) {
            foreach ($products as $product) {
                $this->updateProductsStatusInErli($product['id_product'], $product['id_product_attribute'], $id_sync);
                $same = Erli::getProductDisableProductAttributeInErli($product['id_product']);
                if (empty($same)) {
                    Erli::updateInErliProduct((int)$product['id_product'], 0);
                    Erli::setDisableProductInErli((int)$product['id_product']);
                }
                sleep(1);
            }
        }
    }

    private function removeProductsFromErli_($products, $id_sync)
    {
        if (!empty($products)) {
            foreach ($products as $product) {
                $pro = new Product((int)$product['id_product'], false, (int)$this->context->cookie->id_lang);
                $attr = $pro->getAttributesResume((int)$this->context->cookie->id_lang);
                if (!empty($attr)) {
                    foreach ($attr as $item) {
                        $this->updateProductsStatusInErli($product['id_product'], $item['id_product_attribute'], $id_sync);
                        sleep(1);
                    }
                } else {
                    $this->updateProductsStatusInErli($product['id_product'], 0, $id_sync);
                    sleep(1);
                }
                sleep(1);
            }
        }
    }

    /**
     * first sync "controller"
     */
    private function createFirstSync()
    {
        $debug = $_GET['clear'] ? true : false;
        if ($debug) {
            Db::getInstance()->delete('erli_first_sync', 'checked = 1');
            echo "wyczyszczono first sync";
            exit();
        }
        echo "start: ".date('Y-m-d H:i:s').'<br />';
        $id_sync = ErliSync::add(99);
        $this->addProductToFirstSync();
        $this->checkProductOnErli($id_sync);
        $products = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'erli_first_sync` WHERE `checked` = 1 AND externalId = ""');
        $this->checkProductOnErli($id_sync, $products);
        ErliSync::updateDateEnd((int)$id_sync);
        echo "end: ".date('Y-m-d H:i:s').'<br />';
    }

    /**
     * add all products and products with attributes to db first sync
     */
    private function addProductToFirstSync()
    {
        $products = DB::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'product` ORDER BY `id_product` ASC');
        if (!empty($products)) {
            foreach ($products as $product) {
                $pro = new Product((int)$product['id_product'], false, (int)$this->context->cookie->id_lang);
                $attr = $pro->getAttributesResume((int)$this->context->cookie->id_lang);
                if (!empty($attr)) {
                    foreach ($attr as $item) {
                        $iss = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_first_sync` WHERE `id_product` = '.(int)$product['id_product'].' AND `id_product_attribute` = '.$item['id_product_attribute']);
                        if (empty($iss)) {
                            Db::getInstance()->insert('erli_first_sync', array(
                                'externalId' => '',
                                'id_product' => $product['id_product'],
                                'id_product_attribute' => $item['id_product_attribute'],
                                'id_product_shop' => 0,
                                'ean' => $product['reference'],
                                'sku' => '',
                                'checked' => 0,
                                'date_add' => date('Y-m-d H:i:s'),
                                'date_upd' => date('Y-m-d H:i:s'),
                            ));
                        }
                    }
                } else {
                    $iss = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'erli_first_sync` WHERE `id_product` = '.(int)$product['id_product'].' AND `id_product_attribute` = 0');
                    if (empty($iss)) {
                        Db::getInstance()->insert('erli_first_sync', array(
                            'externalId' => '',
                            'id_product' => $product['id_product'],
                            'id_product_attribute' => 0,
                            'id_product_shop' => 0,
                            'ean' => $product['reference'],
                            'sku' => '',
                            'checked' => 0,
                            'date_add' => date('Y-m-d H:i:s'),
                            'date_upd' => date('Y-m-d H:i:s'),
                        ));
                    }
                }
            }
        }
    }

    /**
     *  check all product in first sync in erli.pl
     */
    private function checkProductOnErli($id_sync, $products = array())
    {
        if (empty($products)) {
            $products = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'erli_first_sync` WHERE `checked` = 0');
        }
        if (!empty($products)) {
            $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
            $add = 0;
            foreach ($products as $product) {
                if ($product['id_product_attribute'] == 0) {
                    $id_product = $product['id_product'];
                } else {
                    $id_product = $product['id_product'].'-'.$product['id_product_attribute'];
                }
                $result = (array)$erliApi->getProduct($id_product);
                $ext = '';
                if ((isset($result['status']) && $result['status'] == 200) || (isset($result['marketplaceId']) && !empty($result['marketplaceId']))) {
                    if ((isset($result['archived']) && $result['archived'] != true) || !isset($result['archived'])) {
                        Db::getInstance()->update('erli_product', array(
                            'inErli' => 1,
                            'status' => 1,
                            'active' => 1,
                        ), 'id_product = ' . (int)$id_product);
                    } elseif (isset($result['archived']) && $result['archived'] == true) {
                        $changeArchived = (int)Configuration::get('ERLI_FS_ARCHIVED');
                        $active = 0;
                        if ($changeArchived) {
                            $data['archived'] = false;
                            $return = (array)$erliApi->updateProduct($data, $id_product);
                            if ($return['status'] == 202) {
                                $active = 1;
                            }
                        }
                        Db::getInstance()->update('erli_product', array(
                            'inErli' => 1,
                            'status' => 1,
                            'active' => $active,
                        ), 'id_product = ' . (int)$id_product);
                    }
                    $ext = $result['externalId'];
                    $add++;
                    ErliSync::deleteProductAttribSyncError((int)$id_sync, (int)$product['id_product'], (int)$product['id_product_attribute']);
                    ErliSync::removeProductSync((int)$product['id_product'], (int)$product['id_product_attribute'], (int)$id_sync);
                } else {
                    $result['status'] = 404;
                    $body = $result;
                    if (isset($body['failureType'])) {
                        $bdy = ' ['.$body['failureType'].'] '.$body['payload'];
                    } else {
                        $bdy = ' nieznany błąd ';
                    }
                    $result['body'] = 'Błąd podczas firstSync. E#404'.$bdy;
                    ErliSync::addSyncError((int)$id_sync, 2, (int)$product['id_product'], $result['status'], $result['body'], (int)$product['id_product_attribute']);
                    ErliSync::updateProductSync((int)$product['id_product'], (int)$product['id_product_attribute'], (int)$id_sync, $result['status']);
                }
                Db::getInstance()->update('erli_first_sync', array(
                    'checked' => 1,
                    'externalId' => $ext,
                    'date_upd' => date('Y-m-d H:i:s'),
                ),'id_fs = '.$product['id_fs']);
                sleep(1);
            }
        } else {
            echo "brak produktów do synchronizacji.<br />";
        }
    }

    private function getPriceList()
    {
        $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
        $request = $erliApi->getPriceList();
        $prices  = str_replace('[','', $request['body']);
        $prices  = str_replace(']','', $prices);
        $prices  = str_replace('"','', $prices);
        $prices  = explode(',', $prices);
        if (!empty($prices)) {
            foreach ($prices as $name) {
                $price = Erli::getDeliveryPrices($name);
                if (empty($price)) {
                    Erli::addDeliveryPrices($name);
                }
            }
        }
    }

    private function setTracking()
    {
        $orders = ErliOrder::getOrdersList();
        if (!empty($orders)) {
            $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
            foreach ($orders as $order) {
                if ((int)$order['id_order_shop'] > 0) {
                    $shipping_number = Db::getInstance()->getValue('SELECT `tracking_number` FROM `' . _DB_PREFIX_ . 'order_carrier` WHERE `id_order` = ' . (int)$order['id_order_shop']);
                    if (!empty($shipping_number)) {
                        $deliveryErli = ErliOrder::getOrderDelivery((int)$order['id_order']);
                        if (empty($deliveryErli['trackingNumber'])) {
                            $data['deliveryTracking']['trackingNumber'] = $shipping_number;
                            $data['deliveryTracking']['status'] = 'sent';
                            $data['deliveryTracking']['vendor'] = Erli::getVendorByDelivery($deliveryErli['typeId']);
                            $request = $erliApi->updateOrder($order['id_payload'], $data);
                            if ($request['status'] == 202) {
                                Db::getInstance()->update('erli_order_delivery', array(
                                    'sendTracking' => 1,
                                    'trackingNumber' => $shipping_number,
                                ), 'id_order = ' . (int)$order['id_order']);
                            } else {
//                                die($request);
                                $body = $request;
                                ErliSync::addSyncError(0, 99, (int)$order['id_order'], $request['status'], $body);
                            }
                        }
                    }
                }
            }
        }
        echo "END";
        exit();
    }

    private function searchProduct2()
    {
        $product_count = 13000;
        $limit = 200;
        $steps = ceil($product_count / $limit);
        if ($steps > 0) {
            Db::getInstance()->delete('erli_product_search');
            $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
            for ($i = 1;$i <= $steps; $i++) {
                $last = '';
                if ($i > 1) {
                    $last = (int)Configuration::get('ERLI_SEARCH_LAST_2');
                }
                $request = $erliApi->searchProducts($limit, $last);
                if ($request['status'] == 200) {
                    $products = json_decode($request['body']);
                    if (!empty($products)) {
                        foreach ($products as $product) {
                            $pro2 = Db::getInstance()->getRow('SELECT `id_product`, `name` FROM `'._DB_PREFIX_.'product_lang` WHERE `id_lang` = 1 AND `id_product` = '.$product->externalId);
                            $pro3 = Db::getInstance()->getRow('SELECT `reference` FROM `'._DB_PREFIX_.'product` WHERE `id_product` = '.$product->externalId);
                            Db::getInstance()->insert('erli_product_search', array(
                                'externalId' => $product->externalId,
                                'name' => addslashes($product->name),
                                'status' => $product->status,
                                'inShop' => !empty($pro2) ? 1 : 0,
                                'shopName' => addslashes($pro2['name']),
                                'sku' => $product->sku,
                                'reference' => $pro3['reference'],
                            ));
                            Configuration::updateValue('ERLI_SEARCH_LAST_2', $product->externalId);
                        }
                    }
                }
            }
        }
        echo "DONE";
        exit();
    }

    private function searchProduct()
    {
        $product_count = 21000;
        $limit = 200;
        $steps = ceil($product_count / $limit);
        if ($steps > 0) {
            $erliApi = new ErliAPI(Configuration::get('ERLI_API_TOKEN'), $this->configuration, (int)Configuration::get('ERLI_API_SANDBOX'));
            for ($i = 1;$i <= $steps; $i++) {
                $last = '';
                if ($i > 1) {
                    $last = Configuration::get('ERLI_SEARCH_LAST');
                }
                $request = $erliApi->searchProducts($limit, $last);
                if ($request['status'] == 200) {
                    $products = json_decode($request['body']);
                    if (!empty($products)) {
                        foreach ($products as $product) {
                            $pro = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'product_shop` WHERE id_product = '.$product->externalId);
                            if (empty($pro)) {
                                if ($product->status != 'inactive') {
                                    $erliApi->delectProduct($product->externalId);
                                }
                            }
                            Configuration::updateValue('ERLI_SEARCH_LAST', $product->externalId);
                        }
                    }
                }
            }
        }
        echo "DONE";
        exit();
    }

}
