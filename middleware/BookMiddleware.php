<?php

namespace middleware;

Class BookMiddleware {

	private $ci;

	public function __construct($ci) {		
		$this->ci = $ci;
	}

	public function checkId($req, $res, $next) {
		$route = $req->getAttribute('route');
		$biblio_id = $route->getArgument('biblio_id');

		if ($biblio_id) {
			if ($this->ci->db->count('biblio', ['biblio_id' => $biblio_id])) {
				return $next($req, $res);
			}
		}

		return $res->withJson(['status' => false, 'message' => 'Book not found']);
	}
}