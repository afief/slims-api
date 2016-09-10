<?php

date_default_timezone_set('Asia/Jakarta');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
	$this->get('/id/{member_id}', controllers\UserCtrl::class . ':getOtherUser');
	
	$this->post('', controllers\UserCtrl::class . ':updateUser');
	$this->get('/books', controllers\UserCtrl::class . ':getBookHistory');
	$this->post('/avatar', controllers\UserCtrl::class . ':updateAvatar');
	$this->post('/reg-id', controllers\UserCtrl::class . ':updateRegId');
	$this->post('/setting', controllers\UserCtrl::class . ':updateUserSetting');

})->add(middleware\AuthMiddleware::class . ':checkLogin');

/* 
 * BOOK Route
 */
$app->group('/book', function() {
	// ambil data buku
	$this->get('s', controllers\BookCtrl::class . ':select');
	$this->get('s/topics', controllers\BookCtrl::class . ':getTopics');
	$this->get('s/reserve', controllers\BookCtrl::class . ':selectReserve');
	$this->post('s/reserve', controllers\BookCtrl::class . ':setReserve');
	$this->post('s/unset-reserve', controllers\BookCtrl::class . ':unsetReserve');

	$this->group('/{biblio_id}', function() {
		$this->get('', controllers\BookCtrl::class . ':get');
		$this->get('/rate', controllers\BookCtrl::class . ':getRate');
		$this->post('/rate', controllers\BookCtrl::class . ':setRate');

		$this->post('/favorit', controllers\BookCtrl::class . ':setFav');
		$this->post('/unfavorit', controllers\BookCtrl::class . ':unsetFav');

		$this->get('/comments', controllers\BookCommentCtrl::class . ':select');
		$this->post('/comment', controllers\BookCommentCtrl::class . ':create');
	})->add(middleware\BookMiddleware::class . ':checkId');

})->add(middleware\AuthMiddleware::class . ':checkLogin');


/*
 * Messages Route
 */
$app->group('/message', function() {
	$this->get('s', controllers\MemberMessageCtrl::class . ':select');
	$this->post('', controllers\MemberMessageCtrl::class . ':create');

	$this->get('/{from_id}', controllers\MemberMessageCtrl::class . ':getMessages');
})->add(middleware\AuthMiddleware::class . ':checkLogin');


/*
 * Notification Route
 */
$app->group('/notif', function() {
	$this->get('', controllers\MemberNotifCtrl::class . ':select');
	$this->post('/read', controllers\MemberNotifCtrl::class . ':read');
});


$app->post('/report', controllers\ReportCtrl:: class . ':send');

$app->get('/check-version/{version_id}', controllers\BaseController::class . ':checkVersion');

/* RUN!! */
$app->run();