<?php

namespace controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Interop\Container\ContainerInterface;

class AuthCtrl extends BaseController {

	public function __construct(ContainerInterface $ci) {
		parent::__construct($ci);
   	}

   	/*
   		Login Function	
   	*/
	public function login(Request $req, Response $res) {
		$postData = $this->getPosts(['email', 'password']);

		if ($postData) {
			/* get user password */
			$user = $this->db->get('member',
				['member_id', 'member_name', 'member_image', 'member_email', 'mpasswd'], ['OR' => ['member_email' => $postData['email'], 'member_id' => $postData['email']]]);

			/* check user password */
			if ($user) {
				if (md5($postData['password']) == $user['mpasswd']) {
					$token = $this->user->generateToken($user['member_id']);

					/* if token valid */
					if ($token) {
						/* RESULT SUCCESS */
						$this->setTrue();

						unset($user['mpasswd']);
						$user['token'] = $token;
						$this->setData($user);

					} else {
						$this->error('Kesalahan sistem.');
					}
				} else {
					$this->error('Password yang dimasukkan salah.');
				}
			} else {
				$this->error('Username / Email tidak ditemukan');
			}
		} else {
			$this->error('Data tidak lengkap');
		}
		return $this->result;
	}

	public function logout(Request $req, Response $res, $args) {
		$logout = $this->user->logout();
		if ($logout) {
			$this->setTrue();
		}

		return $this->result;
	}

	/*
   		Register Function	
   	*/
	public function register(Request $req, Response $res) {
		$postData = $this->getPosts(['member_id', 'member_name', 'email', 'member_phone', 'password']);

		if ($postData) {
			unset($postData['password2']);

			/* check input */
			if (!filter_var($postData['email'], FILTER_VALIDATE_EMAIL)) {
				$this->error('Email yang dimasukkan tidak valid');
				return $this->result;
			}

			/* check exist user */
			$user_id = $this->db->get('member',
				['member_id'], ['OR' => ['member_email' => $postData['email'], 'member_id' => $postData['member_id']]]);

			if ($user_id) {
				$userlogin = $this->login($req, $res);
				if ($userlogin && $userlogin['status']) {
					return $userlogin;
				}

				$this->error('Member ID / Email sudah digunakan oleh akun lain.');
			} else {
				$disallowed = [];
			
				if ($this->cleanInput($postData, ['member_id', 'member_name', 'gender', 'birth_date', 'member_address', 'email', 'postal_code', 'member_phone', 'password'], $disallowed)) {

					$postData['register_date']	= date('Y-m-d H:i:s');
					$postData['input_date']	= date('Y-m-d H:i:s');
					$postData['last_update']	= date('Y-m-d H:i:s');
					$postData['expire_date']	= date('Y-m-d H:i:s', strtotime("+1 year"));
					$postData['member_email']	= $postData['email'];
					$postData['member_type_id']	= 1;
					$postData['mpasswd']		= md5($postData['password']);
					unset($postData['email']);
					unset($postData['password']);

					$this->db->insert('member', $postData);

					$insert = $this->db->insert('member_message', [
						'member_id'		=> $postData['member_id'],
						'from_id'		=> 'adminlabschool',
						'text'			=> "Selamat Datang di Digital Library 2.0"
					]);

					if ($this->db->lastError != '') {
						$this->error($this->db->lastError);
					} else {
						return $this->login($req, $res);
					}


				} else {
					$this->error("Parameter " . implode(',', $disallowed) . " tidak diperbolehkan");
				}
			}

		} else {
			$this->error('Data tidak lengkap');
		}
		return $this->result;
	}

	/*
		Buat kode konfirmasi untuk user dan simpan di tabel t_user_codes
	*/
	protected function createConfirmCode($user_id) {
		$code = $user_id . $this->ci->util->generateRandomString(20);

		$insert = $this->db->insert('t_user_codes', ['user_id' => $user_id, 'code' => $code, 'status' => 1]);
		if ($insert) {
			return $code;
		}
		return false;
	}
	protected function checkConfirmCode($code) {
		return $this->db->get('t_user_codes', ['user_id', 'status'], ['code' => $code]);
	}
	protected function unvalidateConfirmCode($user_id) {
		return $this->db->update('t_user_codes', ['status' => 0], ['user_id' => $user_id]);
	}

	/*
		Proses Konfirmasi User
	 */
	public function confirm(Request $res, Response $res, $args) {
		$code = $args['code'];

		if ($code) {
			$cc = $this->checkConfirmCode($code);
			if ($cc && $cc['status']) {
				/* confirmation success */
				$this->unvalidateConfirmCode($cc['user_id']);
				$this->ci->db->update('mt_member', ['status' => 'Aktif'], ['id' => $cc['user_id']]);

				return "Confirmed";
			} else if ($cc['status'] == '0') {
				return "Already Confirmed";
			}
			return "Invalid Code";
		}
		return "Invalid Parameter";
	}

	/*
		Request Email Lupa Password
	 */
	public function forgotPassword(Request $req, Response $res, $args) {
		$email = $args['email'];

		if ($email) {
			/* get user id */
			$user = $this->db->get('mt_member', ['id', 'email'],
				['email' 	=> $email]);

			if ($user) {
				$code = $this->createConfirmCode($user['id']);
				if ($code) {
					/* NOT YET - kirim kode perubahan password ke email */
					$this->setTrue();
					$this->setData(['email' => $user['email']]);
				} else {
					$this->error("Gagal membuat kode konfirmasi");
				}
			} else {
				$this->error("Email tidak dapat ditemukan");
			}
		} else {
			$this->error("Parameter tidak lengkap");
		}

		return $this->result;
	}
}

?>