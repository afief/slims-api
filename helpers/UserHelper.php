<?php

namespace helpers;

use \Firebase\JWT\JWT;

class UserHelper {
	public $token = "";
	public $id = 0;
	public $isLogin = false;

	private $role = "";
	private $ci;
	public function __construct($ci) {
		$this->ci = $ci;
		$this->checkUser();
	}

	/* check keabsahan TOKEN JWT yang dikirim via header token. */
	private function checkUser() {
		$request = $this->ci->get('request');
		$tokenJWT = $request->getHeader('token');
		if ($tokenJWT) {
			try {
				$tokenData = JWT::decode($tokenJWT[0], $this->ci->settings['server_key'], ['HS256']);
				if ($tokenData) {
					$this->id = $tokenData->uid;
					$this->token = $tokenData->tkn;

					/* Check User Login Token */
					$isValid = $this->ci->db->count('member_logins', ['AND' => ['member_id' => $this->id, 'token' => $this->token]]);
					if ($isValid > 0) {
						$this->isLogin = true;
					} else {
						$this->isLogin = false;
					}
				}
			} catch (\Exception $e) {
				//Invalid token from header
			}
		}
	}


	/* buat token baru dan simpan di database */
	public function generateToken($member_id) {
		$token = $this->ci->util->generateRandomString(10) . $member_id;
		$ip = $this->ci->util->determineClientIpAddress();
		$browser = $this->ci->util->getBrowser();

		$insert = $this->ci->db->insert('member_logins', [
			'member_id'		=> $member_id,
			'token'			=> $token,
			'ip'			=> $ip,
			'browser'		=> $browser
		]);

		if ($insert) {
			$token = [
				'uid'	=> $member_id,
	    		'tkn'	=> $token
			];
			$jwt = JWT::encode($token, $this->ci->settings['server_key']);
			return $jwt;
		}
		return false;
	}
}