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
		$fromMembers = $this->db->manual('SELECT m.member_id, m.member_name, m.gender, m.member_image FROM member m ' .
			'INNER JOIN (SELECT from_id FROM `member_message` WHERE member_id = ' . $this->db->quote($this->user->id) . ' UNION ' .
			'(SELECT member_id as `from_id` FROM `member_message` WHERE from_id = ' . $this->db->quote($this->user->id) . ')) f ON f.from_id = m.member_id');

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

		$result = $this->db->manual('SELECT `id`,`text`,`timestamp` FROM `member_message` WHERE ' . 
			'(`from_id` = ' . $this->db->quote($from_id) . ' AND `member_id` = ' . $this->db->quote($this->user->id) . ') OR ' .
			'(`member_id` = ' . $this->db->quote($this->user->id) . ' AND `from_id` = ' . $this->db->quote($from_id) . ') ' .
			'ORDER BY `timestamp` DESC');

		print_r($this->db->last_query());

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