<?php

namespace helpers;

use \Firebase\JWT\JWT;

class UserHelper {
	public $token = "";
	public $id = 0;
	public $isLogin = false;

	private $role = "";
	private $ci;
	private $currentUser = false;
	public function __construct($ci) {
		$this->ci = $ci;
		$this->checkUser();
	}

	/* check keabsahan TOKEN JWT yang dikirim via header token. */
	private function checkUser() {
		$request = $this->ci->get('request');
		$tokenJWT = $request->getHeader('token');
		if ($tokenJWT) {	
			if (count(explode(".", $tokenJWT[0])) <= 2) {
				$tokenJWT[0] = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9." . $tokenJWT[0];
			}
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
	public function generateToken($user_id) {
		$token = $this->ci->util->generateRandomString(10) . $user_id;
		$ip = $this->ci->util->determineClientIpAddress();
		$browser = $this->ci->util->getBrowser();

		$insert = $this->ci->db->insert('member_logins', [
			'member_id'		=> $user_id,
			'token'			=> $token,
			'ip'			=> $ip,
			'browser'		=> $browser
		]);

		if ($insert) {
			$token = [
				'uid'	=> $user_id,
	    		'tkn'	=> $token
			];
			$jwt = JWT::encode($token, $this->ci->settings['server_key']);

			if (strpos($jwt, "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9") == 0) {
				$jwt = implode(".", array_splice(explode(".", $jwt), 1, 2));
			}
			return $jwt;
		}
		return false;
	}

	public function getCurrentUser() {
		if ($this->currentUser) {
			return $this->currentUser;
		} else if ($this->id) {
			$user = $this->ci->db->get('member',
				[
				'member_id', 'member_name', 'CONCAT[\'' . BASE_URL . 'images/persons/\', member_image](member_image)', 'member_email', 'member_address', 
				'gender', 'birth_date', 'member_phone'
				], ['member_id' => $this->id]);
			if ($user) {
				$this->currentUser = $user;
				return $user;
			}
		}

   		return false;
   	}

	public function logout() {
		if ($this->token && $this->id) {
			$this->ci->db->update('member_logins', ['token' => ''], ['AND' => ['member_id' => $this->id, 'token' => $this->token]]);
		}
		return true;
	}
}