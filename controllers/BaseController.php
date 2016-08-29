<?php

namespace controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Interop\Container\ContainerInterface;

class BaseController {

	protected $db;
	protected $user;
	protected $ci;
	protected $notif;
	protected $result = ['status' => false];

	public function __construct(ContainerInterface $ci) {
		$this->ci = $ci;
		$this->db = $ci->db;
		$this->user = $ci->user;
		$this->notif = $ci->notif;
	}

	/* Posts Functions */
	protected function getPosts($keys = false) {
		if (!$keys)
			$keys = [];

		$req = $this->ci->get('request');

		$postData = $req->getParsedBody();
		if ($this->isKeysExist($postData, $keys)) {
			return $postData;
		}
		return false;
	}

	/* Gets Functions */
	protected function getGets($keys = false) {
		if (!$keys)
			$keys = [];

		$req = $this->ci->get('request');

		$getData = $req->getQueryParams();
		if ($this->isKeysExist($getData, $keys)) {
			return $getData;
		}
		return false;
	}

	protected function isKeysExist($ars, $keys) {
		$res = true;
		$i = 0;
		while (($i < count($keys)) && $res) {
			if (!isset($ars[$keys[$i]])) {
				$res = false;
			}
			$i++;
		}
		return $res;
	}

	protected function cleanInput($datas, $allowedParams, &$disallowed = []) {
		foreach ($datas as $key => $value) {
			if (!in_array($key, $allowedParams)) {
				array_push($disallowed, $key);
			}
		}
		if (count($disallowed) <= 0) {
			return true;
		}
		return false;
	}


	/* Result Functions */
	protected function setTrue() {
		$this->result['status'] = true;
	}
	protected function setFalse() {
		$this->result['status'] = false;
	}
	protected function error($text) {
		$this->result['message'] = $text;
	}
	protected function setData($data) {
		$this->result['data'] = $data;
	}
	protected function addData($data, $key = false) {
		if (!isset($this->result['data']))
			$this->result['data'] = [];
		if (!is_array($this->result['data'])) {
			$this->result['data'] = [$this->result['data']];
		}

		if ($key) {
			$this->result['data'][$key] = $data;
		} else {
			array_push($this->result['data'], $data);
		}
	}

	public function checkVersion(Request $req, Response $res, $args) {
		$vid = intval($args['version_id']);

		if ($vid < 2) {
			$this->setTrue();
			$this->setData([
				'title' => 'Update Aplikasi',
				'text' => 'Silakan update Digilib ke versi lebih baru.',
				'gplay' => "market://details?id=com.esqvt.esqvirtualtraining"
				]);
		}

		return $this->result;
	}
}