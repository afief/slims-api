<?php

namespace controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Interop\Container\ContainerInterface;

class UserCtrl extends BaseController {

	public function __construct(ContainerInterface $ci) {
		parent::__construct($ci);
   	}

   	public function getUser(Request $req, Response $res) {

   		$user = $this->getUserById($this->user->id);
   		if ($user) {
   			$this->setTrue();
   			$this->setData($user);
   		}

   		return $this->result;
   	}

   	private function getUserById($memberId) {
   		$user = $this->db->get('member',
				[
					'member_id', 'member_name', 'CONCAT[\'' . BASE_URL . 'images/persons/\', member_image](member_image)', 'member_email', 'member_address', 
					'gender', 'birth_date', 'member_phone'
				], ['member_id' => $memberId]);

   		return $user;
   	}
}

?>