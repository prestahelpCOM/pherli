<?php

require_once __DIR__.'/../../classes/ErliSync.php';

class AdminErliSyncController extends ModuleAdminController
{

    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'AdminErliSync';
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

        if (Tools::getIsset('detail')) {
            $id_sync = (int)Tools::getValue('detail');
            $sync = ErliSync::getSyncInfo((int)$id_sync);
            if (!empty($sync)) {
                switch ($sync['type']) {
                    case 1:
                        $name = "Synchronizacja zamówień";
                        break;
                    case 2:
                        $name = "Synchronizacja produktów";
                        break;
                    case 3:
                        $name = "Synchronizacja cen i stanów";
                        break;
                    case 99:
                        $name = 'firstSync';
                        break;
                    default:
                        $name = '';
                        break;
                }
                $sync['name'] = $name;
            }
            $errors = ErliSync::getSyncError((int)$id_sync);
            if (!empty($errors)) {
                foreach ($errors as &$error) {
                    $productError = null;
                    $productName = '';
                    if ($sync['type'] > 1) {
                        $product = new Product((int)$error['id'], false, (int)$this->context->cookie->id_lang);
                        if (!empty($product)) {
                            $ida = '';
                            if ($error['ida'] > 0) {
                                $ida .= '<br />ID atrybutu: '.$error['ida'];
                            }
                            $productName = $product->name . '<br />ID: ' . $error['id'].$ida . '<br />Indeks: ' . $product->reference;
                        } else {
                            $productName = 'ID: ' . $error['id'];
                            if ($error['ida'] > 0) {
                                $productName .= '<br />ID atrybutu: '.$error['ida'];
                            }
                        }

                        switch ($error['status']) {
                            case 409:
                                $body2 = str_replace('Błąd E#409 ', '', $error['body']);
                                $body = json_decode($body2);
                                if (isset($body->failureType)) {
                                    $productError = ErliSync::getErrorTypeName($body->failureType) . ': ' . ErliSync::getErrorDetail($body->payload);
                                } else {
                                    if (isset($body->payload)) {
                                        $productError = 'Błąd: ' . ErliSync::getErrorDetail($body->payload);
                                    } else {
                                        $productError = $error['body'];
                                    }
                                }
                                break;
                            case 400:
                                $productError = $error['body'];
                                break;
                            case 429:case 404:
                                $productError = $error['body'];
                                break;
                        }
                    }
                    $error['product_name'] = $productName;
                    $error['error'] = $productError;
                }
            }
            $this->context->smarty->assign(array(
                'sync' => $sync,
                'errors' => $errors,
                'orderShop' => $this->context->link->getAdminLink($this->className),
            ));
        } else {
            $sync_list = ErliSync::getSyncList(25);
            if (!empty($sync_list)) {
                foreach ($sync_list as &$item) {
                    switch ($item['type']) {
                        case 1:
                            $name = "Synchronizacja zamówień";
                            break;
                        case 2:
                            $name = "Synchronizacja produktów";
                            break;
                        case 3:
                            $name = "Synchronizacja cen i stanów";
                            break;
                        case 99:
                            $name = 'firstSync';
                            break;
                        default:
                            $name = '';
                            break;
                    }
                    $item['name'] = $name;
                    $errors = ErliSync::getSyncError((int)$item['id_sync']);
                    $isError = 0;
                    if (!empty($errors)) {
                        $isError = 1;
                    }
                    $item['isError'] = $isError;
                    $url = $this->context->link->getAdminLink('AdminErliSync') . '&detail=' . (int)$item['id_sync'];
                    $item['url'] = $url;

                    $item['no'] = count($errors);
                }
            }

            $this->context->smarty->assign(array(
                'sync_list' => $sync_list,
            ));
        }
    }

    public function display()
    {
        if (Tools::getIsset('detail')) {
            $tpl = 'sync_detail.tpl';
        } else {
            $tpl = 'synchro.tpl';
        }
        $this->context->smarty->assign(array(
            'content' => $this->context->smarty->fetch($this->getTemplatePath() . $tpl)
        ));
        parent::display();
    }

    public function postProcess()
    {
        parent::postProcess();
    }

    public static function decode($encode, $stdClass = false)
    {
        $pos = 0;
        $slen = is_string($encode) ? strlen($encode) : null;
        if ($slen !== null) {
            $error = error_reporting(0);
            $result = self::__decode($encode, $pos, $slen, $stdClass);
            error_reporting($error);
            restore_error_handler();
        } else {
            $result = null;
        }
        return $result;
    }

    private static function __decode(&$encode, &$pos, &$slen, &$stdClass)
    {
        switch($encode{$pos}) {
            case 't':
                $result = true;
                $pos += 4;
                break;
            case 'f':
                $result = false;
                $pos += 5;
                break;
            case 'n':
                $result = null;
                $pos += 4;
                break;
            case '[':
                $result = array();
                ++$pos;
                while($encode{$pos} !== ']') {
                    array_push($result, self::__decode($encode, $pos, $slen, $stdClass));
                    if($encode{$pos} === ',')
                        ++$pos;
                }
                ++$pos;
                break;
            case '{':
                $result = $stdClass ? new stdClass : array();
                ++$pos;
                ++$pos;
                while($encode{$pos} !== '}') {
                    $tmp = self::__decodeString($encode, $pos);
                    ++$pos;
                    if($stdClass)
                        $result->$tmp = self::__decode($encode, $pos, $slen, $stdClass);
                    else
                        $result[$tmp] = self::__decode($encode, $pos, $slen, $stdClass);
                    if($encode{$pos} === ',' && $encode{$pos} === '"')
                        ++$pos;
                    if($encode{$pos} === ':')
                        ++$pos;
                    if($encode{$pos} === '{')
                        ++$pos;
                    if($encode{$pos} === '"')
                        ++$pos;
                }
                ++$pos;
                break;
            case ':':
//                continue;
                break;
            case '"':
                switch($encode{++$pos}) {
                    case '"':
                        $result = "";
                        break;
                    default:
                        $result = self::__decodeString($encode, $pos);
                        break;
                }
                ++$pos;
                break;
            default:
                $tmp = '';
                preg_replace('/^(\-)?([0-9]+)(\.[0-9]+)?([eE]\+[0-9]+)?/e', '$tmp = "\\1\\2\\3\\4"', substr($encode, $pos));
                if($tmp !== '') {
                    $pos += strlen($tmp);
                    $nint = intval($tmp);
                    $nfloat = floatval($tmp);
                    $result = $nfloat == $nint ? $nint : $nfloat;
                }
                break;
        }
        return $result;
    }

    static private function __decodeString(&$encode, &$pos) {
        $replacement = self::__getStaticReplacement();
        $endString = self::__endString($encode, $pos, $pos);
        $result = str_replace($replacement['replace'], $replacement['find'], substr($encode, $pos, $endString));
        $pos += $endString;
        return $result;
    }

    static private function __endString(&$encode, $position, &$pos) {
        do {
            $position = strpos($encode, '"', $position + 1);
        }while($position !== false && self::__slashedChar($encode, $position - 1));
        if($position === false)
            trigger_error('', E_USER_WARNING);
        return $position - $pos;
    }

    static private function __exit($str, $a, $b) {
        exit($a.'FATAL: decode method failure [malicious or incorrect JSON string]');
    }

    static private function __slashedChar(&$encode, $position) {
        $pos = 0;
        while($encode{$position--} === '\\')
            $pos++;
        return $pos % 2;
    }

    private static function __getStaticReplacement()
    {
        static $replacement = array('find'=>array(), 'replace'=>array());
        if($replacement['find'] == array()) {
            foreach(array_merge(range(0, 7), array(11), range(14, 31)) as $v) {
                $replacement['find'][] = chr($v);
                $replacement['replace'][] = "\\u00".sprintf("%02x", $v);
            }
            $replacement['find'] = array_merge(array(chr(0x5c), chr(0x2F), chr(0x22), chr(0x0d), chr(0x0c), chr(0x0a), chr(0x09), chr(0x08)), $replacement['find']);
            $replacement['replace'] = array_merge(array('\\\\', '\\/', '\\"', '\r', '\f', '\n', '\t', '\b'), $replacement['replace']);
        }
        return $replacement;
    }


}
