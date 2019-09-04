<?php 
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/QR/qrlib.php';
use Lime\Request;
//use Lime\Qr;
$request = new Request();
$items = [
    [
        "id" => 0,
        "cname" => "kids",
        "name" => "Детский билет",
        "price" => 10600,
        "limeid" => 1065
    ]
];
$qrs = $request->order($items);
//$qr = new Qr();
foreach($qrs as $qr) {
    QRcode::png($qr, __DIR__ . "/tickets/1.png", QR_ECLEVEL_L, 10);
}

//$generated = $qr->setQrCodes($qrs)->generate();
//print_r($generated);
