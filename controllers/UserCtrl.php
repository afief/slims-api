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

	public function getBookHistory(Request $req, Response $res, $args) {
		$select = $this->db->select('loan',
			[
				'[>]item' => ['item_code' => 'item_code'],
				'[>]biblio' => ['item.biblio_id' => 'biblio_id'],
				'[>]mst_gmd' => ['biblio.gmd_id' => 'gmd_id'],
				'[>]mst_publisher' => ['biblio.publisher_id' => 'publisher_id'],
				'[>]mst_language' => ['biblio.language_id' => 'language_id']
			],
			[
			'loan.loan_date', 'loan.due_date', 'loan.is_lent', 'loan.is_return', 'loan.return_date', 'loan.renewed',
			'biblio.biblio_id', 'biblio.title', 
			'biblio.publish_year', 
			'mst_language.language_name', 'biblio.image', 'biblio.promoted',
			'mst_gmd.gmd_name',
			'mst_publisher.publisher_name'
			],
			[
			'loan.member_id' => $this->user->id,
			'ORDER' => 'loan.is_return ASC'
			]);

		if ($select) {
			$this->setTrue();
			$this->setData($select);
		}

		return $this->result;
	}
}

?>