<?php

header('Access-Control-Allow-Origin: *');

spl_autoload_register(function($class_name) {
	$class_name = str_replace("\\", "/", $class_name);
	require __DIR__ . '/' . $class_name . '.php';
});
header('Access-Control-Allow-Origin: *');
require 'config.php';
require 'containers.php';
header('Access-Control-Allow-Origin: *');
require 'vendor/autoload.php';
header('Access-Control-Allow-Origin: *');