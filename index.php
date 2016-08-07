<?php

require 'init.php';

$app = new \Slim\App($container);

/* auth routers */
$app->group('/auth', function() {
	// login user baru
	$this->post('/login', controllers\AuthCtrl::class . ":login");
	// register user baru
	$this->post('/register', controllers\AuthCtrl::class . ":register");
	// konfirmasi dari email user
	$this->get('/confirm/{code}', controllers\AuthCtrl::class . ":confirm");
	// request kode konfirmasi lupa password
	$this->get('/forgot-password/{email}', controllers\AuthCtrl::class . ":forgotPassword");
});

/* 
 * USER Route
 */
$app->group('/user', function() {
	// ambil data user yang login
	$this->get('', controllers\UserCtrl::class . ':getUser');

})->add(middleware\AuthMiddleware::class . ':checkLogin');

/* 
 * BOOK Route
 */
$app->group('/book', function() {
	// ambil data user yang login
	$this->get('s', controllers\BookCtrl::class . ':select');
	$this->get('/{id}', controllers\BookCtrl::class . ':get');

})->add(middleware\AuthMiddleware::class . ':checkLogin');


/* RUN!! */
$app->run();