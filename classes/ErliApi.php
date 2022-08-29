<?php
/**
 * erli.pl API Info: https://erli.pl/svc/shop-api/doc/reference/
 * @author PrestaHelp
 * @author Rafał Przybylski
 * @version 1.0
 *
 */

class ErliApi
{
    private $bearerKey;

    private $sandbox_url = 'https://sandbox.erli.dev/svc/shop-api/';

    private $production_url = 'https://erli.pl/svc/shop-api/';

    public $api_url;

    public $configuration;

    public static $deliveryTime = array(
        1 => '24h',
        2 => '48h',
        5 => '3-5 dni',
        10 => '6-10 dni',
        14 => 'do 14 dni',
        30 => 'do 30 dni',
        45 => 'do 45 dni',
        60 => 'do 60 dni',
        90 => 'do 90 dni',
    );

    const ERLI_ORDER_CREATED = 'orderCreated';

    const ERLI_ORDER_CHANGED = 'orderStatusChanged';

    public function __construct($bearerKey, $configuration, $sandbox = false)
    {
        $this->bearerKey = $bearerKey;
        $this->configuration = $configuration;
        $this->api_url = $this->production_url;
        if ($sandbox) {
            $this->api_url = $this->sandbox_url;
        }
    }

    /**
     * Creaye a new product
     * @param array $product - product erli structure
     * @param int $id_product - product ID
     * @return array
     */
    public function addProduct($product, $id_product)
    {
        $body = json_encode($product);
        return $this->request_curl('products/'.$id_product, 'POST', $body);
    }

    /**
     * Update product in erli
     * @param array $product - list of product changes
     * @param int $id_product - product ID
     * @return array
     */
    public function updateProduct($product, $id_product)
    {
        $body = json_encode($product);
        return $this->request_curl('products/'.$id_product, 'PATCH', $body);
    }

    /**
     * Delete product in erli - set inactive status
     * @param int $id_product - product ID
     * @return array
     */
    public function delectProduct($id_product)
    {
        $productDelete['status'] = 'inactive';
        $body = json_encode($productDelete);
        return $this->request_curl('products/'.$id_product, 'PATCH', $body);
    }

    /**
     * @param int $id_product - product ID
     * @return array ['body'] - json
     */
    public function getProduct($id_product)
    {
        $return = $this->request_curl('products/'.$id_product, 'GET');
        return json_decode($return['body']);
    }

    /**
     * @param int $limit
     * @param string $last
     * @return array
     */
    public function searchProducts($limit = 200, $last = '')
    {
        $body['pagination']['sortField'] = 'externalId';
        $body['pagination']['order'] = 'ASC';
        $body['pagination']['limit'] = $limit;

        if (!empty($last)) {
            $body['pagination']['after'] = $last;
        }

        $body['fields'][] = 'name';
        $body['fields'][] = 'externalId';
        $body['fields'][] = 'status';
        $body['fields'][] = 'sku';

        $body = json_encode($body);
        return $this->request_curl('products/_search', 'POST', $body);
    }

    /**
     * Get a list of categories
     * @return array ['body'] - json
     */
    public function getCategoryList()
    {
        return $this->request_curl('dictionaries/categories', 'GET');
    }

    /**
     * Get a list of delivery prices
     * @return array - ['body'] - string
     */
    public function getPriceList()
    {
        return $this->request_curl('delivery/priceLists', 'GET');
    }

    /**
     * Get a list of delivery methods
     * @return array - ['body'] - json
     */
    public function getDeliveryMethods()
    {
        return $this->request_curl('dictionaries/deliveryMethods', 'GET');
    }

    /**
     * Get a list of shipping companies
     * @return array ['body'] - json
     */
    public function getDeliveryVendors()
    {
        return $this->request_curl('dictionaries/deliveryVendors', 'GET');
    }

    /**
     * Get the 500 oldest unread messages
     * @return array ['body'] - json
     */
    public function getInbox()
    {
        return $this->request_curl('inbox', 'GET');
    }

    /**
     * Mark message as read
     * @param string $id_message - id message to mark as read
     * @return array
     */
    public function setInboxReadMark($id_message)
    {
        $message['lastMessageId'] = $id_message;
        $body = json_encode($message);
        return $this->request_curl('inbox/mark-read', 'POST', $body);
    }

    /**
     * @param string $id_order
     * @param $data
     * @return array
     */
    public function updateOrder($id_order, $data)
    {
        $body = json_encode($data);
        return $this->request_curl('orders/'.$id_order, 'PATCH', $body);
    }

    /**
     * Register hook in erli.pl
     * @param $hookName
     * @param $url
     * @return array
     */
    public function registerHook($hookName, $url)
    {
        $this->getHookList();
        $body['hookName'] = $hookName;
        $body['url'] = $url;
        $body['accessToken'] = $this->bearerKey;
        $body = json_encode($body);
        return $this->request_curl('hooks/'.$hookName, 'PUT', $body);
    }

    /**
     * Get hook list add to erli.pl
     * @return array
     */
    public function getHookList()
    {
        return $this->request_curl('hooks', 'GET', '');
    }

    public function getOrder($externalId)
    {
        return $this->request_curl('orders/'.$externalId, 'GET', '');
    }

    /**
     * Get payment information
     * @param $id_payment
     * @return array
     */
    public function getPaymentInfo($id_payment)
    {
        return $this->request_curl('payments/'.$id_payment, 'GET', '');
    }

    /**
     * @param $orderId
     * @param int $limit
     * @return array
     */
    public function getPaymentsOrderInfo($orderId, $limit = 1)
    {
        $body['pagination']['sortField'] = 'createdAt';
        $body['pagination']['order'] = 'DESC';
        $body['pagination']['limit'] = $limit;
        $body['filter']['field'] = 'orderId';
        $body['filter']['value'] = $orderId;
        $body['filter']['operator'] = '=';
        $body = json_encode($body);
        return $this->request_curl('payments/_search', 'POST', $body);
    }

    /**
     * Request - send / get data
     * @param $url
     * @param $method
     * @param $body
     * @return array
     */
    private function request_curl($url, $method, $body = '')
    {
        $apiUrl = $this->api_url.$url;
        $curl = curl_init();
        $verbose = $fp = fopen(__DIR__.'/errorlog.txt', 'w');
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method == 'PATCH' ? 'PATCH' : ( $method == 'POST' ? 'POST' : ( $method == 'PUT' ? 'PUT' : 'GET')),
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$this->bearerKey,
                'Content-Type: application/json'
            ),
            CURLOPT_VERBOSE => true,
            CURLOPT_STDERR => $verbose
        ));

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        $return['status'] = $info['http_code'];
        $return['body'] = $response;
        switch ($info['http_code']) {
            case 200:
                $return['info'] = 'Pomyślnie pobrano';
                break;
            case 202:
                $return['info'] = 'Pomyślnie zaktualizowano';
                break;
            case 404:
                $return['info'] = 'E#404 - Nie znaleziono produktu';
                break;
            case 503:
                $return['info'] = 'E#503 - Usługa chwilowo niedostępna. '.$response;
                break;
            default:
                $return['info'] = 'E#'.$info['http_code'].' - Nieoczekiwany błąd';
                break;
        }
        return $return;
    }

}
