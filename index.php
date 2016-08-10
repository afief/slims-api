<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: token");
require 'init.php';

$app = new \Slim\App($container);

/* auth routers */
$app->group('/auth', function() {
	// login user baru
	$this->post('/login', controllers\AuthCtrl::class . ":login");
	// register user baru
	$this->post('/register', controllers\AuthCtrl::class . ":register");

	$this->post('/logout', controllers\AuthCtrl::class . ':logout');
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
	// ambil data buku
	$this->get('s', controllers\BookCtrl::class . ':select');

	$this->group('/{biblio_id}', function() {
		$this->get('', controllers\BookCtrl::class . ':get');
		$this->post('/rate', controllers\BookCtrl::class . ':setRate');

		$this->get('/comments', controllers\BookCommentCtrl::class . ':select');
		$this->post('/comment', controllers\BookCommentCtrl::class . ':set');
	})->add(middleware\BookMiddleware::class . ':checkId');

})->add(middleware\AuthMiddleware::class . ':checkLogin');


/* RUN!! */
$app->run();