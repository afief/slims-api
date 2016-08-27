<?php

namespace helpers;

use \Firebase\JWT\JWT;

class NotificationHelper {
	private $db;
	private $ci;
	public function __construct($ci) {
		$this->ci = $ci;
		$this->db = $ci->db;
	}

	public function send($from_id, $to_id, $text, $param) {
		$insert = $this->db->insert('member_notification', [
			'to_id'		=> $to_id,
			'from_id'	=> $from_id,
			'text'		=> $text,
			'param'		=> $param,
			'is_read'	=> 0
		]);

		if ($insert) {
			return true;
		}
		return false;
	}

	public function read($notifId) {
		$this->db->update('member_notification', ['is_read' => 1], ['id' => $notifId]);
		return true;
	}

	public function fetch($user_id, $offset = 0, $limit = 10, $prev_id = null) {
		$result = [
			'data'	=> [],
			'next'	=> null,
			'unread' => 0
		];

		if ($prev_id) {
			$result['data'] = $this->db->select('member_notification(mn)',
				['[>]member(m)' => ['mn.from_id' => 'member_id']],
				['mn.id', 'mn.from_id', 'm.member_name(from_name)', 'CONCAT[\'' . BASE_URL . 'images/persons/\', member_image](from_image)',
				'mn.text', 'mn.param', 'mn.is_read', 'mn.timestamp'],
				['AND' => ['mn.to_id' => $user_id, 'mn.id[>]' => $prev_id], 'ORDER' => 'mn.timestamp DESC', 'LIMIT' => [0, $limit]]
			);
		} else {
			$result['data'] = $this->db->select('member_notification(mn)',
				['[>]member(m)' => ['mn.from_id' => 'member_id']],
				['mn.id', 'mn.from_id', 'm.member_name(from_name)', 'CONCAT[\'' . BASE_URL . 'images/persons/\', member_image](from_image)',
				'mn.text', 'mn.param', 'mn.is_read', 'mn.timestamp'],
				['AND' => ['mn.to_id' => $user_id, 'mn.id[>]' => $prev_id], 'ORDER' => 'mn.timestamp DESC', 'LIMIT' => [$offset, $limit]]
			);
		}

		if (count($result['data'])) {
			if ($this->db->count('member_notification', ['AND' => ['id[>]' => $result['data'][0]['id'], 'to_id' => $user_id ]]) > 0) {
				$result['next'] = $result['data'][0]['id'];
			}

			$unreadCount = 0;
			foreach ($result['data'] as $rs) {
				if (!$rs['is_read']) {
					$unreadCount++;
				}
			}
			$result['unread'] = $unreadCount;
		}

		return $result;
	}

}