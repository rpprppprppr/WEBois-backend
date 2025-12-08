<?php

define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

spl_autoload_register(function ($class) {
    if (strpos($class, 'api\classes\\') === 0) {
        $relative_class = substr($class, strlen('api\classes\\'));
        $file = $_SERVER['DOCUMENT_ROOT'] . '/local/api/classes/' . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    elseif (strpos($class, 'Legacy\\Iblock\\') === 0) {
        $relative_class = substr($class, strlen('Legacy\\Iblock\\'));
        $file = $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Iblock/' . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    return false;
});

header("HTTP/1.1 200 OK");
header('Content-Type: application/json; charset=utf-8');

use \Bitrix\Main\Context;

$request = Context::getCurrent()->getRequest();
$arRequest = $request->toArray();

$requestUri = $_SERVER['REQUEST_URI'];

$apiPaths = [
    '/local/api/',
    '/api/'
];

$scriptPath = '';
foreach ($apiPaths as $path) {
    if (strpos($requestUri, $path) === 0) {
        $scriptPath = $path;
        break;
    }
}

if (!empty($scriptPath)) {
    $apiPath = substr($requestUri, strlen($scriptPath));
    $pathParts = explode('/', trim($apiPath, '/'));
    $className = $pathParts[0] ?? '';
    $method = $pathParts[1] ?? 'get';
    
    if (strpos($method, '?') !== false) {
        $method = substr($method, 0, strpos($method, '?'));
    }
} else {
    $className = $request->get('CLASS');
    $method = $request->get('METHOD');
}

$class = 'api\classes\\' . ucfirst($className);

try {
    if (empty($className)) {
        throw new Exception("Не указан класс. Используйте формат: /api/Test/get или /local/api/Test/get");
    }
    
    if (!class_exists($class)) {
        throw new Exception("Класс $class не найден. Проверьте путь: /local/api/classes/" . ucfirst($className) . ".php");
    }
    
    if (!method_exists($class, $method)) {
        throw new Exception("Метод $method не найден в классе $class");
    }
    
    $result = call_user_func([$class, $method], $arRequest);
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');