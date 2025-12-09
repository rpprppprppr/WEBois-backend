<?php

echo "<h2>URL Rewrite Test</h2>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "<br>";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "<br>";
echo "PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'NOT SET') . "<br>";

$urlrewritePath = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/urlrewrite.php';
if (file_exists($urlrewritePath)) {
    echo "urlrewrite.php: EXISTS<br>";
    include $urlrewritePath;
    echo "Rules count: " . count($arUrlRewrite ?? []) . "<br>";
} else {
    echo "urlrewrite.php: NOT FOUND<br>";
}

if (function_exists('apache_get_modules')) {
    echo "mod_rewrite: " . (in_array('mod_rewrite', apache_get_modules()) ? 'ENABLED' : 'DISABLED') . "<br>";
} else {
    echo "mod_rewrite: CANNOT CHECK (not Apache)<br>";
}