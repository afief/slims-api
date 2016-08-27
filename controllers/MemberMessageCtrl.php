<?php

namespace controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Interop\Container\ContainerInterface;

class MemberMessageCtrl extends BaseController {

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
					if ($this->cleanInput($posts, ['member_id', 'text'])) {
						$posts['from_id'] = $this->user->id;

						$insert = $this->db->insert('member_message', $posts);
						if ($insert) {
							$this->notif->send($this->user->id, $posts['member_id'], 'mengirim pesan untuk Anda', $insert);					
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
		$fromMembers = $this->db->manual('SELECT m.member_id, m.member_name, CONCAT(\'' . BASE_URL . 'images/persons/\', m.member_image) as member_image FROM member m ' .
			'INNER JOIN (SELECT from_id FROM `member_message` WHERE member_id = ' . $this->db->quote($this->user->id) . ' UNION ' .
			'(SELECT member_id as `from_id` FROM `member_message` WHERE from_id = ' . $this->db->quote($this->user->id) . ')) f ON f.from_id = m.member_id');

		if (is_array($fromMembers)) {

			for ($i = 0; $i < count($fromMembers); $i++) {
				$lastMessage = $this->db->manual(
					'SELECT member_message.`text`, member_message.`timestamp` ' .
					'FROM `member_message` ' .
					'WHERE ' . 
					'(`member_message`.`from_id` = ' . $this->db->quote($fromMembers[$i]['member_id']) . ' AND `member_message`.`member_id` = ' . $this->db->quote($this->user->id) . ') OR ' .
					'(`member_message`.`from_id` = ' . $this->db->quote($this->user->id) . ' AND `member_message`.`member_id` = ' . $this->db->quote($fromMembers[$i]['member_id']) . ') ' .
					'ORDER BY `member_message`.`timestamp` DESC LIMIT 0, 1'
				);
				if (count($lastMessage)) {
					$fromMembers[$i]['last_message'] = $lastMessage[0]['text'];
					$fromMembers[$i]['last_time'] = $lastMessage[0]['timestamp'];
				}
			}

			$this->setTrue();
			$this->setData($fromMembers);
		} else {
			$this->error('failed');
		}

		return $this->result;
	}

	public function getMessages(Request $req, Response $res, $args) {
		$from_id = $args['from_id'];

		$query = 'SELECT member_message.`id`,member_message.`text`, member_message.`timestamp`, ' .
			'member.member_id, member.member_name, CONCAT(\'' . BASE_URL . 'images/persons/\', member.member_image) as member_image ' .
			'FROM `member_message` ' .
			'LEFT JOIN member ON member.member_id = member_message.from_id ' .
			'WHERE ' . 
			'(`member_message`.`from_id` = ' . $this->db->quote($from_id) . ' AND `member_message`.`member_id` = ' . $this->db->quote($this->user->id) . ') OR ' .
			'(`member_message`.`from_id` = ' . $this->db->quote($this->user->id) . ' AND `member_message`.`member_id` = ' . $this->db->quote($from_id) . ') ' .
			'ORDER BY `member_message`.`timestamp` DESC';

		$result = $this->db->manual($query);

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