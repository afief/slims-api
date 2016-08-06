<?php

namespace middleware;

Class AuthMiddleware {

	private $ci;

	public function __construct($ci) {		
		$this->ci = $ci;
	}

	public function checkLogin($req, $res, $next) {
		$token = $req->getHeader('token');

		if ($token) {
			if ($this->ci->user->isLogin) {
				return $next($req, $res);
			}
		}
		return $res->withJson(['status' => false, 'message' => 'forbidden']);
	}

	public function checkAdmin($req, $res, $next) {
		$token = $req->getHeader('token');

		if ($token) {
			if ($this->ci->user->getRoleIndex() <= 0) { //admin
				return $next($req, $res);
			}
		}
		return $res->withJson(['status' => false, 'message' => 'forbidden']);
	}
}