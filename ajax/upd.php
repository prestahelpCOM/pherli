<?php

require_once __DIR__.'/../../../config/config.inc.php';
require_once __DIR__.'/../pherli.php';

$id_product = (int)$_REQUEST['idProduct'];
$deliveryTime = (int)$_REQUEST['deliveryTime'];
$deliveryPrice = isset($_REQUEST['deliveryPrice']) ? $_REQUEST['deliveryPrice'] : 0;
$active = (int)$_REQUEST['active'];
$id_product_attribute = 0;

$erli = new pherli();

echo json_encode($erli->updateProductToErli((int)$id_product, (int)$id_product_attribute, (int)$deliveryTime, $deliveryPrice, (int)$active));
exit();
