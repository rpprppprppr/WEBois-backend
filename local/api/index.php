<?php

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: http://127.0.0.1:5500');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Content-Length: 0');
    header('Content-Type: text/plain');
    http_response_code(200);
    exit();
}

header('Access-Control-Allow-Origin: http://127.0.0.1:5500');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
require($_SERVER['DOCUMENT_ROOT'].'/local/vendor/autoload.php');

use Bitrix\Main\Context;
use Legacy\General\Api;

header("HTTP/1.1 200 OK");

$request = Context::getCurrent()->getRequest();
$namespace = '\Legacy\API';
$class = $namespace.'\\'.ucwords($request->get('CLASS'));
$method = $request->get('METHOD');
$arRequest = $request->toArray();
unset($arRequest['CLASS'], $arRequest['METHOD']);
$request->set($arRequest);

$api = Api::getInstance();

header('Content-Type: application/json; charset=utf-8');
echo $api->execute($class, $method);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');