<?php

require_once __DIR__.'/../../../config/config.inc.php';
require_once __DIR__.'/../pherli.php';

$id_product = (int)$_REQUEST['idProduct'];
$id_product_attribute = 0;

$erli = new pherli();

echo json_encode($erli->deleteProductToErli((int)$id_product, (int)$id_product_attribute));
exit();
