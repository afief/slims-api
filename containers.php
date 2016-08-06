<?php

$container['db'] = function($c) {
	$db = new \libraries\medoo([
		'database_type' => 'mysql',
		'database_name' => $c['settings']['db_name'],
		'server' => $c['settings']['db_server'],
		'username' => $c['settings']['db_username'],
		'password' => $c['settings']['db_password'],
		'charset' => 'utf8'
		]);
	return $db;
};

$container['user'] = function($c) {
	return new \helpers\UserHelper($c);
};
$container['util'] = function($c) {
	return new \helpers\UtilHelper($c);
};
