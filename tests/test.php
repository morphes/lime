<?php 
require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload
use Lime\Request;
$request = new Request();
var_dump($request->checkInstallation());
die();
echo Request::test();
