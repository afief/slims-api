<?php

namespace controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Interop\Container\ContainerInterface;

class MemberMessage extends BaseController {

	public function __construct(ContainerInterface $ci) {
		parent::__construct($ci);

	}


	public function create(Request $req, Response $res, $args) {
		$posts = $this->getPosts(['member_id', 'text']);

		if ($posts) {
			if ($posts['member_id'] != $this->user->id) {

				$checkUser = $this->db->count('member', ['member_id' => $posts['member_id']]);
				if ($checkUser) {
					$dis = false;
					if ($this->cleanInput($posts, ['member_id', 'text', 'reply_to'])) {
						$posts['from_id'] = $this->user->id;

						$insert = $this->db->insert('member_message', $posts);
						if ($insert) {
							$this->setTrue();
						} else{
							$this->error('Gagal tersimpan');
						}
					}
				} else {
					$this->error('User tidak ditemukan.');
				}
			} else {
				$this->error('Harus dikirim ke orang lain.');
			}
		} else {
			$this->error('Data tidak lengkap');
		}

		return $this->result;
	}

	public function select(Request $req, Response $res, $args) {
		$fromMembers = $this->db->select('member_message(msg)',
			['[>]member(m)' => ['msg.from_id' => 'member_id']],
			['m.member_id', 'm.member_name', 'm.gender', 'CONCAT[\'' . BASE_URL . 'images/persons/\', m.member_image](member_image)'],
			['AND' => ['msg.member_id' => $this->user->id, 'msg.is_from_admin' => 0], 'GROUP' => 'msg.member_id', 'ORDER' => 'msg.timestamp DESC']
		);

		if (is_array($fromMembers)) {
			$this->setTrue();
			$this->setData($fromMembers);
		} else {
			$this->error('failed');
		}

		return $this->result;
	}

	public function getMessages(Request $req, Response $res, $args) {
		$from_id = $args['from_id'];

		$result = $this->db->select('member_message',
			['id', 'text', 'timestamp'],
			['from_id' => $from_id, 'ORDER' => 'timestamp DESC']
		);

		if (is_array($result)) {
			$this->setTrue();
			$this->setData($result);
		} else {
			$this->error('failed');
		}

		return $this->result;
	}

}

?>