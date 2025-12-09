<?php

$_SERVER['DOCUMENT_ROOT'] = '/home/bitrix/www';
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$command = new \Console\UpdateCommand();
$input = new \Symfony\Component\Console\Input\ArrayInput([]);
$output = new \Symfony\Component\Console\Output\ConsoleOutput();

try {
    $command->execute($input, $output);
    echo "Constants generated successfully!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}